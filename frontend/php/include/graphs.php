<?php
# Attempt to replace HTML_graphs.php with something more spartian, efficient
# and w3c-compliant.
#
# Copyright (C) 2004-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2018, 2020 Ineiev
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
# Total must be an array too, if provided.
function graphs_build ($result, $field=0, $dbdirect=1, $total=0, $id0 = 0)
{
  if (!$result)
    {
      fb(_("No data to work on, no graph will be built"), 1);
      return array($id0, '', '');
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

  # Get the total number of items.
  # Total should not be passed as argument, normally.
  if (!$total)
    {
      $totalvar = 0;
      foreach ($content as $k => $v)
        $totalvar += $v;

      $total = array();
      foreach ($content as $k => $v)
        $total[$k] = $totalvar;
    }
  else
    {
      # If total was passed as argument, no crosscheck, assume it is accurate.
      $totalvar = 1;
    }

  $id = $id0;
  $widths = "";
  $output = "";
  # Print the stats, unless $total is nul.
  # If total was passed as argument, strange result may be printed.
  if ($totalvar)
    {
      $output .= "\n\n".'<table class="graphs">'."\n";
      foreach ($content as $k => $v)
        {
          if ($total[$k] > 0)
            {
              $percent_width = round(($v / $total[$k]) * 100);
# TRANSLATORS: printing percentage.
              $percent_print = sprintf(_("%s%%"), $percent_width);
            }
          else
            {
              $percent_width = 0;
              $percent_print = _("n/a");
              $total[$k] = 0;
            }

          if ($field && $field == "assigned_to")
            $title = utils_user_link($k);
          else
            $title = $k;

          if ($percent_width > 25)
            $class = '';
          else
            $class = 'closed';

          $output .= '<tr class="half-width">
<td class="first">'.$title.'</td>
<td class="second">'
# TRANSLATORS: the arguments mean "%1$s of (total) %2$s".
  .sprintf(_('%1$s/%2$s'), $v, $total[$k]).'</td>
<td class="second">'.$percent_print.'</td>
<td class="third">'
.'<div class="prioraclosed"><div class="priori'.$class
.'" id="graph-bar'.$id.'">&nbsp;</div></div></td>
</tr>';
          $widths = $widths . "," . $percent_width;
          $id++;
        }
      $output .= "\n</table>\n\n";
    }
  else
    {
      $output .= '<p class="warn">';
      $output .= _("The total number of results is zero.");
      $output .= '</p>';
    }
  return array($id, substr ($widths, 1), $output);
}
?>
