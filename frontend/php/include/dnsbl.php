<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 2006 (c) Mathieu Roy <yeupou--gnu.org>
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


# Import the list of dnsbl to check
# (sys_incdir should have been secured in pre.php)
$DNSBL = array();
$DNSBL_INFOURL = array();
$file = utils_get_content_filename ('dnsbl');
if ($file != null)
     require_once($file);

require_once(dirname(__FILE__).'/session.php');

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
	      session_logout();
	    }
	  
	  # Log error
	  exit_log("rejected data from ".$ip." - found in ".$address.$extrainfo);
	  # Finally, block here
	  exit_error(sprintf(_("Your IP address is blacklisted %s"), $extrainfo));
	}
    }
}
