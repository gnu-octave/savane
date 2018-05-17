#! /usr/bin/perl
# Encrypt a message to specified savane user.
#
# Copyright (C) 2017, 2018 Ineiev <ineiev--gnu.org>
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

use strict;
use DBI;
use File::Temp qw(tempdir tempfile);
use Getopt::Long;
my $getopt;
my $help;
my $user;
my $sys_dbname;
my $sys_dbhost;
my $sys_dbuser;
my $sys_dbparams;
my $sys_dbpasswd;
my $exit_code = 0;

eval {
    $getopt = GetOptions("help" => \$help,
                         "user=s" => \$user,
                         "dbname=s" => \$sys_dbname,
                         "dbhost:s" => \$sys_dbhost,
                         "dbparams:s" => \$sys_dbparams);
};

sub print_help {
    print STDERR <<EOF;
Usage: $0 [OPTIONS]

Encrypt a message to user's registered GPG key.

  -h, --help            Display this help and exit
      --user            Savannah user to encrypt to
      --dbname          Savannah database name
      --dbhost          Savannah database host
      --dbparams        Savannah database parameters

Database user and password are passed in the first two lines of input.

EOF
}

if($help) {
    print_help();
    exit(0);
}

$sys_dbuser = <> or die "No database user is supplied.";
$sys_dbpasswd = <> or die "No database password is supplied.";

$sys_dbuser =~ s/\n$//;
$sys_dbpasswd =~ s/\n$//;

our $dbd = DBI->connect('DBI:mysql:database='.$sys_dbname
		       .':host='.$sys_dbhost
		       .$sys_dbparams,
                       $sys_dbuser, $sys_dbpasswd,
                       { RaiseError => 1, AutoCommit => 1});

## Encrypt to user GPG key if available
# arg1: user id
# arg2: message
# return encrypted message when encryption succeeded,
#        empty string encryption failed.
# Exit codes:
#   0 when encryption succeeded,
#   1 when it failed,
#   2 when no suitable key was found,
#   3 when key selection error occurred,
#   4 when creating temporary files failed,
#   5 when extracted key_id is invalid.
sub UserEncrypt {
    my ($user, $message) = @_;
    my $key = $dbd->selectrow_array("SELECT gpg_key FROM user WHERE user_id=".$user);

    $exit_code = 3;
    return "" unless $key ne "";

    $exit_code = 4;

    my ($mh, $mname) = tempfile(UNLINK => 1);
    return "" if $mname eq "";

    my $temp_dir = tempdir(CLEANUP => 1);
    return "" if $temp_dir eq "";

    my $input;
    my $key_id = "";
    my $msg = "";

    print $mh $message;

    $exit_code = 2;
    open($input, '|-', 'gpg --homedir='.$temp_dir.' --batch -q --import');
    print $input $key;
    close($input) or return "";

# Get the first ID of a public key with encryption capability.
    open($input, '-|', 'gpg --homedir='.$temp_dir.
                       ' --list-keys --with-colons 2> /dev/null');
    while(<$input>)
      {
        if(!/^pub/)
          {
            next;
          }
        my @fields = split /:/;
        if(@fields[11] !~ /[eE]/)
          {
            next;
          }
        $key_id = @fields[4];
        last unless $key_id eq "";
      }
    close($input) or return "";
    return "" unless $key_id ne "";
    $exit_code = 5;
    return "" unless $key_id =~ /^[0-9A-F]*$/;
    $exit_code = 1;
    open($input, '-|', 'gpg --homedir='.$temp_dir.
                       ' --trust-model always --batch -a --encrypt -r '
                       .$key_id." -o - ".$mname);
    while(<$input>)
      {
        $msg = $msg.$_;
      }
    close $input and $exit_code = 0;
    return $msg;
}

my $msg = "";

while(<>)
  {
    $msg = $msg.$_;
  }

print UserEncrypt($user, $msg);

exit $exit_code;
