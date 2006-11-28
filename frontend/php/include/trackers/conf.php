<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2004-2004 (c) Mathieu Roy <yeupou--at--gnu.org>
#                          Yves Perrin <yves.perrin--at--cern.ch>
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


# This page should store function related to trackers configuration
# (some of these are in general/data and should be moved here)


# Copy for a given tracker the configuration of the tracker of another
# project. This action is irreversible and can alter in an incoherent way
# already posted items: it is supposed to be mainly used to configure a
# new tracker. It can be used to keep several project using a coherent
# configuration but it should not be used a trackers will divergeant
# configuration already being used.
#
# To ease development, we ll make simple SQL query and we ll parse the
# result. We ll be doing dumb code, code that we ll be able to debug.
# (you need to be smarter than the code to be able to debug it, so lets avoid
# writing the smartest code, so we still have a chance)
function trackers_conf_copy ($group_id, $artifact, $from_group_id)
{
  if (!$artifact || !$group_id || !$from_group_id)
    {
      # case that should never happen
      fb(_("Missing parameters"), 1);
      return 0;
    }
  fb(sprintf(_("Start copying configuration of group #%s %s tracker"),
	     $from_group_id,
	     $artifact));

# Copy the notification settings
  $res_groups_from_group = db_query("SELECT * FROM groups WHERE group_id='$from_group_id'");
  $sql = "UPDATE groups SET ".
     "new_".$artifact."_address='".db_result($res_groups_from_group,
					     0,
					     "new_".$artifact."_address")."', ".
     $artifact."_glnotif='".db_result($res_groups_from_group,
				      0,
				      $artifact."_glnotif")."', ".
     "send_all_".$artifact."='".db_result($res_groups_from_group,
					  0,
					  "send_all_".$artifact)."', ".
     $artifact."_private_exclude_address='".db_result($res_groups_from_group,
						       0,
						       $artifact."_private_excluded_address")."' ".
     "WHERE group_id='$group_id'";

  if (db_affected_rows(db_query($sql)))
    {  fb(_("Notification settings copied")); }

  # Delete currently set field usage and field values
  # Copy the field usage and field values of the other project
  $sql = "DELETE FROM ".$artifact."_field_value WHERE group_id='$group_id'";
  if (db_affected_rows(db_query($sql)))
    {  fb(_("Previous field values deleted")); }
  $sql = "DELETE FROM ".$artifact."_field_usage WHERE group_id='$group_id'";
  if (db_affected_rows(db_query($sql)))
    {  fb(_("Previous field usage deleted")); }

  $result_field_usage_from_group = db_query("SELECT * FROM ".$artifact."_field_usage WHERE group_id='$from_group_id'");
  $z = 0;
  unset($itemsdone);
  while ($thisone = db_fetch_array($result_field_usage_from_group))
      {

	$sql = db_createinsertinto($result_field_usage_from_group,
				   $artifact."_field_usage",
				   $z,
				   "none",
				   "group_id",
				   $group_id);

        if (db_affected_rows(db_query($sql)))
	  {  $itemsdone .= "#".$thisone['bug_field_id']." "; }

	$z++;
      }
  if ($itemsdone)
    { fb(sprintf(_("Field values %s copied"), $itemsdone)); }


  $result_field_value_from_group = db_query("SELECT * FROM ".$artifact."_field_value WHERE group_id='$from_group_id'");
  $z = 0;
  unset($itemsdone);
  while ($thisone = db_fetch_array($result_field_value_from_group))
      {

	$sql = db_createinsertinto($result_field_value_from_group,
				   $artifact."_field_value",
				   $z,
				   "bug_fv_id",
				   "group_id",
				   $group_id);

        if (db_affected_rows(db_query($sql)))
	  {  $itemsdone .= "#".$thisone['bug_fv_id']." "; }

	$z++;
      }
  if ($itemsdone)
    { fb(sprintf(_("Field values %s copied"), $itemsdone)); }


  # Delete currently set canned responses
  # Copy the canned responses of the other project
  $sql = "DELETE FROM ".$artifact."_canned_responses WHERE group_id='$group_id'";
  if (db_affected_rows(db_query($sql)))
    {  fb(_("Previous canned responses deleted")); }

  $result_canned_from_group = db_query("SELECT * FROM ".$artifact."_canned_responses WHERE group_id='$from_group_id'");
  $z = 0;
  unset($itemsdone);
  while ($thisone = db_fetch_array($result_canned_from_group))
      {

	$sql = db_createinsertinto($result_canned_from_group,
				   $artifact."_canned_responses",
				   $z,
				   "bug_canned_id",
				   "group_id",
				   $group_id);

        if (db_affected_rows(db_query($sql)))
	  {  $itemsdone .= "#".$thisone['bug_canned_id']." "; }

	$z++;
      }
  if ($itemsdone)
    { fb(sprintf(_("Canned responses %s copied"), $itemsdone)); }

  # Delete currently set query forms
  # Copy the query forms of the other project
  $res_queryforms = db_query("SELECT * FROM ".$artifact."_report WHERE group_id='$group_id'");
  $sql = "DELETE FROM ".$artifact."_report WHERE group_id='$group_id'";
  if (db_affected_rows(db_query($sql)))
    {  fb(_("Previous query forms deleted")); }
  while ($thisone = db_fetch_array($res_queryforms))
    {
      # Not verbose
      db_query("DELETE FROM ".$artifact."_report_field WHERE report_id='".$thisone['report_id']."'");
    }

  $result_queryforms_from_group = db_query("SELECT * FROM ".$artifact."_report WHERE group_id='$from_group_id'");
  $z = 0;
  unset($itemsdone);
  while ($thisone = db_fetch_array($result_queryforms_from_group))
      {
	# Copy the report
	$sql = db_createinsertinto($result_queryforms_from_group,
				   $artifact."_report",
				   $z,
				   "report_id",
				   "group_id",
				   $group_id);
	$thisone_id = db_insertid(db_query($sql));
        if ($thisone_id)
	  {
	    $itemsdone .= "#".$thisone['report_id']." ";

            # Copy the info related to the report in report_field
	    $result_thisqueryforms_from_group = db_query("SELECT * FROM ".$artifact."_report_field WHERE report_id='".$thisone['report_id']."'");
	    $y = 0;
	    while ($thisonequery = db_fetch_array($result_thisqueryforms_from_group))
	      {
		$sql = db_createinsertinto($result_thisqueryforms_from_group,
					   $artifact."_report_field",
					   $y,
					   "none",
					   "report_id",
					   $thisone_id);

		# Silent: if we list even these insert, the feedback will
		# be unreadable, too long
		db_query($sql);

		$y++;
	      }

	  }

	$z++;
      }
  if ($itemsdone)
    { fb(sprintf(_("Query forms %s copied"), $itemsdone)); }


  # Delete current set transitions
  # Copy the transition of the other project
  $res_transitions= db_query("SELECT * FROM trackers_field_transition WHERE group_id='$group_id' AND artifact='$artifact'");
  $sql = "DELETE FROM trackers_field_transition WHERE group_id='$group_id' AND artifact='$artifact'";
  if (db_affected_rows(db_query($sql)))
    {  fb(_("Previous field transitions deleted")); }
  while ($thisone = db_fetch_array($res_transitions))
    {
      # Not verbose
      db_query("DELETE FROM trackers_field_transition_other_field_update WHERE transition_id='".$thisone['transition_id']."'");
    }

  $result_transitions_from_group = db_query("SELECT * FROM trackers_field_transition WHERE artifcat='$artifact' AND group_id='$from_group_id'");
  $z = 0;
  unset($itemsdone);
  while ($thisone = db_fetch_array($result_transitions_from_group))
      {
	# Copy the report
	$sql = db_createinsertinto($result_transitions_from_group,
				   "trackers_field_transition",
				   $z,
				   "transition_id",
				   "group_id",
				   $group_id);
	$thisone_id = db_insertid(db_query($sql));
        if ($thisone_id)
	  {
	    $itemsdone .= "#".$thisone['transition_id']." ";

            # Copy the info related to the report in report_field
	    $result_thistransitions_from_group = db_query("SELECT * FROM trackers_field_transition_other_field_update  WHERE transition_id='".$thisone['transition_id']."'");
	    $y = 0;
	    while ($thisonequery = db_fetch_array($result_thistransitions_from_group))
	      {
		$sql = db_createinsertinto($result_thistransitions_from_group,
					   "trackers_field_transition_other_field_update",
					   $y,
					   "other_field_update_id",
					   "report_id",
					   $thisone_id);

		# Silent: if we list even these insert, the feedback will
		# be unreadable, too long
		db_query($sql);

		$y++;
	      }

	  }

	$z++;
      }
  if ($itemsdone)
    { fb(sprintf(_("Transitions %s copied"), $itemsdone)); }



  fb(_("Configuration copy finished"));

}



function conf_form ($group_id, $artifact)
{

  $result = db_query("SELECT groups.group_name,"
		     . "groups.group_id "
		     . "FROM groups,user_group "
		     . "WHERE groups.group_id=user_group.group_id "
		     . "AND user_group.user_id='". user_getid() ."' "
		     . "AND groups.status='A' "
		     . "AND groups.use_".$artifact."='1'");

  $vals = array();
  $texts = array();
  unset($found);
  while ($thisgroup = db_fetch_array($result))
    {
      $vals[] = $thisgroup['group_id'];
      $texts[] = $thisgroup['group_name'];
      $found = 1;
    }


  if ($found)
    {
      print '<p>'.sprintf(_("You can copy the configuration of the %s tracker of the following projects (this list was established according to your currently membership record)."), $artifact).'</p>
<p class="warn">'._("Beware, your current configuration will be irremediably lost.").'</p>

<form action="'.$PHP_SELF.'" method="post">
<input type="hidden" name="group_id" value="'.$group_id.'" />
<input type="hidden" name="artifact" value="'.$artifact.'" />
<span class="preinput">'._("Projects:").'</span><br />
&nbsp;&nbsp;&nbsp;
';

      print html_build_select_box_from_arrays($vals, $texts,'from_group_id');

      print form_footer();

    }
  else
    {
      print '<p>'.sprintf(_("You cannot copy the configuration of other projects because you are not member of any project hosted here that uses a %s tracker"), $artifact).'</p>';
    }

}

?>