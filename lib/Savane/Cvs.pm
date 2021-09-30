#!/usr/bin/perl
# Cvs-related subroutines.
#
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2003-2006, 2009 Sylvain Beucler <beuc--beuc.net>
# Copyright (C) 2003-2006 Free Software Foundation, Inc.
# Copyright (C) 2021 Ineiev
#
# This file is part of Savane.
#
# Savane is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

# (Called Cvs instead of CVS to avoid confusion with /CVS)
#
# Currently, the location of the locks is hardcoded, as the anoncvs
# group.

use strict;
use warnings;

require Exporter;
our @ISA = qw(Exporter);
our @EXPORT = qw(CvsMakeArea CvsMakeAreaAttic CvsMakeAreaSavannah WebCvsMakeAreaSavannahGNU WebCvsMakeAreaSavannahNonGNU );
our $version = 1;

our $dbd;

# Make a cvs area.
sub CvsMakeArea {
    my ($name,$dir_cvs,$is_public) = @_;
    my $warning = " (This sub is bit-rotten; it is disabled)";

    return " " . $dir_cvs . $warning;
    # %PROJECT is not mandatory, but if it is missing, it may well be
    # a major misconfiguration.
    # It should only happen if a directory has been set for a specific
    # project.
    unless ($dir_cvs =~ s/\%PROJECT/$name/) {
	$warning = " (The string \%PROJECT was not found, there may be a group type serious misconfiguration)";
    }

    return 0 if (-e $dir_cvs);

    # Build the directory.
    my $mode = $is_public ? 2775 : 2770;
    system("mkdir", "-p", $dir_cvs);
    system("chmod", $mode, $dir_cvs);
    `cd $dir_cvs && CVSROOT=$dir_cvs cvs init`;

    # Configure cvs.
    open(FILE, "> $dir_cvs/CVSROOT/config");
    print FILE "# Set this to \"no\" if pserver shouldn't check system users/passwords
SystemAuth=no

# Put CVS lock files in this directory rather than directly in the repository.
LockDir=/var/lock/cvs/$name

# Set TopLevelAdmin to yes to create a CVS directory at the top
# level of the new working directory when using the cvs checkout
# command.
#TopLevelAdmin=no

# Set LogHistory to AMRT
# (log only modifications)
LogHistory=AMRT
";
    close(FILE);

    open(FILE, ">> $dir_cvs/CVSROOT/checkoutlist");
    print FILE "readers         Wont be able to control read-only access.
passwd          Wont be able to add pserver accounts.
";
    close(FILE);

    open(FILE, "> $dir_cvs/CVSROOT/passwd");
    print FILE "anoncvs:02oawyZdjhhpg
anonymous:02oawyZdjhhpg
";
    close(FILE);

    open(FILE, "> $dir_cvs/CVSROOT/readers");
    print FILE "anoncvs
anonymous
";
    close(FILE);

    open(FILE, "> $dir_cvs/CVSROOT/config");
    print FILE "# Set this to \"no\" if pserver shouldn't check system users/passwords
SystemAuth=no

# Put CVS lock files in this directory rather than directly in the repository.
LockDir=/var/lock/cvs/$name

# Set TopLevelAdmin to yes to create a CVS directory at the top
# level of the new working directory when using the cvs checkout
# command.
#TopLevelAdmin=no


# Set LogHistory to AMRT
# (log only modifications)
LogHistory=AMRT
";
    close(FILE);

    # Copy the config file to be able to do ci on 'passwd' and 'readers'
    # without being prompted.
    system("cp", "$dir_cvs/CVSROOT/config,v", "$dir_cvs/CVSROOT/passwd,v");
    system("cp", "$dir_cvs/CVSROOT/config,v", "$dir_cvs/CVSROOT/readers,v");

    system("rcs", "-q", "-U", "$dir_cvs/CVSROOT/config", "$dir_cvs/CVSROOT/passwd", "$dir_cvs/CVSROOT/checkoutlist", "$dir_cvs/CVSROOT/readers");
    system("ci", "-q", "-m\"added by Savannah::Pm (anoncvs LockDir + SystemAuth)\"", "$dir_cvs/CVSROOT/config", "$dir_cvs/CVSROOT/passwd", "$dir_cvs/CVSROOT/checkoutlist", "$dir_cvs/CVSROOT/readers");
    system("co", "-q", "$dir_cvs/CVSROOT/config", "$dir_cvs/CVSROOT/passwd", "$dir_cvs/CVSROOT/checkoutlist", "$dir_cvs/CVSROOT/readers");

    system("touch", "$dir_cvs/CVSROOT/val-tags");
    system("mkdir", "$dir_cvs/$name");    # create the default module
    system("chmod", "2775", "$dir_cvs/$name");
    system("chgrp", "-R", $name, $dir_cvs);
    system("chown", "root:adm", "$dir_cvs/CVSROOT", "-R");
    # Make the CVSROOT ro for anybody;
    # doing otherwise is a major security hole.
    system("chgrp", $name, "$dir_cvs/CVSROOT/history");
    # History must be group writable.
    system("chmod", "664", "$dir_cvs/CVSROOT/history");
    system("chgrp", $name, "$dir_cvs/CVSROOT/val-tags");
    # val tag go world writable,
    # see task #147 @gna.org.
    system("chmod", "666", "$dir_cvs/CVSROOT/val-tags");

    # Build the locks.
    system("mkdir", "-p", "/var/lock/cvs/$name");
    system("chmod", "777", "/var/lock/cvs/$name");
    system("chgrp", $name, "/var/lock/cvs/$name");

    return " " . $dir_cvs . $warning;
}

# Make a cvs area at gn!elsewhere.
# Ask yeupou@gna.org before modifying this function.
sub CvsMakeAreaAttic {
    # Run the default sequence
    my $ret = CvsMakeArea(@_);

    if ($ret) {
	my ($name,$dir_cvs,$is_public) = @_;

	$dir_cvs =~ s/\%PROJECT/$name/;

	# hardcode cvsreport support
	open(FILE, "> $dir_cvs/CVSROOT/commitinfo");
	print FILE "ALL\tcvsreport -e 'mail text+html $name-commits'\n";
	close(FILE);
	system("rcs", "-q", "-U", "$dir_cvs/CVSROOT/commitinfo");
	system("ci", "-q", "-m\"added by Savannah::Pm (cvsreport support)\"", "$dir_cvs/CVSROOT/commitinfo");
	system("co", "-q", "$dir_cvs/CVSROOT/commitinfo");

	return " " . $dir_cvs;
    }

    return;
}

# Compute the type argument for new-savannah-project/new.py.
# It is derived from the dir_type_homepage of the group_type table
# with the exception of 'www'.
sub CvsSavannahWebType {
    my ($name) = @_;
    my $dir_type = GetGroupTypeSettings(GetGroupType($name), 'dir_type_homepage');

    return 'www' if $name eq 'www';

    # Return 'gnu' when $dir_type is 'savannah-gnu',
    #        'non-gnu' when $dir_type is 'savannah-nongnu',
    # else $dir_type (e.g. 'translations').
    if ($dir_type =~ s/^savannah-//) {
	$dir_type =~ s/^non/non-/;
    }
    return $dir_type;
}

# Build the loginfo and commitinfo configuration for a single hook.
sub BuildHooks {
    my ($group, $repo, $match_type, $dir_list, $emails_notif,
	$diff_on, $emails_diff, $branches, $hook_id) = @_;
    my $warning = '';
    my $log_accum = '';
    $dir_list = '' if (!defined($dir_list));
    $emails_diff = '' if (!defined($emails_diff));
    $branches = '' if (!defined($branches));
    
    my @dirs = split(',', $dir_list);

    # Check for spaces in dir names - this could be used to insert
    # a custom command, such as rm -rf...
    foreach my $dir (@dirs) {
	# Use POSIX [:space:] instead of \s for completeness
	# (check man perlre).
	if ($dir =~ /[[:space:]]/) {
	    return ('', '', "A space in the dir name '" . $dir . "'");
	}
    }

    my $regexp;
    if ($match_type eq 'ALL' || $match_type eq 'DEFAULT') {
	$regexp = $match_type;
    } elsif ($match_type eq 'dir_list') {
	if (scalar(@dirs) == 0) {
	    return  ('', '', $warning);
	} elsif (scalar(@dirs) == 1) {
	    $regexp = '^' . $dirs[0] . '\(/\|$\)';
	} else {
	    $regexp = '^\(' . join('\|', @dirs) . '\)\(/\|$\)';
	}
    } else {
	$warning = "Unknown match type: $match_type";
    }
    $log_accum .= $regexp;
    $log_accum .= ' /usr/local/bin/log_accum.pl ';

    if ($emails_notif eq '') {
	$warning = "No emails_notif - skipping...";
	return  ('', '', $warning);
    } else {
	my @emails = split(',', $emails_notif);
	$log_accum .= join('', map { "--mail-to $_ " } @emails);
    }

    if ($diff_on == 1) {
	$log_accum .= '--send-diff';
	$log_accum .= ' ';
	if ($emails_diff ne '') {
	    my @emails = split(',', $emails_diff);
	    $log_accum .= join('', map { "--separate-diffs $_ " } @emails);
	}
    } else {
	$log_accum .= '--no-send-diff';
	$log_accum .= ' ';
    }

    if ($branches ne '') {
	$log_accum .= join('', map { "--only-tag $_ " } split(',', $branches));
    }

    # Give a unique prefix for temporary files for each line.
    # Some lines may run concurrently (eg. when regexp='ALL').
    $log_accum .= '--file-prefix ' . $group . '_' . $hook_id;
    $log_accum .= ' ';

    my $config_file;
    if ($repo eq 'sources') {
	$config_file = 'log_accum-sources.config';
    } else {
	$config_file = 'log_accum-web.config';
    }
    $log_accum .= "--config /etc/$config_file";
    $log_accum .= ' ';

    $log_accum .= "%p %{sVv}";
    $log_accum .= "\n";

    my $commit_prep = "$regexp /usr/local/bin/commit_prep.pl ";
    $commit_prep .= '-T ' . $group . '_' . $hook_id;
    $commit_prep .= " %p\n";

    return ($log_accum, $commit_prep, $warning);
}

sub BasicWebHook {
    my ($name) = @_;
    my $type = CvsSavannahWebType($name);
    return "ALL echo 'Triggering webpages update...'; "
                   . "cat > /dev/null; "
                   . "curl http://www.gnu.org/new-savannah-project/new.py "
                   . "-s -F type=$type -F project=`basename %r`\n"
}

# Update email notification hooks in commitinfo if needed.
sub UpdateCvsNotifications {
    my ($name, $dir_cvs, $repo) = @_;
    my $warning = "";
    my $loginfo = "";
    my $commitinfo = "";

    my $hooks_ref = $dbd->selectall_hashref("
	SELECT groups.unix_group_name, cvs_hooks.*, cvs_hooks_log_accum.*
	FROM groups JOIN cvs_hooks ON groups.group_id = cvs_hooks.group_id
                    JOIN cvs_hooks_log_accum ON cvs_hooks.id = cvs_hooks_log_accum.hook_id
        WHERE groups.unix_group_name = '" . $name . "'
	      AND repo_name = '" . $repo . "'
	ORDER by hook_id", 'hook_id');

    foreach my $hook_id (keys %{$hooks_ref}) {
	my ($log_accum, $commit_prep, $w)
	    = BuildHooks($hooks_ref->{$hook_id}->{'unix_group_name'},
			 $hooks_ref->{$hook_id}->{'repo_name'},
			 $hooks_ref->{$hook_id}->{'match_type'},
			 $hooks_ref->{$hook_id}->{'dir_list'},
			 $hooks_ref->{$hook_id}->{'emails_notif'},
			 $hooks_ref->{$hook_id}->{'enable_diff'},
			 $hooks_ref->{$hook_id}->{'emails_diff'},
			 $hooks_ref->{$hook_id}->{'branches'},
			 $hook_id);
	$loginfo .= $log_accum;
	$commitinfo .= $commit_prep;
	if ($w ne '') {
	    $warning .= ' (' . $name . ':' . $repo . ': ' . $w . ')';
	}
    }

    if ($repo eq 'web') {
	$loginfo .= BasicWebHook($name);
    }

    sub update {
        my $w = '';
	my ($file, $replace_string) = @_;
	my $text;
	my $prev_text;
	{
	    local $/;
	    undef $/; # slurp mode
	    open(IN, "< $file") || ($w = " Cannot open $file: $! for reading.");
	    $text = <IN>;
	    close(IN);
	}
	$prev_text = $text;
	$text =~ s,^#<savane>\n[[:space:]]*.*^#</savane>($),#<savane>\n$replace_string#</savane>,sm;
	return $w if $prev_text eq $text;
	# Find lines present in $to_replace, but absent in current file, and vice versa.
	my $to_replace = $prev_text;
	$to_replace =~ s,.*^#<savane>\n[[:space:]]*(.*)^#</savane>.*,$1,sm;
        my @new_lines = split("\n", $replace_string);
        my @old_lines = split("\n", $to_replace);
	my %line_hash;
	my $diff = '';
	foreach my $val (@old_lines) {
	    $line_hash{$val} = 0;
	}
	foreach my $val (@new_lines) {
	    $line_hash{$val} = 0;
	}

	foreach my $val (@old_lines) {
	    $line_hash{$val}--;
	}
	foreach my $val (@new_lines) {
	    $line_hash{$val}++;
	}

	while (each %line_hash) {
	    if ($line_hash{$_} > 0) {
		$diff .= '+ ' . $_ . "\n";
	    }
	    if ($line_hash{$_} < 0) {
		$diff .= '- ' . $_ . "\n";
	    }
	}
	if ($diff ne '') {
	    print "updating $file:\n" . $diff;
	} else {
	    return $w;
	}

	open(OUT, "> $file") || ($w .= " Cannot open $file: $! for writing.");
	print OUT $text;
	close(OUT);
	return $w;
    }
    my $w;
    $w = update("$dir_cvs/CVSROOT/commitinfo", $commitinfo);
    if ($w ne '') {
	$warning .= ' (' . $name . ':' . $repo . ':' . $w . ')';
    }
    $w = update("$dir_cvs/CVSROOT/loginfo", $loginfo);
    if ($w ne '') {
	$warning .= ' (' . $name . ':' . $repo . ':' . $w . ')';
    }
    return 0 if $warning eq '';
    return ' ' . $dir_cvs . $warning;
}

# Make a cvs area at Savannah.
sub CvsMakeAreaSavannah {
    my ($name,$dir_cvs,$is_public,$repo_type) = @_;
    my $warning = "";

    $repo_type = 'sources' if (!defined($repo_type));

    # %PROJECT is not mandatory, but if it is missing, it may well be
    # a major misconfiguration.
    # It should only happen if a directory has been set for a specific
    # project.
    unless ($dir_cvs =~ s/\%PROJECT/$name/) {
	$warning = " (The string \%PROJECT was not found, there may be a group type serious misconfiguration)";
    }

    return UpdateCvsNotifications($name, $dir_cvs, $repo_type) if (-e $dir_cvs);

    # Build the directory.
    my $mode = $is_public ? '2775' : '2770';
    system('mkdir', '-p', '-m', $mode, $dir_cvs);
    system('cvs', '-d', "$dir_cvs", 'init');

    # Make the CVSROOT ro for anybody doing otherwise is a major
    # security hole (pserver, if ran as root and without the
    # latest patches, can be set to give root access using the
    # CVSROOT/passwd file; you also basically give local access if
    # you allow people to modify the hooks).
    system('chown', '-R', 'root:root', "$dir_cvs/CVSROOT");
    system('chmod', '755', "$dir_cvs/CVSROOT");

    # Clean-up CVSROOT.
    while(glob("$dir_cvs/CVSROOT/*,v"))  { unlink };
    while(glob("$dir_cvs/CVSROOT/.\#*")) { unlink };

    # Configure cvs.
    open(FILE, "> $dir_cvs/CVSROOT/config");
    print FILE <<"EOF";
# Set this to "no" if pserver shouldn't check system users/passwords
SystemAuth=no
# Set also this to "no" under Debian for the same reason
#PamAuth=no

# Put CVS lock files in this directory rather than directly in the repository.
LockDir=/var/lock/cvs/$repo_type/$name

# Set `TopLevelAdmin' to `yes' to create a CVS directory at the top
# level of the new working directory when using the `cvs checkout'
# command.
#TopLevelAdmin=no

# Set `LogHistory' to `all' or `TOEFWUPCGMAR' to log all transactions to the
# history file, or a subset as needed (ie `TMAR' logs all write operations)
#LogHistory=TOEFWUPCGMAR
# Cut down on disk I/O by only logging write operations
LogHistory=TMAR

# Set `RereadLogAfterVerify' to `always' (the default) to allow the verifymsg
# script to change the log message.  Set it to `stat' to force CVS to verify
# that the file has changed before reading it (this can take up to an extra
# second per directory being committed, so it is not recommended for large
# repositories.  Set it to `never' (the previous CVS behavior) to prevent
# verifymsg scripts from changing the log message.
#RereadLogAfterVerify=always

# Set `UserAdminOptions' to the list of `cvs admin' commands (options)
# that users not in the `cvsadmin' group are allowed to run.  This
# defaults to `k', or only allowing the changing of the default
# keyword expansion mode for files for users not in the `cvsadmin' group.
# This value is ignored if the `cvsadmin' group does not exist.
#
# The following string would enable all `cvs admin' commands for all
# users:
#UserAdminOptions=aAbceIklLmnNostuU
UserAdminOptions=k

# Set `UseNewInfoFmtStrings' to `no' if you must support a legacy system by
# enabling the deprecated old style info file command line format strings.
# Be warned that these strings could be disabled in any new version of CVS.
UseNewInfoFmtStrings=yes
EOF
    close(FILE);

    open(FILE, "> $dir_cvs/CVSROOT/passwd");
    print FILE <<EOF;
anonymous::nobody
anoncvs::nobody
EOF
    close(FILE);

    open(FILE, "> $dir_cvs/CVSROOT/readers");
    print FILE <<EOF;
anonymous
anoncvs
EOF
    close(FILE);

# Mark files for Savane hooks management.
    open(FILE, "> $dir_cvs/CVSROOT/commitinfo");
    print FILE <<EOF;
#<savane>
#</savane>
EOF
    close(FILE);
    open(FILE, "> $dir_cvs/CVSROOT/loginfo");
    print FILE <<EOF;
#<savane>
EOF
    if ($repo_type eq 'web') {
        print FILE BasicWebHook($name);
    }

    print FILE <<EOF;
#</savane>
EOF
    close(FILE);

    # If not present, pserver assumes write access for everybody
    # not in 'readers'.
    open(TOUCH, "> $dir_cvs/CVSROOT/writers");
    close(TOUCH);

    chmod(0644, "$dir_cvs/CVSROOT/config");
    chmod(0644, "$dir_cvs/CVSROOT/passwd");
    chmod(0644, "$dir_cvs/CVSROOT/readers");
    chmod(0644, "$dir_cvs/CVSROOT/writers");

    # val tag go world writable,
    # see task #147 @gna.org.
    chmod(0666, "$dir_cvs/CVSROOT/val-tags");

    # History is group writable.
    system('chgrp', $name, "$dir_cvs/CVSROOT/history");
    chmod(0664, "$dir_cvs/CVSROOT/history");


    # Create the default module.
    system('mkdir', '-m', '2775', "$dir_cvs/$name");
    # Allow group access.
    system('chgrp', $name, $dir_cvs);
    system('chgrp', $name, "$dir_cvs/$name");

    # Build the locks.
    system('mkdir', '-p', '-m', '777', "/var/lock/cvs/$repo_type/$name");

    # Seal CVSROOT dir; 2775 on the top dir allows group members
    # with local access to rename CVSROOT and replace it with
    # their own.
    system('chattr', '+i', "$dir_cvs/CVSROOT/");

    return ' ' . $dir_cvs . $warning;
}

sub WebCvsMakeArea {
    my ($name,$dir_cvs,$is_public,$update_acl) = @_;
    my $ws_type = CvsSavannahWebType($name);

    unless ($dir_cvs =~ s/\%PROJECT/$name/) {
	return 1;
    }

    # Give CvsMakeAreaSavannah a chance to update hooks
    # before skipping the operations that are only needed for fresh repos.
    my $have_dir_cvs = -e $dir_cvs;
    CvsMakeAreaSavannah($name,$dir_cvs,$is_public,'web');
    return 0 if $have_dir_cvs;

    if ($update_acl) {
        # Allow modifications from group 'www' in the web module.
        system('setfacl',
               '-m',         'group:www:rwx',
               '-m', 'default:group:www:rwx',
               "$dir_cvs/$name");

        # Same for CVSROOT/history.
        system('setfacl',
               '-m',         'group:www:rw',
               "$dir_cvs/CVSROOT/history");
    }
    # Perform an initial checkout so that updates happen:
    # (will also be done at commit time now).
    system ('/usr/bin/curl', 'http://www.gnu.org/new-savannah-project/new.py',
            '-s', '-F', "type=$ws_type", '-F', "project=$name");
}

# Make a gnu webcvs area at Savannah.
sub WebCvsMakeAreaSavannahGNU {
    my ($name,$dir_cvs,$is_public) = @_;

    WebCvsMakeArea($name, $dir_cvs, $is_public, 1);
}

# Make a non-gnu webcvs area at Savannah.
# The difference from gnu webcvs is that no ACLs
# are updated for GNU webmasters access.
sub WebCvsMakeAreaSavannahNonGNU {
    my ($name,$dir_cvs,$is_public) = @_;
    WebCvsMakeArea($name, $dir_cvs, $is_public, 0);
}
