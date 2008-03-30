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
extract(sane_import('get', array('from', 'export_id')));

if (!$group_id)
{ exit_no_group(); }

$project=project_get_object($group_id);
    
if (!member_check(0, $group_id))
{
  exit_error(_("Data Export is currently restricted to projects members"));
}

if (!$from || !$export_id)
{
  exit_missing_param();
}

$result = db_execute("SELECT * FROM trackers_export WHERE export_id=? LIMIT 1", array($export_id));
if (db_numrows($result) < 1)
{
  # No such export id or not in 'I' status
  exit_error(_("This export job cannot be modified"));  
}
$timestamp = db_result($result, 0, "date");
$requested_hour = db_result($result, 0, "frequency_hour");
$requested_day = db_result($result, 0, "frequency_day");

trackers_init($group_id);
$vfl = array();
# For now, hardcode the export directory to domain/export.
# It is easy to set up a redirection from there to some other server
# so we will avoid for now adding plenty of configuration options.
# We put it in a directory for each group and user, so it is lighter for
# the filesystem and it will allow us to implement .htaccess restrictions
# on some directories
$export_url = $GLOBALS['sys_https_url'].$GLOBALS['sys_home']."export/$group/".user_getname()."/".$export_id.".xml";

$vfl['summary'] = 'Data Export #'.$export_id.' ('.$from.')';

# FIXME: Job details is currently not shown in a user friendly way
$vfl['details'] = 'A new export job has been registered.
 
This task has been created to keep the project informed. However, only '.user_getname(0, 1).', that created the job, can remove the job itself. 

= Job URL =

Once the job will be done, it will be available at:

  <'.$export_url.'>


= Job Removal  =

Closing this task will not remove the job. To remove the job, '.user_getname(0, 1).' must go at: 

  <'.$GLOBALS['sys_https_url'].$GLOBALS['sys_home'].$from.'/export.php?group='.$group.'>


= Job Details =

The SQL query will be:
'.addslashes(db_result($result, 0, 'sql')).'


(Note: We are aware this information is not tremendously user-friendly. This will be improved in future Savane releases)
';

# Set the task to be private per default, until we implement access
# restriction, that is the way to go.
# Apache should be configured not to allow people to browse export/
$vfl['privacy'] = '2';
$vfl['planned_starting_date'] = date("Y")."-".date("m")."-".date("d");
# As we cannot store a specific hour, we must add 24h to the close
# date
$vfl['planned_close_date'] = strftime("%Y-%m", $timestamp)."-".(strftime("%d", $timestamp)+1);
if ($requested_hour && $requested_day)
{
  # If it is frequent job, artificially increment the year because
  # the ending date is not the date of the next export
  $vfl['planned_close_date'] = (date("Y")+2)."-".strftime("%m-%d", $timestamp);
}

$address = '';
$item_id = trackers_data_create_item($group_id,$vfl,$address);

# Send email notification
list($additional_address, $sendall) = trackers_data_get_item_notification_info($item_id, 'task', 1);
if ((trim($address) != "") && (trim($additional_address) != "")) 
{ $address .= ", "; }
$address .= $additional_address;
trackers_mail_followup($item_id, $address, false);

# Update the export table to make it aware of the relevant task
$result = db_execute("UPDATE trackers_export SET status='P', task_id=? WHERE export_id=? LIMIT 1",
		     array($item_id, $export_id));

session_redirect($GLOBALS['sys_home'].$from."/export.php?group=".rawurlencode($group)."&feedback=".rawurlencode(sprintf(_("Export job #%s registered, task #%s created"), $export_id, $item_id)));
