<?php
# Membership functions.
#
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
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

require_once(dirname(__FILE__).'/database.php');

# Add or update a user to/in a group
# status is the 'admin_flags', can be pending or admin.
function member_add ($user_id, $group_id, $status='')
{
  if (!member_check($user_id,$group_id))
    {
      $result = db_autoexecute('user_group',
        array('user_id' => $user_id,
              'group_id' => $group_id,
              'admin_flags' => $status),
        DB_AUTOQUERY_INSERT);
      if ($result)
        {
          # Add different history item if the addition is in pending status.
          if ($status != "P" && $status != 'SQD')
            {
              group_add_history('Added User',user_getname($user_id),$group_id);
            }
          elseif ($status == 'SQD')
            {
               group_add_history('Created Squad',user_getname($user_id),
                                 $group_id);
            }
          else
            {
              group_add_history('User Requested Membership',
                                 user_getname($user_id),$group_id);
            }
        }
      return $result;
    }
  else
    {
      fb(_("This user is already member of the group."));
      return 0;
    }
}

# Approve a pending user for a group.
function member_approve ($user_id, $group_id)
{
  $result = db_execute("UPDATE user_group SET admin_flags=''
                        WHERE user_id=? AND group_id=?",
                       array($user_id, $group_id));
  if ($result)
    group_add_history('Approved User',user_getname($user_id),$group_id);
  return $result;
}

function member_remove ($user_id, $group_id)
{
  # Find out if it is a squad.
  $result = db_execute("SELECT admin_flags FROM user_group
                        WHERE user_id=? AND group_id=?",
                       array($user_id, $group_id));
  if (db_numrows($result) == 0)
    return false;

  $admin_flags = db_result($result, 0, 'admin_flags');

  $sql = '';
  $result = db_execute("DELETE FROM user_group WHERE user_id=? AND group_id=?",
                       array($user_id, $group_id));
  if ($result)
    {
      if ($admin_flags != 'SQD')
        {
          group_add_history('Removed User',user_getname($user_id),$group_id);
          # If it is not a squad, make sure the user is no longer associated
          # to squads of the group.
          db_execute("DELETE FROM user_squad WHERE user_id=? AND group_id=?",
                     array($user_id, $group_id));
        }
      else
        {
          group_add_history('Deleted Squad',user_getname($user_id),$group_id);
          # If it is a squad, it means that we also mark the user account as
          # shutdowned.

          db_execute ("
            UPDATE user
            SET realname = '-Deleted Squad-', status='S', user_name = ?
            WHERE user_id = ?",
            ["_$user_id", $user_id]
          );
          # We also  make sure no user is any longer associated
          # to the squad.
          db_execute("DELETE FROM user_squad WHERE squad_id=? AND group_id=?",
                     array($user_id, $group_id));
        }
    }
  return $result;
}

# Add a given member to a squad.
function member_squad_add ($user_id, $squad_id, $group_id)
{
  # First check if user is not already in.
  $result = db_execute("SELECT user_id FROM user_squad WHERE user_id=?
                        AND squad_id=? AND group_id=?",
                       array($user_id, $squad_id, $group_id));
  if (db_numrows($result))
    return false;
  # If we get here, we need to do an insert.
  $result = db_autoexecute('user_squad',
                           array('user_id' => $user_id,
                                 'squad_id' => $squad_id,
                                 'group_id' => $group_id),
                           DB_AUTOQUERY_INSERT);
  if ($result)
    {
      group_add_history('Added User to Squad '.user_getname($squad_id),
                        user_getname($user_id),
                        $group_id);

    }
  return $result;
}

# Add a given member to a squad.
function member_squad_remove ($user_id, $squad_id, $group_id)
{
  # First check if user is in.
  $result = db_execute("SELECT user_id FROM user_squad WHERE user_id=?
                        AND squad_id=? AND group_id=?",
                       array($user_id, $squad_id, $group_id));
  if (!db_numrows($result))
    return false;

  $result = db_execute("DELETE FROM user_squad WHERE user_id=?
                        AND squad_id=? AND group_id=?",
                       array($user_id, $squad_id, $group_id));
  if ($result)
    {
      group_add_history('Removed User From Squad '.user_getname($squad_id),
                        user_getname($user_id),
                        $group_id);
    }
  return $result;
}

# Get all permissions for a given user.
function member_getpermissions ($group_id, $flags, $user_id=0)
{
  if (!$user_id)
    {
      $user_id = user_getid();
    }
  if ($flags)
    {
      if (!preg_match('/^[a-z]+$/', $flags))
        die('group_getpermissions: unvalid argument flags');
      return db_result(db_execute("SELECT ".$flags."_flags FROM user_group
                                   WHERE group_id=? AND user_id=?",
                                  array($group_id, $user_id)),
                       0, $flags."_flags");
    }
}

# Check membership: by default, check only if someone is member of a project.
#
# With the flag option, you can check for specific right:
#    - the first letter of the flag should designate the tracker
#       (B = bugs, P = patch...
#        please use member_create_tracker_flag(ARTIFACT))
#    - the second letter, if specified, designate a role
#       1 = technician
#       2 = technican AND manager
#       3 = manager
#
# The strict variable permit to have a return "true" only if the flag
# found is exactly equal to the flag asked. For instance, if you are
# looking for someone who is only technician, and not techn. and manager,
# you can use that flag.
function member_check ($user_id=0, $group_id, $flag=0, $strict=0)
{
  if (!$user_id)
    {
      if (!user_isloggedin())
        # Not able to get a valid user id.
        return false;
      if (user_is_super_user())
        # Site admins: always return true.
        return true;
      $user_id = user_getid();
    }
  # determine whether someone is member of a project or not
  $result = db_execute("SELECT user_id FROM user_group WHERE user_id=?
                        AND group_id=? AND admin_flags<>'P'",
                       array($user_id, $group_id));

  if (!$result || db_numrows($result) < 1)
    # Not a member of the project.
    return false;
  if (!$flag)
    # Member of a project, not looking for specific permission.
    return true;

  # When looking for permissions, first we look at the user permission,
  # if NULL at the group def permission, if NULL at the group type def
  # permission.
  $flag_tracker = substr(strtoupper($flag), 0, 1);
  $flag_level = substr(strtoupper($flag), 1, 2);
  if (!$flag_level)
    {
      # If flag_level does not exists, the level is the tracker flag
      # (like P or A for admin_flags).
      $flag_level = $flag_tracker;
      $flag_tracker = "admin";
    }

  # Get the tracker.
  switch ($flag_tracker)
    {
    case 'B': $flag_tracker = "bugs"; break;
    case 'P': $flag_tracker = "patch"; break;
    case 'T': $flag_tracker = "task"; break;
    case 'S': $flag_tracker = "support"; break;
    case 'N': $flag_tracker = "news"; break;
    case 'C': $flag_tracker = "cookbook"; break;
    }

  $value = member_getpermissions($group_id, $flag_tracker, $user_id);
  if (!$value)
    $value = group_getpermissions($group_id, $flag_tracker);
  if (!$value)
    $value = group_gettypepermissions($group_id, $flag_tracker);
  if (!$value)
    $value = "ERROR";

  # Compare the value and what is asked.
  if ($value == $flag_level)
    {
      # If the value is equal to the flag, we are obviously in a
      # "true" case.
      dbg("accept permission (A): role found value:$value, "
          ."asked flag_level:$flag_level");
      return true;
    }
  if (!$strict && (2 == $value && (1 == $flag_level || 3 == $flag_level)))
    {
      # If the value is equal to 2 (manager and tech) if tech (1) or
      # manager (3) is asked it is "true".
      dbg("accept permission (B): role found value:$value, "
          ."asked flag_level:$flag_level");
      return true;
    }
  if (!$strict && (2 == $flag_level  && (1 == $value || 3 == $value)))
    {
      # If the value is equal to 3 (manager) or 1 (techn) if tech and
      # manager (2) is asked it is "true".
      dbg("accept permission (C): role found value:$value, "
          ."asked flag_level:$flag_level");
      return true;
    }
  # Any other case, false.
  dbg("reject permission: role found value:$value, "
      ."asked flag_level:$flag_level");
  return false;
}
# Additional function to check whether a member is pending for a group
# (partly member, so).
function member_check_pending ($user_id=0, $group_id)
{
  if (!$user_id)
    $user_id = user_getid();

  $result = db_execute("SELECT user_id FROM user_group WHERE user_id=?
                        AND group_id=? AND admin_flags='P'",
                       array($user_id, $group_id));

  if (db_numrows($result))
    return true;
  else
    return false;
}

# Find out if the member is a squad or a normal uiser.
function member_check_squad ($user_id=0, $group_id)
{
  if (!$user_id)
    $user_id = user_getid();

  $result = db_execute("SELECT user_id FROM user_group WHERE user_id=?
                        AND group_id=? AND admin_flags='SQD'",
                       array($user_id, $group_id));

  if (db_numrows($result))
    return true;
  else
    return false;
}

# Function like member_check() only checking if one specific user is allowed
# to read private content.
# This stuff was not included in member_check() to ease development, nothing
# else.
function member_check_private ($user_id=0, $group_id)
{
  if (!$user_id)
    {
      if (!user_isloggedin())
        # Not able to get a valid user id.
        return false;
      if (user_is_super_user())
        # Site admins: always return true.
        return true;
      $user_id = user_getid();
    }
  # Check if its a project admin. If so, give access.
  if (member_check($user_id, $group_id, 'A'))
    return true;

  # Determine whether someone is member allowed to read private date
  # of a project or not.
  if (db_numrows(db_execute("SELECT user_id FROM user_group WHERE user_id=?
                             AND group_id=? AND admin_flags<>'P'
                             AND privacy_flags='1'",
                            array($user_id, $group_id))))
    return true;
  return false;
}

# Permit to keep the "simple" syntax of member_check but also
# to be able to generate this simple syntax on-fly depending on
# artifact.
# (well, I admit, it's a bit strange...,
#  it could be directly inside member_check)
function member_create_tracker_flag ($artifact)
{
  switch ($artifact)
    {
    case 'bugs': return "B";
    case 'patch': return "P";
    case 'task': return "T";
    case 'support': return "S";
    case 'news': return "N";
    case 'cookbook': return "C";
    }
}

# Check if a user belongs to a group and is pending.
# Return value: The whole row of user_group.
function member_check_is_pending ($user_id, $group_id)
{
  return member_check($user_id, $group_id, 'P');
}

function member_explain_roles ($role=5)
{
  html_member_explain_roles ($role);
}
?>
