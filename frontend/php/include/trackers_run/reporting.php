<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2001-2002 (c) Laurent Julliard, CodeX Team, Xerox
#
#  Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                          Yves Perrin <yves.perrin--cern.ch>
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


if (!$group_id)
{ exit_no_group(); }

# Give access to this page to anybody: people can already collect such
# information since they are able to browse the trackers.
# It does not make sense to restrict access to this data, in this spirit.
# But if some specific installation need to do so for whatever reason,
# we can make that a configuration option.

trackers_header(array ("title"=>_("Statistics")));

# FIXME: work in progress. We need to study how to mix infos coming from
# trackers_data_...()
# If artifact is not defined, we want statistics of all trackers.
if (ARTIFACT == "project")
{ $artifact = "bugs,task,patch,support"; }
else
{ $artifact = ARTIFACT; }


# Specific function that list possible report.
function specific_reports_list ($thisfield=0)
{
  global $group_id;

  if ($thisfield)
    {
      print '<p>&nbsp;</p><h3>'._("Other statistics:").'</h3>';
    }
  print "<ul>\n";

  if ($thisfield != 'aging')
    {
      print "<li><a href=\"reporting.php?group_id=$group_id&amp;field=aging\">"._("Aging Statistics")."</a></li>\n";
    }
  while ($field = trackers_list_all_fields())
    {

      if (trackers_data_is_special($field) || $field  == $thisfield)
	{ continue; }

      if (trackers_data_is_select_box($field) && trackers_data_is_used($field))
	{

	  print "<li><a href=\"reporting.php?group_id=$group_id&amp;field=$field\">";
	  printf(_("Statistics by '%s'"), trackers_data_get_label($field));
	  print "</a></li>\n";
	}
    }

  print "</ul>\n";
}

# Initialize the global data structure before anything else
trackers_init($group_id);

if ($field)
{
  if ($field == 'aging')
    {
      print '<h3>'._("Aging statistics:").'</h3>';

      $time_now=time();
      unset($content);

      for ($counter=1; $counter<=8; $counter++)
	{

	  $start=($time_now-($counter*604800));
	  $end=($time_now-(($counter-1)*604800));

	  $sql="SELECT round(avg((close_date-date)/86400), 0) FROM ".$artifact." WHERE close_date > 0 AND (date >= $start AND date <= $end)  AND group_id='$group_id' AND spamscore < 5 ";
	  $result = db_query($sql);

          $key = sprintf(_("%s to %s"), format_date($sys_datefmt,($start)), format_date($sys_datefmt,($end)));
	  $content[$key] = db_result($result, 0,0);
	}

      print '<h5>'._("Average Turnaround Time For Closed Items").'</h5>';
      graphs_build($content,0,0);
      unset($content);
      print "<p>&nbsp;&nbsp;</p>";

      for ($counter=1; $counter<=8; $counter++)
	{

	  $start=($time_now-($counter*604800));
	  $end=($time_now-(($counter-1)*604800));

	  $sql="SELECT count(*) FROM ".$artifact." WHERE date >= $start AND date <= $end AND group_id='$group_id' AND spamscore < 5";
	  $result = db_query($sql);

          $key = sprintf(_("%s to %s"), format_date($sys_datefmt,($start)), format_date($sys_datefmt,($end)));
	  $content[$key] = db_result($result, 0,0);
	}

      print '<h5>'._("Number of Items Opened").'</h5>';
      graphs_build($content,0,0);
      unset($content);
      print "<p>&nbsp;&nbsp;</p>";

      for ($counter=1; $counter<=8; $counter++)
	{

	  $start=($time_now-($counter*604800));
	  $end=($time_now-(($counter-1)*604800));

	  $sql="SELECT count(*) FROM ".$artifact." WHERE date <= $end AND (close_date >= $end OR close_date < 1 OR close_date is null) AND group_id='$group_id' AND spamscore < 5";
	  $result = db_query($sql);

	  $content[format_date($sys_datefmt,($end))] = db_result($result, 0,0);
	}

      print "\n<h5>"._("Number of Items Still Open")."</h5>";
      graphs_build($content,0,0);
      unset($content);
      print "<p>&nbsp;&nbsp;</p>";

    }
  else
    {

# It's any of the select box field.
	  $label = trackers_data_get_label($field);

          # Title + field description
          print '<h3>'.sprintf(_("Statistics by '%s':"), $label).'</h3>'.
            '<p><em>'._('Field Description:').'</em> '.trackers_data_get_description($field).'</p>';

	  # Make sure it is a correct field
	  if (trackers_data_is_special($field) || !trackers_data_is_used($field) ||
	      !trackers_data_is_select_box($field) )
	    {

	      print '<p class="error">'.sprintf(_("Can't generate report for field %s"), $label).'</p>';

	    }
	  else
	    {

	      # First graph the bug distribution for Open item only.
              # Assigned to must be handle in a specific way.
              # Meaningless in case of status field.

	      if ($field != 'status_id')
		{

	      print "\n<h5>".sprintf(_("Open Items"), $label)."</h5>";

	      # First graph the bug distribution for Open item only.
              # Assigned to must be handle in a specific way.

	      if ($field == 'assigned_to')
		{

		  $sql="SELECT user.user_name, count(*) AS Count FROM user,".$artifact." ".
		     "WHERE user.user_id=".$artifact.".assigned_to AND ".
		     "".$artifact.".status_id = '1' AND ".$artifact.".group_id='$group_id' AND ".$artifact.".spamscore < 5 ".
		     "GROUP BY user_name";
		}
	      else
		{

                  # check if the project has its own instance of the
                  # value set

                  $sql="SELECT ".$artifact."_field_value.value FROM "
                       .$artifact."_field_value WHERE "
                       .$artifact."_field_value.bug_field_id='".                                        trackers_data_get_field_id($field)."' AND ".                                    $artifact."_field_value.group_id='$group_id'";

                  $result=db_query($sql);
                  if ($result && db_numrows($result) > 0) {
                     $group_to_be_used = $group_id;
                  } else {
                     # the project does not have its own instance so
                     # use the default one (group_id  = '100')
                     $group_to_be_used = 100;
                  }

                  $sql="SELECT ".$artifact."_field_value.value, count(*) AS Count FROM ".$artifact."_field_value,".$artifact." ".
		     "WHERE ".$artifact."_field_value.value_id=".$artifact.".$field AND ".
		     "".$artifact."_field_value.bug_field_id='".
		     trackers_data_get_field_id($field)."' AND ".
		     $artifact."_field_value.group_id=$group_to_be_used AND ".
		     "".$artifact.".status_id = '1' AND ".$artifact.".group_id='$group_id' AND spamscore < 5 ".
		     "GROUP BY value_id ORDER BY order_id";
		}

	      $result=db_query($sql);
	      if ($result && db_numrows($result) > 0)
		{ graphs_build($result, $field); }
	      else
		{ print _("No item found."); }


	      print "<p>&nbsp;&nbsp;</p>";

                 }

	      #Second  graph the bug distribution for all items

	      print "\n<h5>".sprintf(_("All Items"), $label)."</h5>";

	      if ($field == 'assigned_to')
		{

		  $sql="SELECT user.user_name, count(*) AS Count FROM user,".$artifact." ".
		     "WHERE user.user_id=".$artifact.".assigned_to AND ".$artifact.".group_id='$group_id' AND ".$artifact.".spamscore < 5 ".
		     "GROUP BY user_name";



		}
	      else
		{

                  $sql="SELECT ".$artifact."_field_value.value FROM "
                       .$artifact."_field_value WHERE "
                       .$artifact."_field_value.bug_field_id='".
                        trackers_data_get_field_id($field)."' AND ".
                        $artifact."_field_value.group_id='$group_id'";

                  $result=db_query($sql);
                  if ($result && db_numrows($result) > 0) {
                     $group_to_be_used = $group_id;
                  } else {
                     # the project does not have its own instance so
                     # use the default one (group_id  = '100')
                     $group_to_be_used = 100;
                  }

		  $sql="SELECT ".$artifact."_field_value.value, count(*) AS Count FROM ".$artifact."_field_value,".$artifact." ".
		     "WHERE ".$artifact."_field_value.value_id=".$artifact.".$field AND ".
		     "".$artifact."_field_value.bug_field_id='".
		     trackers_data_get_field_id($field)."' AND ".
		     $artifact."_field_value.group_id=$group_to_be_used AND ".$artifact.".group_id='$group_id' AND spamscore < 5 ".
		     "GROUP BY value_id ORDER BY order_id";
		}
	      $result=db_query($sql);
	      if ($result && db_numrows($result) > 0)
		{ graphs_build($result, $field); }
	      else
		{ print _("No item found. This field is probably unused"); }

	    }
	}

    }

  specific_reports_list($field);
  trackers_footer(array());

?>
