<?php
# Output tracker statistics.
#
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2017, 2018 Ineiev
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

require_once('../include/init.php');
require_once('../include/graphs.php');
require_directory("trackers");

if (!$group_id)
  exit_no_group();

extract(sane_import('get', array('field')));

# Give access to this page to anybody: people can already collect such
# information since they are able to browse the trackers.
# It does not make sense to restrict access to this data, in this spirit.
# But if some specific installation need to do so for whatever reason,
# we can make that a configuration option.

# If artifact is not defined, we want statistics of all trackers.
if (ARTIFACT == "project")
  $artifact = "bugs,task,patch,support";
else
  $artifact = ARTIFACT;

# Specific function that list possible report.
function specific_reports_list ($thisfield=0)
{
  global $group_id;

  if ($thisfield)
    print "<p>&nbsp;</p>\n<h2>"._("Other statistics:")."</h2>\n";
  print "<ul>\n";

  if ($thisfield != 'aging')
    print "<li><a href=\"reporting.php?group_id=$group_id&amp;field=aging\">"
# TRANSLATORS: aging statistics is statistics by date.
          ._("Aging Statistics")."</a></li>\n";

  while ($field = trackers_list_all_fields())
    {
      if (trackers_data_is_special($field) || $field  == $thisfield)
        continue;

      if (trackers_data_is_select_box($field) && trackers_data_is_used($field))
        {
          print "<li><a href=\"reporting.php?group_id="
                ."$group_id&amp;field=$field\">";
          # TRANSLATORS: the argument is field label.
          printf(_("Statistics by '%s'"), trackers_data_get_label($field));
          print "</a></li>\n";
        }
    }
  print "</ul>\n";
}

# Initialize the global data structure before anything else.
trackers_init($group_id);

$page = "";
$graph_id = 0;
$widths = "";

if ($field)
  {
    if ($field == 'aging')
      {
# TRANSLATORS: aging statistics is statistics by date.
        $page .= '<h2>'._("Aging statistics:")."</h2>\n";

        $time_now=time();
        unset($content);

        for ($counter=1; $counter<=8; $counter++)
          {
            $start=($time_now-($counter*604800));
            $end=($time_now-(($counter-1)*604800));

            $result = db_execute("SELECT round(avg((close_date-date)/86400), 0)
                                  FROM ".$artifact." WHERE close_date > 0
                                  AND (date >= ? AND date <= ?)  AND group_id=?
                                  AND spamscore < 5 ",
                                 array($start, $end, $group_id));

            # TRANSLATORS: the arguments are dates.
            $key = sprintf(_('%1$s to %2$s'), utils_format_date($start),
                           utils_format_date($end));
            $content[$key] = db_result($result, 0,0);
          }

        $page .= '<h3>'._("Average Turnaround Time for Closed Items")."</h3>\n";
        $build = graphs_build ($content, 0, 0, 0, $graph_id);
        if ($graph_id != $build[0])
          {
            $widths = $widths . "," . $build[1];
            $graph_id = $build[0];
          }
        $page .= $build[2];

        unset($content);
        $page .= "<p>&nbsp;&nbsp;</p>\n";

        for ($counter=1; $counter<=8; $counter++)
          {
            $start=($time_now-($counter*604800));
            $end=($time_now-(($counter-1)*604800));

            $result = db_execute("SELECT count(*) FROM ".$artifact."
                                  WHERE date >= ? AND date <= ? AND group_id=?
                                  AND spamscore < 5",
                                 array($start, $end, $group_id));

            # TRANSLATORS: the arguments are dates.
            $key = sprintf(_('%1$s to %2$s'), utils_format_date($start),
                           utils_format_date($end));
            $content[$key] = db_result($result, 0,0);
          }

        $page .= '<h3>'._("Number of Items Opened")."</h3>\n";
        $build = graphs_build ($content, 0, 0, 0, $graph_id);
        if ($graph_id != $build[0])
          {
            $widths = $widths . "," . $build[1];
            $graph_id = $build[0];
          }
        $page .= $build[2];
        unset($content);
        $page .= "<p>&nbsp;&nbsp;</p>\n";

        for ($counter=1; $counter<=8; $counter++)
          {
            $start=($time_now-($counter*604800));
            $end=($time_now-(($counter-1)*604800));

            $result = db_execute("SELECT count(*) FROM ".$artifact."
                                  WHERE date <= ? AND
                                  (close_date >= ? OR close_date < 1
                                  OR close_date is null) AND group_id=?
                                  AND spamscore < 5",
                                 array($end, $end, $group_id));

            $content[utils_format_date($end)] = db_result($result, 0,0);
          }

        $page .= "\n<h3>"._("Number of Items Still Open")."</h3>\n";
        $build = graphs_build ($content, 0, 0, 0, $graph_id);
        if ($graph_id != $build[0])
          {
            $widths = $widths . "," . $build[1];
            $graph_id = $build[0];
          }
        $page .= $build[2];
        unset($content);
        $page .= "<p>&nbsp;&nbsp;</p>\n";
      }
    else
      {
# It's any of the select box field.
        $label = trackers_data_get_label($field);

        # Title + field description
        # TRANSLATORS: the argument is field label.
        $page .= '<h2>'.sprintf(_("Statistics by '%s':"), $label)."</h2>\n"
          .'<p><em>'._('Field Description:').'</em> '
          .trackers_data_get_description($field)."</p>\n";

        # Make sure it is a correct field
        if (trackers_data_is_special($field) || !trackers_data_is_used($field)
            || !trackers_data_is_select_box($field))
          $page .= '<p class="error">'
        # TRANSLATORS: the argument is field label.
            .sprintf(_("Can't generate report for field %s"), $label)."</p>\n";
        else
          {
            # First graph the bug distribution for Open item only.
            # Assigned to must be handle in a specific way.
            # Meaningless in case of status field.

            if ($field != 'status_id')
              {
                $page .= "\n<h3>".sprintf(_("Open Items"), $label)."</h3>\n";

                # First graph the bug distribution for Open item only.
                # Assigned to must be handle in a specific way.

                if ($field == 'assigned_to')
                  {
                    $sql="SELECT user.user_name, count(*) AS Count FROM user,"
                       .$artifact." "
                       ."WHERE user.user_id=".$artifact.".assigned_to AND "
                       .$artifact.".status_id = '1' AND ".$artifact
                       .".group_id=? AND ".$artifact.".spamscore < 5 "
                       ."GROUP BY user_name";
                    $params = array($group_id);
                  }
                else
                  {
                    # Check if the project has its own instance of the
                    # value set.

                    $result = db_execute("SELECT ".$artifact
                      ."_field_value.value FROM "
                      .$artifact."_field_value WHERE "
                      .$artifact."_field_value.bug_field_id=? AND "
                      .$artifact."_field_value.group_id=?",
                      array(trackers_data_get_field_id($field), $group_id));
                    if ($result && db_numrows($result) > 0)
                      $group_to_be_used = $group_id;
                    else
                      # the project does not have its own instance so
                      # use the default one (group_id  = '100')
                      $group_to_be_used = 100;

                    $sql="SELECT ".$artifact
                      ."_field_value.value, count(*) AS Count FROM ".$artifact
                      ."_field_value,".$artifact." "
                      ." WHERE ".$artifact."_field_value.value_id="
                      .$artifact.".$field AND "
                      .$artifact."_field_value.bug_field_id = ? "
                      ." AND ".$artifact."_field_value.group_id = ? AND "
                      .$artifact.".status_id = '1' AND ".$artifact
                      .".group_id=? AND spamscore < 5 "
                      ." GROUP BY value_id ORDER BY order_id";
                    $params = array(trackers_data_get_field_id($field),
                                    $group_to_be_used, $group_id);
                  }

                $result=db_execute($sql, $params);
                if ($result && db_numrows($result) > 0)
                  {
                    $build = graphs_build ($result, $field, 1, 0, $graph_id);
                    if ($graph_id != $build[0])
                      {
                        $widths = $widths . "," . $build[1];
                        $graph_id = $build[0];
                      }
                    $page .= $build[2];
                  }
                else
                  $page .= _("No item found.");
                $page .= "<p>&nbsp;&nbsp;</p>\n";
               }

            #Second  graph the bug distribution for all items
            $page .= "\n<h3>".sprintf(_("All Items"), $label)."</h3>\n";

            if ($field == 'assigned_to')
              {
                $sql="SELECT user.user_name, count(*) AS Count FROM user,"
                   .$artifact." "
                   ."WHERE user.user_id=".$artifact.".assigned_to AND "
                   .$artifact.".group_id = ? AND ".$artifact.".spamscore < 5 "
                   ."GROUP BY user_name";
                $params = array($group_id);
              }
            else
              {
                $result = db_execute("SELECT ".$artifact."_field_value.value FROM "
                  .$artifact."_field_value WHERE "
                  .$artifact."_field_value.bug_field_id = ? "
                  ." AND ".$artifact."_field_value.group_id = ?",
                  array(trackers_data_get_field_id($field), $group_id));
                if ($result && db_numrows($result) > 0)
                  $group_to_be_used = $group_id;
                else
                  # The project does not have its own instance, so
                  # use the default one (group_id  = '100').
                  $group_to_be_used = 100;

                $sql="SELECT ".$artifact
                  ."_field_value.value, count(*) AS Count FROM ".$artifact
                  ."_field_value,".$artifact." "
                  ."WHERE ".$artifact."_field_value.value_id=".$artifact
                  .".$field AND "
                  .$artifact."_field_value.bug_field_id = ? "
                  ." AND ".$artifact."_field_value.group_id = ? AND ".$artifact
                  .".group_id = ? AND spamscore < 5 "
                  ."GROUP BY value_id ORDER BY order_id";
                $params = array(trackers_data_get_field_id($field),
                                $group_to_be_used, $group_id);
              }
            $result=db_execute($sql, $params);
            if ($result && db_numrows($result) > 0)
              {
                $build = graphs_build ($result, $field, 1, 0, $graph_id);
                if ($graph_id != $build[0])
                  {
                    $widths = $widths . "," . $build[1];
                    $graph_id = $build[0];
                  }
                $page .= $build[2];
              }
            else
              $page .= _("No item found. This field is probably unused");
          }
      }
  }

$css = "";
if ($widths != '')
  $css = '/css/graph-widths.php?widths=' . substr ($widths, 1);

trackers_header(array ("title"=>_("Statistics"), "css" => $css));
print $page;
specific_reports_list($field);
trackers_footer(array());
?>
