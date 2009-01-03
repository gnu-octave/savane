<?php
# OPTIONAL: Strip invalid UTF-8 characters
# Copyright (C) 2008  Sylvain Beucler
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


// When testing the MySQL utf8 definition fixes on Savannah, I got
// some warnings about data being truncated due to invalid UTF-8
// characters.  Since I didn't want data to be truncated, but rather
// have invalid chars stripped, I had to do that manually.  You may
// not have to do it if your database isn't as old as Savannah's.

// I didn't find a more simple way: using 'mysqldump | iconv -c' is a
// no-op since mysqldump UTF-8-encodes everything, so problematic
// fields are double-encoded. Doing that when fields are BINARY and
// before they are switched to UTF-8 is also inconvenient because
// either BINARY fields will be hex-encoded, either BLOB field will
// get corrupted. So I needed to do that at the SQL level.

// Usage:
// SAVANE_CONF=/tmp/savane-mini/savane php test.php


header('Content-type: text/plain;charset=UTF-8');
include('include/init.php');

mysql_set_charset('latin1');

$tables = array
  (
   'bugs' => array('custom_ta1', 'details', 'originator_name', 'summary'),
   'bugs_field_usage' => array('custom_label', 'custom_description'),
   'bugs_history' => array('new_value', 'old_value'),
   'forum' => array('body', 'subject'),
   'forum_group_list' => array('forum_name'),
   'groups' => array('group_name', 'long_description', 'register_purpose', 'registered_gpg_keys', 'short_description'),
   'news_bytes' => array('details', 'summary'),
   'patch' => array('details', 'summary'),
   'patch_history' => array('new_value', 'old_value'),
   'support' =>  array('details', 'summary'),
   'support_field_value' => array('description', 'value'),
   'support_history' => array('new_value', 'old_value'),
   'task' => array('details', 'summary'),
   'task_history' => array('new_value', 'old_value'),
   'trackers_file' => array('description', 'filename'),
   'user' => array('gpg_key', 'people_resume', 'realname'),
   );

$pks = array
  (
   'bugs' => 'bug_id',
   'bugs_field_usage' => 'bug_field_id',
   'bugs_history' => 'bug_history_id',
   'forum' => 'msg_id',
   'forum_group_list' => 'group_forum_id',
   'groups' => 'group_id',
   'news_bytes' => 'id',
   'patch' => 'bug_id',
   'patch_history' => 'bug_history_id',
   'support' => 'bug_id',
   'support_field_value' => 'bug_fv_id',
   'support_history' => 'bug_history_id',
   'task' => 'bug_id',
   'task_history' => 'bug_history_id',
   'trackers_file' => 'file_id',
   'user' => 'user_id',
   );

foreach($tables as $table => $fields)
{
  $i = 0;
  $fields[] = $pks[$table];
  $result = db_execute("SELECT " . join(',', $fields) . " FROM $table");
  while($row = db_fetch_array($result))
    {
      $i++;
      $need_update = 0;
      $new_vals = array();
      foreach($fields as $field)
	{
	  $conv = @iconv("UTF-8", "UTF-8//IGNORE", $row[$field]);
	  if ($conv != $row[$field])
	    {
	      $new_vals[$field] = $conv;
	      $need_update = 1;
	    }
	}
      if ($need_update)
	{
	  print "Update: $table at row $i [{$row[$pks[$table]]} - " . join(',', array_keys($new_vals)) . "]\n";
	  db_autoexecute($table, $new_vals, DB_AUTOQUERY_UPDATE,
			 $pks[$table] . " = ?", array($row[$pks[$table]]));
	}
    }
}
