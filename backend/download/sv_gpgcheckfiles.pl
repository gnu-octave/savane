#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2005-2006  (c) Mathieu Roy <yeupou--gnu.org> 
#
# The Savane project is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# The Savane project is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
#
##
## This script should be used via a cronjob to check if files in a download
## area are properly signed.
## Properly signed mean that it has been signed with a GPG signature that is 
## in the related group keyring.
## The keyring should have been added by sv_groups in /home/savane-keyrings/.
##
## More details given with --full-help

use strict;
use Savane;
use Savane::Mail;
use Getopt::Long;
use POSIX qw(strftime);
use File::Basename;
use File::Find::Rule;
use Digest::MD5 qw(md5_base64);

my $script = "sv_gpgcheckfiles";
my $logfile = "/var/log/$script.log";
my $getopt;
my $help;
my $debug;
my $version = GetVersion();
my $chroot;
my $fullhelp;
my $dir;

my $nocache;
my $validate_cache;
my $cachedir = "/var/cache/$script";
my $cachefile = "$cachedir/cache.pl";

# If sys_url_topdir is not simply, an ending slash must be added.
my $url_topdir = GetConf("sys_url_topdir");
$url_topdir .= "/" if $url_topdir ne "/";
my $basepath;

if (GetConf("sys_https_host")) {
    $basepath .= "https://".GetConf("sys_https_host").$url_topdir;
} else {
    $basepath .= "http://".GetConf("sys_default_domain").$url_topdir;
}
my $faq_url = $basepath."cookbook/?group=".GetConf("sys_unix_group_name")."#download";
my $support_url = $basepath."support/?group=".GetConf("sys_unix_group_name");

# get options
eval {
    $getopt = GetOptions("help" => \$help,
			 "full-help" => \$fullhelp,
			 "debug" => \$debug,
			 "dir=s" => \$dir,
			 "no-cache" => \$nocache,
			 "validate-cache" => \$validate_cache,
			 "chroot=s" => \$chroot);
};

if ($help) {
    print STDERR <<EOF;
Usage: $0 [OPTIONS] 

Update the system to reflect the database, about groups.
Normally, sv_users should run just after.

  -h, --help                   Show this help and exit
      --full-help              Describe the script purpose and behavior.
  -d, --debug                  Do nothing, print everything

      --dir=/path              Used to determine which directory contains 
                               projects area to check. This information is
			       not extracted from the database for 
			       flexibility and simplicity reasons.
		       

      --chroot=/path           Useful if the script run outside a the download
                               area chroot, if any.
			       (it should be safer to have this script outside
			       the chroot, since it would be unaffected if the
			       chroot get compromised)
			       It will mostly be used to access /home content,
			       especially GPG keyrings.
			       Note that this path will not be automatically
			       added to the path provided with --dir.

      --no-cache               Will not read the cache (but will write it).
      --validate-cache         Will check whether the cache content is still
                               accurate in regard of leftover. It is 
			       recommended to use that option once per week.

Savane version: $version
EOF
exit(1);
}

if ($fullhelp) {
    print STDERR <<EOF;

This script should be used via a cronjob to check if files in a download
area are properly signed.
Properly signed mean that it has been signed with a GPG signature that is 
in the related group keyring.
The keyring should have been added by sv_groups in /home/savane-keyrings/.

The approach followed is to provide security in a non-coercitive way.
File upload is allowed through usual means, file are assumed cleaned until
proven unclean:
    - user upload files in his download area
    - later, the cronjob look at it:
               * if no files are gpg signed, a file HEADER.html will be 
                 added (saving such previous file under another name), not
                 user modifiable, saying files there cannot be checked using
                 gpg. One should assume they are clean, but only assume.
                 This HEADER.html file will be added only in top directory,
                 not in subdirectories.
               * if files exists and are gpg signed properly (even if not 
                 all files)
                 nothing is done specifically.
                 If a HEADER.html added by Savane exists, it gets removed.
                 The md5 of checked files is cached and will not be checked
                 unless their md5 change
               * if files exists, are gpg signed and the signature is not
                 ok, files are moved in a subdirectory called maybe-corrupted
                 and a HEADER.html is added listing failed signature checks,
                 saying that one should not assume that files are clean.
                 (this one will stay until the project upload properly signed
                 files)
                 A mail should be sent to project admins.
                 Additionnaly, a HEADER.html file would be added in 
                 maybe-corrupted, saying these files are questionable.
                 This would happen in any directory, no matter how deep they
                 are. 

           Note: HEADER.html files will not be 
	         modifiable by projects members. They will be added whenever
                 it makes sense and they will be removed when incriminated
                 files no longer exists.

Savane version: $version
EOF
exit(1);

}

if (!$dir) {
    die "The --dir argument is mandatory!\n";
    
}

# Log: Starting logging
open(LOG, ">>$logfile");
print LOG strftime "[$script] %c - starting\n", localtime;


# Locks: There should be only one session of the script running at a time
# So we add a lock
AcquireReplicationLock();


#####################################################################
#####################################################################
# Read cache (outside of the chroot if any)
# $cache{$file} = md5
# $cache_headers{$dir} = type (nosign/dirty)
our %cache;
our %cache_headers;

# (by default, cacheformat is not set, so if it is still
# not set after the cache is read, it means that we are in format 0)
our $cacheformat = 0; 


if (-e $cachefile && ! $nocache) {
    # If the cache does not belongs to root, exit with error. The content of
    # the cache could have been altered.
    my @stat_cachedir = stat($cachedir);
    my @stat_cachefile = stat($cachefile);

    die "Strange cache ($cachefile) ownership, exiting" unless
	($stat_cachedir[4] eq 0) and ($stat_cachedir[5] eq 0) and
	($stat_cachefile[4] eq 0) and ($stat_cachefile[5] eq 0);
    
    # Otherwise, run the cache
    do $cachefile;
    print "DBG: cached loaded\n" if $debug;

    # If cacheformat is not equal to 1, the current version, it means it must
    # be converted but it is the previous one
    if ($cacheformat < 1) {
	print "Cache is not in the current format, it will be converted\n";
	print LOG strftime "[$script] %c - Cache is not in the current format, it will be converted\n", localtime;    
    }
    
}


#####################################################################
#####################################################################
# Get the list of active projects
my %db_groups;
foreach my $line (GetDB("groups", 
			"status='A'",
			"unix_group_name")) {
    chomp($line);
    $db_groups{$line} = 1;
}

print LOG strftime "[$script] %c - database infos grabbed\n", localtime;


#####################################################################
#####################################################################
# Get the gid according to the chroot, if inside the chroot.
# We'll maybe need that information for chown calls.
my %project_gid;
if ($chroot) {
    # We cannot use getgrent since we have to grab data inside the chroot and
    # since chroot() would not allow use to get out of it.
    # One option could be to call a specific script for that but it's easier 
    # to directly read $chroot/etc/group.
    # Indeed, it may not be very portable.
    open(GROUP, "< $chroot/etc/group");
    while (<GROUP>) {
	my ($name, $mark, $id) = split(":", $_);
	next unless $db_groups{$name};
	$project_gid{$name} = $id;
    }
    close(GROUP);
    print LOG strftime "[$script] %c - $chroot/etc/group infos grabbed\n", localtime;
}


#####################################################################
#####################################################################
# First look, first impression, find out project directories status :
#
#    - no files at all -> will be ignored
#    - no files GPG signed 
#    - files with GPG signatures not cached 

my %project_area_with_nothing_signed;
my %project_area_with_questionable_files;
my %project_area_questionable_files;

my %directory_found_to_be_clean;

# Poses problems if you want to read /home from another chroot
#$dir = $chroot.$dir if $chroot;

opendir(MAIN, $dir)
    or die "Unable to open $dir. Exiting";
while (defined (my $project = readdir(MAIN))) {
    # ignore if not a directory
    next unless -d "$dir/$project";
    # ignore if not a project directory
    # (this is a very basic test that will not work if projects have 
    # directories name different than their unix group name)
    next unless $db_groups{$project};

    print "DBG: verifying $dir/$project\n" if $debug;

    # Find out if there were questionable files in the past that remains
    # (maybe-corrupted dirs)
    # If so, make sure the project area wont be marked as clean by later tests,
    # make sure the directories containing a subdir maybe-corrupted wont get
    # their warning HEADER removed
    my @brokengpg_directories = File::Find::Rule->directory()
	->name("maybe-corrupted")
	->directory
	->in(("$dir/$project"));

    if (scalar(@brokengpg_directories)) {
	for (@brokengpg_directories) {
	    $directory_found_to_be_clean{dirname($_)} = 2;
	}
	$directory_found_to_be_clean{"$dir/$project"} = 2;

	print "DBG: $project: ".scalar(@brokengpg_directories)." broken gpg directories remaining\n" if $debug;
    }
    
    # Find out if there are GPG files
    my @gpg_files = File::Find::Rule->file()
	->name("*.sig")
	->in(("$dir/$project"));

    print "DBG: $project: ".scalar(@gpg_files)." signed files found\n" if $debug;

    # If not, find out if there are files at all
    unless (scalar(@gpg_files)) {
	my @all_files = File::Find::Rule->file()
	    ->not_directory
	    ->in(("$dir/$project"));
	
	# If not empty, the project should get HEADER saying nothing is signed
	if (scalar(@all_files)) {
	    $project_area_with_nothing_signed{$project} = "$dir/$project";
	}
	
	# If we reach this point there was no GPG files, proceed to the next
	# project area
	next;
    }
    
    # Now, do GPG checks 
    my @gpg_args = ("gpg",
		    "--quiet",
		    "--batch",
		    "--no-tty",
		    "--no-default-keyring",
		    "--keyring",
		    GetGroupGPGKeyringFile($project, $chroot),
		    '--verify');

    foreach my $signature (@gpg_files) {
	my $signature_dir = dirname($signature);
	my $signed_file = $signature_dir."/".basename($signature, ".sig");
	my $result;

	# Ignore if the signature is orphaned (.sig existing with no file
	# to be checked)
	next unless -e $signed_file;

	# Get md5 of the signature and the related file
	open(SIGN, $signature);
	open(FILE, $signed_file);
	binmode(SIGN);
	binmode(FILE);
	my $signature_md5 = Digest::MD5->new->addfile(*SIGN)->b64digest.":".(stat($signature))[7];
	my $signed_file_md5 = Digest::MD5->new->addfile(*FILE)->b64digest.":".(stat($signed_file))[7];

	# Backward compat: in cacheformat v0, only real md5 were stored, not
	# filesize. If we detected that the cache was in v0, we must override
	# the value to ignore the filesize info.
	if ($cacheformat < 1){
	    $signature_md5 = Digest::MD5->new->addfile(*SIGN)->b64digest;
	    $signed_file_md5 = Digest::MD5->new->addfile(*FILE)->b64digest;
	}
	  
	
	# Check the cache
	my $is_cached;
	if (($cache{$signature} and 
	     $cache{$signature} eq $signature_md5) and
	    ($cache{$signed_file} and 
	     $cache{$signed_file} eq $signed_file_md5)) {
	    $is_cached = 1;
	    print "DBG: $signature is cached\n" if $debug;
	}

	# Will generate garbage out STDERR :(
	# We wont run gnupg if we have the md5 of this file cached.
	$result = system(@gpg_args, $signature)
	    unless $is_cached;

	# If result is false, everything is ok. Otherwise, there's an issue
	if ($result) {
	    
	    # Any error found, mark the project 
	    $project_area_with_questionable_files{$project} = 1
		unless $project_area_with_questionable_files{$project};
	    
	    # List the files
	    push(@{$project_area_questionable_files{$project}}, $signature);
	    
	    print "DBG: $signature found to be questionable (error $result)\n" if $debug;
	    
	    # Override/fordib any assumptions about the directory cleanness
	    $directory_found_to_be_clean{$signature_dir} = 2;	       
	    $directory_found_to_be_clean{"$dir/$project"} = 2;

	    print "DBG: mark $signature_dir (+ top dir) as ugly directory\n" if $debug;
	} else {
	    
	    # Cache the result, if necessary
	    unless ($is_cached) {
		$cache{$signature} = $signature_md5;
		$cache{$signed_file} = $signed_file_md5;
	    }

	    # Backward compat: in cacheformat v0, only real md5 were stored
	    # not filesize. If we previously detected that the cache was in v0,
	    # then the following variables were stripped of the filesize 
	    # info: we must put it back so it gets included in the cache
	    if ($cacheformat < 1){
		$cache{$signature} = $signature_md5.":".(stat($signature))[7];
		$cache{$signed_file} = $signed_file_md5.":".(stat($signed_file))[7];
	    }

	    # Note that the directory is on his way to be clean.
	    # This is important to remember, in case an old header is to be
	    # removed.
	    # This is overriden by any failure happening otherwise.

	    $directory_found_to_be_clean{$signature_dir} = 1
		unless $directory_found_to_be_clean{$signature_dir};
	    $directory_found_to_be_clean{"$dir/$project"} = 1
		unless $directory_found_to_be_clean{"$dir/$project"};
	    
	    print "DBG: mark $signature_dir as clean directory\n" if $debug and $directory_found_to_be_clean{$signature_dir} eq 1;
	    print "DBG: mark $dir/$project as clean directory\n" if $debug and $directory_found_to_be_clean{"$dir/$project"} eq 1;
	    
	}
	
    }
        
}
closedir(MAIN);

print LOG strftime "[$script] %c - filesystem infos grabbed\n", localtime;

#####################################################################
#####################################################################
# Add headers in case no GPG signed files exists 
# 
# A file .HEADER.savane is added to be able to determine whether the current
# HEADER file as been written by Savane or not.
#

while (my($project, $directory) = each(%project_area_with_nothing_signed)) {
    my $header = "$directory/HEADER.html";
    
    # Debug? Do nothing
    next if $debug;

    # If there's already a header related to this issue, skip it
    # (this info is cached)
    next if $cache_headers{$directory} eq 'nosign';

    # If there's already a header, backup it
    system("mv", "-f", $header, $header.".bak") if -e $header;

    
    # Finally, write the proper header, update cache
    $cache_headers{$directory} = 'nosign';
    
    open(HEADER, "> $header");
    # This should be made site specific at some point...
    print HEADER '<html>
<p>No files were signed using GPG in this project area. As result, Savane
was unable to verify their authenticity.<br />
While you should assume these files are clean, truly from the project <a href="'.$basepath.'projects/'.$project.'">'.$project.'</a> as expected, there is no way to be 100% sure.</p>
<p><a href="'.$faq_url.'">Read the Cookbook</a> for more details, <a href="'.$support_url.'">post a support request</a> in case of problem.</p>
</html>';
    close(HEADER);

    # Depending on modes of dirs, new files may not belong to root.
    # Make sure it is by brute force
    system("chown", "root:root", $header);

    print LOG strftime "[$script] %c ----  *nothing signed* for $project\n", localtime;
  
}

print LOG strftime "[$script] %c - *nothing signed* cases handled\n", localtime;


#####################################################################
#####################################################################
# Remove headers in case GPG properly signed files exists 
# 
# The point is to remove remains of previous checks, headers no longer
# accurate.

while (my($directory, $value) = each(%directory_found_to_be_clean)) {
    # if false, nothing to care about
    next unless $value;

    # if equal to 2, nothing to care about either, it means there are
    # conflictings values; and the unclean is stronger.
    next if $value eq 2;

    # Otherwise, it's truly clean now, so we'll clean the area 
    # (we do not restore the original header, if any, since we are not sure
    # it was not written by Savane in first place)
    my $header = "$directory/HEADER.html";
    
    unlink($header) if -e $header;
    $cache_headers{$directory} = 0 if $cache_headers{$directory};
    
    print "DBG: clean $directory (value $value)\n" if $debug;
    print LOG strftime "[$script] %c ----  $directory cleaned, case *found out to be clean*\n", localtime;
}

print LOG strftime "[$script] %c - *found out to be clean* cases handled\n", localtime;


#####################################################################
#####################################################################
# Add warning header and move questionable files.
# 


while (my($project,) = each(%project_area_with_questionable_files)) {
    my %directory_found_to_be_dirty;
    my @files_moved;

    # Automatically add the top directory that need to get header in any cases
    $directory_found_to_be_dirty{"$dir/$project"} = 1;

    #####################################
    # Relocate questionable files
    foreach my $file (@{$project_area_questionable_files{$project}}) {
	print "DBG: would move out $file\n" if $debug;

	# Move files in /maybe-corrupted,
	my $curdir = dirname($file);
	my $newdir = "$curdir/maybe-corrupted";
	my $realfile = basename($file, ".sig");

	# do nothing in debug mode
	$directory_found_to_be_dirty{$curdir} = 1 
	    unless $directory_found_to_be_dirty{$curdir};
	push(@files_moved, $realfile);

	# do nothing in debug mode
	next if $debug;

	system("mkdir", $newdir) unless -e $newdir;
	system("mv", "-f", $file, "$newdir/$realfile.brokensig");
	system("mv", "-f", "$curdir/$realfile", $newdir);

	print LOG strftime "[$script] %c ----  $realfile moved out\n", localtime;
    }
    
    #####################################
    # Add headers
    while (my($directory,) = each(%directory_found_to_be_dirty)) {
	print "DBG: will update dirty directory $directory\n" if $debug;
	my $header = "$directory/HEADER.html";
	
	# If there's already a header related to this issue, skip it
	# (this information is cached)
	next if $cache_headers{$directory} eq "dirty";
	
	# If there's already a header not written by savane, backup it
	system("mv", "-f", $header, $header.".bak") if -e $header;
	
	# Debug? Do nothing
	next if $debug;

	# Finally, write the proper header, update cache
	$cache_headers{$directory} = 'dirty';
	
	open(HEADER, "> $header");
	# This should be made site specific at some point...
	# No point to the support tracker, that could mislead people in contact
	# site admins (that will be anyway warned by cc)
	print HEADER '<html>
<p><b>Beware:</b> GPG signatures verification failed for some files. It may be caused by a simple mistake but it may also mean that someone maliciously modified the files available for download.<br />
Questionable files have been moved in the subdirectory /maybe-corrupted. But you should, as well, treat carefully the others file available in this project download area, especially the ones that have not been GPG signed. People of the project <a href="'.$basepath.'projects/'.$project.'">'.$project.'</a> should verify files.</p>
<p><a href="'.$faq_url.'">Read the FAQ</a> for more details.</p>
</html>';
	close(HEADER);

	# Depending on modes of dirs, new files may not belong to root.
	# Make sure it is by brute force
	system("chown", "root:root", $header);	

	print LOG strftime "[$script] %c ----  $header written\n", localtime;
	
	if (-e "$directory/maybe-corrupted") {
	    # In maybe-corrupted, we need to mention that all files here
	    # are corrupted.
	    open(HEADERSUBDIR, "> $directory/maybe-corrupted/HEADER.html");
	    
	    print HEADERSUBDIR '<html>
<p><b>Beware:</b> The following files have been moved here because their authentificity is questionable.<br />
These files as been stored here so members of the project <a href="'.$basepath.'projects/'.$project.'">'.$project.'</a> can check them. But users should consider these files with extreme suspicion.</p>
</html>';
	    close(HEADERSUBDIR);
	    
	    # Content of maybe corrupted should belong to the project, so they
	    # can clean it
	    
	    # We must find out the gid of the group according to the chroot
	    # (we cannot call chroot directly because people are free to use
	    # a --dir that is in fact outside the --chroot)
	    # This is done first, and only if --chroot is set.
	    # If chroot is not set, we'll use the group name.
	    $project_gid{$project} = $project unless $chroot;
	    
	    system("chown", "root:".$project_gid{$project}, "$directory/maybe-corrupted");
	    system("chmod", "g+rw", "$directory/maybe-corrupted");
	    
	    print LOG strftime "[$script] %c ----  $directory/maybe-corrupted/HEADER.html written\n", localtime;

	}
    }
    
    #####################################
    # Mail admins
    # (will assume that username@domain will work; relies on aliases)
    my $to;
    for (GetGroupAdmins($project)) {
	$to .= ", " if $to;
	$to .= GetUserName($_)."\@".GetConf("sys_mail_domain");
    }
    my $cc = GetConf("sys_mail_admin")."\@".GetConf("sys_mail_domain");
    my $title = GetConf("sys_name")." Download Areas: questionable files";
    my $mail = "Hello ".$project." admins,

You receive this mail because ".scalar(@files_moved)." GPG signed files in your project download 
area appear questionable.

We were unable to verify their authenticity.

Maybe you forgot to register through Savane web interface your GPG Key. You
can check this at 
  <".$basepath."project/memberlist-gpgkeys.php?group=$project>
Maybe these files have been corrupted during their upload.
Maybe these files were maliciously modified/replaced by someone.

These files were moved in subdirectories called /maybe-corrupted. 

Please check these files:
";
    sort(@files_moved);
    $mail .= join("\n", @files_moved);
    $mail .= "


If you have any questions, please post a support request at 
  <$support_url>
after checking the FAQ
  <$faq_url>

Regards,";


    unless ($debug) {
	MailSend("",$to,$title,$mail,$cc);
    } else {
	print "---- $to, $title, $cc ----\n".
	    $mail."\n";
    }	
    
}
    
print LOG strftime "[$script] %c - *dirty* cases handled\n", localtime;


#####################################################################
#####################################################################
# Write cache (outside of the chroot if any)
#
# If at some point cache appears to be gigantic (like 500 Mb), we'll split
# cache, write one cache per project.

# Verify if cached value refers to files that still exists. The script should
# be run weekly with this option
if ($validate_cache) {
    while (my($file,) = each(%cache)) {
	$cache{$file} = 0 unless -e $file;
    }
    while (my($directory,) = each(%cache_headers)) {
	$cache_headers{$directory} = 0 unless -e $directory;
    }
}

unless ($debug) {
    # Check if the cache directory exists. If not, built it
    system("mkdir", "-p", $cachedir);
    
    # Always make sure mode and ownership are acceptable (overwrite)
    system("chown", "root:root", "-R", $cachedir);
    system("chmod", "o-rwx", "-R", $cachedir);
    
    # Write cache
    open(CACHE, "> $cachefile");
    print CACHE '#!/usr/bin/perl
';
    print CACHE strftime "# %c\n", localtime;
print CACHE '
$cacheformat = 1;

%cache = (
';
    my $count = 0;
    while (my($file,$md5) = each(%cache)) {
	next unless $md5;
	print CACHE ",\n" if $count;
	print CACHE "\t\"$file\"\t".' => "'.$md5.'"';
	$count++;
    }
    print CACHE '
);
';
    
    # Write header cache
    print CACHE '

%cache_headers = (
';
    $count = 0;
    while (my($directory,$type) = each(%cache_headers)) {
	next unless $type;
	print CACHE ",\n" if $count;
	print CACHE "\t\"$directory\"\t".' => "'.$type.'"';
	$count++;
    }
    print CACHE '
);
';    

    print CACHE '
# EOF
';
    close(CACHE);

    # Must be executable
    system("chmod", "u+x", $cachefile);
}

    
#####################################################################
# Final exit
print LOG strftime "[$script] %c - work finished\n", localtime;
print LOG "[$script] ------------------------------------------------------\n";


# EOF
