<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
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


require_once(dirname(__FILE__).'/member.php');

# Unset these globals until register_globals if off everywhere
unset($USER_IS_SUPER_USER);
$USER_RES=array();

function user_isloggedin() 
{
  global $G_USER;
  if (!empty($G_USER['user_id']))
    {
      return true;
    } 
  else 
    {
      return false;
    }
}

function user_can_be_super_user() 
{
  global $USER_IS_SUPER_USER;
  /*
		members of sys_group_id  are admins and have super-user privs site-wide
  */
  
  if (isset($USER_IS_SUPER_USER)) 
    {
      return $USER_IS_SUPER_USER;
    } 
  else 
    {
      if (user_isloggedin()) 
	{
	  $result=db_execute("SELECT * FROM user_group WHERE user_id=? AND group_id=? AND admin_flags='A'",
			     array(user_getid(), $GLOBALS['sys_group_id']));
	  if (!$result || db_numrows($result) < 1) 
	    {
	      $USER_IS_SUPER_USER=false;
	      return $USER_IS_SUPER_USER;
	    } 
	  else 
	    {
	      #matching row was found - set and save this knowledge for later
	      $USER_IS_SUPER_USER=true;
	      return $USER_IS_SUPER_USER;
	    }
	} 
      else 
	{
	  $USER_IS_SUPER_USER=false;
	  return $USER_IS_SUPER_USER;
	}
    }
}


function user_is_super_user() 
{
  // User is superuser only if he wants to, otherwise he's going to see
  // things like any other user + a link in the left menu
  if (user_can_be_super_user()
      && isset($_COOKIE["session_su"])
      && $_COOKIE["session_su"] == "wannabe")
    { 
      return true;
    }

 return false;
}

function user_ismember($group_id,$type=0) 
{
  return member_check(0, $group_id, $type);
}

# Check the user role in a project  - deprecated
function user_check_ismember($user_id, $group_id, $type=0) 
{
  return member_check($user_id, $group_id, $type);
}

# Get the groups to which a user belongs
function user_groups($uid) 
{
  $result = db_execute("SELECT * FROM user_group WHERE user_id=", array($uid));
  $arr=array();
  while ($val = db_fetch_array($result))
    {
      array_push($arr,$val['group_id']);
    };   
  return $arr;
}

# Get the email of a user
function user_get_email($uid)
{
  $result = db_execute("SELECT * FROM user WHERE user_id=?", array($uid));
  $val = db_fetch_array($result);   
  return $val['email'];
}

# Check if a user belongs to a group - deprecated
function user_is_group_member($uid, $gid)
{
  return user_check_ismember($uid, $gid);
}

# Check if a user belongs to a group and is pending - deprecated
# Return value: The whole row of user_group
function user_is_group_pending($uid, $gid)
{
  return member_check_is_pending($user_id, $group_id);
}

# Approve a pending user for a group - deprecated
function user_approve_for_group($uid, $gid)
{
  return member_approve($uid, $gid);
}

# Add or update a user to/in a group - deprecated
function user_add_to_group($uid, $gid, $admin_flags, $bug_flags,$forum_flags, $project_flags, $patch_flags, $support_flags, $doc_flags) 
{

  return member_add($uid, $gid);
}

# Remove a user from a group - deprecated
function user_remove_from_group($uid, $gid) 
{
  return member_remove($uid, $gid);
}

function user_getname($user_id=0, $getrealname=0)
{
  global $G_USER,$USER_NAMES;

  if (!$user_id && $getrealname != 0)
    {
      $user_id = user_getid();
    }

  # use current user if one is not passed in
  if (!$user_id && $getrealname == 0)
    {
      return ($G_USER?$G_USER['user_name']:"NA");
    }
  else
    {
      if ($user_id == 0) {
        if ($getrealname == 0) { return ("NA"); }
        else { return ("anonymous"); }
      }

      # else must lookup name
      if (!empty($USER_NAMES["user_$user_id"]) &&  $getrealname == 0)
	{
	  #user name was fetched previously
	  return $USER_NAMES["user_$user_id"];
	}
      elseif (!empty($USER_NAMES["realname_$user_id"]) && $getrealname != 0)
	{
	  #user name was fetched previously
	  return $USER_NAMES["realname_$user_id"];
	}
      else
	{
	  #fetch the user name and store it for future reference
	  $result = db_execute("SELECT user_id,user_name,realname FROM user WHERE user_id=?",
			       array($user_id));
	  if ($result && db_numrows($result) > 0)
	    {
	      if ($getrealname == 0)
		{
		  #valid user - store and return
		  $USER_NAMES["user_$user_id"]=db_result($result,0,"user_name");
		  return $USER_NAMES["user_$user_id"];
		}
	      else
		{
		  #valid user - store and return
		  $USER_NAMES["realname_$user_id"]=db_result($result,0,"realname");
		  return $USER_NAMES["realname_$user_id"];

		}
	    }
	  else
	    {
	      if ($getrealname == 0)
		{
		  #invalid user - store and return
		  $USER_NAMES["user_$user_id"]="<strong>Invalid User ID</strong>";
		  return $USER_NAMES["user_$user_id"];
		}
	      else
		{
		  #invalid user - store and return
		  $USER_NAMES["realname_$user_id"]="<strong>Invalid User ID</strong>";
		  return $USER_NAMES["realname_$user_id"];
		}
	    }
	}
    }
}


function user_getid($username=0)
{
  if (!$username) 
    {
      # No username, return info for the current user
      global $G_USER;
      return ($G_USER?$G_USER['user_id']:0);
    }
  else 
    {
      $result = db_execute("SELECT user_id FROM user WHERE user_name=?",
			   array($username));
      if ($result and db_numrows($result) > 0)
	return db_result($result,0,"user_id");
    }
}

function user_exists($user_id, $squad_only=false) 
{
  $result = user_get_result_set($user_id); 
  if ($result && db_numrows($result) > 0) 
    {
      if (!$squad_only)
	{ return true; }
      else if ($squad_only && db_result($result, 0, 'status') == 'SQD')
	{ return true; }
    } 
  return false;
}

#quick hack - this entire library needs a rewrite similar to groups library
# yeupou@gnu.org Please no! rewrite both the library and this one, and 
# please avoid object things without discussing about it on savannah-dev
function user_getrealname($user_id=0, $rfc822_compliant=0)
{
  $ret = user_getname($user_id, 1);
  # rfc822 requires some characters to be escaped. We usually care about this
  # compliance only in email headers.
  if ($rfc822_compliant && ereg("\.|\,|\@|\/|\\|\||\;|\!", $ret))
    { $ret = "\"$ret\""; }
  return $ret;
}

function user_getemail($user_id=0)
{
  if (!$user_id)
    { $user_id = user_getid(); }

  $result = user_get_result_set($user_id); 
  if ($result && db_numrows($result) > 0) 
    {
      return db_result($result,0,"email");
    } 
  else 
    {
      return false;
    }
}

function user_get_result_set($user_id) 
{
  #create a common set of user result sets,
  #so it doesn't have to be fetched each time
  
  global $USER_RES;
  if (empty($USER_RES["_".$user_id."_"]))
    {
      $USER_RES["_".$user_id."_"]=db_execute("SELECT * FROM user WHERE user_id=?", array($user_id));
      return $USER_RES["_".$user_id."_"];
    } 
  else
    {
      return $USER_RES["_".$user_id."_"];
    }
}

function user_get_result_set_from_unix($user_name) 
{
  #create a common set of user result sets,
  #so it doesn't have to be fetched each time
  
  global $USER_RES;
  $res = db_execute("SELECT * FROM user WHERE user_name=?", array($user_name));
  $user_id = db_result($res,0,'user_id');
  $USER_RES["_".$user_id."_"] = $res;
  return $USER_RES["_".$user_id."_"];
}       

function user_get_timezone() 
{
  if (user_isloggedin()) 
    {
      $result=user_get_result_set(user_getid());
      return db_result($result,0,'timezone');
    } 
  else 
    {
      return '';
    }
}

function user_set_preference ($preference_name,$value) 
{
  global $user_pref;
  if (user_isloggedin()) 
    {
      $preference_name=strtolower(trim($preference_name));
      if (db_numrows(db_execute("SELECT NULL FROM user_preferences WHERE user_id=? AND preference_name=?",
				array(user_getid(), $preference_name))) > 0)
	$result=db_autoexecute('user_preferences',
			       array('preference_value' => $value),
			       DB_AUTOQUERY_UPDATE,
			       "user_id=? AND preference_name=?",
			       array(user_getid(), $preference_name));
      else
	$result=db_autoexecute('user_preferences',
			       array('user_id' => user_getid(),
				     'preference_name' => $preference_name,
				     'preference_value' => $value),
			       DB_AUTOQUERY_INSERT);
      
# Update the Preference cache if it was setup by a user_get_preference
      if (isset($user_pref)) 
	{ $user_pref[$preference_name] = $value; }
      
      return true;
    }

  return false;
}
function user_unset_preference ($preference_name) 
{
  global $user_pref;
  if (user_isloggedin()) {
    $preference_name=strtolower(trim($preference_name));
    $result=db_execute("DELETE FROM user_preferences WHERE user_id=? AND preference_name=? LIMIT 1",
		     array(user_getid(), $preference_name));

    # Update the Preference cache if it was setup by a user_get_preference
    if (isset($user_pref))
      { unset($user_pref[$preference_name]); }

    dbg("Remove pref $preference_name");
    return true;
  }
  return false;
}




function user_get_preference ($preference_name, $user_id=false) 
{
  global $user_pref;

  if ($user_id) 
    {
      # looking for information without being the user
      $res = db_execute("SELECT preference_value FROM user_preferences
			 WHERE user_id=? AND preference_name=?",
			array($user_id, $preference_name));
      if(db_numrows($res) > 0)
	return db_result($res,0,'preference_value');
      else
	return null;
    }

  if (user_isloggedin()) 
    {
      $preference_name=strtolower(trim($preference_name));

      # First check to see if we have already fetched the preferences
    if ($user_pref) {
      if (!empty($user_pref["$preference_name"])) {
	#we have fetched prefs - return part of array
	return $user_pref["$preference_name"];
      } else {
	#we have fetched prefs, but this pref hasn't been set
	return false;
      }
    } else {
      #we haven't returned prefs - go to the db
      $result=db_execute("SELECT preference_name,preference_value"
			 . " FROM user_preferences"
			 . " WHERE user_id=?",
			 array(user_getid()));
      if (db_numrows($result) < 1) {
	return false;
      } else {
	#iterate and put the results into an array
	for ($i=0; $i<db_numrows($result); $i++) {
	  $user_pref[db_result($result,$i,'preference_name')]=db_result($result,$i,'preference_value');
	}
	if (isset($user_pref["$preference_name"])) {
	  #we have fetched prefs - return part of array
	  return $user_pref["$preference_name"];
	} else {
	  #we have fetched prefs, but this pref hasn't been set
	  return false;
	}
      }
    }
  } else {
    return false;
  }
}

# Find out if the user use the vote, very similar to 
# trackers_votes_user_remains_count
function user_use_votes ($user_id=false) 
{
  if (!$user_id)
    { $user_id = user_getid(); }

  $sql = "SELECT vote_id FROM user_votes WHERE user_id='$user_id'";
  $result = db_query($sql);
  if (db_numrows($result) > 0) 
    {
      return true;
    }
  return false;
}

## 
# Like context_guess, this will set a AUDIENCE constant that could be used
# later to determine specific page context, for instance to know which
# recipes are relevant.
# This should be called once in pre.
# The valid AUDIENCE names depends on include/trackers/cookbook.php
# except that role specific "managers" and "technicians" will not be handled
# here, as you can be both manager and technician, and since it implies that
# you are member of the pro
function user_guess ()
{
  # $group_id should have been sanitized already
  global $group_id;

  # Not logged in?
  if (!user_isloggedin())
    {
      define('AUDIENCE', 'anonymous');
      return true;
    }

  # On a non-group page?
  if (!$group_id)
    {
      define('AUDIENCE', 'loggedin');
      return true;
    }
  
  # On a group page without being member of the group?
  if (!member_check(0, $group_id))
    {
      define('AUDIENCE', 'loggedin');
      return true;
    }

  # Being member
  define('AUDIENCE', 'members');
  return true;      

}

# Function that should always be used to remove an user account
# This function should always be used in a secure context, when user_id
# is 100% sure.
# Best is to not to pass the user_id argument unless necessary
function user_delete ($user_id=false, $confirm_hash=false)
{
  if (!$user_id)
    { $user_id = user_getid(); }

  # Serious deal, serious check of credentials: allowed only to superuser
  # and owner of the account
  if (!user_is_super_user() && $user_id != user_getid())
    {  
      exit_permission_denied();
    }

  # If self-destruct, the correct confirm_hash must be provided
  if (!user_is_super_user())
    {
      $confirm_hash_test = " confirm_hash=? AND ";
      $confirm_hash_param = array($confirm_hash);
    }
  else
    {
      $confirm_hash_test = '';
      $confirm_hash_param = array();
    }


  $success = db_autoexecute('user',
   array('user_pw' => '!',
	 'realname' => '-Deleted Account-',
	 'status' => 'S',
	 'email' => 'idontexist@nowhere.net',
	 'confirm_hash' => '',
	 'authorized_keys' => '',
	 'people_view_skills' => '0',
	 'people_resume' => '',
	 'timezone' => 'GMT',
	 'theme' => '',
	 'gpg_key' => '',
	 'email_new' => ''),
   DB_AUTOQUERY_UPDATE,
   "$confirm_hash_test user_id=?", array_merge($confirm_hash_param, array($user_id)));
  
  if ($success)
    { 
      # Remove from any groups, if by any chances this was not done before
      # (normally, an user must quit groups before being allowed to delete his
      # account)
      db_execute("DELETE FROM user_group WHERE user_id=?", array($user_id));
      db_execute("DELETE FROM user_squad WHERE user_id=?", array($user_id));

      # Additionally, clean up sessions, remove prefs
      db_execute("DELETE FROM user_bookmarks WHERE user_id=?", array($user_id));
      db_execute("DELETE FROM user_preferences WHERE user_id=?", array($user_id));
      db_execute("DELETE FROM user_votes WHERE user_id=?", array($user_id));
      db_execute("DELETE FROM session WHERE user_id=?", array($user_id));
      
      fb(_("Account deleted.")); 
      return true;
    }

  fb(_("Failed to update the database."), 1); 
  return false;
}
