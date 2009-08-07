<?php
mysql_connect('localhost', 'root', '');
mysql_select_db('savane_old');
mysql_set_charset('utf8');

$conversions = array(
		array('user', 'user_id', 'realname'),
		array('groups', 'group_id', 'group_name'),
		);

$count = 0;
foreach ($conversions as $fields)
{
  list($table, $pk, $field) = $fields;
  $res = mysql_query("SELECT $pk, $field FROM $table");
  while ($row = mysql_fetch_array($res))
    {
      $conv = html_entity_decode($row[$field], ENT_COMPAT, "UTF-8");
      if ($conv != $row[$field])
	{
	  mysql_query("UPDATE $table SET $field='"
		      . mysql_real_escape_string($conv)
		      . "' WHERE $pk="
		      . $row[$pk]) or die(mysql_error());
	  echo "{$row[$field]} => $conv\n";
	  $count++;
	}
    }
}
echo "$count replacements\n";
