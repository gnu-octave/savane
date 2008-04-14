#!/usr/bin/perl
# Copyright (C) 2008  Aleix Conchillo Flaque
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

# Init Mercurial repository.

use strict;
use warnings;

require Exporter;
our @ISA = qw(Exporter);
our @EXPORT = qw(HgMakeArea);
our $version = 1;

sub HgMakeArea {
    my ($name,$dir_hg) = @_;
    my $warning = '';

    # %PROJECT is not mandatory, but if it is missing, it may well be 
    # a major misconfiguration.
    # It should only happen if a directory has been set for a specific 
    # project.
    unless ($dir_hg =~ s/\%PROJECT/$name/) {
	$warning = " (The string \%PROJECT was not found, there may be a group type serious misconfiguration)";
    }

    unless (-e $dir_hg) {
	# Layout: /srv/hg/sources/project_name
        #         /srv/hg/sources/project_name/other_module
	
	# Create a repository
	my $old_umask = umask(0002);

        # Initialise Mercurial repository
	system('hg', 'init', $dir_hg);
	
	system('chmod', 'g+s', $dir_hg);
	system('chgrp', '-R', $name, $dir_hg);

	# Create folder for subrepositories (need to code multi-repo support first)
	# TODO: precise directory location
	#system('mkdir', '-m', '2775', ".../$name/");
	#system('chown', "root:$name", ".../$name/");

	# Clean-up environment
	umask($old_umask);

	return ' '.$dir_hg.$warning;	
    }
    return 0;
}
