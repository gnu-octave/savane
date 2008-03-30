<?php
# Attempt to replace HTML_graphs.php with something more spartian, efficient
# and w3c-compliant.
# 
# <one line to give a brief idea of what this does.>
# 
# Copyright 2004-2006 (c) Mathieu Roy <yeupou--gnu.org>
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


# It can accept db result directy or an array.
# Total must be an array too, if provided
function graphs_build ($result, $field=0, $dbdirect=1, $total=0)
{
  if (!$result)
    {
      fb(_("No data to work on, no graph will be built"), 1);
      return;
    }

  if ($dbdirect)
    {
      $content = array();
      for ($i=0; $i < db_numrows($result) ; $i++)
	{
	  $content[db_result($result, $i, 0)] = db_result($result, $i, 1);
	}
    }
  else
    {
      $content = $result;
    }

  # Get the total number of items
  # Total should not be passed as argument, normally
  if (!$total)
    {
      $totalvar = 0;
      while(list($k, $v)=each($content))
	{
	  $totalvar += $v;
	}

      $total = array();
      reset($content);
      while(list($k, $v)=each($content))
	{
	  $total[$k] = $totalvar;
	}
    }
  else
    {
      # If total was passed as argument, no crosscheck, assume it is accurate
      $totalvar = 1;
    }

  # Print the stats, unless $total is nul
  # If total was passed as argument, strange result may be printed.
  if ($totalvar)
    {
      print "\n\n".'<table style="width: 98%;">'."\n";
      reset($content);
      while(list($k, $v)=each($content))
	{
          if ($total[$k] > 0)
            {
              $percent_width = round(($v / $total[$k]) * 100);
              $percent_print = sprintf(_("%s%%"), $percent_width);
            }
          else
            {
              $percent_width = 0;
              $percent_print = _("n/a");
              $total[$k] = 0;
            }

	  if ($field && $field == "assigned_to")
	    { $title = utils_user_link($k); }
	  else
	    { $title = $k; }


	  if ($percent_width > 25)
	    { $class = ''; }
	  else
	    { $class = 'closed'; }


	  print '<tr style="width: 50%;">'.
	    '<td style="width: 15%; text-align: right; vertical-align: center;">'.$title.'</td>'.
	    '<td style="width: 5%; text-align: right; vertical-align: center;">'.sprintf(_("%s/%s"), $v, $total[$k]).'</td>'.
	    '<td style="width: 5%; text-align: right; vertical-align: center;">'.$percent_print.'</td>'.
	    '<td style="width: 75%; text-align: left; vertical-align: center;"><div style="width: 95%;" class="prioraclosed"><div class="priori'.$class.'" style="padding: 1px; line-height: 1em; width: '.$percent_width.'%; border-top: 0; border-left: 0; border-bottom: 0;">&nbsp;</div></div></td>'.
	    '</tr>';
	}
      print "\n</table>\n\n";
    }
  else
    {
      print '<p class="warn">';
      print _("The total of results is zero.");
      print '</p>';
    }
}
