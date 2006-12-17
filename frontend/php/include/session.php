<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                          Derek Feichtinger <derek.feichtinger--cern.ch>
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


$G_SESSION=array();
$G_USER=array();

function session_login_valid($form_loginname,
			     $form_pw,
			     $allowpending=0,
			     $cookie_for_a_year=0,
			     $crypted_pw=0,
			     $stay_in_ssl=1)
{

  # Password is crypted if we are coming from the brother site.
  # Normally, users should not use this feature
  # if they never login at brother site.
  # FIXME: feel free to mess with PHP3 and crypt...
  global $session_hash;
  
  if (!$form_loginname || !$form_pw) 
    {
      fb(_('Missing Password Or User Name'), 1);
      return false;
    }
  
  $resq = db_query("SELECT user_id,user_pw,status FROM user WHERE "
		   . "user_name = '$form_loginname'");
  if (!$resq || db_numrows($resq) < 1) 
    {
      fb(_('Invalid User Name'), 1);
      return false;
    }

  $usr = db_fetch_array($resq);

  # Check status first:
  # if allowpending (for verify.php) then allow
  if ($allowpending && ($usr['status'] == 'P')) 
    {
      #1;
    } 
  else 
    {
      if ($usr['status'] == 'SQD') 
	{ 
	  # squad account, silently exit
	  return false;
	} 
      if ($usr['status'] == 'P') 
	{ 
	  #account pending
	  fb(_('Account Pending'), 1);
	  return false;
	} 
      if ($usr['status'] == 'D' || $usr['status'] == 'S')
	{ 
	  #account deleted
	  fb(_('Account Deleted'), 1);
	  return false;
	}
      if ($usr['status'] != 'A') 
	{
	  #unacceptable account flag
	  fb(_('Account Not Active'),1);
	  return false;
	}
    }



  # TODO: Brother site login mechanism should be implemented later for
  #       all authentication methods:
  #


  #
  # authentication method: PAM based
  # this requires the 'pam_auth' php extension from
  # http:#www.math.ohio-state.edu/~ccunning/pam_auth.html
  #
  if($usr['user_pw'] == 'PAM') 
    {
      $pam_error='';
      if(! pam_auth($form_loginname, $form_pw, &$pam_error)) 
	{
	  fb(_('Invalid Password (AFS)'), 1);
	  return false;
	}

    } 
  else if ($usr['user_pw'] == '') 
    {
      #
      # authentication method: Kerberos
      # If both user_pw and unix_pw are empty the user might
      # be able to login if she/he has a Kerberos account.
      # Update unix_pw and user_pw.
      # TODO: THE KERBEROS PASSWORD SHOULD NOT BE STORED LOCALLY!!!!
      #
      if ($GLOBALS[sys_use_krb5])
	{ $ret = krb5_login($form_loginname, $form_pw); }

      if($ret == KRB5_NOTOK) 
	{
	  fb("phpkrb5 module failure", 1);
	  return false;
	}
      if($ret == KRB5_BAD_USER) 
	{
	  fb("user is not a kerberos principal", 1);
	  return false;
	}
      if($ret == KRB5_BAD_PASSWORD) 
	{
	  fb("user is a kerberos principal but passwords do not match", 1);
	  return false;
	}
      $md5_pw = md5($form_pw);
      db_query("UPDATE user SET user_pw = '$md5_pw' WHERE user_id = ".$usr['user_id']);
    }
  else if($usr['user_pw'] == 'SSH') 
    {
      fb('This user is known but we have no way to authenticate her/him. Please ask for a password to site administrators', 1);
      return false;
      
    }
  else 
    {
      # Default authentication method:
      # MD5 encrypted password stored locally

      # For this authentication method we enable a brother site
      # login mechanism:
      # Password is crypted (crypt()) if we are coming from the brother site.
      # Normally, users shouldn't use this feature
      # unless they login at brother site one time.
      # FIXME: feel free to mess with PHP3 and crypt...
      if ($crypted_pw) 
	{
	  if (crypt($usr['user_pw'],$form_pw) != $form_pw) 
	    {
	      #invalid password or user_name
	      fb(_('Invalid Password'),1);
	      return false;
	    }
	} 
      else 
	{
	  if ($usr['user_pw'] != md5($form_pw)) 
	    {
	      #invalid password or user_name
	      fb(_('Invalid Password'),1);
	      return false;
	    }
	}

    }

 
  #create a new session
  session_set_new($usr['user_id'], $cookie_for_a_year, $stay_in_ssl);

  return true;
}

function session_checkip($oldip,$newip) 
{
  $eoldip = explode(".",$oldip);
  $enewip = explode(".",$newip);
  
  # ## require same class b subnet
  if (($eoldip[0]!=$enewip[0])||($eoldip[1]!=$enewip[1])) 
    {
      return 0;
    } 
  else 
    {
      return 1;
    }
}

function session_issecure() 
{
  return (getenv('HTTPS') == 'on');
}


function session_needsstayinssl()    
{
  return db_result(db_query("SELECT stay_in_ssl FROM session WHERE session_hash='$GLOBALS[session_hash]'"), 0, 'stay_in_ssl');
}

# Define a cookie, just for session or for a year, https-only or not
function session_cookie($name, $value, $cookie_for_a_year=0, $secure=0)
{
  $expiration = 0; # at the end of session
  if ($cookie_for_a_year == 1)
    $expiration = time() + 60*60*24*365; # for a year

  $path = $GLOBALS['sys_home'];

  # If we're using a real domain name, enable subdomain matching.
  # $domain needs an embedded dot otherwise the cookie won't be
  # accepted (eg 'localhost', cf. bug #6694 and rfc2109)
  $domain = '.' . $GLOBALS['sys_default_domain'];
  if (!eregi('[a-z0-9-]\.[a-z0-9-]', $domain))
      $domain = '';

  setcookie($name, $value, $expiration, $path, $domain, $secure);
}

# Removes a cookie. This is an alternative to setting it to an empty
# or irrelevant value, and will just prevent the browser from sending
# it again.
function session_delete_cookie($n)
{
  $expiration = time() - 3600; # in the past
  $path = $GLOBALS['sys_home'];
  # specify domain? - cf. session_cookie ^^^
  $domain = '.' . $GLOBALS['sys_default_domain'];
  if (!eregi('[a-z0-9-]\.[a-z0-9-]', $domain))
      $domain = '';
  setcookie($n, '', $expiration, $path, $domain);
}

function session_redirect($loc) 
{
  header('Location: http' . (session_issecure()?'s':'') . '://' . (session_issecure()?$GLOBALS['sys_https_host']:$GLOBALS['sys_default_domain']).$loc);
  print("\n\n");
  exit;
}

function session_require_test($req) 
{
  /* 
       Similiar tho session_require but only return 1 or 0
  */
  if (user_is_super_user()) 
    {
      return true;
    }
  if ($req['group']) 
    {
      $query = "SELECT user_id FROM user_group WHERE user_id=" . user_getid()
	 . " AND group_id=$req[group]";
      if ($req['admin_flags']) 
	{
	  $query .= " AND admin_flags = '$req[admin_flags]'";
	}
      if ((db_numrows(db_query($query)) < 1) || !$req['group']) 
	{
	  return false;
	}
    
    }
  elseif ($req['user']) 
    {
      if (user_getid() != $req['user']) 
	{
	  return false;
	}
    }
  elseif ($req['isloggedin']) 
    {
      if (!user_isloggedin()) 
	{
	  return false;
	}
    } 
  else 
    {
      return false;
    }
  
}

function session_require($req) 
{
  if (user_is_super_user()) 
    { return true; }
  
  if (!empty($req['group']))
    {
      $query = "SELECT user_id FROM user_group WHERE user_id=".user_getid()." AND group_id='{$req['group']}'";
      if (!empty($req['admin_flags']))
	{
	  $query .= " AND admin_flags='$req[admin_flags]'";	
	}
      
      if (!db_numrows(db_query($query))) 
	{
	  exit_permission_denied();
	}
      
      return true;
    }
  elseif (!empty($req['user']))
    {
      if (user_getid() != $req['user']) 
	{		  
	  exit_permission_denied();
	}

      return true;
    }
  elseif (!empty($req['isloggedin']))
    {
      if (!user_isloggedin()) 
	{
	  exit_not_logged_in();
	}
      return true;
    } 
  else 
    {
      exit_missing_param();
    }
}

function session_setglobals($user_id) 
{
  global $G_USER;
  
  #	unset($G_USER);
  
  if ($user_id > 0) 
    {
      $result=db_query("SELECT user_id,user_name FROM user WHERE user_id='$user_id'");
      if (!$result || db_numrows($result) < 1) 
	{
	  #echo db_error();
	  $G_USER = array();
	} 
      else 
	{
	  $G_USER = db_fetch_array($result);
	  #			echo $G_USER['user_name'].'<BR>';
	}
    } 
  else 
    {
      $G_USER = array();
    }
}

function session_set_new($user_id, $cookie_for_a_year=0, $stay_in_ssl=1) 
{
  global $G_SESSION;
  
  #	unset($G_SESSION);
  
  # concatinate current time, and random seed for MD5 hash
  # continue until unique hash is generated (SHOULD only be once)
  do {
    $pre_hash = time() . rand() . $_SERVER['REMOTE_ADDR'] . microtime();
    $GLOBALS['session_hash'] = md5($pre_hash);
  } 
  while (db_numrows(db_query("SELECT session_hash FROM session WHERE session_hash='$GLOBALS[session_hash]'")) > 0);
  
  # make new session entries into db
  db_query("INSERT INTO session (session_hash, ip_addr, time, user_id, stay_in_ssl) VALUES ('".addslashes($GLOBALS['session_hash'])."','".addslashes($_SERVER['REMOTE_ADDR'])."'," . time() . ",'$user_id','$stay_in_ssl')");
  
  # set global
  $res=db_query("SELECT * FROM session WHERE session_hash='$GLOBALS[session_hash]'");
  if (db_numrows($res) > 1) 
    {
      db_query("DELETE FROM session WHERE session_hash='$GLOBALS[session_hash]'");
      exit_error("ERROR","ERROR - two people had the same hash - backarrow and re-login. It should never happen again");
    } 
  else 
    {
      $G_SESSION = db_fetch_array($res);
      session_setglobals($G_SESSION['user_id']);
    }

  # if the user specified he wants only one session to be opened at a time,
  # kill the others sessions
  if (user_get_preference("keep_only_one_session"))
    {
      db_query("DELETE FROM session WHERE session_hash<>'$GLOBALS[session_hash]' AND user_id='$user_id'");
    }

  session_set_new_cookies($user_id, $cookie_for_a_year, $stay_in_ssl);
}

function session_set_new_cookies($user_id, $cookie_for_a_year=0, $stay_in_ssl=1) 
{
  # set session cookies
  # set a non-secure cookie so that Savane will automatically redirect to https
  if ($stay_in_ssl)
    {
      # 0=non-https-only
      session_cookie('redirect_to_https', 1, $cookie_for_a_year, 0);
    }
								       
  session_cookie('session_uid', $user_id, $cookie_for_a_year, $stay_in_ssl);
  session_cookie('session_hash', $GLOBALS['session_hash'], $cookie_for_a_year, $stay_in_ssl);
  session_delete_cookie('cookie_probe');
}

function session_set() 
{
  global $G_SESSION,$G_USER;
  
  #	unset($G_SESSION);
  
  # assume bad session_hash and session. If all checks work, then allow
  # otherwise make new session
  $id_is_good = 0;
  
  # here also check for good hash, set if new session is needed
  $session_hash = sane_cookie("session_hash");
  $session_uid = sane_cookie("session_uid");
  if ($session_hash && $session_uid) 
    {
      $result=db_query("SELECT * FROM session WHERE session_hash='".$session_hash."' AND user_id='".$session_uid."'");
      $G_SESSION = db_fetch_array($result);
    
      # does hash exist?
      if ($G_SESSION['session_hash']) 
	{
	  if (session_checkip($G_SESSION['ip_addr'],$_SERVER['REMOTE_ADDR'])) 
	    {
	      $id_is_good = 1;
	    } 
	} # else hash was not in database
    } # else (hash does not exist) or (session hash is bad)
  
  if ($id_is_good) 
    {
      session_setglobals($G_SESSION['user_id']);
    } 
  else 
    {
      unset($G_SESSION);
      unset($G_USER);
    }
}

function session_count ($uid) 
{
  return db_numrows(db_query("SELECT ip_addr FROM session WHERE "
			     . "user_id = '".$uid."'"));
 
}

function session_exists($uid, $hash) {
  return (db_numrows(db_query("SELECT NULL FROM session WHERE "
			     . "user_id = '".$uid."' and session_hash='".$hash."'")) == 1);
}

?>
