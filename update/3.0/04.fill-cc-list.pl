#!/usr/bin/perl
#
# Copyright (C) 2006  Mathieu Roy
#
# This file is part of Savane.
# 
# Savane is free software; you can redistribute it and/or modify it
# under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Savane; if not, write to the Free Software Foundation,
# Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


# Add in CC list all the users addresses that would have been included in
# the notification with the previous mechanism.

use strict;
use Savane;
use Getopt::Long;

our $dbd;
my $debug;
my $getopt;

eval {
    $getopt = GetOptions("debug" => \$debug);
};

### Follows the PHP code of the previous notification mechanism.
###
# # # function trackers_build_notification_list($item_id, $group_id, $changes)
# # # {
# # #
# # #   $sql="SELECT assigned_to, submitted_by from ".ARTIFACT." WHERE bug_id='$item_id'";
# # #   $res_as=db_query($sql);
# # #
# # #   # Rk: we store email addresses in a hash to make sure they are only
# # #   # stored once. Normally if an email is repeated several times sendmail
# # #   # would take care of it but I prefer taking care of it now.
# # #   # Same for user ids.
# # #   # We also use the user_ids hash to check if a user has already been selected for
# # #   # notification. If so it is not necessary to check it again in another role.
# # #   $addresses = array();
# # #   $user_ids = array();
# # #
# # #   # check submitter notification preferences
# # #   $user_id = db_result($res_as,0,'submitted_by');
# # #   if ($user_id != 100)
# # #     {
# # #       if (trackers_check_notification($user_id, 'SUBMITTER', $changes))
# # #     {
# # #       $user_ids[$user_id] = true;
# # #     }
# # #     }
# # #
# # #   # check assignee  notification preferences
# # #   $user_id = db_result($res_as,0,'assigned_to');
# # #   if ($user_id != 100)
# # #     {
# # #       if (!$user_ids[$user_id] && trackers_check_notification($user_id, 'ASSIGNEE', $changes))
# # #     {
# # #       $user_ids[$user_id] = true;
# # #     }
# # #     }
# # #
# # #   # check old assignee  notification preferences if assignee was just changed
# # #   $user_name = $changes['assigned_to']['del'];
# # #   if ($user_name)
# # #     {
# # #       $res_oa = user_get_result_set_from_unix($user_name);
# # #       $user_id = db_result($res_oa,0,'user_id');
# # #       if ($user_id != 100 && !$user_ids[$user_id] && trackers_check_notification($user_id, 'ASSIGNEE', $changes))
# # #     {
# # #       $user_ids[$user_id] = true;
# # #     }
# # #     }
# # #
# # #   # check all CC
# # #   # a) check all the people in the current CC list
# # #   # b) check the CC that has just been removed if any and see if she
# # #   # wants to be notified as well
# # #   # if the CC indentifier is an email address then notify in any case
# # #   # because this user has no personal setting
# # #   $res_cc = trackers_data_get_cc_list($item_id);
# # #   $arr_cc = array();
# # #   if ($res_cc && (db_numrows($res_cc) > 0))
# # #     {
# # #       while ($row = db_fetch_array($res_cc))
# # #     {
# # #       $arr_cc[] = $row['email'];
# # #     }
# # #     }
# # #   # Only one CC can be deleted at once so just append it to the list....
# # #   $arr_cc[] = $changes['CC']['del'];
# # #
# # #   while (list(,$cc) = each($arr_cc))
# # #     {
# # #       # Remove extra white spaces
# # #       $cc = trim($cc);
# # #
# # #       # The CC may have been added in the form like:
# # #       #    THIS NAME <this@address.net>
# # #       # So the validation check must be made only on the part in < >, if 
# # #       # it exists
# # #       unset($realaddress);
# # #       if (preg_match("/\<([\w\d\-\@\.]*)\>/", $cc, $realaddress))
# # #     { $realaddress = $realaddress[1]; }
# # #       else
# # #     { $realaddress = $cc; }
# # #
# # #       if (validate_email($realaddress))
# # #     {
# # #       # We have an address like ablab@adad.net
# # # 
# # #       # FIXME: Should get back the THIS NAME info is existant
# # #       # but sendmail_mail() is not yet capable of handling this
# # #       # so for now, we simply ignore the real address.
# # #       # Yes, that is a bit annoying, as we implicitely allow users to
# # #       # add names that will not be later reused.
# # #       # But it is not blocker and we could implement it later, if 
# # #       # requested
# # #       ## $extrainfo = str_replace("<$realaddress>", "", $cc);
# # #
# # #       $addresses[utils_normalize_email($realaddress)] = true;
# # #     }
# # #       else
# # #     {
# # #       # We have an address like ablab
# # #
# # #       $res = user_get_result_set_from_unix($realaddress);
# # #       $user_id = db_result($res,0,'user_id');
# # #      
# # #       # If it as real user, check settings
# # #       if ($user_id)
# # #         {
# # #           if (!$user_ids[$user_id] && trackers_check_notification($user_id, 'CC', $changes))
# # #         {
# # #           $user_ids[$user_id] = true;
# # #         }
# # #           continue;
# # #         }
# # #
# # #       # Otherwise, try to assume what savane would do 
# # #       # (append @maildomain) and check if it is somehow valid.
# # #       # If not, completly ignore
# # #       $normalized = utils_normalize_email($realaddress);
# # #       if (validate_email($normalized))
# # #         { 
# # #           $addresses[$normalized] = true;
# # #         }      
# # #     }
# # #     } 
# # #
# # #
# # #   # check all commenters
# # #   $res_com = trackers_data_get_commenters($item_id);
# # #   if (db_numrows($res_com) > 0)
# # #     {
# # #       while ($row = db_fetch_array($res_com))
# # #     {
# # #       $user_id = $row['mod_by'];
# # #           if ($user_id != 100)
# # #             {
# # #               if (!$user_ids[$user_id] && trackers_check_notification($user_id, 'COMMENTER', $changes))
# # #             {
# # #               $user_ids[$user_id] = true;
# # #             }
# # #             }
# # #     }
# # #     }
# # #
# # #   # build the final list of email addresses
# # #   reset($user_ids);
# # #   while (list($user_id,) = each($user_ids))
# # #     {
# # #       if ($user_id)
# # #     {
# # #           # Dirty hack: for a reason need to be cleared out,
# # #       # a user_id = 0 arrived here.
# # #           # Must not define email address so soon. Just passing user_id
# # #       $addresses[$user_id] = true;
# # #     }
# # #     }
# # #
# # #   # return an array with all the email addresses the notification must be sent to
# # #   return (array_keys($addresses));
# # #
# # # }


# First get a list of valid users: we wont add in CC guys that no longer exists
my %valid_users;
foreach my $user (GetDBLists("user", "status='A'", "user_id")) {
    ($user) = @$user;
    $valid_users{$user} = 1;

}


my @trackers = ("bugs", "task", "patch", "support", "cookbook");

foreach my $tracker (@trackers) {

    # Go through the items list
    foreach my $line (GetDBLists($tracker, "status_id<>'100' ORDER BY bug_id", "bug_id, submitted_by, date")) {
	my ($item_id, $submitted_by, $date) = @$line;
	
	# Store a list of already registered id. Duplicates are not a big
	# deal because the frontend will deal with them properly. But
	# we should avoid them whenever possible
	my %seen_before;

	# First, add the submitter in CC, if not anonymous
	unless ($submitted_by eq '100' || !$valid_users{$submitted_by}) {
	    InsertDB($tracker."_cc", 
		     "bug_id, email, added_by, comment, date",
		     "'$item_id', '$submitted_by', '$submitted_by', ".$dbd->quote("-SUB-").", '$date'") 
		unless $debug;
	    $seen_before{$submitted_by} = 1;
	    print "Add user \#$submitted_by (-SUB-) to $tracker \#$item_id\n";
	}	

	# Add in CC anyone that posted a made an action that affected history
	foreach my $history (GetDBLists($tracker."_history", "bug_id='$item_id' AND mod_by<>'100' GROUP BY mod_by", "mod_by,date,type")) {
	    my ($mod_by, $mod_date, $type) = @$history;

	    next if $seen_before{$mod_by};
	    next unless $valid_users{$mod_by};

	    my $comment = '-UPD-';
	    $comment = '-COM-' if $type eq '100';

	    InsertDB($tracker."_cc", 
		     "bug_id, email, added_by, comment, date",
		     "'$item_id', '$mod_by', '$mod_by', ".$dbd->quote($comment).", '$mod_date'") 
		unless $debug;
	    print "Add user \#$mod_by ($comment) to $tracker \#$item_id\n";
	    $seen_before{$mod_by} = 1;	    
	}	
    }
}

print "Dont run this script twice!\n";

# EOF
