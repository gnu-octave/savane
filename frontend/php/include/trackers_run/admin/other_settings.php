<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2001-2002 (c) Laurent Julliard, CodeX Team, Xerox
#
#  Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
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

#input_is_safe();
#mysql_is_safe();

extract(sane_import('post', array('submit', 'form_preamble')));

require_directory("project");

$is_admin_page='y';

if (!$group_id)
{
  exit_no_group();
}

if (!member_check(0,$group_id, member_create_tracker_flag(ARTIFACT).'2') && !user_ismember($group_id,'A'))
{
  # Must be at least Bug Admin or Project Admin
  exit_permission_denied();
}

if ($submit)
{

  group_add_history ('Changed Tracking System Settings','',$group_id);

  # Update the Bug table now
  $result = db_execute('UPDATE groups SET '.ARTIFACT.'_preamble=? '
		       .'WHERE group_id=?',
		       array(htmlspecialchars($form_preamble), $group_id));

  if (!$result)
    {
      fb(_("Update failed"));
    } 
  else if (db_affected_rows($result) < 1)
    {
      fb(_("NO DATA CHANGED!"));
    }
  else
    {
      fb(_("SUCCESSFUL UPDATE"));
    }

}


##################
# Show main page #
##################

trackers_header_admin(array ('title'=>_("Other Settings")));

$res_grp = db_execute("SELECT * FROM groups WHERE group_id=?", array($group_id));
if (db_numrows($res_grp) < 1)
{
  exit_no_group();
}
$row_grp = db_fetch_array($res_grp);

echo '<h3>'._("Item Post Form Preamble").'</h3>';
echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';

# FIXME: preamble should not be in the groups table!!
echo '<input type="hidden" name="group_id" value="'.$group_id.'" />';
echo '<span class="preinput">'.sprintf(_("Introductory message showing at the top of the item submission form %s:"), markup_info("rich")).'</span>
<br /><textarea cols="70" rows="8" wrap="virtual" name="form_preamble">'.$row_grp[ARTIFACT.'_preamble'].'</textarea>';

echo '
<div class="center"><input type="submit" name="submit" value="'._("Submit").'" /></div>
</form>';

trackers_footer(array());
