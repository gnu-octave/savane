<?php
# Various boxes.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017 Ineiev
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

function show_features_boxes()
{
  GLOBAL $HTML;
  $return = '';

  # General stats.
  $return .= $HTML->box_top(utils_link($GLOBALS['sys_home']."stats/",
                   # TRANSLATORS: the argument is site name (like Savannah).
                                       sprintf(_("%s Statistics"),
                                       $GLOBALS['sys_name']),"sortbutton"));
  $return .= show_sitestats();
  $return .= $HTML->box_bottom();

  # Job offers stats.
  $jobs = people_show_category_list();

  if ($jobs)
    {
      $return .= "<br />\n";
      $return .= $HTML->box_top(_("Help Wanted"),'',1);
      $return .= $jobs;
      $return .= $HTML->box_bottom(1);
    }

  # Popular items.
  $votes = show_votes();

  if ($votes)
    {
      $return .= "<br />\n";
      $return .= $HTML->box_top(_("Most Popular Items"),'',1);
      $return .= $votes;
      $return .= $HTML->box_bottom(1);
    }

  # Group type stats.
  $result = db_query("SELECT type_id,name FROM group_type ORDER BY name");
  # Try to find out how many latest registered groups per group type
  # we should print, depending on how many groups we have.
  $limit = 10;
  $count = db_numrows($result);
  if ($count > 25)
    $limit = 2;
  elseif ($count < 2)
    $limit = 25;
  else
    $limit = round(35 / $count);

  while ($eachtype = db_fetch_array($result))
    {
      $groupdata = show_newest_projects($eachtype['type_id'], $limit);
      if (!$groupdata)
        continue;

      $return .= "<br />\n";
# TRANSLATORS: the argument is project type (like Official GNU software).
      $return .= $HTML->box_top(sprintf(_("Newest %s Projects"),
                                $eachtype['name']),'',1);
      $return .= $groupdata;
      global $j;
      $return .= '<div class="'.utils_get_alt_row_color($j)
                 .'"><span class="smaller"><a href="'.$GLOBALS['sys_home']
                 .'search/?type_of_search=soft&amp;words=%%%&amp;type='
                 .$eachtype['type_id'].'">[';
# TRANSLATORS: the argument is project type (like Official GNU software).
      $return .= sprintf( _("all %s projects"),$eachtype['name'] ) ;
      $return .= ']</a></span></div>';
      $return .= $HTML->box_bottom(1);
    }
  return $return;
}

function show_sitestats()
{
  $return = '';
  $return .= '<span class="smaller">';
  $users = stats_getusers();
  $return .= sprintf(ngettext("%s registered user",
                              "%s registered users", $users),
                     "<strong>$users</strong>");
  $return .= '</span></div>';
  $return .= '<div class="'.utils_get_alt_row_color(0).'"><span class="smaller">';
  $projects = stats_getprojects_active();
  $return .= sprintf(ngettext("%s hosted project",
                              "%s hosted projects", $projects),
                     "<strong>$projects</strong>").'</span></div>';
  $result = db_query("SELECT type_id,name FROM group_type ORDER BY name");
  $i = 0;
  while ($eachtype = db_fetch_array($result))
    {
      $i++;
      $return .= '<div class="'.utils_get_alt_row_color($i).'"><span class="smaller">';
      $return .= '&nbsp;&nbsp;- <a href="'.$GLOBALS['sys_home']
        .'search/?type_of_search=soft&amp;words=%%%&amp;type='
        .$eachtype['type_id'].'" class="center">';
      $return .= stats_getprojects_bytype_active($eachtype['type_id']);
      $return .= ' '.$eachtype['name'].'</a>';
      $return .= '</span></div>';
    }
  $pending = stats_getprojects_pending();
  $return .= '<div class="'.utils_get_alt_row_color(($i+1))
             .'"><span class="smaller">&nbsp;&nbsp;';
  $return .= sprintf(ngettext("+ %s registration pending",
                              "+ %s registrations pending", $pending),
                     $pending).'</span>';
  return $return;
}

function show_newest_projects($group_type, $limit)
{
  global $j;

  # Shows only projects that were added in the last trimester.
  $since = mktime(0,0,0,(date("m")-2));

  $res_newproj = db_execute(
"SELECT group_id,unix_group_name,group_name,register_time FROM groups
WHERE is_public=1 AND status='A' AND type=? AND register_time >= ?
ORDER BY register_time DESC LIMIT ?", array($group_type, $since, $limit));
  if (!db_numrows($res_newproj))
    return false;

  $base_url = '';
  $res_newproj_type = db_execute(
    "SELECT type_id,base_host FROM group_type WHERE type_id=?",
                                 array($group_type));
  $row_newproj_type = db_fetch_array($res_newproj_type);
  if ($row_newproj_type['base_host'])
    {
      $base_url = 'http'.(session_issecure()?'s':'').'://'
                  .$row_newproj_type['base_host'];
    }

  $return = '';
  while ($row_newproj = db_fetch_array($res_newproj))
    {
      if ($row_newproj['register_time'])
        {
          $return .= '<div class="'.utils_get_alt_row_color($j)
            .'"><span class="smaller">&nbsp;&nbsp;- <a href="'
            .$base_url.$GLOBALS['sys_home']
            ."projects/$row_newproj[unix_group_name]/\">"
            . $row_newproj['group_name'].'</a>, '
            .utils_format_date($row_newproj['register_time'], 'minimal')
            .'</span></div>';
          $j++;
        }
    }
  return $return;
}

function show_votes ($limit=10)
{
  # Find out interesting bugs and store them in a hash.
  # Closed and private items are ignored.
  $trackers = array("bugs", "task", "support", "patch");

  $item_vote = array();
  $item_summary = array();

  while (list(,$tracker) = each($trackers))
    {
      $result=db_execute("SELECT bug_id,group_id,summary,vote
FROM $tracker WHERE vote >=35 AND privacy=1 AND status_id=1
ORDER BY vote DESC LIMIT ?", array($limit));
      $rows=db_numrows($result);
      $results = 0;
      if ($result && $rows > 0)
        {
          $results = 1;
          for ($j=0; $j<$rows; $j++)
            {
              # Create item unique name.
              $thisitem = $tracker.'#'.db_result($result,$j,'bug_id');

              # Store data.
              $item_vote[$thisitem] = db_result($result,$j,'vote');
              $item_summary[$thisitem] = db_result($result,$j,'summary');
            }
        }
    }
  if (!$results)
    return false;

  # Sort items in rank and print the first 10 ones.
  $return = '';
  $count = 0;
  arsort($item_vote);
  foreach ($item_vote as $thisitem => $vote)
    {
      $count++;
      if ($count > $limit)
        break;

      list($tracker, $item_id) = explode("#", $thisitem);
      $prefix = utils_get_tracker_prefix($tracker);


      # If the summar item is large (>30), only show the first
      # 30 characters of the story.
      if (strlen($item_summary[$thisitem]) > 30)
        {
          $item_summary[$thisitem] = substr($item_summary[$thisitem], 0, 30);
          $item_summary[$thisitem] = substr($item_summary[$thisitem], 0,
                                            strrpos($item_summary[$thisitem],
                                                    ' '));
          $item_summary[$thisitem] .= "...";
        }
      $return .= '<div class="'.utils_get_alt_row_color($count).'">'
        .'<span class="smaller">&nbsp;&nbsp;- '
        .'<a href="'.$GLOBALS['sys_home'].$tracker.'/?'.$item_id.'">'
        .$prefix .' #'.$item_id.'</a>'
        ._(": ").'&nbsp;'
        .'<a href="'.$GLOBALS['sys_home'].$tracker.'/?'.$item_id.'">'
        .$item_summary[$thisitem].'</a>,'
        .'&nbsp;'.sprintf(ngettext("%s vote", "%s votes", $vote), $vote)
        .'</span></div>';
    }
  return $return;
}
?>
