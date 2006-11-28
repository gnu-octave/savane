use strict;
use Savane;
use String::Random qw(random_string);
use POSIX qw(strftime);
our $dbd;
my $group_id;
my $timestamp;

################## CONFIG

$group_id = '103';
# $timestamp = '1133253163'; # old date (2005)

                  

################## RUN

# this script will produce plenty of items on the group 101. This is useful
# to debug/test a developer installation
my $count;
$timestamp = time unless $timestamp;
my $timeinfo = strftime("%H:%m", localtime());
foreach ($count=1; $count<1250; $count++) {
    for (("bugs", "task", "support")) {
	my $item = "INSERT INTO $_ (group_id, status_id , severity , privacy , category_id , submitted_by , assigned_to , date , summary , details) VALUES ('$group_id', '1', '5', '1', '100', '100', '100', '$timestamp', ".$dbd->quote("Sample $count, $timeinfo ".random_string("ssssssss")).", ".$dbd->quote("BLA BLA BLA").")";
	
	my $sthinsert=$dbd->prepare($item);
	$sthinsert->execute;
    }
}

# OEF

