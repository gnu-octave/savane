#!/usr/bin/perl
# <one line to give a brief idea of what this does.>
# 
# Copyright 2003-2005 (c) Mathieu Roy <yeupou--gnu.org>
#                         Timothee Besset <ttimo--ttimo.net>
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
#
#

##
## Desc: any subs related to database access...
## Usually used in other subs.
##

use strict "vars";
require Exporter;
use Text::Wrap;

# Exports
our @ISA = qw(Exporter);
our @EXPORT = qw(GetConf );
our $version = 1;

# Imports (needed for strict)
our $conffile;
our $sys_default_domain;
our $prefix;

# Init: read configuration file
# 
# FIXME: we should have some way to select different Savannah installation
# on the same computer, for instance by saving the location of their conffiles
# in /etc/savannah.list
# Currently solution is to do what we do with Apache, we set an environment
# variable, SAVANE_CONF.

# First try to honor environment variable
if ($ENV{SAVANE_CONF}) {
    if (-e $ENV{SAVANE_CONF}."/savane.conf.pl") {
	$conffile = $ENV{SAVANE_CONF}."/savane.conf.pl";	
    } else {
	print RED,"Can't honor SAVANE_CONF environment variable,",RESET," going back to default.\n";
    }
}
unless ($conffile) {
    if (-e "/etc/savane/savane.conf.pl") {
	$conffile = "/etc/savane/savane.conf.pl";
    } elsif (-e "/etc/savane/savane.conf.pl") {
	$conffile = "/etc/savane/savane.conf.pl";  
    } elsif (-e "/usr/etc/savane/savane.conf.pl") {
	$conffile = "/usr/etc/savane/savane.conf.pl";    
    } elsif (-e "/usr/local/etc/savane/savane.conf.pl") {
	$conffile = "/usr/local/etc/savane/savane.conf.pl";    
    } else {
	die wrap("", "", "Unable to find any configuration file. If you use a non standard path, something different than /etc/savane, set your shell environment variable SAVANE_CONF accordingly\n\nStopped");
    }
}
do $conffile or die "Unable to run $conffile.\n", RED,"Most commonly, it's a privilege issue.",RESET,"\n\nStopped";


# Return a configuration item
sub GetConf {
    my $arg = $_[0];
    return $$arg;    
}
