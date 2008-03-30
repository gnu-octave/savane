#!/usr/bin/perl
# <one line to give a brief idea of what this does.>
# 
# Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                         Sylvain Beucler <beuc--beuc.net>
#                         Free Software Foundation, Inc.
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

##
## Desc: any subs related to CVS.
## (Called Cvs instead of CVS to avoid confusion with /CVS)
## 
## Currently, the location of the locks is hardcoded, as the anoncvs
## group.
##

use strict;
use warnings;

require Exporter;
our @ISA = qw(Exporter);
our @EXPORT = qw(CvsMakeArea CvsMakeAreaAttic CvsMakeAreaSavannah WebCvsMakeAreaSavannahGNU WebCvsMakeAreaSavannahNonGNU );
our $version = 1;


## Make a cvs area
sub CvsMakeArea {
    my ($name,$dir_cvs,$is_public) = @_;
    my $warning = "";

    # %PROJECT is not mandatory, but if it is missing, it may well be 
    # a major misconfiguration.
    # It should only happen if a directory has been set for a specific 
    # project.
    unless ($dir_cvs =~ s/\%PROJECT/$name/) {
	$warning = " (The string \%PROJECT was not found, there may be a group type serious misconfiguration)";
    }

    unless (-e $dir_cvs) {
	
	# build the directory
	my $mode = $is_public ? 2775 : 2770;
	system("mkdir", "-p", $dir_cvs);
	system("chmod", $mode, $dir_cvs);
	`cd $dir_cvs && CVSROOT=$dir_cvs cvs init`;

	# configure cvs
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

	# cp the config file to be able to do ci on 'passwd' and 'readers'
	# without being prompted
	system("cp", "$dir_cvs/CVSROOT/config,v", "$dir_cvs/CVSROOT/passwd,v");
	system("cp", "$dir_cvs/CVSROOT/config,v", "$dir_cvs/CVSROOT/readers,v");

	# ci
	system("rcs", "-q", "-U", "$dir_cvs/CVSROOT/config", "$dir_cvs/CVSROOT/passwd", "$dir_cvs/CVSROOT/checkoutlist", "$dir_cvs/CVSROOT/readers");
	system("ci", "-q", "-m\"added by Savannah::Pm (anoncvs LockDir + SystemAuth)\"", "$dir_cvs/CVSROOT/config", "$dir_cvs/CVSROOT/passwd", "$dir_cvs/CVSROOT/checkoutlist", "$dir_cvs/CVSROOT/readers");
	system("co", "-q", "$dir_cvs/CVSROOT/config", "$dir_cvs/CVSROOT/passwd", "$dir_cvs/CVSROOT/checkoutlist", "$dir_cvs/CVSROOT/readers");


	system("touch", "$dir_cvs/CVSROOT/val-tags");
	system("mkdir", "$dir_cvs/$name");    # create the default module
	system("chmod", "2775", "$dir_cvs/$name");
	system("chgrp", "-R", $name, $dir_cvs);
	system("chown", "root:adm", "$dir_cvs/CVSROOT", "-R");
	                                     # make the CVSROOT ro for anybody
	                                     # doing otherwise is a major
	                                     # security hole.
	system("chgrp", $name, "$dir_cvs/CVSROOT/history"); 
                                             # history must be group writable
	system("chmod", "664", "$dir_cvs/CVSROOT/history");
	system("chgrp", $name, "$dir_cvs/CVSROOT/val-tags");
	system("chmod", "666", "$dir_cvs/CVSROOT/val-tags");
	                                     # val tag go world writable
	                                     # see task #147 @gna.org

	# build the locks
	system("mkdir", "-p", "/var/lock/cvs/$name");
	system("chmod", "777", "/var/lock/cvs/$name");
	system("chgrp", $name, "/var/lock/cvs/$name");

	return " ".$dir_cvs.$warning;
    } 
    return 0;
}


## Make a cvs area at gn!elsewhere.
## Ask yeupou@gna.org before modifying this function
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
	
	return " ".$dir_cvs;
    }

    return;
}

## Make a cvs area at Savannah
## This is temporary
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

    unless (-e $dir_cvs) {
	
	# build the directory
	my $mode = $is_public ? '2775' : '2770';
	system('mkdir', '-p', '-m', $mode, $dir_cvs);
	system('cvs', '-d', "$dir_cvs", 'init');


	# make the CVSROOT ro for anybody doing otherwise is a major
	# security hole (pserver, if ran as root and without the
	# latest patches, can be set to give root access using the
	# CVSROOT/passwd file; you also basically give local access if
	# you allow people to modify the hooks)
	system('chown', '-R', 'root:root', "$dir_cvs/CVSROOT");
	system('chmod', '755', "$dir_cvs/CVSROOT");

	# clean-up CVSROOT
	while(glob("$dir_cvs/CVSROOT/*,v"))  { unlink };
	while(glob("$dir_cvs/CVSROOT/.\#*")) { unlink };

	# configure cvs
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

# Mark files for Savane hooks management
	open(FILE, "> $dir_cvs/CVSROOT/commitinfo");
	print FILE <<EOF;
#<savane>
#</savane>
EOF
	open(FILE, "> $dir_cvs/CVSROOT/loginfo");
	print FILE <<EOF;
#<savane>
#</savane>
EOF

	# if not present, pserver assumes write access for everybody
	# not in 'readers'
	open(TOUCH, "> $dir_cvs/CVSROOT/writers"); close(TOUCH);

	chmod(0644, "$dir_cvs/CVSROOT/config");
	chmod(0644, "$dir_cvs/CVSROOT/passwd");
	chmod(0644, "$dir_cvs/CVSROOT/readers");
	chmod(0644, "$dir_cvs/CVSROOT/writers");


	# val tag go world writable
	# see task #147 @gna.org
	chmod(0666, "$dir_cvs/CVSROOT/val-tags");

	# history is group writable
	system('chgrp', $name, "$dir_cvs/CVSROOT/history"); 
	chmod(0664, "$dir_cvs/CVSROOT/history");


        # create the default module
	system('mkdir', '-m', '2775', "$dir_cvs/$name");    
	# allow group access
	system('chgrp', $name, $dir_cvs);
	system('chgrp', $name, "$dir_cvs/$name");


	# build the locks
	system('mkdir', '-p', '-m', '777', "/var/lock/cvs/$repo_type/$name");


	# seal CVSROOT dir; 2775 on the top dir allows a group member
	# with local access to rename CVSROOT and replace it with
	# his/her own
	system('chattr', '+i', "$dir_cvs/CVSROOT/");

	return ' '.$dir_cvs.$warning;
    } 
    return 0;
}


## Make a webcvs area at Savannah
## This is temporary
sub WebCvsMakeAreaSavannahGNU {
    my ($name,$dir_cvs,$is_public) = @_;

    my $replace_tag = "%PROJECT";
    
    unless ($dir_cvs =~ s/\%PROJECT/$name/) {
	return 1;
    }

    unless (-e $dir_cvs) {
        CvsMakeAreaSavannah($name,$dir_cvs,$is_public,'web');

	# Allow modifications from group 'www' in the web module
	system('setfacl',
	       '-m',         'group:www:rwx',
	       '-m', 'default:group:www:rwx',
	       "$dir_cvs/$name");

	# Same for CVSROOT/history
	system('setfacl',
	       '-m',         'group:www:rw',
	       "$dir_cvs/CVSROOT/history");


        # perform an initial checkout so that updates happen:
	# (will also be done at commit time now)
        system ('/usr/bin/curl', 'http://www.gnu.org/new-savannah-project/new.py',
		'-s', '-F', "type=gnu", '-F', "project=$name");
    }
}


## Make a webcvs area at Savannah
## This is temporary
sub WebCvsMakeAreaSavannahNonGNU {
    my ($name,$dir_cvs,$is_public) = @_;

    my $replace_tag = "%PROJECT";
    
    unless ($dir_cvs =~ s/\%PROJECT/$name/) {
	return 1;
    }

    unless (-e $dir_cvs) {
        CvsMakeAreaSavannah($name,$dir_cvs,$is_public,'web');
    
        # perform an initial checkout so that updates happen:
	# (will also be done at commit time now)
        system ('/usr/bin/curl', 'http://www.gnu.org/new-savannah-project/new.py',
		'-s', '-F', "type=non-gnu", '-F', "project=$name");
    }
}
