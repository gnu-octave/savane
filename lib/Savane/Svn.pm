#!/usr/bin/perl
# <one line to give a brief idea of what this does.>
# 
# Copyright 2005      (c) Timothee Besset <ttimo--ttimo.net>
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
## Desc: any subs related to SVN.
## read-write access: through authenticated ssh, spawning svnserve
## anonymous read: through svnserve daemon on port 3690
##   by default, svnserve only lets read for anonymous, and read-write for authed
##   since the systems have no passwords for users (only ssh keys), one can't auth to the anonymous daemon

use strict;
use warnings;

require Exporter;
our @ISA = qw(Exporter);
our @EXPORT = qw(SvnMakeArea SvnMakeAreaAttic SvnMakeAreaAtticWebsite );
our $version = 1;

sub SvnMakeArea {
    # FIXME: are private project handled as expected here?
    my ($name,$dir_svn) = @_;
    my $warning = "";

    # %PROJECT is not mandatory, but if it is missing, it may well be 
    # a major misconfiguration.
    # It should only happen if a directory has been set for a specific 
    # project.
    unless ($dir_svn =~ s/\%PROJECT/$name/) {
	$warning = " (The string \%PROJECT was not found, there may be a group type serious misconfiguration)";
    }

    unless (-e $dir_svn) {
	# fsfs repositories are the most stable and scalable. bdb just
	# doesn't cut it. fsfs also is the default in new SVN releases.
	system('svnadmin', 'create', '--fs-type', 'fsfs', $dir_svn);

	# <no default repository layout for the default SVN>

	# group ownership - svnadmin takes care of setgid
        # giving privs to db/ is enough
	# giving privs to hooks/ would mean giving local access
	system('chgrp', '-R', $name, "$dir_svn/db");
	system('chmod', '-R', 'g+w', "$dir_svn/db");
	return ' '.$dir_svn.$warning;	
    }
    return 0;
}



## Make a svn area at gna!
## Ask yeupou--gna.org before modifying this function
sub SvnMakeAreaAttic {
    my $ret = SvnMakeArea(@_);

    if ($ret) {
	my ($name,$dir_svn) = @_;

	$dir_svn =~ s/\%PROJECT/$name/;
	
	# create the default repository layout
	system("svn", "mkdir", "-q", "-m \"default layout\"", "file://$dir_svn/trunk");
	system("svn", "mkdir", "-q", "-m \"default layout\"", "file://$dir_svn/tags");
	system("svn", "mkdir", "-q", "-m \"default layout\"", "file://$dir_svn/branches");

	# hardcode svnmailer + ciabot support
	open(FILE, "> $dir_svn/hooks/post-commit");
	print FILE "#!/usr/bin/perl
# (obviously, svn-mailer and ciabot.sh must be in the relevant PATH)
system(\"sv_extra_svn_postcommit_brigde\", \"-t\", \"\$ARGV[0]\", \"-r\", \"\$ARGV[1]\", \"-p\", \"$name\");
system(\"svn-mailer\", \"-d\$ARGV[0]\", \"-r\$ARGV[1]\", \"-f/etc/svn-mailer.conf\");
system(\"ciabot.sh\", \"\$ARGV[0]\", \"\$ARGV[1]\", \"$name\");
";
	close(FILE);
	system("chmod", "755", "$dir_svn/hooks", "$dir_svn/hooks/post-commit");
	return " ".$dir_svn;
    }

    return;
}


## Make a svn area for the website at gna!
## Ask yeupou--gna.org before modifying this function
sub SvnMakeAreaAtticWebsite {
    # Create the whole repository, if it does not exists yet
    my ($name,$dir_svn) = @_;
    $dir_svn =~ s/\%PROJECT/$name/;
    
    my $missing = 0;

    unless (-e $dir_svn) {
	SvnMakeAreaAttic(@_);
        # if dir svn still does not exists, there is something weird
	return 0 unless -e $dir_svn;
	$missing = 1;
    }

    # Add the subdirectory is not yet present

    # Check if necessary
    unless ($missing) {
	# From here, assume that the entry may be missing and that it is not
	# only if svnlook return on STDOUT the correct string
	$missing = 1;
	open(CHECK, "svnlook tree $dir_svn /website |");
	while (<CHECK>) {
	    chomp($_);
	    $missing = 0 if $_ eq "website/";
	    last;
	}
	close(CHECK);
    }

    if ($missing) {
	system("svn", "mkdir", "-q", "-m \"website at home.gna.org\"", "file://$dir_svn/website");
	return " ".$dir_svn;
    }

    return;
}
