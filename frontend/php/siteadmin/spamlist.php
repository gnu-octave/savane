<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: lastlogins.php 6310 2006-11-14 15:45:39Z yeupou $
#
# Copyright 2006      (c) Mathieu Roy <yeupou--gnu.org>
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

#input_is_safe();
#mysql_is_safe();

require_once('../include/init.php');

register_globals_off();
session_require(array('group'=>'1','admin_flags'=>'A'));

###### See if we were asked to perform anything
extract(sane_import('get', array('ban_user_id', 'wash_user_id', 'wash_ip',
				 'max_rows', 'offset')));

if ($ban_user_id)
{
  if (!user_exists($ban_user_id))
    { fb(_("User not found"), 1); }
  else
    {
      user_delete($ban_user_id);
    }
}

if ($wash_user_id)
{
  if (!user_exists($wash_user_id))
    { fb(_("User not found"), 1); }
  else
    {
      # Update the user spamscore field
      db_execute("UPDATE user SET spamscore='0' WHERE user_id=?", array($wash_user_id));

      # Previous comment flagged as spam will stay as such.
      # We just changed the affected user id that it wont affect this guy
      # any more.
      # We assume that message flagged as spam really were.
      # (we may change that in the future, depending on user experience)
      db_execute("UPDATE trackers_spamscore SET affected_user_id='100' WHERE affected_user_id=?",
		 array($wash_user_id));      
      
    }
}

if ($wash_ip)
{
  db_execute("DELETE FROM trackers_spamban WHERE ip=?", array($wash_ip));
}



###### Start HTML

site_admin_header(array('title'=>_("Monitor Spams"),'context'=>'admhome'));

print '<h3>'.html_anchor(_("Suspected users"), "users_results").'</h3>';
print '<p>'._("Follow the list of users that post content that as been flagged as spam, ordered by their spam score. If the user is an obvious spammer, you can ban him immediately. If it was flagged by mistake, you can wash his reputation.").' <span class="warn">'._("Banning an user is a one-way-ticket process. Be careful. For efficiency purpose, there won't be any warnings.").'</span></p>';
$title_arr=array();
$title_arr[]=_("User");
$title_arr[]=_("Score");
$title_arr[]=_("Ban user");
$title_arr[]=_("Wash score");
$title_arr[]=_("Incriminated content");
$title_arr[]=_("Flagged by");

if (!isset($max_rows))
{ $max_rows = 50; }
else
{ $max_rows = intval($max_rows); }
     
if (!isset($offset))
{ $offset = 0; }
else
{ $offset = intval($offset); }

$result = db_execute("SELECT user_name,realname,user_id,spamscore FROM user WHERE status='A' AND spamscore > 0 ORDER BY spamscore DESC LIMIT ?,?", array($offset,($max_rows+1)));
if (!db_numrows($result))
{
  print '<p>'._("No suspects found").'</p>';
} 
else 
{
  print html_build_list_table_top($title_arr);
  
  $i = 0;
  while ($entry = db_fetch_array($result)) 
    {
      $i++;

      # the sql was artificially asked to search more result than the number
      # we print. If $i > $max, it means that there were more results than
      # the max, we wont print these more, but below we will add next/prev
      # links
      if ($i > $max_rows)
	{ break; }

      $res_score = db_execute("SELECT trackers_spamscore.artifact,trackers_spamscore.item_id,trackers_spamscore.comment_id,user.user_name FROM trackers_spamscore,user WHERE trackers_spamscore.affected_user_id=? AND user.user_id=trackers_spamscore.reporter_user_id LIMIT 50", array($entry['user_id']));
      $flagged_by = '';
      $incriminated_content = '';
      $seen_before = array();
      while ($entry_score = db_fetch_array($res_score))
	{
	  if (!isset($seen_before[$entry_score['user_name']]))
	    {
	      $flagged_by .= utils_user_link($entry_score['user_name']).', ';
	      $seen_before[$entry_score['user_name']] = true;
	    }
	  
	  if (!isset($seen_before[$entry_score['artifact'].$entry_score['item_id'].'C'.$entry_score['comment_id']]))
	    {
	      # only put the string "here" for each item, otherwise it gets
	      # overlong when we have to tell comment #nnn of item #nnnn
	      $incriminated_content .= utils_link($GLOBALS['sys_home'].$entry_score['artifact'].'/?item_id='.$entry_score['item_id'].'&amp;func=viewspam&amp;comment_internal_id='.$entry_score['comment_id'].'#spam'.$entry_score['comment_id'],
						 _("here")).', ';	 
	      $seen_before[$entry_score['artifact'].$entry_score['item_id'].'C'.$entry_score['comment_id']] = true;
	    }
	  
	}
      $flagged_by = rtrim($flagged_by, ', ');
      $incriminated_content = rtrim($incriminated_content, ', ');
      
      print '<tr class="'.utils_get_alt_row_color($i).'">';
      print '<td width="25%">'.utils_user_link($entry['user_name'], $entry['realname']).'</td>';
      print '<td width="5%" class="center">'.$entry['spamscore'].'</td>';
      print '<td width="5%" class="center">'.utils_link($_SERVER['PHP_SELF'].'?ban_user_id='.$entry['user_id'].'#users_results', '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/misc/trash.png" alt="'._("Ban user").'" />').'</td>';
      print '<td width="5%" class="center">'.utils_link($_SERVER['PHP_SELF'].'?wash_user_id='.$entry['user_id'].'#users_results', '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/bool/ok.png" alt="'._("Wash score").'" />').'</td>';
      print '<td width="30%">'.$incriminated_content.'</td>';
      print '<td width="30%">'.$flagged_by.'</td>';	
      print '</tr>';
    }
  print '</table>';
  
  
  # More results than $max? Print next/prev
  html_nextprev($_SERVER['PHP_SELF'].'?', $max_rows, $i, "users");      
  
}

print '<p>&nbsp;</p>';
print '<h3>'.html_anchor(_("Banned IPs"), "ip_results").'</h3>';
print '<p>'._("Follow the list of IPs that are currently banned because content their owner posted was flagged as spam. This ban affect only anonymous users and do not prevent them to log in. IPs are automatically removed by a cronjob from this list after a few hours delay but, from here, you can force the removal to be done instantly.").'</p>';

$result = db_execute("SELECT ip FROM trackers_spamban WHERE 1 GROUP BY ip ORDER BY ip LIMIT ?,?",
		     array($offset,$max_rows+1));
if (!db_numrows($result)) 
{
  print '<p>'._("No IP banned").'</p>';
} 
else 
{
  print '<div class="box"><div class="boxtitle">'._("IPs").'</div><div class="boxitem">';
  
  $i = 0;
  while ($entry = db_fetch_array($result)) 
    {
      $i++;

      # the sql was artificially asked to search more result than the number
      # we print. If $i > $max, it means that there were more results than
      # the max, we wont print these more, but below we will add next/prev
      # links
      if ($i > $max_rows)
	{ break; }

      if ($i > 1)
	{ print ', '; }

      print utils_link($_SERVER['PHP_SELF'].'?wash_ip='.$entry['ip'].'#ip_results',
		       $entry['ip'].' <img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/bool/ok.png" alt="'._("Wash IP").'" />');
      
    }
  print '</div>';
    
  # More results than $max? Print next/prev
  html_nextprev($_SERVER['PHP_SELF'].'?', $max_rows, $i, "ip");   


}



# NOT YET SURE IF WE WANT THE ADMIN TO WASTE TIME TO CHECK SUSPECTED CONTENT
# We would then get an enormous list. If there is a non-spam flagged by 
# mistake, the admin can simply browse the item page and unflag it. 
# 
# This part would only make sense if we implement a two time item submission
# process (not approved before spamassassin check). Then the admin could
# want to manually speed up the process
# 
#print '<h3>'._("Suspected content").'</h3>';
#$title_arr=array();
#$title_arr[]=_("Item");
#$title_arr[]=_("Score");
#$title_arr[]=_("Posted by");
#$title_arr[]=_("Flagged by");
#print html_build_list_table_top ($title_arr);
#print '</table>';

$HTML->footer(array());
