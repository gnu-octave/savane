#!/usr/bin/perl
# <one line to give a brief idea of what this does.>
# 
#
#
# Copyright (C) Loic Dachary <loic@gnu.org>, 2001
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
#
# Reload skill (people_skill) and skilllevel (people_skill_level) from
# CJN http://cjn.sourceforge.net/
#
#use strict;

use Getopt::Long;
use Savane;

my($verbose) = 0;
my($user) = "$sys_dbuser";
my($password) = "$sys_dbpasswd";
my($fake) = 0;
my($getopt);
my($help);

eval {
    $getopt = GetOptions("verbose+" => \$verbose,
			 "user=s" => \$user,
			 "password=s" => \$password,
			 "fake" => \$fake,
			 "help" => \$help);
};

if($help || !$getopt) {
    print STDERR <<EOF;
usage: $0 [--user=<user> --password=<password>] [--help] [--fake] [--verbose]

	Load the people_skill and people_skill_level tables from the 
	lists published on CJN (http://cjn.sourceforge.net/).

	--user=<user>		MySQL user name
	--password=<password>	MySQL password for user
	--verbose		increase verbosity level
	--fake			dont\'t do anything, only pretend
	--help			print this help

Author: loic\@gnu.org
EOF
     exit(1);
}

#$user = "--user=$user" if($user);
#$password = "--password=$password" if($password);

my(%cjn2sf) = (
	       'skill' => 'people_skill',
	       'skilllevel' => 'people_skill_level',
	       );

my(%ignore) = (
'Lotus Domino' => 1,
'Motif/X11' => 1,
'Oracle' => 1,
'Solaris' => 1,
'Turbo Pascal' => 1,
'IBM DB2' => 1,
'Routeur' => 1,
'Routeurs' => 1,
'Assembleur' => 1,
'Qmail' => 1,
'SourceForge Bug Track' => 1,
'SourceForge Task Lists' => 1,
);

open(MYSQL, "|mysql -u $user -p$password $sys_dbname") or die "cannot open MySQL connection : $!";
my($key);
foreach $key (qw(skill skilllevel)) {
    print "load $key\n" if($verbose);
    my($file) = "${key}_en_US.xml";
    system("rm -f ${file}* ; wget -q http://cjn.sourceforge.net/dictionaries/$file");
    die "could not load $file from CJN" if(! -f $file);
    my($table) = $cjn2sf{$key};
    print "delete from $table;\n" if($verbose);
    print MYSQL "delete from $table;\n" if(!$fake);
    open(XML, "<$file") or die "cannot open $file for reading : $!";
    while(<XML>) {
	if(m:^\s*<name id='(\d+)'>\s*(.*?)\s*</name>:) {
            my($id, $name) = ($1, $2);
            $name =~ s/\'/\'\'/g;
	    next if($key eq 'skill' && $ignore{$name});
	    my($sql) =  "insert into $table values ($id, '$name');\n";
	    print $sql if($verbose);
	    print MYSQL $sql if(!$fake);
        }
    }
    close(XML);
}

my($id) = 10000;
my($name);
foreach $name ('GNU Emacs lisp', 'GNU Emacs internals', 'Ruby', 'GNU Arch', 'Subversion', 'C#') {
	print "insert into people_skill values ($id, '$name');\n" if($verbose);
	print MYSQL "insert into people_skill values ($id, '$name');\n" if(!$fake);
        $id++;
}

close(MYSQL);
