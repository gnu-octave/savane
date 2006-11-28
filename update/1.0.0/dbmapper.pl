#!/usr/bin/perl
#
# Copyright (c) 2003-2004 Derek Feichtinger <derek.feichtinger@cern.ch>
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

##################################################################
# dbmapper.pl
#
# script to map database structures and to copy columns from
# one structure to the other.
#
# Author: Derek Feichtinger <derek.feichtinger@cern.ch>
##################################################################

use strict;
use DBI;
use Term::ReadKey;
use Getopt::Std;


# only for debugging: the Dumper module
use Data::Dumper;



sub usage {

print <<'EOF' ;

dbmapper.pl:

    usage:  dbmapper.pl -i infile [-c configuration_file]
                       [-o sql-outfile] [-l logfile]

 script to map database structures and to copy columns from
 one structure to the other.

    example for the contents of a configuration file:

  #############################################################
  # DBMAPPER.PL CONFIGURATION FILE
  #
  # name of the source MySQL data base
  $src_dbname="old_savannah";
  # name of the target MySQL data base
  $tgt_dbname="df_savannah";
  # hostname and port information for the DBs
  $dbhost="localhost";
  $dbport=3306;
  # MySQL user with read/write access to both DBs
  $username="df_savannah";
  #############################################################

EOF
}


############################
# getTables
# usage: ($dbstruct,$keys)=getTables(dbh)
#
# input parameters:
#   dbh    mysql data base handle
#
# return values:
#   dbstruct      reference to a hash containing information on
#                 the table structure of the data base
#   keys          reference to a hash containing the primary
#                 key field for every table name
#
#
# Example for a dbstruct element:
#
#  'project_group_list' => {
#                            'description' => 'text',
#                            'is_public' => 'int(11)',
#                            'group_project_id' => 'int(11)',
#                            'order_id' => 'int(11)',
#                            'group_id' => 'int(11)',
#                            'project_name' => 'text'
#                          },
sub getTables {
  my $dbh=shift;   # data base handle

  #my $dbstruct;    # database description (return value)

  my $sth;   # Statement handle objects
  my $sth2;

  my $tableref;   # reference for result hash
  my $colref;
  my $dbstring;
  my $query;
  my $tablename;
  my $colname;

  my %dbstruct; # the db structure hash to be returned
  my %primkeys; # hash mapping {tablename}=>{primary key column}

  $query="show tables";
  $sth = $dbh->prepare($query);
  $sth->execute();

  while ( $tableref=$sth->fetchrow_hashref ) {
    foreach $dbstring (keys %$tableref) { # overkill:tableref has only 1 element 
      my $tablename=$tableref->{$dbstring};
      #print "$dbstring: $tablename\n";

      $query="describe $tablename";
      $sth2 = $dbh->prepare($query);
      $sth2->execute();

      # build up a hash containing  column name => field type
      #   for this table
      my %tablestruct;
      while ($colref=$sth2->fetchrow_hashref) {
	$tablestruct{$$colref{"Field"}}=$$colref{"Type"};

	# identify the primary key for the table
	if($$colref{"Key"} eq "PRI") {
	  $primkeys{$tablename}=$$colref{"Field"};
	  #print "          Key: $$colref{Field} is primary key\n";
	}
      }

      # add this hash to the db structure hash
      $dbstruct{$tablename}=\%tablestruct;

    }

  }

return (\%dbstruct,\%primkeys);
}

############################
# match_patterns
# usage: test=match_patterns(string,patterns)
#
# input parameters:
#   string    string to be tested
#   patterns  reference to array containing regexp patterns
#
# return values:
# function will return true(1) if string matches any of the
# search patterns, false(0) otherwise
sub match_patterns {
  my $string=shift; # string to search
  my $patterns=shift; # reference to array of search patterns

  my $pattern;
  foreach $pattern (@$patterns) {
    if ($string =~ /$pattern/) { 
      return 1;
    }
  }
  return 0;
}

#############################################################
# MAIN
#############################################################


my $dbh;  # standard db handle
my $tablename;
my $colname;
my $fullname;
my $src_tablename;
my $src_colname;

# user provided table mappings from input file
my %user_table_map;
# user provided column mappings from input file
my %user_col_map;
# user provided information for tables.columns with no mappings
# (this will prevent the printing of warning messages for these
# cases)
my @nomap_source_col = ();
my @nomap_target_col = ();
my @nomap_target_table = ();


# main result hash listing the mapping for each target table
# structure: $target_col_map{$targettable}{column}=srctable.column
my %target_col_map;

my $infilename; # input file containing mapping information
my $conffilename; # name of the configuration file
my $logfilename; # output log file (can be used as input file for next run)
my $dbcomfile;  # MySQL output command file
my $flag_verbose;


############################################
# option parsing

my %option=();
getopts("c:hi:l:o:v",\%option);

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

if (defined $option{i}) {
  $infilename=$option{i};
} else {
  print stderr "ERROR: missing input file name\n";
  usage();
  exit;
}

if (defined $option{l}) {
  $logfilename=$option{l};
} else {
  $logfilename=$infilename . ".log";
}

if (defined $option{o}) {
  $dbcomfile=$option{o};
} else {
  $dbcomfile=$infilename . ".sql";
}

#######################################################

#####################################################
# Configuration options:
#

# CONFIGURATION FILE SOURCING
# read in the configuration file containing information
# on the data base
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
  print "\n";
}

# get the two database structures and primary key hashes
$dbh = DBI->connect("DBI:mysql:$src_dbname:$dbhost:$dbport",
		    $username,$password,
		    { RaiseError => 1, AutoCommit => 1}
		   );
my ($db_source,$primkeys_source)=getTables($dbh);
$dbh->disconnect();

$dbh = DBI->connect("DBI:mysql:$tgt_dbname:$dbhost:$dbport",
		    $username,$password,
		    { RaiseError => 1, AutoCommit => 1}
		   );
my ($db_target,$primkeys_target)=getTables($dbh);
$dbh->disconnect();

open(OUTP,">$logfilename") or die "Error: Could not open output file: $logfilename\n";

#print OUTP "DEBUG: ".Dumper($db_source);
#print OUTP "DEBUG: ".Dumper($db_target);
#print OUTP "DEBUG: ".Dumper($primkeys_source);
#print OUTP "DEBUG: ".Dumper($primkeys_target);

# -----------------------------------------------------
# INPUT FILE PARSING
#
# build two hashes for the mapping information
# 
# entries always ordered as
#               new name => old name
#
# table name mappings:
# %user_table_map = ( "bugs" => "bug",
#		       "bug_field_value" => "bugs_field"
#                    );
# column mappings:
# %user_col_map = ( "support.bug_id" => "support.support_id" ,
#			 "support.date" => "support.open_date"
#                    );

print OUTP "################################################################\n";
print OUTP "#                START OF USER SUPPLIED INFORMATION              #\n";
print OUTP "#                                                                #\n";

open(INFILE,"<$infilename") or die "Error: Could not open $infilename\n";

my $line;
my ($tok1,$tok2);
my $linecounter=0;
while($line=<INFILE>) {
  $linecounter++;
  print OUTP $line;
  chomp $line;
  $line =~ s/^\s*//; # get rid of leading whitespace
  if($line eq "") { next }
  if($line=~/^\s*#.*/) { next } # ignore comment lines

  if( ($tok1) = $line =~ m/^\s*nomap_source_col\s+([^\s]+).*/) {
    push (@nomap_source_col, $tok1);
    next;
  }

  if( ($tok1) = $line =~ m/^\s*nomap_target_col\s+([^\s]+).*/) {
    push (@nomap_target_col, $tok1);
    next;
  }

  if( ($tok1) = $line =~ m/^\s*nomap_target_table\s+([^\s]+).*/) {
    push (@nomap_target_table, $tok1);
    next;
  }



  if( ($tok1,$tok2) = $line =~ m/^\s*map_table\s+([^\s]+)\s+([^\s]+).*/) {
    $user_table_map{$tok1}=$tok2;
    next;
  }

  if( ($tablename,$colname,$src_tablename,$src_colname) = $line =~
      m/^\s*map_col\s+([^\s]+)\.([^\s]+)\s+([^\s]+)\.([^\s]+).*/ ) {
    $user_col_map{"$tablename.$colname"}="$src_tablename.$src_colname";

    # test whether source column exists
    if(!exists $$db_source{$src_tablename}{$src_colname}) {
      die "\nERROR (line: $linecounter): no such source column\n>>  $line\n";
    }
    # test whether target column exists
    if(!exists $$db_target{$tablename}{$colname}) {
      die "\nERROR (line: $linecounter): no such target column\n>>  $line\n";
    }

    next;
  }

  die "ERROR: cannot parse line:\n   >$line\n";
}
close INFILE;


print OUTP "#                                                            #\n";
print OUTP "#              END OF USER SUPPLIED INFORMATION              #\n";
print OUTP "##############################################################\n";



# -----------------------------------------------------------------
# CREATE ALL COLUMN MAPPINGS (AUTOMATIC + USER SUPPLIED INFORMATION)

# build the target_col_map hash wich will contain
# all the mappings for the target db columns. Columns with no
# mappings are to contain the empty "" string

foreach $tablename (keys %$db_target) {

  # ignore target tables that user defined as being not mapped
  if(match_patterns("$tablename",\@nomap_target_table)) {next}

  my $tablehash=$$db_target{$tablename};
  foreach $colname (keys %$tablehash ) {

    # defaults
    $src_tablename=$tablename;
    $src_colname=$colname;

    # include user specified table name mappings
    if(exists $user_table_map{$tablename}) {
      $src_tablename=$user_table_map{$tablename};
    }

    # include user specified column mappings
    if(exists $user_col_map{"$tablename.$colname"}) {
      ($src_tablename,$src_colname)=
       $user_col_map{"$tablename.$colname"} =~ m/(.*)\.(.*)/;
    }



    # test whether source column exists
    if(!exists $$db_source{$src_tablename}{$src_colname}) {
      # collect columns with no mappings
      $target_col_map{$tablename}{$colname}="";
      next;
    }

    # test whether column mapping is excluded on purpose by user
    if(match_patterns("$tablename.$colname",\@nomap_target_col)) {next}

    # test whether source and target columns are of the same
    # type
    if($$db_target{$tablename}{$colname} ne
       $$db_source{$src_tablename}{$src_colname}) {
      print OUTP "# WARNING (TYPE MISMATCH): $tablename.$colname (".
	$$db_target{$tablename}{$colname}.") <= ".
	  "$src_tablename.$src_colname (".
	    $$db_source{$src_tablename}{$src_colname}.")\n";
      if($flag_verbose) {
	print "\nWARNING (TYPE MISMATCH): $tablename.$colname (".
	  $$db_target{$tablename}{$colname}.") <= ".
	    "$src_tablename.$src_colname (".
	      $$db_source{$src_tablename}{$src_colname}.")\n";
      }
    }

    # if everything is fine, accept this mapping
    $target_col_map{$tablename}{$colname}="$src_tablename.$src_colname";
  }
}
#print OUTP "DEBUG: ".Dumper(\%target_col_map);


# Write the MySQL command file
open (DBFILE,">$dbcomfile") or die "Error: Could not open db command file: $dbcomfile";

print DBFILE "use $tgt_dbname;\n";

my %collect;
foreach $tablename (sort keys %$db_target) {

  # build "col1,col2,col3" strings for SQL command
  %collect=();
  my $primkey_gets_mapped=0; # flag indicating whether primary key is mapped
  my $has_mappings=0; # counter for number of column mappings for this table

  my $tablehash=$$db_target{$tablename};
  foreach $colname (keys %$tablehash ) {

    if($colname eq $$primkeys_target{$tablename}) {
      $primkey_gets_mapped=1;
    }

    if($target_col_map{$tablename}{$colname} ne "") {
      $has_mappings+=1;
      ($src_tablename,$src_colname) =
	$target_col_map{$tablename}{$colname} =~ m/\s*([^.]+)\.(.+)/;

      $collect{$src_tablename}{"srccol"}.="$src_colname,";
      $collect{$src_tablename}{"tgtcol"}.="$colname,";
    }
  }


  # skip tables with no mappings at all. Collect their names
  if (!$has_mappings) {next}

  # warn if there is just one mapping
  if ($has_mappings==1 && $flag_verbose) {
    print "WARNING: Table $tablename has just one mapped column: ".
      $collect{$src_tablename}{"tgtcol"}."\n";
  }

  # CURRENT LIMITATION: we allow source columns to come from just
  #    one table, i.e. no combinations of two or more soure tables
  #    into the target table (but the code already is written to
  #    make this extension if required)
  if (keys %collect >1) {
    die "ERROR: Currently the program allows only for source".
      " columns all coming from the same table\n".
	Dumper(\%collect);
  }

  # exit with an error if target table contains a primary key column
  # but the column does not get mapped (IS THIS A VALID ASSUMPTION?)
  if (!$primkey_gets_mapped & exists $$primkeys_target{$tablename}) {
    die "There is no primary key among the mappings for\n".
      "target table: $tablename  primary key: $$primkeys_target{$tablename}\n".
	"mapped columns: ".$collect{$src_tablename}{"tgtcol"}."\n";
  }

  # compose the SQL command similar to
  # INSERT INTO $tablename (col1,col2) SELECT col1,col2 FROM $src_tablename
  foreach $src_tablename (keys %collect) {
    my $src_col;
    $collect{$src_tablename}{"srccol"}=~s/,$//; # remove terminal comma
    $collect{$src_tablename}{"tgtcol"}=~s/,$//; # remove terinal comma

    print DBFILE "#\nINSERT INTO $tablename (" .
      $collect{$src_tablename}{"tgtcol"} . ") SELECT " .
	$collect{$src_tablename}{"srccol"} .
	  " FROM $src_dbname.$src_tablename;\n";

  }
}



# USER FEEDBACK
# the following parts only serve for giving the user better feedback
# about the above mappings

# first construct an inverse hash (source_col_map) from target_col_map.
my %source_col_map;
foreach $src_tablename (keys %$db_source) {
  my $tablehash=$$db_source{$src_tablename};
  foreach $src_colname (keys %$tablehash) {
    $source_col_map{$src_tablename}{$src_colname}="";
  }
}

foreach $tablename (keys  %target_col_map) {
  my $tablehash=$target_col_map{$tablename};
  foreach $colname (keys %$tablehash) {
    if ( ($src_tablename,$src_colname)=
	 $target_col_map{$tablename}{$colname} =~ m/(.*)\.(.*)/) {
      $source_col_map{$src_tablename}{$src_colname} = "$tablename.$colname";
    }
  }
}



print OUTP "\n##########################################################\n";
print OUTP "#   TARGET TABLE COLUMNS with NO MAPPINGS TO the src db  #\n";
print OUTP "#\n";
foreach $tablename (sort keys %target_col_map) {

  # ignore target tables that user defined as being not mapped
  if(match_patterns("$tablename",\@nomap_target_table)) {next}

  my $tablehash=$target_col_map{$tablename};
  foreach $colname (sort keys %$tablehash) {
    if ($target_col_map{$tablename}{$colname} eq "") {
      # Only print if not excluded by user
      if (!match_patterns("$tablename.$colname",\@nomap_target_col)) {
	print OUTP "# nomap_target_col $tablename.$colname\n";
      }
    }
  }
}


print OUTP "\n###########################################################\n";
print OUTP "# SOURCE TABLE COLUMNS with NO MAPPINGS to the target db  #\n";
print OUTP "#\n";
foreach $src_tablename (keys %source_col_map) {
  my $tablehash=$source_col_map{$src_tablename};
    foreach $src_colname (keys %$tablehash) {
      if ($source_col_map{$src_tablename}{$src_colname} eq "") {
	if (!match_patterns("$src_tablename.$src_colname",
			    \@nomap_source_col)) {
	  print OUTP "# nomap_source_col $src_tablename.$src_colname\n";
      }
      }
  }
}


close(DBFILE);
close(OUTP);

