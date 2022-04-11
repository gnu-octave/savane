<?php
# Enable and configure a group's available services
#
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007, 2008  Sylvain Beucler
# Copyright (C) 2008  Aleix Conchillo Flaque
# Copyright (C) 2017, 2020, 2022 Ineiev
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

require_once('../../include/init.php');
require_once('../../include/sane.php');

session_require(array('group' => $group_id,
                      'admin_flags' => 'A'));

$post_names = function ()
{
  $vcs = ['cvs', 'arch', 'svn', 'git', 'hg', 'bzr'];
  $use_url = [
    'bugs', 'support', 'patch', 'task', 'mail', 'download', 'homepage',
    'forum', 'extralink_documentation',
  ];
  $use_ = array_merge ($vcs, $use_url, ['news']);
  $names = ['true' => ['update'], 'specialchars' => ['dir_download']];
  foreach ($use_ as $u)
    $names['true'][] = 'use_' . $u;
  $viewvcs = [];
  foreach ($vcs as $v)
    $viewvcs[] = $v . '_viewcvs';
  $urls = array_merge ($vcs, $viewvcs, $use_url);
  foreach ($urls as $u)
    $names['specialchars'][] = 'url_' . $u;
  $names['specialchars'][] = 'url_cvs_viewcvs_homepage';
  return $names;
};

$get_cases = function ($names)
{
  $n = $names['true'];
  unset ($n[0]);
  return array_merge ($n, $names['specialchars']);
};

function str_match ($needle, $haystack)
{
  return strpos ($haystack, $needle) !== false;
}

$names = $post_names ();
extract (sane_import ('post', $names));

$query = sane_import ('get',
  ['specialchars' => 'feedback', 'digits' => 'error']
);

if ($query['feedback'])
  fb ($query['feedback'], $query['error']);

$project = project_get_object ($group_id);

# If this was a submission, make updates.
if ($update)
  {
    #FIXME: feeds the database with default values... instead of checkbox,
    # it should be select boxes "default/activated/deactivated".
    group_add_history ('Changed Activated Features', '', $group_id);
    # In the database, these all default to '1',
    # so we have to explicity set 0 (this is ugly).
    foreach (
      [
        'bugs', 'mail', 'homepage', 'download', 'patch', 'forum', 'task',
        'cvs', 'arch', 'svn', 'git', 'hg', 'bzr', 'news', 'support',
        'extralink_documentation'
      ] as $u
    )
      {
        $var = 'use_' . $u;
        if (!$$var)
          $$var = 0;
      }

    $cases = $get_cases ($names);
    $upd_list = array();

    foreach ($cases as $field)
      {
        $field_name = substr($field, 4, strlen($field));
        $type = substr($field, 0, 3);

        if ($project->CanUse($field_name)
            || ($field_name == "extralink_documentation")
            || ($field == "url_cvs_viewcvs_homepage"
                && $project->CanUse("homepage"))
            || ($field == "url_cvs_viewcvs" && $project->CanUse("cvs"))
            || ($field == "url_arch_viewcvs" && $project->CanUse("arch"))
            || ($field == "url_svn_viewcvs" && $project->CanUse("svn"))
            || ($field == "url_git_viewcvs" && $project->CanUse("git"))
            || ($field == "url_hg_viewcvs" && $project->CanUse("hg"))
            || ($field == "url_bzr_viewcvs" && $project->CanUse("bzr")))
          {
            if ($type == "use")
              $upd_list[$field] = $$field;
            elseif ($type == "url")
              {
                if ($project->CanModifyUrl($field_name))
                  $upd_list[$field] = $$field;
              }
            elseif ($type == "dir" && $field == "dir_download"
                    && $project->CanUse("download")
                    && $project->CanModifyDir("download_dir"))
              {
                $upd_list[$field] = $$field;
              }
          }
      }

    if ($upd_list)
      {
        $result = db_autoexecute (
         'groups', $upd_list, DB_AUTOQUERY_UPDATE, "group_id=?", [$group_id]
        );
        $error = intval(!$result);
        $fb = _("Update failed.");
        if ($result)
          $fb = _("Update successful.");
        $fb = rawurlencode ($fb);
        $fb = "&feedback=$fb&error=$error";
        session_redirect ("{$_SERVER['PHP_SELF']}?group=$group$fb");
      }
    else
      fb(_("Nothing to update."));
  }

site_project_header (
  ['title' => _("Select Features"),'group' => $group_id, 'context' => 'ahome']
);

$next_td = function (&$i)
{
  print ' <td class="' . utils_altrow ($i) . '">';
};
$close_td = function () { print "</td>\n"; };

function specific_line ($artifact, $explanation, $use, $increment=1)
{
  global $next_td, $close_td;
  # Just a little function to clean that part of the code, no
  # interest to generalize it.
  global $i, $project;
  if ($increment)
    $i++;
  print "<tr>\n";
  $next_td ($i);
  print "<label for=\"use_$artifact\">$explanation</td>\n";
  $next_td ($i);
  # Print the checkbox to de/activate it
  # (viewcvs cannot be activated or deactivated, they are not in the menu).
  if (str_match ("viewcvs", $artifact))
    print "---";
  else
    print form_checkbox ("use_$artifact", $use);
  $close_td ();
  # Print the default setting
  # (extralink_* does not have any default).
  $next_td ($i);
  if (!str_match ("extralink", $artifact))
    {
      $art_url = group_get_artifact_url ($artifact);
      print "<a href=\"$art_url\">$art_url</a>";
    }
  $close_td ();
  # If allowed from the group type, add a text field to put a non-standard
  # url (news cannot be activated and using a non-standard url, it would
  # broke the news system).
  $next_td ($i);

  $tail = "</td>\n</tr>\n";
  if (!$project->CanModifyUrl ($artifact))
    {
      print "---" . $tail;
      return;
    }
  if ($artifact == "homepage" || $artifact == "download"
      || str_match ("viewcvs", $artifact) || str_match ("extralink", $artifact))
    $url = $project->getUrl($artifact);
  else
    $url = $project->getArtifactUrl($artifact);
  $url = htmlspecialchars_decode ($url);
  $extra = 'size="20" title="' . _("Alternative Address") . '"';
  print form_input ("text", "url_$artifact", $url, $extra) . $tail;
}

print '<p>';
print _("You can activate or deactivate feature/artifact for your project. In
some cases, depending on the system administrator's choices, you can even use
change the URL for a feature/artifact. If the field &ldquo;alternative
address&rdquo; is empty, the standard is used.");
print "</p>\n";

print form_header ($_SERVER['PHP_SELF'])
  . form_input ("hidden", "group_id", $group_id);

print html_build_list_table_top (
  [
    _("Feature, Artifact"), _("Activated"),
    _("Standard Address"), _("Alternative Address")
  ]
);

if ($project->CanUse("homepage"))
  {
    specific_line("homepage", _("Homepage"), $project->Uses("homepage"));
    specific_line("cvs_viewcvs_homepage",
                  _("Homepage Source Code Web Browsing"), 0, 0);
  }

if ($project->CanModifyUrl("extralink_documentation"))
  specific_line("extralink_documentation", _("Documentation"),
                $project->Uses("extralink_documentation"));

function specific_can_use ($project, $artifact, $explanation)
{
  if ($project->CanUse ($artifact))
    specific_line ($artifact, $explanation, $project->Uses ($artifact));
}
specific_can_use ($project, "download", _("Download Area"));

if ($project->CanUse("download") && $project->CanModifyDir("download_dir"))
  {
    $i++; print '<tr>';
    $next_td ($i);
    print _("Download Area Directory");
    $close_td ();
    $next_td ($i);
    print "---";
    $close_td ();
    $next_td ($i);
    print $project->getTypeDir("download");
    $close_td ();
    $next_td ($i);
    print ' '
      . form_input (
          "text", "dir_download", $project->getDir("download"), 'size="20"'
        );
    print "</td>\n</tr>\n";
  }

if ($project->CanUse("cvs") || $project->CanUse("homepage"))
  {
    specific_line("cvs", _("CVS"), $project->Uses("cvs"));
    specific_line("cvs_viewcvs", _("CVS Web Browsing"), 0, 0);
  }
if ($project->CanUse("arch"))
  {
    specific_line("arch", _("GNU Arch"), $project->Uses("arch"));
    specific_line("arch_viewcvs", _("Arch Web Browsing"), 0, 0);
  }

if ($project->CanUse("svn"))
  {
    specific_line("svn", _("Subversion"), $project->Uses("svn"));
    specific_line("svn_viewcvs", _("Subversion Web Browsing"), 0, 0);
  }

if ($project->CanUse("git"))
  {
    specific_line("git", _("Git"), $project->Uses("git"));
    specific_line("git_viewcvs", _("Git Web Browsing"), 0, 0);
  }

if ($project->CanUse("hg"))
  {
    specific_line("hg", _("Mercurial"), $project->Uses("hg"));
    specific_line("hg_viewcvs", _("Mercurial Web Browsing"), 0, 0);
  }

if ($project->CanUse("bzr"))
  {
    specific_line("bzr", _("Bazaar"), $project->Uses("bzr"));
    specific_line("bzr_viewcvs", _("Bazaar Web Browsing"), 0, 0);
  }

foreach (
  [
    "mail" => _("Mailing Lists"), "forum" => _("Forum"), "news" => _("News"),
    "support" => _("Support Tracker"), "bugs" => _("Bug Tracker"),
    "task" => _("Task Tracker"), "patch" => _("Patch Tracker"),
  ] as $k => $v
)
  specific_can_use ($project, $k, $v);

$HTML->box1_bottom();
print form_footer();
site_project_footer(array());
?>
