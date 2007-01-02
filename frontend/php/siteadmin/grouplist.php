<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
# Copyright 1999-2000 (c) The SourceForge Crew
#
# Copyright 2004-2006 (c) Mathieu Roy <yeupou--gnu.org>
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


require "../include/pre.php";

site_admin_header(array('title'=>_("Group List"),'context'=>'admgroup'));

extract(sane_import('post', array('search', 'groupsearch')));
extract(sane_import('get', array('group_name_search', 'offset', 'max_rows', 'status')));

print '<h3>'._("Group List Filter").'</h3>';

$title_arr=array();
$title_arr[]=_("Type");
$title_arr[]=_("Number");
$title_arr[]=_("Action");

$inc = 0;

print html_build_list_table_top ($title_arr);

print '<tr class="'.utils_get_alt_row_color($inc++).'">';
$res = db_query("SELECT count(*) AS count FROM groups");
$row = db_fetch_array();
print '<td>'._("Any").'</td>';
print '<td>'.$row['count'].'</td>';
print '<td><a href="grouplist.php">'._("Browse").'</a></td>';
print "</tr>\n";

print '<tr class="'.utils_get_alt_row_color($inc++).'">';
$res = db_query("SELECT count(*) AS count FROM groups WHERE status='P' ");
$row = db_fetch_array();
print '<td>'._("Pending projects (normally, an opened task should exist about them)").'</td>';
print '<td>'.$row['count'].'</td>';
print '<td><a href="grouplist.php?status=P">'._("Browse").'</a></td>';
print "</tr>\n";

# These are automatically removed by the backend and certainly does not require
# any attention
#print '<tr class="'.utils_get_alt_row_color($inc++).'">';
#$res = db_query("SELECT count(*) AS count FROM groups WHERE status='I' ");
#$row = db_fetch_array();
#print '<td>'._("Incompleted projects").'</td>';
#print '<td>'.$row[count].'</td>';
#print '<td><a href="grouplist.php?status=I">'._("Browse").'</a></td>';
#print "</tr>\n";

print '<tr class="'.utils_get_alt_row_color($inc++).'">';
$res = db_query("SELECT count(*) AS count FROM groups WHERE status='D' ");
$row = db_fetch_array();
print '<td>'._("Deleted projects (the backend will remove the record soon)").'</td>';
print '<td>'.$row['count'].'</td>';
print '<td><a href="grouplist.php?status=D">'._("Browse").'</a></td>';
print "</tr>\n";

print "</table>\n";



$MAX_ROW = !empty($max_rows) ? $max_rows : 100;

$abc_array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0','1','2','3','4','5','6','7','8','9');

$status_arr=array();
$status_arr['A']=_("Active");
$status_arr['P']=_("Pending");
$status_arr['I']=_("Incomplete");
$status_arr['D']=_("Deleted");
$status_arr['M']=_("Maintenance");

print '<h3>'._("Group Search").'</h3>';

print '<p>'._("Display Groups beginning with:").' ';

for ($i=0; $i < count($abc_array); $i++) {
	echo "<a href=\"grouplist.php?group_name_search=$abc_array[$i]\">$abc_array[$i]</a> ";
}

print '<br />'._("or search by group_id, group_unix_name or group_name:");

print '
<form name="gpsrch" action="grouplist.php" method="POST">
  <input type="text" name="search" value ="'.$search.'" />
  <input type="hidden" name="groupsearch" value="1" />
  <input type="submit" value="'._("Search").'" />
</form>
</p>';

print '<h3>'._("Group List").' ';

if (!$offset or !ctype_digit($offset) or $offset < 0)
{ $offset = 0; }


$where = "1";
$msg = '';
if (isset($group_name_search)) 
{
  $msg = sprintf(_("Groups that begin with %s"), $group_name_search);
  $where = "group_name LIKE '$group_name_search%' ";
  $search_url = "&group_name_search=$group_name_search";
  
} 
else if (!empty($status_arr[$status]))
{
  $msg = $status_arr[$status].' '._("Projects");
  $where = "status='$status'";
  $search_url = "&status=$status";
  
} 
else if ($groupsearch) 
{
  $msg = _("that match")." <strong>'" .$search. "'</strong>\n";
  $where = "group_id LIKE '%$search%' OR unix_group_name LIKE '%$search%' OR group_name LIKE '%$search%'";
  $search_url = "&groupsearch=1&search=".urlencode($search)."";
}

$res = db_query("SELECT DISTINCTROW group_name,unix_group_name,group_id,is_public,status,license "
		. "FROM groups WHERE $where ORDER BY group_name LIMIT $offset,".($MAX_ROW+1)) or ($feedback = db_error());
print "<strong>$msg</strong>\n";

print '</h3>';

$rows = $rows_returned = db_numrows($res);

$title_arr=array();
$title_arr[]=_("Group Name (click to edit)");
$title_arr[]=_("System Name");
$title_arr[]=_("Status");
$title_arr[]=_("Public?");
$title_arr[]=_("License");
$title_arr[]=_("Members");

print html_build_list_table_top ($title_arr);

if ($rows_returned < 1) 
{
  print '<tr class="'.utils_get_alt_row_color($inc++).'"><td colspan="7">';
  print _("No matches");
  print '.</td></tr>';
  
} 
else 
{
  if ($rows_returned > $MAX_ROW) {
    $rows = $MAX_ROW;
  }
  for ($i = 0; $i < $rows; $i++) 
    {
      $grp = db_fetch_array($res);
      print '<tr class="'.utils_get_alt_row_color($inc++).'">';
      print "<td><a href=\"groupedit.php?group_id=$grp[group_id]\">$grp[group_name]</a></td>";
      print "<td>$grp[unix_group_name]</td>";
      print '<td>'.$status_arr[$grp['status']]."</td>";
      print '<td>'.($grp['is_public']?_("yes"):_("no")).'</td>';
      print "<td>$grp[license]</td>";
      
      # members
      $res_count = db_query("SELECT user_id FROM user_group WHERE group_id=$grp[group_id]");
      print "<td>" . db_numrows($res_count) . "</td>";
      
      print "</tr>\n";
    }
}

print '</table>';

html_nextprev($_SERVER['PHP_SELF'].'?groupsearch=1&amp;group_name_search='.urlencode($group_name_search).'&amp;search='.urlencode($search), $rows, $rows_returned);

site_admin_footer(array());

?>
