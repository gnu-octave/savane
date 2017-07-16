<?php
# List mailing lists for a group.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
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

if (!$group_id)
  exit_no_group();

exit_test_usesmail($group_id);
site_project_header(array('group'=>$group_id, 'context'=>'mail'));

if (user_isloggedin() && user_ismember($group_id))
  $public_flag='0,1';
else
  $public_flag='1';

$result = db_execute("SELECT * FROM mail_group_list WHERE group_id=? "
                     ."AND is_public IN ($public_flag) ORDER BY list_name ASC",
                     array($group_id));
$rows = db_numrows($result);

if (!$result || $rows < 1)
  {
# TRANSLATORS: The argument is Savannah group (project) name.
    printf ('<h2>'._("No Lists found for %s").'</h2>',$project->getName());
    print '<p>'
    ._("Project administrators can add mailing lists using the admin interface.")
    .'</p>';
    $HTML->footer(array());
    exit;
  }

# The <br /> in front is here to put some space with the menu.
# Please, keep it.
print '<br />';

for ($j = 0; $j < $rows; $j++)
  {
    $is_public = db_result($result,$j,'is_public');
    $pass = db_result($result,$j,'password');
    $list = db_result($result, $j, 'list_name');

    # Pointer to listinfo or to the mailing list address, if no listinfo is found.
    if ($project->getTypeMailingListListinfoUrl($list)
        && $project->getTypeMailingListListinfoUrl($list) != "http://")
      $default_pointer = $project->getTypeMailingListListinfoUrl($list);
    else
      unset($default_pointer);

    print '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME
          .'.theme/contexts/mail.png" border="0" alt="" /> <a href="'
          .$default_pointer.'">'.$list.'</a> ';

    print '&nbsp;&nbsp;<em>'.db_result($result, $j, 'description').'</em>';
    print "\n".'<p class="smaller">';

    $text = '';
    if ($is_public && $project->getTypeMailingListArchivesUrl($list)
        && $project->getTypeMailingListArchivesUrl($list) != "http://")
      {
        # Pointer to archives
        $text .= sprintf(_("To see the collection of prior posting to the list,
visit the <a href=\"%s\">%s archives</a>."),
                      $project->getTypeMailingListArchivesUrl($list), $list);
        $text .= "\n".'<br />';
      }

    if (!$is_public && $project->getTypeMailingListArchivesPrivateUrl($list)
        && $project->getTypeMailingListArchivesPrivateUrl($list) != "http://")
      {
        # Pointer to archives.
        $text .= sprintf (_("To see the collection of prior posting to the
list, visit the <a href=\"%s\">%s archives</a> (authorization required)."),
                $project->getTypeMailingListArchivesPrivateUrl($list), $list);
        $text .= "\n".'<br />';
      }

    if ($project->getTypeMailingListAddress($list))
      {
        # TRANSLATORS: the argument is mailing list address.
        $text .= sprintf(
              _("To post a message to all the list members, write to %s."),
              utils_email($project->getTypeMailingListAddress($list)));
        $text .= "\n".'<br />';
      }
    else
      $text .= '<br /><span class="error">'
            ._("No mailing-list address was found, the configuration of the
server is probably broken, contact the admins!").'</span><br />';

    # Subscribe, unsubscribe:
    # if these fields are empty, go back on the listinfo page.
    if ($project->getTypeMailingListSubscribeUrl($list)
        && $project->getTypeMailingListSubscribeUrl($list) != "http://"
        && $project->getTypeMailingListUnsubscribeUrl($list)
        && $project->getTypeMailingListUnsubscribeUrl($list) != "http://")
      {
        if ($project->getTypeMailingListSubscribeUrl($list)
            && $project->getTypeMailingListSubscribeUrl($list) != "http://")
          {
            $text .= "<a href=\""
                  .$project->getTypeMailingListSubscribeUrl($list)."\">"
                  ._("Subscribe to the list.")."</a>";
            $text .= "\n".'<br />';
          }
        if ($project->getTypeMailingListUnsubscribeUrl($list)
            && $project->getTypeMailingListUnsubscribeUrl($list) != "http://")
          {
            $text .= "<a href=\""
                   .$project->getTypeMailingListUnsubscribeUrl($list)."\">"
                   ._("Unsubscribe from the list.")."</a>";
            $text .= "\n".'<br />';
          }
      }
    elseif ($project->getTypeMailingListListinfoUrl($list)
             && $project->getTypeMailingListListinfoUrl($list) != "http://")
      {

        $text .= sprintf(_("You can (un)subscribe to the list by following
instructions on the <a href=\"%s\">list information page</a>."),
                         $project->getTypeMailingListListinfoUrl($list));
        $text .= "\n".'<br />';
      }

    if ($project->getTypeMailingListAdminUrl($list)
        && $project->getTypeMailingListAdminUrl($list) != "http://")
      {
        $text .= sprintf(_("Project administrators can use the
<a href=\"%s\">administrative interface</a> to manage the list."),
               $project->getTypeMailingListAdminUrl($list));
        $text .= "\n".'<br />';
      }
    if (substr ($text, -6) == '<br />')
      $text = substr ($text, 0, -6);
    print $text.'</p>'."\n";
  }
site_project_footer(array());
?>
