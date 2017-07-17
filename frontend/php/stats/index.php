<?php
# Show site statistics.
# 
# Copyright (C) 2004-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2004-2006 Yves Perrin <yves.perrin--cern.ch>
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

require_once('../include/init.php');
require_once('../include/sane.php');
require_once('../include/stats/general.php');
require_once('../include/calendar.php');
require_once('../include/graphs.php');

register_globals_off();

extract(sane_import('get',
  array('update', 'since_month', 'since_day', 'since_year',
	          'until_month', 'until_day', 'until_year')));

site_header(array('title'=>"Statistics"));

######################## BETWEEN TWO DATES

if (empty($update))
{
  # Replace since_ and util_ parameters
  $since_month = date("m")-1;
  $since_day = date("d");
  $since_year = date("Y");

  $until_month = date("m");
  $until_day = date("d");
  $until_year = date("Y");

  $hour = date("H");
  $min = date("i");
}
else
{
  # If the user selected date, assume he speaks of completed days
  $hour = 0;
  $min = 0;
}

$since = mktime($hour,$min,0,$since_month, $since_day, $since_year);
$until = mktime($hour,$min,0,$until_month, $until_day, $until_year);

$form_opening = '<form action="'.htmlentities ($_SERVER['PHP_SELF'])
                .'#options" method="GET">';
$form_submit = '<input class="bold" value="'._("Apply")
               .'" name="update" type="submit" />';
# TRANSLATORS: The arguments are two dates.
# Example: "From 12. September 2005 till 14. September 2005"
print html_show_displayoptions(
       sprintf(_('From %1$s till %2$s.'),
               calendar_selectbox("day",$since_day,"since_day")
               .calendar_selectbox("month",$since_month,"since_month")
               .'<input type="text" value="'.htmlentities($since_year)
               .'" name="since_year" size="4" maxlength="4" />',
               calendar_selectbox("day",$until_day,"until_day")
               .calendar_selectbox("month",$until_month,"until_month")
               .'<input type="text" value="'.htmlentities($until_year)
               .'" name="until_year" size="4" maxlength="4" />'),
               $form_opening, $form_submit);

# TRANSLATORS: The arguments are two dates.
# Example: "From 12. September 2005 till 14. September 2005"
print '
<h3>'.html_anchor(sprintf(_('From %1$s till %2$s'),utils_format_date($since),
                          utils_format_date($until)),"between")
.'</h3>
';

if ($since > $until)
{
  print '<p class="error">'
  ._("The begin of the period you asked for is later than its end.").'</p>
';
}

print '
<h4>'._("Accounts").'</h4>
<ul>
';

$count_users = stats_getusers();
$count_groups = stats_getprojects();

$content = array();
$total = array();

$count = stats_getusers("add_date>='$since' AND add_date<='$until'");
$key = _("New users");
$content[$key] = $count;
$total[$key] = $count_users;
print '<li>'.sprintf(ngettext("%s new user", "%s new users", $count),
                               $count)."</li>\n";

$count = stats_getprojects("","",
              "register_time>='$since' AND register_time<='$until'");
$key = _("New groups");
$content[$key] = $count;
$total[$key] = $count_groups;
print '
<li> '.sprintf(ngettext("%s new project", "%s new projects",
                                        $count),$count)."</li>\n</ul>\n";

print '<h4>'._("New users and new groups / total")."</h4>\n";
graphs_build($content,0,0,$total);

$content = array();
$total = 0;

$total_patch = stats_getitems("patch");
$total_task = stats_getitems("task");
$total_bugs = stats_getitems("bugs");
$total_support = stats_getitems("support");


print '
<h4>'._("Trackers").'</h4>
';
if (($total_patch + $total_task + $total_support + $total_bugs > 0))
  print "<ul>\n";

### FIXME: ngettext force us to split several sentences in different bit.
### It may be severily unsuitable for proper translation.

$content = array();
$content_total = array();

$total_open = 0;
if ($total_support > 0)
{
  $count = stats_getitems("support", 0, "date>='$since' AND date<='$until'");
  $total = $count;
  $count_open = stats_getitems("support", 3, "date>='$since' AND date<='$until'");
  $total_open += $count_open;

  print '<li>'
  # TRANSLATORS: The next two msgids form one sentence.
  # The HTML comment in the second part is used to differentiate it
  # from the same texts used with other first part.
  .sprintf(ngettext("%s new support request,", "%s new support requests,",
                    $count), $count)." "
  .sprintf(ngettext("including %s already closed<!-- support request -->",
                    "including %s already closed<!-- support requests -->",
           $count_open),
           $count_open)."</li>\n";
  $key = _("Support requests");
  $content[$key] = $count;
  $content_total[$key] = $total_support;
}

if ($total_bugs > 0)
{
  $count = stats_getitems("bugs", 0, "date>='$since' AND date<='$until'");
  $total += $count;
  $count_open = stats_getitems("bugs", 3, "date>='$since' AND date<='$until'");
  $total_open += $count_open;

  print '<li>'
  # TRANSLATORS: The next two msgids form one sentence.
  # The HTML comment in the second part is used to differentiate it
  # from the same texts used with other first part.
  .sprintf(ngettext("%s new bug,", "%s new bugs,", $count), $count)." "
  .sprintf(ngettext("including %s already closed<!-- bug -->",
                    "including %s already closed<!-- bugs -->", $count_open),
           $count_open)
  ."</li>\n";
  $key = _("Bugs");
  $content[$key] = $count;
  $content_total[$key] = $total_bugs;
}

if ($total_task > 0)
{
  $count = stats_getitems("task", 0, "date>='$since' AND date<='$until'");
  $total += $count;
  $count_open = stats_getitems("task", 3, "date>='$since' AND date<='$until'");
  $total_open += $count_open;

  print '<li>'
  # TRANSLATORS: The next two msgids form one sentence.
  # The HTML comment in the second part is used to differentiate it
  # from the same texts used with other first part.
  .sprintf(ngettext("%s new task,", "%s new tasks,", $count), $count)." "
  .sprintf(ngettext("including %s already closed<!-- task -->",
                    "including %s already closed<!-- tasks -->",
           $count_open),
           $count_open)."</li>\n";
  $key = _("Tasks");
  $content[$key] = $count;
  $content_total[$key] = $total_task;
}

if ($total_patch > 0)
{
  $count = stats_getitems("patch", 0, "date>='$since' AND date<='$until'");
  $total += $count;
  $count_open = stats_getitems("patch", 3, "date>='$since' AND date<='$until'");
  $total_open += $count_open;

  print '<li>'
  # TRANSLATORS: The next two msgids form one sentence.
  # The HTML comment in the second part is used to differentiate it
  # from the same texts used with other first part.
  .sprintf(ngettext("%s new patch,", "%s new patches,", $count), $count)." "
  .sprintf(ngettext("including %s already closed<!-- patch -->",
                    "including %s already closed<!-- patches -->", $count_open),
           $count_open)."</li>\n";
  $key = _("Patches");
  $content[$key] = $count;
  $content_total[$key] = $total_patch;
}

if ($total_patch < 1 &&  $total_task  < 1 && $total_support < 1 && $total_bugs < 1)
{
  print _("The trackers look unused, no items were found");
}
else
{
  print "<li>"
  # TRANSLATORS: The next two msgids form one sentence.
  # The HTML comment in the second part is used to differentiate it
  # from the same texts used with other first part.
  .sprintf(ngettext("%s new item,", "%s new items,", $total), $total)." "
  .sprintf(ngettext("including %s already closed<!-- item -->",
                    "including %s already closed<!-- items -->", $total_open),
           $total_open)."</li>\n";

  print '</ul>
<h4>'._("New items per tracker / tracker total")."</h4>\n";
  graphs_build($content,0,0,$content_total);
  unset($content,$content_total);
}

print "</p>\n";

print '<p>&nbsp;</p>';

##################### GENERAL
print '
<h3>'.html_anchor(_("Overall"),"overall").'</h3>
';

print '
<h4>'._("Accounts").'</h4>
<ul>
';

$content = array();

print '<li>'.sprintf(ngettext("%s user", "%s users", $count_users),
                               $count_users)."</li>\n";
$count_groups_private = stats_getprojects("","0");
print '<li>'
  # TRANSLATORS: The next two msgids form one sentence.
  .sprintf(ngettext("%s project,", "%s projects,", $count_groups),
           $count_groups)." "
  .sprintf(ngettext("including %s in private state",
                    "including %s in private state", $count_groups_private),
         $count_groups_private)."</li>\n</ul>\n";

$result = db_query("SELECT type_id,name FROM group_type ORDER BY name");
while ($eachtype = db_fetch_array($result))
{
  $content[$eachtype['name']] = stats_getprojects($eachtype['type_id']);
}

print '
<h4>'._("Projects per group type")."</h4>\n";
graphs_build($content,0,0);
unset($content);

print '<h4>'._("Trackers").'</h4>
<ul>
';

$content = array();

$count = $total_support;
$total = $count;
$total_open = 0;
if ($count > 0)
{
  $count_open = stats_getitems("support", 1);
  $total_open += $count_open;

  print '<li>'
  # TRANSLATORS: The next two msgids form one sentence.
  # The HTML comment in the second part is used to differentiate it
  # from the same texts used with other first part.
  .sprintf(ngettext("%s support request,", "%s support requests,", $count), $count)." "
  .sprintf(ngettext("including %s still open<!-- support request -->",
                    "including %s still open<!-- support requests -->",
                    $count_open), $count_open)."</li>\n";

  $content[_("Support requests")] = $count;
}

$count = $total_bugs;
$total += $count;
if ($count > 0)
{
  $count_open = stats_getitems("bugs", 1);
  $total_open += $count_open;

  print '<li>'
  # TRANSLATORS: The next two msgids form one sentence.
  # The HTML comment in the second part is used to differentiate it
  # from the same texts used with other first part.
  .sprintf(ngettext("%s bug,", "%s bugs,", $count), $count)." "
  .sprintf(ngettext("including %s still open<!-- bug -->",
                    "including %s still open<!-- bugs -->", $count_open),
           $count_open)
  ."</li>\n";

  $content[_("Bugs")] = $count;
}

$count = $total_task;
$total += $count;
if ($count > 0)
{
  $count_open = stats_getitems("task", 1);
  $total_open += $count_open;

  print '<li>'
  # TRANSLATORS: The next two msgids form one sentence.
  # The HTML comment in the second part is used to differentiate it
  # from the same texts used with other first part.
  .sprintf(ngettext("%s task,", "%s tasks,", $count), $count)." "
  .sprintf(ngettext("including %s still open<!-- task -->",
                    "including %s still open<!-- tasks -->", $count_open),
           $count_open)."</li>\n";

  $content[_("Tasks")] = $count;
}

$count = $total_patch;
$total += $count;
if ($count > 0)
{
  $count_open = stats_getitems("patch", 1);
  $total_open += $count_open;

  print '<li>'
  # TRANSLATORS: The next two msgids form one sentence.
  # The HTML comment in the second part is used to differentiate it
  # from the same texts used with other first part.
  .sprintf(ngettext("%s patch,", "%s patches,", $count), $count)." "
  .sprintf(ngettext("including %s still open<!-- patch -->",
                    "including %s still open<!-- patches -->", $count_open),
           $count_open)."</li>\n";

  $content[_("Patches")] = $count;
}
print "<li>"
  # TRANSLATORS: The next two msgids form one sentence.
  # The HTML comment in the second part is used to differentiate it
  # from the same texts used with other first part.
.sprintf(ngettext("%s item,", "%s items,", $total), $total)." "
.sprintf(ngettext("including %s still open<!-- item -->",
                  "including %s still open<!-- items -->", $total_open),
         $total_open)."</li>\n</ul>\n";

print '
<h4>'._("Items per tracker")."</h4>\n";
graphs_build($content,0,0);
unset($content);

print '
<h4>'._("Most popular themes").'</h4>
';

# Get the more popular themes. 7 at most, all superior to 0%
$themes_list = theme_list();
$popular_themes = array();

// Check if there's already at least one user registered
if ($count_users)
{
  print "<ul>\n";
  while (list(,$theme) = each($themes_list))
    {
      // Get the number of users of the theme
      unset($count);
      $count = stats_getthemeusers(strtolower($theme));
      if (strtolower($theme) == strtolower($GLOBALS['sys_themedefault']))
	{ 
	  // If it is the default theme, add the users that use the default
	  $count += stats_getthemeusers("");
	}
      
      // Compute the percentage of users using it
      $percent = ($count / $count_users) * 100;
      
      // Store it only if superior to 0
      if (round($percent))
	{
	  $popular_themes[$theme] = $percent;
	}
    }

  // Print the most popular theme
  arsort($popular_themes);
  $themes = '';
  while (list($theme,$percent) = each($popular_themes))
    print ("<li>".$theme." (".round($percent)."%)</li>\n");
  print "</ul>\n";
} else {
  print _('No users yet.');
}

print '
</p>';

site_footer(0);
?>
