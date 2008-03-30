<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 2005-2006 (c) Mathieu Roy <yeupou--gnu.org>
#
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


register_globals_off();

# We need to do this step in a page separated from the export.php page because
# ARTIFACT must be set to task

if (!$group_id)
{ exit_no_group(); }

$project=project_get_object($group_id);
    
if (!member_check(0, $group_id))
{
  exit_error(_("Data Export is currently restricted to projects members"));
}

extract(sane_import('get', array('from', 'export_id', 'task_id')));

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
$changes['details']['add'] = $comment;
$changes['details']['type'] = '100';

# Harshly close the relevant task
$now = time();
$result = db_execute("UPDATE task SET status_id='3',close_date=? WHERE bug_id=? LIMIT 1", array($now, $task_id));
$changes['status_id']['add'] = 'Closed';

# Send a mail notification
list($additional_address, $sendall) = trackers_data_get_item_notification_info($task_id, 'task', 0);
$address = '';
if ((trim($address) != "") && (trim($additional_address) != "")) 
{ $address .= ", "; }
$address .= $additional_address;
trackers_mail_followup($task_id, $address, $changes,false,'task');

session_redirect($GLOBALS['sys_home'].$from."/export.php?group=".rawurlencode($group)."&feedback=".rawurlencode(sprintf(_("Export job #%s deleted, task #%s closed"), $export_id, $task_id)));
