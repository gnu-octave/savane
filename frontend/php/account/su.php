<?php
# Enable or disable admin privileges for the current user
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2004-2006 (c) Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
#
# This file is part of Savane.
# 
# Savane is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

require_once('../include/init.php');
require_once('../include/sane.php');
register_globals_off();
#input_is_safe();
#mysql_is_safe();

# Login was asked and user can be super user? Set a cookie and that's done.
# For now, set a cookie that does not stay long, we'll see if admin complains 
# :P
extract(sane_import('get', array('action', 'uri', 'from_brother')));

if ($action == "login" && user_can_be_super_user())
{
  session_cookie("session_su", "wannabe");
  if (!empty($GLOBALS['sys_brother_domain']))
    {
      if (!$from_brother)
	{
	  header ("Location: ".su_getprotocol()."://".$GLOBALS['sys_brother_domain'].$GLOBALS['sys_home']."account/su.php?action=login&from_brother=1&uri=".urlencode($uri));
	}
      else {
	header("Location: ".su_getprotocol()."://".$GLOBALS['sys_brother_domain'].$uri);
      }
    }
  else {
    header("Location: ".$uri);
  }
}

elseif ($action == "login" && !user_is_super_user() && $from_brother)
{
  # The user is not logged at this website, go back to the brother website
  header("Location: ".su_getprotocol()."://".$GLOBALS['sys_brother_domain'].$uri);
}

elseif ($action == "logout" && user_is_super_user())
{
  #session_cookie('session_su', 'FALSE');
  session_delete_cookie("session_su");
  if (!empty($GLOBALS['sys_brother_domain']))
    {
      if (!$from_brother)
	{
	  header ("Location: ".su_getprotocol()."://".$GLOBALS['sys_brother_domain'].$GLOBALS['sys_home']."account/su.php?action=logout&from_brother=1&uri=".urlencode($uri));
	  exit;
	}
      else {
	header("Location: ".su_getprotocol()."://".$GLOBALS['sys_brother_domain'].$uri);
      }
    }
  else {
    header("Location: ".$uri);
  }
}

elseif ($action == "logout" && !user_is_super_user() && $from_brother)
{
  # The user is not logged at this website, go back to the brother website
  header("Location: ".su_getprotocol()."://".$GLOBALS['sys_brother_domain'].$uri);
}

else
{
  exit_error(_("What are you doing here?"));
}

function su_getprotocol()
{
  if (session_issecure())
    {
      return "https";
    }
  else
    {
      return "http";
    }
}
