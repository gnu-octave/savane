#!/usr/bin/perl
#
# Copyright (c) 2003-2004 Derek Feichtinger <derek.feichtinger@cern.ch>
#	                  Yves Perrin <yves.perrin@cern.ch>
#
#   The Savane project is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   The Savane project is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with the Savane project; if not, write to the Free Software
#   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
#
# $Id$

# loose_ends.pl
#
# As suggested by the name, this script tries to tie up a few very
# CERN specific things concerning the database migration from the
# pre-release days of Savannah to the first real release.
# They are kept out of the other migration scripts, since it makes
# no sense at all to mix these issues up with issues of a more
# generic nature, which may be of interest to other sites.


use strict;
use DBI;
use Term::ReadKey;
use Getopt::Std;
# only for debugging: the Dumper module
use Data::Dumper;


sub usage {

print <<'EOF' ;

loose_ends.pl

   usage: loose_ends.pl [-c configuration_file]

 As suggested by the name, this script tries to tie up a few very
 CERN specific things concerning the database migration from the
 pre-release days of Savannah to the first real release.
 They are kept out of the other migration scripts, since it makes
 no sense at all to mix these issues up with issues of a more
 generic nature, which may be of interest to other sites.

EOF
}



#############################################################
# MAIN


my $flag_verbose;
my $conffilename;

# option parsing

my %option=();
getopts("c:hv",\%option);

if (defined $option{v}) {
    $flag_verbose=1;
}
if (defined $option{h}) {
  usage();
  exit;
}

if (defined $option{c}) {
  $conffilename=$option{c};
} else {
  $conffilename="dbmapper.conf";
}


# source the configuration file
our $src_dbname;
our $tgt_dbname;
our $dbhost;
our $dbport;
our $username;
our $password;
if (! -e $conffilename) {
  die "ERROR: could not open configuration file $conffilename\n";
}
do $conffilename;



if (!defined($password)) {
  # prompt user for the data base password
  print "DB password for user $username: ";
  ReadMode 'noecho';
  $password=ReadLine 0;
  chomp $password;
  ReadMode 'normal';
  print"\n";
}

my $dbh = DBI->connect("DBI:mysql:$tgt_dbname:$dbhost:$dbport",
		    $username,$password,
		    { RaiseError => 1, AutoCommit => 1}
		   );

# The user permissions stored in the user_group table need
# to be adapted. A value of 9 in such a column now carries
# the meaning that the permission will default to the
# default permission defined for the group.
if ($flag_verbose) {print "Updating the user permission flags:\n";}
my @perms=("forum","bugs","task","patch","support","news");
my $perm;
foreach $perm (@perms) {
  if ($flag_verbose) {print "   Changing the flags for: $perm\n";}
  my $sth=$dbh->prepare("UPDATE user_group SET ${perm}_flags=9 ".
		"WHERE ${perm}_flags=0");
  $sth->execute;
}


# STANDARDIZE THE URLs FOR THE HOMEPAGES
# by adding the 'http://' prefix where it is lacking

if ($flag_verbose) {
  print "\nStandardizing the homepage URLs:\n".
    "(adding 'html://' where necessary)\n";
}
my $counter=0;
my $sth = $dbh->prepare("SELECT group_id,url_homepage FROM groups LIMIT ?,1");

while(1) {
  $sth->execute($counter);
  $counter++;

  my $item = $sth->fetchrow_hashref;

  if(!$item) {last};

  if($$item{url_homepage} !~ /^http:\/\// &&
    $$item{url_homepage} !~ /^\//) {
    # dont't treat unfinished project registrations
    if($$item{url_homepage} !~ /^__/) {
      my $sth2=$dbh->prepare("UPDATE groups SET url_homepage='http://".
			     "$$item{url_homepage}' WHERE group_id=".
			     $$item{group_id});
      $sth2->execute;
      #if ($flag_verbose) {
	#print "   $$item{url_homepage} => http://$$item{url_homepage}\n";
      #}
    }

  }
}
