<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: download.php 4969 2005-11-15 10:32:43Z yeupou $
#
#  Copyright 2005-2006 (c) Mathieu Roy <yeupou--gnu.org>
#
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

register_globals_off();

# We need to do this step in a page separated from the export.php page because
# ARTIFACT must be set to task
$group_id = sane_all("group_id");
$group = sane_all("group_name");
$group_name = $group;

if (!$group_id)
{ exit_no_group(); }

$project=project_get_object($group_id);
    
if (!member_check(0, $group_id))
{
  exit_error(_("Data Export is currently restricted to projects members"));
}

$from = sane_get("from");
$export_id = sane_get("export_id");
$task_id = sane_get("task_id");

if (!$from || !$export_id || !$task_id)
{
  exit_missing_param();
}


$changes = array();
# Post a comment on the relevant task
$comment = "Job removed per request of his owner, ".user_getrealname()." 

".$export_id.".xml is no longer available";

trackers_data_add_history('details',
			  htmlspecialchars($comment),
			  '',
			  $task_id,
			  false,
			  'task');
$changes['details']['add'] = stripslashes($comment);
$changes['details']['type'] = '100';

# Harshly close the relevant task
$now = time();
$result = db_query("UPDATE task SET status_id='3',close_date='$now' WHERE bug_id='$task_id' LIMIT 1");  
$changes['status_id']['add'] = 'Closed';

# Send a mail notification
list($additional_address, $sendall) = trackers_data_get_item_notification_info($task_id, 'task', 0);
if ((trim($address) != "") && (trim($additional_address) != "")) 
{ $address .= ", "; }
$address .= $additional_address;
trackers_mail_followup($task_id, $address, $changes,false,'task');

session_redirect($GLOBALS['sys_home'].$from."/export.php?group=".rawurlencode($group)."&feedback=".rawurlencode(sprintf(_("Export job #%s deleted, task #%s closed"), $export_id, $task_id)));

?>