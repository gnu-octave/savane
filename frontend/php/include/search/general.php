<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2003-2006 (c) Stéphane Urbanovski <s.urbanovski--ac-nancy-metz.fr>
#                          Mathieu Roy <yeupou--gnu.org>
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

# only_artifact is quite important here:
#       - if it is not set, it will allow to select all trackers
#       - if it is set to menu, it will make sure the search is not restricted
#       to a given group, since it means it is in the left menu
function search_box ($searched_words='', $only_artifact=0, $size=15, $class="")
{
  global $words,$forum_id,$group_id,$exact,$type_of_search,$type,$max_rows,$only_group_id,$project;

  if ($only_group_id)
    {
      $group_id = $only_group_id;
    }

  if ($size > 15)
    { $is_small = 0; }
  else
    { $is_small = 1; }


  # If it is the left menu, small box, then make sure any group_id info
  # is ignored, because we want to keep the left menu site-wide.
  if ($only_artifact == "menu")
    { unset($group_id, $only_group_id, $only_artifact); }

# if there is no search currently, set the default
  if (!isset($type_of_search))
    { $exact = 1; }
  if (!isset($max_rows))
    { $max_rows = "25"; }


# if the wildcard '%%%' is searched, replace it with the more usual '*'
  if ($words == "%%%")
    { $words = "*"; }

  if ($class)
    { $class = ' class="'.$class.'"'; }

  $ret = '      <form action="'.$GLOBALS['sys_home'].'search/#options" method="get" '.$class.'>';

  if (!$is_small)
    {
      # If it's a big form, we want the submit button on the right
      $ret .= '<span class="boxoptionssubmit"><input type="submit" name="Search" value="'._("Search").'" /></span>';
    }

  $ret .= '<input type="text" size="'.$size.'" name="words" value="'.htmlspecialchars($searched_words).'" />';

  if ($is_small)
    { $ret .= '<br />'; }

  if (!empty($only_artifact))
    {
      $ret .= '<input type="hidden" name="type_of_search" value="'.htmlspecialchars($only_artifact).'" />';
    }
  else
    {
      # I18N
      # This word is used in the phrase "Search [phrase] in
      # [projects, bugs, tasks, ...]" in the main menu on the left side.
      # Please try to find a translation which is as short as possible,
      # so the layout of the menu wont be destroyed.
      $ret .= ' '._("<em>in</em>").' <select name="type_of_search">';

      # If the search is restricted to a given group, remove the possibility
      # to search another group, unless we're showing the left box
     if (empty($group_id))
       {
      $ret .= '<option value="soft"'.(($type_of_search == "soft")||($type_of_search == "") ? ' selected="selected"' : "").'>'._("Projects")."</option>\n";

      $ret .= '<option value="people"'.(($type_of_search == "people") ? ' selected="selected"' : "").'>'._("People")."</option>\n";
       }

     if (!empty($group_id))
       $group_realname = substr(group_getname($group_id), 0, 10)."...";

      if (!$project && !empty($group_id))
	{
	  $project = project_get_object($group_id);
	}

      $text = '';
      if (empty($group_id) || $is_small)
	{ $text = _("Cookbook"); }
      else
	{
	  $text = sprintf(_("%s Cookbook"), $group_realname);
	}
      if ($text)
	{
	  $ret .= '<option value="cookbook"'.(($type_of_search == "cookbook") ? ' selected="selected"' : "").'>'.$text."</option>\n";
	}

      $text = '';
      if (empty($group_id) ||
	  ($is_small && $project->Uses("support")))
	{ $text = _("Support");	}
      else
	{
	  if ($project->Uses("support"))
	    {
	      $text = sprintf(_("%s Support"), $group_realname);
	    }
	}

      if ($text)
	{
	  $ret .= '<option value="support"'.(($type_of_search == "support") ? ' selected="selected"' : "").'>'.$text."</option>\n";
	}

      $text = '';
      if (empty($group_id) ||
	  ($is_small && $project->Uses("bugs")))
	{ $text = _("Bugs");	}
      else
	{
	  if ($project->Uses("bugs"))
	    {
	      $text = sprintf(_("%s Bugs"), $group_realname);
	    }
	}
      if ($text)
	{
	  $ret .= '<option value="bugs"'.(($type_of_search == "bugs") ? ' selected="selected"' : "").'>'.$text."</option>\n";
	}

      $text = '';
      if (empty($group_id) ||
	  ($is_small && $project->Uses("task")))
	{ $text = _("Tasks");	}
      else
	{
	  if ($project->Uses("task"))
	    {
	      $text = sprintf(_("%s Tasks"), $group_realname);
	    }
	}
      if ($text)
	{
      $ret .= '<option value="task"'.(($type_of_search == "task") ? ' selected="selected"' : "").'>'.$text."</option>\n";
	}

      $text = '';
      if (empty($group_id) ||
	  ($is_small && $project->Uses("patch")))
	{ $text = _("Patches");	}
      else
	{
	  if ($project->Uses("patch"))
	    {
	      $text = sprintf(_("%s Patches"), $group_realname);
	    }

	}
      if ($text)
	{
	  $ret .= '<option value="patch"'.(($type_of_search == "patch") ? ' selected="selected"' : "").'>'.$text."</option>\n";
	}

      if ($GLOBALS['sys_use_google'])
	{
	  $ret .= '<option value="google"'.(($type_of_search == "google") ? ' selected="selected"' : "").'><em>'._("via Google")."</em></option>\n";
	}

      $ret .= '</select>';

    }
  if ($size < 16)
    { $ret .= '<br />'; }

  if (isset($group_id))
    {
      $ret .="<input type=\"hidden\" value=\"$group_id\" name=\"only_group_id\" />\n";
    }

  if ($size < 16)
    {
      # If it's a small form, the submit button has not already been inserted
  $ret .= '&nbsp;&nbsp;&nbsp;<input type="submit" name="Search" value="'._("Search").'" />';
    }

  if ($size > 15)
    {
      $ret .= '<br />&nbsp;<input type="radio" name="exact" value="0"'.( $exact ? " " : " checked").' />'._("with at least one of the words")."\n";
      $ret .= '<br />&nbsp;<input type="radio" name="exact" value="1"'.( $exact ? " checked" : " " ).' />'._("with all of the words")."\n";
      $ret .= '<br />&nbsp;'.sprintf(_("%s results per page"), '<input type="text" name="max_rows" value="'.$max_rows.'" size="4" />')."\n";


  if (!isset($group_id))
    {
      # add the functionality to restrict the search to a project type
      $ret .="<br />&nbsp;";

      $select = '<select name="type" size="1"><option value="">'._("any").'</option>'."\n";
      $result = db_query("SELECT type_id,name FROM group_type ORDER BY type_id");
      while ($eachtype = db_fetch_array($result))
        {
          $select .= '<option value="'.$eachtype['type_id'].'"'.($type == $eachtype['type_id'] ? ' selected="selected"' : '').'>'.$eachtype['name'].'</option>'."\n";
        }
      $select .= '</select>'."\n";

      $ret .=sprintf(_("Search in %s group type, when searching for a \"Project/Group\"."), $select);
    }

  $ret .= '<p>'._("Notes: You can use the wildcard *, standing for everything. You can also search items by number.").'</p>';
    }
  else
    {
      $ret .= '<input type="hidden" name="exact" value="1" />';
    }

  $ret .= '      </form>';

 return $ret;
}

function search_send_header ()
{
  global $HTML,$words,$type_of_search,$only_group_id;

  if ($type_of_search == "soft" || $type_of_search == "people")
    {
      # there cannot be a group id specific if we are looking for a group
      # group id is meaningless when looking for someone
      $group_id = 0;
    }

  site_header(array('title'=>_("Search"),'context'=>'search'));


# Print the form

  if (!$only_group_id)
    { $title = _("Search Criteria:"); }
  else
    { $title = sprintf(_("New search criteria for the Group %s:"), group_getname($only_group_id)); }

  print html_show_boxoptions($title, search_box($words, '', 45));

  # Print the result
  print '<h3>';
  if ($words && $type_of_search)
    {
      # Print real words describing the type of search
      if ($type_of_search == "soft")
	{ $type_of_search_real = _("Project/Group"); }
      else if ($type_of_search == "support")
	{ $type_of_search_real = _("Support"); }
      else if ($type_of_search == "bugs")
	{ $type_of_search_real = _("Bugs"); }
      else if ($type_of_search == "task")
	{ $type_of_search_real = _("Task"); }
      else if ($type_of_search == "patch")
	{ $type_of_search_real = _("Patch"); }
      else if ($type_of_search == "cookbook")
	{ $type_of_search_real = _("Cookbook"); }
      else if ($type_of_search == "people")
	{ $type_of_search_real = _("People"); }

      if (!$only_group_id)
	{
	  printf(_("Search results for %s (in %s):"), '<strong>'.htmlspecialchars($words).'</strong>', $type_of_search_real);
	}
      else
	{
	  printf(_("Search results for %s (in %s, for the Group %s):"), '<strong>'.htmlspecialchars($words).'</strong>', $type_of_search_real, group_getname($only_group_id));
	}
    }
else
    { print _("Search results:"); }

  print '</h3><a name="results"></a>';

}


function result_no_match ()
{
  return search_failed();
}

function search_failed ()
{
  global $no_rows,$words;
  $no_rows = 1 ;
  search_send_header();
  print '<span class="warn">';
  print _("None found. Please note that only search words of more than three characters are valid.");
  print '</span>';
  print db_error();
}

# Run a search in the database, by default on softwares
function search_run ($words, $type_of_search="soft", $return_error_messages=1)
{
  global $type, $exact, $crit, $offset, $only_group_id, $max_rows;

  # Remove useless blank spaces, escape nasty characters
  $words = trim($words);
  $words = addslashes($words);

  # Convert the wildcard * to the similar SQL one, when it is alone
  if ($words == "*")
    { $words = "%%%"; }

  # Replace the wildcard * to the similar SQL one, when included in a
  # word
  $words = strtr($words, "*", "%");

  # Convert the crit form value to the SQL equiv.
  if ($exact)
    { $crit='AND'; }
  else
    { $crit='OR'; }

  # No offset defined? Start the search in the db at 0
  if (!$offset || $offset < 0)
    { $GLOBALS['offset'] = 0; }

  # No how many rows to print? Print 25 rows by default
  if (!$max_rows)
    { $GLOBALS['max_rows'] = 25; }

  # Accept only to do a search for more than 2 characters.
  # Exit only if we were not told to avoid returning error messages.
  # Note: we tell user we want more than 3 characters, to incitate to
  # do clever searchs. But it will be ok for only 2 characters (limit
  # that conveniently allow us to search by items numbers)
  if ($words && (strlen($words) < 3) && $return_error_messages)
    {
      search_failed();
      exit;
    }

  # Build arrays
  $array = explode(" ", $words);
  if ($type_of_search == "soft")
    {
      $words1 = implode($array,"%' $crit group_name LIKE '%");
      $words2 = implode($array,"%' $crit short_description LIKE '%");
      $words3 = implode($array,"%' $crit unix_group_name LIKE '%");
      $words4=implode($array,"%' $crit group_id LIKE '%");
      # remove # from words4
      $words4=ereg_replace("#","",$words4);
    }
  else if ($type_of_search == "people")
    {
      $words1=implode($array,"%' $crit user_name LIKE '%");
      $words2=implode($array,"%' $crit realname LIKE '%");
      $words3=implode($array,"%' $crit user_id LIKE '%");
      # remove # from words3
      $words3=ereg_replace("#","",$words3);
    }
  else if ($type_of_search == 'bugs' ||
	   $type_of_search == 'support' ||
	   $type_of_search == 'patch' ||
	   $type_of_search == 'cookbook' ||
	   $type_of_search == 'task')
    {
      $words1=implode($array,"%' $crit ".$type_of_search.".details LIKE '%");
      $words2=implode($array,"%' $crit ".$type_of_search.".summary LIKE '%");
      $words3=implode($array,"%' $crit ".$type_of_search.".bug_id LIKE '%");
      # remove # from words3
      $words3=ereg_replace("#","",$words3);
    }

  # Build SQL command
  if ($type_of_search == "soft")
    {
      if (!$type)
	{
	  $sql = "SELECT group_name,unix_group_name,type,group_id,short_description ".
	     "FROM groups ".
	     "WHERE status='A' AND is_public='1' ".
	     "AND ((group_name LIKE '%$words1%') OR (short_description LIKE '%$words2%') OR (unix_group_name LIKE '%$words3%') OR (group_id LIKE '$words4')) ORDER BY  unix_group_name,group_name LIMIT $offset,".($max_rows+1);
	}
      else
	{
	  $sql = "SELECT group_name,unix_group_name,type,group_id,short_description ".
	     "FROM groups ".
	     "WHERE status='A' AND type='$type' AND is_public='1' ".
	     "AND ((group_name LIKE '%$words1%') OR (short_description LIKE '%$words2%') OR (unix_group_name LIKE '%$words3%') OR (group_id LIKE '$words4')) ORDER BY unix_group_name,group_name LIMIT $offset,".($max_rows+1);
	}
    }
  else if ($type_of_search == "people")
    {
      $sql = "SELECT user_name,user_id,realname "
	 . "FROM user "
	 . "WHERE ((user_name LIKE '%$words1%') OR (realname LIKE '%$words2%') OR (user_id LIKE '$words3')) AND (status='A') ORDER BY user_name LIMIT $offset,".($max_rows+1);
    }
  else if ($type_of_search == 'bugs' ||
	   $type_of_search == 'support' ||
	   $type_of_search == 'patch' ||
	   $type_of_search == 'cookbook' ||
	   $type_of_search == 'task')
    {

      if ($only_group_id)
	{
	  # $search_without_group_id can be set to avoid restricting search
	  # to a group even if group_id is set
	  $sql_with_group_id = "AND ".$type_of_search.".group_id='".addslashes($only_group_id)."' ";
	}

      $sql = "SELECT ".$type_of_search.".bug_id,".
	$type_of_search.".summary,".
	$type_of_search.".date,".
	$type_of_search.".privacy,".
	$type_of_search.".submitted_by,".
	"user.user_name,".
	$type_of_search.".group_id "
	. "FROM ".$type_of_search.",user "
	. "WHERE user.user_id=".$type_of_search.".submitted_by AND ((".$type_of_search.".details LIKE '%$words1%') "
	. "OR (".$type_of_search.".summary LIKE '%$words2%') "
	. "OR (".$type_of_search.".bug_id = '$words3')) "
	. $sql_with_group_id
	. "AND ".$type_of_search.".spamscore < 5 "
	. "GROUP BY bug_id,summary,date,user_name "
	. "ORDER BY ".$type_of_search.".date DESC "
	. "LIMIT $offset,".($max_rows+1);
    }
  return db_query($sql);
}

?>
