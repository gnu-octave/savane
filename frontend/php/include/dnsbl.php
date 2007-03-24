<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: form.php 5803 2006-09-14 13:43:39Z yeupou $
#
#  Copyright 2006      (c) Mathieu Roy <yeupou--gnu.org>
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

# Import the list of dnsbl to check
# (sys_incdir should have been secured in pre.php)
$DNSBL = array();
$DNSBL_INFOURL = array();
if (file_exists($GLOBALS['sys_incdir'].'/dnsbl.txt'))
     require_once($GLOBALS['sys_incdir'].'/dnsbl.txt');


# Clever function that cleverly check who is posting data.
function dnsbl_check() 
{
  global $DNSBL, $DNSBL_INFOURL, $group_id;

  # If the list of DNSBL is empty, stop here
  if (!count($DNSBL))
    { return true; }
  
  # Assume that projects members are not spammers
  if ($group_id && member_check(0, $group_id))
    { return true; }

  # Get the real IP
  $ip = $_SERVER['REMOTE_ADDR'];

  # Reverse the ip numbers, necessary to run the requests
  # (should be IPv6 compliant)
  $ip_reversed = implode(".", array_reverse(explode(".", $ip)));

  # Go through the list
  foreach ($DNSBL as $key => $address)
    {
      if (gethostbyname("$ip_reversed.$address") != "$ip_reversed.$address")
	{
	  if ($DNSBL_INFOURL[$key])
	    { $extrainfo = ", ".$DNSBL_INFOURL[$key].$ip; }
	 
	  # FIXME: if it is a logged-in user that was blacklisted, maybe
	  # it would be worth warning the site admin.

	  # If logged-in, kill the session
	  if (user_isloggedin())
	    {
	      db_query("DELETE FROM session WHERE session_hash='".sane_cookie("session_hash")."'");
	    }
	  
	  # Log error
	  exit_log("rejected data from ".$ip." - found in ".$address.$extrainfo);
	  # Finally, block here
	  exit_error(sprintf(_("Your IP address is blacklisted %s"), $extrainfo));
	}
    }
}

?>