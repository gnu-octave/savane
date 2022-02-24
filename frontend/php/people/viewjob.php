<?php
# Vew jobs.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2017, 2022 Ineiev
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
require_once('../include/people/general.php');
require_once('../include/vars.php');
extract (sane_import ('get', ['digits' => ['group_id', 'job_id']]));

if (!$group_id)
  exit_no_group ();
if (!$job_id)
  exit_error (_("Error"), _("Posting ID not found"));

# Fill in the info to create a job.
site_project_header (
  ['title' => _("View a Job"), 'group' => $group_id, 'context' => 'home']
);

# For security, include group_id.
$result = db_execute ("
  SELECT
    groups.group_name, groups.type, groups.unix_group_name,
    j.title AS job_title, j.date, j.description, j.category_id AS category_id,
    jc.name AS category_name, js.name AS status_name,
    user.user_name, user.user_id
  FROM
    people_job j, groups, people_job_status js, people_job_category jc, user
  WHERE
    jc.category_id = j.category_id AND js.status_id = j.status_id
    AND user.user_id = j.created_by AND groups.group_id = j.group_id
    AND j.job_id = ? AND j.group_id = ?",
  array($job_id, $group_id)
);

$finish_page = function ()
{
  site_project_footer ([]);
  exit (0);
};

if (!$result || db_numrows ($result) < 1)
  {
    print db_error();
    fb (_("POSTING fetch FAILED"), 1);
    print '<h1>' . _("No Such Posting For This Project") . "</h1>\n";
    $finish_page ();
  }
$project = project_get_object ($group_id);
$user_name = db_result ($result, 0, 'user_name');
$group_link = "<a href=\"{$GLOBALS['sys_home']}projects/"
  . db_result ($result, 0, 'unix_group_name') . '">'
  . db_result ($result, 0, 'group_name') . '</a>';
print '<h1 class=toptitle>';
printf (
  # TRANSLATORS: the first argument is job title (like Tester or Developer),
  # the second argument is group name (like GNU Coreutils).
  _('%1$s for %2$s'), db_result ($result, 0, 'job_title'), $group_link
);
print"</h1>\n<p><span class='preinput'>" . _("Category:")
  . "</span> <a href=\"{$GLOBALS['sys_home']}people/?categories[]="
  . db_result ($result, 0, 'category_id') . '">'
  . db_result ($result, 0, 'category_name') . "</a><br />\n"
  . '<span class="preinput">' . _("Submitted By:") . '</span> '
  . "<a href='{$GLOBALS['sys_home']}users/$user_name'>$user_name</a><br />\n"
  . '<span class="preinput">' . _("Date:") . '</span> '
  . utils_format_date (db_result ($result, 0, 'date'))
  . "<br />\n<span class=\"preinput\">" . _("Status:") . '</span> '
  . db_result ($result, 0, 'status_name') . "</p>\n";

if ($project->getTypeDescription())
  print "<p>" . markup_full ($project->getTypeDescription()) . "</p>\n";
print "<p>";
if ($project->getLongDescription())
  print markup_full (htmlspecialchars ($project->getLongDescription()));
elseif ($project->getDescription())
  print $project->getDescription();
print "</p>\n";
$license = $project->getLicense();
print '<p><span class="preinput">' . _("License") . '</span> ';
$lic_label = $LICENSE[$license];
$lic_url = $LICENSE_URL[$license];
if ($lic_url != "0")
  $lic_label = "<a href=\"{$lic_url}\" target=\"_blank\">$lic_label</a>";
print "$lic_label</p>\n";
$devel_status_id = $project->getDevelStatus();
$devel_status = "&lt;" . _("Invalid status ID") . "&gt;";
if (isset ($DEVEL_STATUS[$devel_status_id]))
  $devel_status = $DEVEL_STATUS[$devel_status_id];
print "<span class=\"preinput\"><br />\n"
  . _("Development Status") . "</span>: $devel_status";

print '<p><span class="preinput">'
  . _("Details (job description, contact ...):") . "</span></p>\n";
print markup_full (htmlspecialchars (db_result ($result, 0, 'description')));
print '<h2>' . _("Required Skills:") . "</h2>\n";
print people_show_job_inventory($job_id);
$finish_page ();
?>
