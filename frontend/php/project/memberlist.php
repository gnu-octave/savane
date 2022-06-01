<?php
# List members.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2000-2003 Free Software Foundation
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2000-2006 Lorenzo Hernandez Garcia-Hierro
#                                      <lorenzohgh--tuxedo-es.org>
# Copyright (C) 2017, 2018, 2022 Ineiev
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

require_once ('../include/init.php');
require_directory ("trackers");

function specific_print_role ($row, $title)
{
  # TRANSLATORS: these roles are explained in
  # html.php:html_member_explain_roles.
  $roles = [
    1 => _("technician"), 3 => _("manager"), 2 => _("techn. & manager")
  ];
  if (isset ($roles[$row]))
    print "$title {$roles[$row]},<br />";
}

$detailed = sane_get("detailed");
$form_grp = sane_all("form_grp");

if ((!$group_id) && $form_grp)
  $group_id = htmlentities($form_grp);

site_project_header (
  [
    'title' => _("Project Memberlist"), 'group' => $group_id,
    'context' => 'people'
  ]
);

$checked = '';
if ($detailed)
  $checked = " selected='selected'";
$form_opening = '<form action="' . htmlentities ($_SERVER['PHP_SELF'])
  . '#options" method="get">';
$form_submit = '<input class="bold"  type="submit" value="'
  . _("Apply") . '" />';
$selector = '<select title="' . _("basic or detailed")
  . '" name="detailed"><option value="0">'
  # TRANSLATORS: this is used in context of "Browse with the %s memberlist."
  . _("basic") . "</option>\n<option value='1'$checked>"
  # TRANSLATORS: this is used in context of "Browse with the %s memberlist."
  . _("detailed") . "</option>\n</select>\n"
  . form_hidden (['group' => $group]);
# TRANSLATORS: the argument is "basic" or "detailed".
print html_show_displayoptions (
  sprintf (_("Browse with the %s memberlist."), $selector),
  $form_opening, $form_submit
);

print '<p>';
if (member_check (0, $group_id))
  print
    _("Note that you can &ldquo;watch&rdquo; a member of your\nproject. "
      . "It allows you, for instance, to be the backup of someone when "
      . "they are\naway from the office, or to review all their activities "
      . "on this project: you\nwill receive a copy of their mail "
      . "notifications related to this\nproject.");
else
  printf (
    _("If you would like to contribute to this project by\nbecoming a member, "
      . "use the <a href=\"%s\">request for inclusion</a> form."),
    "${sys_home}my/groups.php?words=" . group_getname ($group_id)
    . '#searchgroup'
  );
print "</p>\n";

$activeness = [1, 0];
foreach ($activeness as $active)
  {
    if ($detailed)
      {
        $res_memb = db_execute ("
          SELECT
            user.user_name AS user_name, user.user_id AS user_id,
          user.realname AS realname, user.add_date AS add_date,
          user.people_view_skills AS people_view_skills,
          user_group.admin_flags AS admin_flags,
          user_group.bugs_flags AS bugs_flags,
          user_group.task_flags AS task_flags,
          user_group.patch_flags AS patch_flags,
          user_group.news_flags AS news_flags,
          user_group.support_flags AS support_flags, user.email AS email
          FROM user, user_group
          WHERE
            user.user_id = user_group.user_id AND user_group.group_id = ?
            AND user_group.admin_flags <> 'P' AND user_group.onduty = ?
          ORDER BY user.user_name",
          [$group_id, $active]
        );
        member_explain_roles ();
        # FIXME: yeupou--gnu.org 2003-11-07
        # The best would be to print non-specific roles but roles in any case.
        # It requires more, so we will see if there are people interested
        # in that or not.
        print '<p>'
          . _("On this page are only presented specific roles, roles\nwhich "
              . "are not attributed by default when joining this project.")
          . "</p>\n";
      }
    else
      $res_memb = db_execute ("
        SELECT
          user.user_name AS user_name, user.user_id AS user_id,
          user.realname AS realname, user.add_date AS add_date,
          user.people_view_skills AS people_view_skills,
          user_group.admin_flags AS admin_flags, user.email AS email
        FROM user, user_group
        WHERE
          user.user_id = user_group.user_id AND user_group.group_id = ?
          AND user_group.admin_flags <> 'P' AND user_group.onduty = ?
        ORDER BY user.user_name",
        [$group_id, $active]
      );
    $title_arr = ["&nbsp;", _("Member")];
    if ($detailed)
      $title_arr[] = _("Specific Role");
    # yeupou--gnu.org, 2004-11-04, remove email from this page; this data
    # is accessible elsewhere, via links. It saves us extra tests on whether
    # users want to hide their email or not.
    $title_arr[] = _("Resume and Skills");
    if (user_ismember ($group_id))
      $title_arr[] = _("Watch");

    if  (db_numrows($res_memb) == 0)
      continue;

    if ($active)
      print '<h2>' . _('Active members on duty') . "</h2>\n";
    else
      print '<h2>' . _('Currently inactive members') . "</h2>\n";
    echo html_build_list_table_top ($title_arr);

    $i = 1;
    while ($row_memb = db_fetch_array ($res_memb))
      {
        if ($row_memb['admin_flags'] != 'P')
          {
            $i++;
            $color = utils_altrow ($i);
            if ($row_memb['admin_flags'] == 'A')
              $color = "boxhighlight";
            print "\n\t<tr class=\"$color\">\n";
            print "\t\t";

            if ($row_memb['admin_flags'] == 'A')
              {
                if ($group_id != $sys_group_id)
                  {
                    $icon = "project-admin";
                    $icon_alt = _("Project Administrator");
                  }
                else
                  {
                    $icon = "site-admin";
                    $icon_alt = _("Site Administrator");
                  }
              }
            elseif ($row_memb['admin_flags'] == 'SQD')
              {
                $icon = "people";
                $icon_alt = _("Squad");
              }
            else
              {
                $icon = "project-member";
                $icon_alt = _("Project Member");
              }

            print "\t\t<td><span class='help' title=\"$icon_alt\">"
              . html_image (
                  "roles/$icon.png", ['alt' => $icon_alt, 'class' => 'icon']
                )
              . "</span></td>\n<td>"
              . utils_user_link ($row_memb['user_name'], $row_memb['realname'])
              . "</td>\n";
            if ($detailed)
              {
                print "\t\t<td align=\"middle\">";
                if ($row_memb['admin_flags'] == 'A')
                  print _("project admin");
                else
                  foreach (
                    [
                      'support_flags' => _("support tracker"),
                      'bugs_flags' => _("bug tracker"),
                      'task_flags' => _("task tracker"),
                      'patch_flags' => _("patch tracker"),
                      'news_flags' => _("news tracker")
                    ] as $idx => $val
                  )
                    specific_print_role ($row_memb[$idx], $val);
                print "</td>\n";
              }

            print "\t\t<td align='middle'>";
            if ($row_memb['people_view_skills'] == 1)
              print "<a href=\"$sys_home"
                . "people/resume.php?user_id={$row_memb['user_id']}\">"
                . _("View Skills") . "</a>";
            else
              # TRANSLATORS: this is a label shown when user's skills
              # are unavailable.
              print _("Set to private");
            print "</td>\n";
          # Watch
           if (user_ismember($group_id))
             {
               $thisuser = user_getid ();
               $is_watched = trackers_data_is_watched (
                 $thisuser, $row_memb['user_id'], $group_id
               );
               print "\t\t<td align='middle'>";
               if ($row_memb['user_id'] != $thisuser && !$is_watched)
                  # Permit to add a watchee only if not already in the watched
                  # list.
                  print "<a href=\"${sys_home}my/groups.php?"
                    . "func=addwatchee&amp;group_id=$group_id&amp;watchee_id="
                    . $row_memb['user_id'] . "\">" . _("Watch partner")
                    . "</a>";
                else
                  print "---";
                print "</td>\n";
              }
            print "\t<tr>\n";
          }
      }
    print "\t</table>\n";
  } # foreach ($activeness as $active)

if ($project->getGPGKeyring ())
  {
    print '<p>';
    printf (
      _("You may also be interested in the <a href=\"%s\">GPG Keys of\n"
        . "all members</a>"),
      "memberlist-gpgkeys.php?group=$group"
    );
    print "</p>\n";
  }
site_project_footer ([]);
?>
