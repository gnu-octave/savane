#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: sv_cleaner.pl 5975 2006-09-26 09:12:56Z yeupou $
#
#  Copyright 2006      (c) Mathieu Roy <yeupou--gnu.org> 
#
# The Savane project is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# The Savane project is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


use strict;
use Savane;
use Getopt::Long;
use Term::ANSIColor qw(:constants);
use POSIX qw(strftime);
use Time::Local;

our $dbd;
my $script = "sv_extra_merge_projects";
my $getopt;
my $help;
my $debug;
my $source_group;
my $destination_group;
my $category;
my $version = GetVersion();
my @trackers = ("cookbook", "support", "bugs", "task", "patch");

# get options
eval {
    $getopt = GetOptions("help" => \$help,
			 "source=s" => \$source_group,
			 "destination=s" => \$destination_group,
			 "to-category=s" => \$category,
			 "debug" => \$debug);
};


if ($help or !$source_group or !$destination_group) {
    print STDERR <<EOF;
Usage: $0 --source unixname --dest unixname [OPTIONS] 
 
This script can be used to merge two groups. It will reassign all items
from the "source group" to the "destination group". It will also add all
the users from the "source group" to the "destination group", preserving
their admin flag.

It wont delete the "source group", just in case it is still needed. But it
is the "destination group" that will be the result of the merge, that will
be the project having all the items.

If you want to merge several groups, you will have to run this several 
times, always using the group that is supposed to remain 
as "destination group"


  -h, --help                   Show this help and exit
      --source=unixname        The project of which items should be taken 
                               from.
			       This is the one that will be left empty.
      --destination=unixname   The project that will get all the items.
                               This is the one that will be the result
			       of the merge.

      --to-category="Cat"     All the items from the source group will
                               be associated to the given category of the
			       destination group.
			       It wont create the category on the trackers,
			       this must be done before.
			       Warning:
			       This instruction will be ignored if the
			       category is not found on the current tracker.
			       
      --debug                  Say what it would do but do nothing at all.

WARNING: this script as only been tested lightly. Use debug mode first to 
check what it is about to do.
It does not log what it does. You may want to first run it in debug mode
redirecting STDOUT in a text file.

WARNING2: this script currently DOES NOT handle custom field. 

Savane version: $version
EOF
exit(1);
}


print strftime "[$script] %c - starting\n", localtime;


###********************************************************************
###********************************************************************
###
### CHECKS IF EVERYTHING IS IN ORDER
###
###********************************************************************
###********************************************************************

# Obtain and check the group ids
my $source_group_id =  GetGroupSettings($source_group, "group_id");
my $destination_group_id =  GetGroupSettings($destination_group, "group_id");
 
die "Unable to determine group_id of $source_group. Exiting"
    unless $source_group_id;
print "Identified source $source_group as group \#$source_group_id\n";

die "Unable to determine group_id of $destination_group. Exiting"
    unless $destination_group_id;
print "Identified destination $destination_group as group \#$destination_group_id\n";  

   
# If a category was given as argument, try to find the relevant value id
# for each tracker.
# If the category was not found at least once, assume there is an error and
# exit.
my %destination_category;
if ($category) {
    my $found_at_least_one;
    foreach my $tracker (@trackers) {
	# Category is always bug_field_id=103
	my ($value_id) = GetDBSettings($tracker."_field_value", "group_id='$destination_group_id' AND bug_field_id='103' AND value=".$dbd->quote($category), "value_id");
	chomp($value_id);

	if ($value_id) {
	    $destination_category{$tracker} = $value_id;
	    print "Identified destination $tracker category \"$category\" as category \#$value_id\n"; 
	    $found_at_least_one = 1;
	} else {
	    print "Unable to determine destination $tracker category \"$category\"\n"; 
	}
    }
    
    die "Unable to find at least one tracker with the category \"$category\". Exiting"
	unless $found_at_least_one;
}


###********************************************************************
###********************************************************************
###
### ADD USERS 
###
### Here, we want to add all users of the source group to the destination
### one.
### We want to keep their admin flag of their original group, but we will
### loose less important flags if they are already members of the destination
### project, because we can assume that this destination project matters more
### and have the settings that should override the others regarding settings
### of secondary importance.
###
###********************************************************************
###********************************************************************


# Build a hash with the users of the source group
print "\nIdentify users of $source_group:\n";
my %source_users;
foreach my $line (GetDB("user_group", 
			"group_id='$source_group_id'",
			"user_id,admin_flags,privacy_flags,bugs_flags,task_flags,patch_flags,support_flags,cookbook_flags,news_flags")) {

    chomp($line);
    my ($user_id, $admin_flags, $privacy_flags) = split(",", $line);
    $admin_flags = 0 unless $admin_flags;
    
    print "\tuser $user_id (admin: $admin_flags, privacy: $privacy_flags)\n";
    
    $source_users{$user_id} = $line;
}

# Now add users or update their settings
print "\nAdd users of $source_group to $destination_group:\n";
while (my ($user_id,$line) = each(%source_users)) {
    my ($ignore, $admin_flags, $privacy_flags, $bugs_flags, $task_flags, $patch_flags, $support_flags, $cookbook_flags, $news_flags) = split(",", $line);

    my ($already_member, $destination_admin_flags, $destination_privacy_flags)
	= GetDBSettings("user_group", "group_id='$destination_group_id' AND user_id='$user_id'", "user_id,admin_flags,privacy_flags");

    # Look if this user is already associated to the group
    if ($already_member){
	$destination_admin_flags = 0 unless $destination_admin_flags;
	
	print "\tuser $user_id already belongs to $destination_group (admin: $destination_admin_flags, privacy: $destination_privacy_flags)\n";
	
	# If admin on the source project, make admin on the destination
	# project.
	# Otherwise, assume that the destination project admin flags is valid
	# enough.
	if ($admin_flags eq 'A' && $destination_admin_flags ne 'A')	{
	    SetDBSettings("user_group",
			  "group_id='$destination_group_id' AND user_id='$user_id'",
			  "admin_flags='A'")
		unless $debug;
	    print "\t\t- set as admin of $destination_group\n";
	}

	# If able to read private items on the source project, 
	# enable to do so on the destination  project.
	# Otherwise, assume that the destination project privacy flags is valid
	# enough.
	if ($privacy_flags && !$destination_privacy_flags) {
	    SetDBSettings("user_group",
			  "group_id='$destination_group_id' AND user_id='$user_id'",
			  "privacy_flags='1'")
		unless $debug;	    
	    print "\t\t- enable to read private items of $destination_group\n";
	}


	# We do not update other trackers roles. Settings may well be at
	# conflict and we have no way to determine what would be the
	# appropriate ones (unless admin and private items, there are 
	# of less obvious effect)
	# Indeed, it means that some may end up not being a technician on 
	# a tracker and still be assigned some items of this tracker because
	# of the past situation. Well, it is harmless.
    }
    else
    {
	InsertDB("user_group", 
		 "user_id,group_id,admin_flags,privacy_flags,bugs_flags,task_flags,patch_flags,support_flags,cookbook_flags,news_flags",
		 "'$user_id', '$destination_group_id', '$admin_flags', '$privacy_flags', '$bugs_flags', '$task_flags', '$patch_flags', '$support_flags', '$cookbook_flags', '$news_flags'")
	    unless $debug;
	print "\tuser $user_id now added to $destination_group\n";
    }

}


###********************************************************************
###********************************************************************
###
### COPY GROUP HISTORY 
###
### It is best of the old group to keep it's history, just in case.
###
###********************************************************************
###********************************************************************

print "\nCopy history of $source_group:\n";
foreach my $line (GetDB("group_history", 
			"group_id='$source_group_id'",
			"field_name,old_value,mod_by,date")) {
    
    chomp($line);
    my ($field_name, $old_value, $mod_by, $date) = split(",", $line);

    # Check if this exact entry was not already registered 
    # (case where a project was merged twice... by mistake of intentionally)
    unless (GetDBSettings("group_history", "group_id='$destination_group_id' AND field_name='$field_name' AND old_value='$old_value' AND mod_by='$mod_by' AND date='$date'", "group_history_id")) {
	
	# Directly do new inserts
	InsertDB("group_history", 
		 "group_id,field_name,old_value,mod_by,date",
		 $dbd->quote($destination_group_id).', '.
		 $dbd->quote($field_name).', '.
		 $dbd->quote($old_value).', '.
		 $dbd->quote($mod_by).', '.
		 $dbd->quote($date))
	    unless $debug;
	print "\tcopy \"$field_name\" \"$old_value\"\n";    

    }
    else
    {
	print "\tignore \"$field_name\" \"$old_value\" (already in)\n";
    }
}


###********************************************************************
###********************************************************************
###
### COPY TRACKERS SPECIAL FIELDS
###
### Easy solution to copy items is to do it without taking care of 
### project specific field values.
### But it would be a real nightmare to use this script to merge two project
### that have conflicting specific fields values: most items would have
### erroneous values.
###
### Here, we will check if there is diverging fields values. If so
### we will register this, we will add new fields values in hidden mode
### (cannot be selected by correctly viewed on the items) for the values
### of the source group that are missing from the destination group.
###
### We wont make overcomplicated things like trying to avoid duplicate field
### values. If in the two project, there is a different field value id for
### the same field, they will have to deal with that. We wont make assumptions.
### It wont be a big deal for them anyway, as the duplicated field value will
### be in hidden mode.
###
###********************************************************************
###********************************************************************

# FIXME: what about the custom fields?

print "\nUpdate trackers field values:\n";
foreach my $tracker (@trackers) {
    # We first have to determine if a copy is necessary:
    # It is the case if at least one of the project have specific field values.
    # If it only the destination one that have specific field values, we have
    # to copy the standard field values.
    my $source_have_specific_field_values =
	GetDB($tracker."_field_value", 
	      "group_id='$source_group_id'",
	      "bug_fv_id");
    print "\tfound non-standard values for $source_group $tracker tracker\n"
	if $source_have_specific_field_values;

    my $destination_have_specific_field_values =
	GetDB($tracker."_field_value", 
	      "group_id='$destination_group_id'",
	      "bug_fv_id");    
    print "\tfound non-standard values for $destination_group $tracker tracker\n"
	if $destination_have_specific_field_values;

    # Skip the tracker if nothing non-standard was found
    next unless 
	$source_have_specific_field_values or $destination_have_specific_field_values;

    # Now slurps all the relevant field values: default, source group
    # destination group
    my %source_field_values;
    if ($source_have_specific_field_values) {
	%source_field_values = GetDBHash($tracker."_field_value",
					 "group_id='$source_group_id'", 
					 "bug_field_id,value_id,value,description,order_id");
    } 
    else 
    {
	%source_field_values = GetDBHash($tracker."_field_value",
					 "group_id='100'", 
					 "bug_field_id,value_id,value,description,order_id");	
    }

    my %destination_field_values;
    if ($destination_have_specific_field_values) {
        %destination_field_values = GetDBHash($tracker."_field_value",
					      "group_id='$destination_group_id'", 
					      "bug_field_id,value_id,value");
    }
    else
    {
	%destination_field_values = GetDBHash($tracker."_field_value",
					      "group_id='100'", 
					      "bug_field_id,value_id,value");
    }

    
    # Now build hashes that will enable us to do comparisons
    my %destination_field_values_data;
    while (my ($key, $value) = each(%destination_field_values)) {
	my ($bug_field_id, $value_id, $real_value) = (@{$value});
	$destination_field_values_data{$bug_field_id."-".$value_id} = $real_value;	
    }


    # No start to compare and insert in the database a new field if necessary
    # Remind for later if we have do change a some field value
    my %new_value_id;
    while (my ($key, $value) = each(%source_field_values)) {
	my ($bug_field_id, $value_id, $real_value, $description, $order_id) = (@{$value});

	my $destination_real_value = $destination_field_values_data{$bug_field_id."-".$value_id};
	
	# Ignore if found and similar
	next if $real_value eq $destination_real_value;

	# Identify the field we are working on
	my $field_name = GetDBSettings($tracker."_field", 
				       "bug_field_id='$bug_field_id'", 
				       "field_name");

	# If the current field is category id and that we are asked to 
	# reassign items in a specific category, we wont copy categories
	next if $field_name eq 'category_id' &&
	    $destination_category{$tracker};	    

	# Define a safe new_value_id, if needed
	$new_value_id{$field_name} = GetDBSettings($tracker."_field_value",
						   "bug_field_id='$bug_field_id' AND group_id='$destination_group_id'",
						   "max(value_id) as max")
	    unless $new_value_id{$field_name};
	$new_value_id{$field_name} = 250 if $new_value_id{$field_name} < 250;


	if (!$destination_real_value) {
	    # No destination real value: we can do a simple copy

	    # Insert as hidden field value
	    InsertDB($tracker."_field_value", 
		     "group_id,bug_field_id,value_id,value,description,order_id,status",
		     $dbd->quote($destination_group_id).', '.
		     $dbd->quote($bug_field_id).', '.
		     $dbd->quote($value_id).', '.
		     $dbd->quote($real_value).', '.
		     $dbd->quote($description." (from group $source_group)").', '.
		     $dbd->quote($order_id).", 'H'")
		unless $debug;
	    print "\t\tadd \"$real_value\" for $field_name:$value_id\n";
	}
	else
	{
	    # If there is a destination_real_value that does not match, 
	    # it means
	    # that we have to copy the field value giving it a new value_id
	    $new_value_id{$field_name}++;
	    

	    # Insert as hidden field value
	    print "\t\tadd \"$real_value\" for $field_name:$value_id as $field_name:$new_value_id{$field_name}\n";
	    InsertDB($tracker."_field_value", 
		     "group_id,bug_field_id,value_id,value,description,order_id,status",
		     $dbd->quote($destination_group_id).', '.
		     $dbd->quote($bug_field_id).', '.
		     $dbd->quote($new_value_id{$field_name}).', '.
		     $dbd->quote($real_value).', '.
		     $dbd->quote($description." (from group $source_group)").', '.
		     $dbd->quote($order_id).", 'H'")
		unless $debug;

	    # Update the original one so if the script is run more than once
	    # we wont recreate over an over this field value
	    # (this make important that all the items that have this field
	    # value get updated properly)
	    print "\t\t\tupdate $source_group $field_name:$value_id to $field_name:$new_value_id{$field_name} \n";
	    SetDBSettings($tracker."_field_value",
			  "group_id='$source_group_id' AND bug_field_id='$bug_field_id' AND value_id='$value_id'",
			  "value_id='".$new_value_id{$field_name}."'")
		unless $debug;

	    # Update all the items of source group accordingly
	    print "\t\t\tupdate $source_group items $field_name accordingly\n";
	    SetDBSettings($tracker,
			  "group_id='$source_group_id' AND $field_name='$value_id'",
			  "$field_name='".$new_value_id{$field_name}."'")
		unless $debug;
	}
    }
}


# If specific  category assignement was required, do it now
# (yes this could have done before, but keeping this apart avoid to
# overcomplicate the code)
if ($category) {
    print "\nUpdate trackers category values:\n";
    
    foreach my $tracker (@trackers) {
	if ($destination_category{$tracker}) {
	    print "\tupdate $tracker to destination category \"$category\" \#$destination_category{$tracker} as category\n";
	    SetDBSettings($tracker,
			  "group_id='$source_group_id'",
			  "category_id='".$destination_category{$tracker}."'")
		unless $debug;

	}
    }
    
}


###********************************************************************
###********************************************************************
###
### REASSIGN ITEMS
###
### Now reassign all the things that make sense to move from one
### project to another that does not requires much thoughts 
###
###********************************************************************
###********************************************************************

print "\n";

foreach my $tracker (@trackers) {
    print "Update $tracker items\n";
    SetDBSettings($tracker,
		  "group_id='$source_group_id'",
		  "group_id='$destination_group_id'")
	unless $debug;

    print "Update $tracker query forms\n";
    SetDBSettings($tracker."_report",
		  "group_id='$source_group_id'",
		  "group_id='$destination_group_id'")
	unless $debug;

}


print "Update cookbook context <-> recipe\n";
SetDBSettings("cookbook_context2recipe",
	      "group_id='$source_group_id'",
	      "group_id='$destination_group_id'")
    unless $debug;


print "Update news items\n";
SetDBSettings("news_bytes",
	      "group_id='$source_group_id'",
	      "group_id='$destination_group_id'")
    unless $debug;



# Final exit
print strftime "[$script] %c - work finished\n", localtime;

# EOF
