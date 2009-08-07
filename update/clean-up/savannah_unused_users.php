<?php
mysql_connect('localhost', 'root', '');
mysql_select_db('savane_old');
mysql_set_charset('utf8');

// Get all user_id that are used in the system: group membership, comments, etc.
$sqls = array();
$sqls[] = 'SELECT DISTINCT user_id FROM user_group;';

$sqls[] = 'SELECT DISTINCT submitted_by FROM support;';
$sqls[] = 'SELECT DISTINCT assigned_to FROM support;';
$sqls[] = 'SELECT DISTINCT added_by FROM support_cc;';
$sqls[] = 'SELECT DISTINCT mod_by FROM support_history;';

$sqls[] = 'SELECT DISTINCT submitted_by FROM bugs;';
$sqls[] = 'SELECT DISTINCT assigned_to FROM bugs;';
$sqls[] = 'SELECT DISTINCT added_by FROM bugs_cc;';
$sqls[] = 'SELECT DISTINCT mod_by FROM bugs_history;';

$sqls[] = 'SELECT DISTINCT submitted_by FROM task;';
$sqls[] = 'SELECT DISTINCT assigned_to FROM task;';
$sqls[] = 'SELECT DISTINCT added_by FROM task_cc;';
$sqls[] = 'SELECT DISTINCT mod_by FROM task_history;';

$sqls[] = 'SELECT DISTINCT submitted_by FROM patch;';
$sqls[] = 'SELECT DISTINCT assigned_to FROM patch;';
$sqls[] = 'SELECT DISTINCT added_by FROM patch_cc;';
$sqls[] = 'SELECT DISTINCT mod_by FROM patch_history;';

$sqls[] = 'SELECT DISTINCT submitted_by FROM cookbook;';
$sqls[] = 'SELECT DISTINCT assigned_to FROM cookbook;';
$sqls[] = 'SELECT DISTINCT added_by FROM cookbook_cc;';
$sqls[] = 'SELECT DISTINCT mod_by FROM cookbook_history;';

$sqls[] = 'SELECT DISTINCT submitted_by FROM trackers_file;';

$sqls[] = 'SELECT DISTINCT posted_by FROM forum;';

$sqls[] = 'SELECT DISTINCT submitted_by FROM news_bytes;';

$sqls[] = 'SELECT DISTINCT created_by FROM people_job;';

// Merge all lists of user_ids
$actives = array();
foreach ($sqls as $sql)
{
  $res = mysql_query($sql);
  while ($row = mysql_fetch_array($res))
    {
      $actives[$row['user_id']] = 1;
    }
}
$actives = array_keys($actives);
sort($actives);

$idles = array();
// Get the list of suspicious users - the ones whose password was
// reset fall of 2003 and who never connected ever since
$res = mysql_query("SELECT user_id FROM user WHERE user_pw='*' ORDER BY user_id;");
// More generic variant: get the list of users who registered more
// than 1 year ago and do not have a session Warning: this removes
// people who registered more than 1 year, connected (say) yesterday
// and clicked on logout the same day, AND still have nothing on the
// system beside their profile.
#$res = mysql_query("SELECT DISTINCT user.user_id
#  FROM user LEFT JOIN session ON user.user_id = session.user_id
#  WHERE add_date < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 YEAR))
#    AND (session.time < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 YEAR))
#      OR session.time IS NULL)
#  ORDER BY user.user_id;");
while ($row = mysql_fetch_array($res))
  {
    $idles[] = $row['user_id'];
  }

print "idles: " . count($idles) . "\n";
print "actives: " . count($actives) . "\n";


// Get the list of suspicious users that never left any trace on the
// system, besides their own preferences. Other will be kept to keep
// for history consistency.

// $to_delete = array_diff($idles, $actives); // slow!
$to_delete = array();
$i = 0;
$a = 0;
while ($i < count($idles) and $a < count($actives))
{
  #print "i[$i]={$idles[$i]} a[$a]={$actives[$a]}\n";
  if ($idles[$i] < $actives[$a])
    {
      // include and increase 'i'
      $to_delete[] = $idles[$i];
      $i++;
    }
  else if ($idles[$i] == $actives[$a])
    {
      // include 'i' and increase both
      $i++;
      $a++;
    }
  else if ($idles[$i] > $actives[$a])
    {
      // skip and increase 'a'
      $a++;
    }
}

// Remove said users
print "Idle without activity: " . count($to_delete) . "\n";
print "\n";
print $to_delete[0] . "\n";
print $to_delete[1] . "\n";
print $to_delete[2] . "\n";
print $to_delete[3] . "\n";

$id_list = join(',', $to_delete);
#echo "$id_list\n";
mysql_query("DELETE FROM user WHERE user_id IN ($id_list)");
