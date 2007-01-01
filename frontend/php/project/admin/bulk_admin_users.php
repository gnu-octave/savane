<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2003 (c) Frederik Orellana <Frederik.Orellana@cern.ch>
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


require "../../include/pre.php";    

require  $GLOBALS['sys_www_topdir']."/include/account.php";
require $GLOBALS['sys_www_topdir']."/include/Email.class";

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

$err = array();
$action = $_GET['action'];


function check_file($file)
{
	if(empty($file)){
    exit_error('You have to upload something.', $file['error']);
  }
  if(!$file['name']){
    exit_error('You have to upload something.', $file['name']);
  }
  if(!is_uploaded_file($file['tmp_name'])){
    exit_error('No file found.', $file['tmp_name']);
  }
  
  $mime = preg_split("/\//",$file['type']);

  if($mime[0] != "text"){
    exit_error('File type not allowed.', $file['type']);
  }
}

function check_user_name($name)
{
	global $err;
  $retval = 1;

	if (db_numrows(db_query("SELECT user_id FROM user WHERE "
		. "user_name='".trim($name)."'")) > 0) {
    $err[$name] .= "user_exists ";
    $retval = 0;
	}

  # For compatibility with various PAM mechanisms, we restrict to 8 characters
  if(strlen(trim($name)) > 8) {
    $err[$name] .= "bad_user_name ";
    $retval = 0;
  }
  elseif(!ereg('^[_a-zA-Z0-9-]*$', trim($name))) {
    $err[$name] .= "bad_user_name ";
    $retval = 0;
  }
  
  return $retval;
}

function check_real_name($name, $real_name)
{
	global $err;
  # The MySQL field is varchar(32)
#  if(strlen(trim($real_name))>32 || !ereg('^[\. _a-zA-Z0-9-]*$', trim($real_name))) {
  if(strlen(trim($real_name))>32 || !ereg('^[\.\' _a-zA-Z0-9-]*$', trim($real_name))) {
    $err[$name] .= "bad_real_name ";
    return 0;
  }
  else{
    return 1;
  }
}

function check_email($name, $email) 
{
	global $err;
  if(/*Does full chekc of host, etc. but takes a very long time*/
     /*is_emailable_address(trim($email))*/
     validate_email(trim($email))){
    return 1;
  }
  else{
      $err[$name] .= "bad_email ";
      return 0;    
  }
}

function check_user_exists($name)
{
	global $err;
  $result = db_query("SELECT user_id FROM user WHERE " .
    "user_name='".trim($name)."'");
  $id = db_fetch_array($result);
	if (!$id['user_id']) {
    $err[$name] .= "user_exists_not ";
    return 0;
	}
  else{
    return $id;
  }
}

function check_flags($name, $flags)
{
	global $err;
  $retval = 1;
  foreach($flags as $flagname => $flag){
    $flag = trim($flag);
    $flagname = trim($flagname);
    if($flagname != "admin_flags"){
      if($flag == "N"){ $flags[$flagname] = 0;}
      elseif($flag == "T"){ $flags[$flagname] = 1;}
      elseif($flag == "TA"){ $flags[$flagname] = 2;}
      elseif($flag == "A"){ $flags[$flagname] = 3;}
      else{
        $err[$name] .= "bad_$flagname ";
        $retval = 0;
      }
    }
    else{
      if($flag == "N"){ $flags[$flagname] = "";}
      elseif($flag == "P"){ $flags[$flagname] = "P";}
      elseif($flag == "A"){ $flags[$flagname] = "A";}
      else{
        $err[$name] .= "bad_$flagname ";
        $retval = 0;
      }
    }
  }
  if($retval){
    return $flags;
  }
  else{
    return 0;
  };
}

function check_user_in_group($name)
{
	global $err;
  global $group_id;
  $retval = 1;
  $id_arr = check_user_exists($name);
  $id = $id_arr['user_id'];

	if (!user_is_group_member($id, $group_id)) {
    $err[$name] .= "user_not_in_group ";
    return 0;
	}
  else{
    return 1;
  }
}

function check_passwd($name, $password, $password1, $method)
{

 # Check a a MySQL encrypted password (or "PAM") or non-encrypted password
  # From register_valid in register.php - should be abstracted
  global $err;
  global $GLOBALS;
  # Only do password sanity checks if user does not want
  # to authenticate via PAM
  if (!$password) {
    $err[$name] .= "bad_password ";
    return 0;
  }
  if ($password != $password1 && $method == "PLAIN") {
    $err[$name] .= "bad_password ";
    return 0;
  }
  if (!account_pwvalid($password) && $method == "PLAIN") {
    $err[$name] .= "bad_password ";
    return 0;
  }
  if ($GLOBALS['sys_use_krb5'] != "no") {
      $krb5ret = krb5_login($real_name, $password);
      if($krb5ret == -1) {
      $err[$name] .= "KRB5_NOTOK ";
      return 0;
      }
      if($krb5ret == 1) {
        $err[$name] .= "KRB5_BAD_PASSWORD ";
        return 0;
      }
      if($krb5ret == 2) {
        if(is_emailable_address($real_name . "@" . $GLOBALS['sys_lists_domain'])) {
          $err[$name] .= "KRB5_BAD_USER ";
          return 0;
      }
    }
  }
  if ($method == "MYSQL") {
    # MySQL encrypted passwords have 16 characters.
    if(strlen(trim($password)) != 16) {
      $err[$name] .= "bad_password ";
      return 0;
    }
  }
  return 1;
}

function add_user($name, $real_name, $email, $password, $method)
{
 # From register_valid in register.php - should be abstracted
  global $err;
  global $GLOBALS;
  if ($GLOBALS['sys_use_pamauth'] == "yes" && ($method=="PAM" || $password=="PAM")) {
    # if user chose PAM based authentication, set his encrypted
    # password to the specified string
    $passwd='PAM';
  } elseif($method=="MYSQL") {
    $passwd=$password;
  }
  else {
    $passwd=md5($password);
  }

  $confirm_hash = substr(md5($session_hash . $passwd . time()),0,16);

  $result=db_query("INSERT INTO user (user_name,user_pw,realname,email,add_date,"
    . "status,confirm_hash) "
    . "VALUES ('$name','"
    . $passwd . "','"
#    . "$real_name','$email'," . time() . ","
    .  addslashes($real_name) . "','". addslashes($email) . "'," . time() . ","
    . "'A','" # status
    . $confirm_hash
    . "')");

  if (!$result) {
    exit_error('error',db_error());
  } else {

    $GLOBALS['newuserid'] = db_insertid($result);

    # send mail
    $message = "Thank you for registering on the "
                . $GLOBALS['sys_name'] . " web site. In order\n"
    . "to complete your registration, visit the following url:\n\n"
    . $GLOBALS['sys_https_url'].$GLOBALS['sys_home']
                . "/account/verify.php?confirm_hash=$confirm_hash\n\n"
          . "Enjoy the site.\n\n"
          . "--the " . $GLOBALS['sys_name'] . " team.\n";
    if($krb5ret == KRB5_OK) {
            $message = $message  
            . "P.S. Your kerberos password is now stored in encrypted form\n"
      . "in the " . $GLOBALS['sys_name'] . " database. For better security we advise you\n"
      . "to change your " . $GLOBALS['sys_name'] . " password as soon as possible\n";
    }

    mail($GLOBALS['form_email'],$GLOBALS['sys_name'] . " Account Registration",$message,"From: " . $GLOBALS['sys_replyto'] . "@".$GLOBALS['sys_lists_domain']);

    return 1;
  }
}

function update_users($file_name)
{
$ret=1;
  global $err;
  global $action;
  global $group_id;
  $arr = file($file_name);
  foreach($arr as $line_num => $line){
    # Ignore comments
    if($line[0] != "#"){
      $entries = split(":", $line);
        switch ($entries[0]) {

          /*------------------------*/
          case "add_user":
            if(count($entries) != 5){
              $err[trim($entries[1])] .= " bad_syntax ";
              break;
            }
            check_user_name($entries[1]);
            # For now, we allow only either PAM or MYSQL (-> use script passwd.php)
            if($entries[2]=="PAM"){
              $method="PAM";
            }
            else{
              $method="MYSQL";
            }
            check_passwd($entries[1], $entries[2], $entries[2], $method);
            check_real_name($entries[1], $entries[3]);
            check_email($entries[1], $entries[4]);
            # Add the user if so chosen and the tests went ok
            if(!$err[trim($entries[1])] && $action == "execute"){
              if(add_user($entries[1], $entries[3], $entries[4], $entries[2], $method)){
              }
              else{
                $err[$entries[1]] .= "add_user_failed ";
              };
            }
            else{
            };
            break;

          /*------------------------*/
          case "project_add_user":
            if(count($entries) != 9){
              $err[trim($entries[1])] .= " bad_syntax ";
              break;
            }
            $uid_arr=check_user_exists($entries[1]);
            $uid=$uid_arr['user_id'];
            check_user_exists($entries[1]);
            # All flags should be set to 0 (no permissions), 1 (tech), 2 (tech & admin) or 3 (admin).
            # Input values: "N", "T", "TA", "A".
            $tmp_entries=$entries;
            $flag_names=array("admin_flags", "bug_flags", "forum_flags",
            "project_flags", "patch_flags", "support_flags", "doc_flags");
            $flags=array();
            foreach($flag_names as $flagname){
              $flags[$flagname] = $tmp_entries[2];
              array_shift($tmp_entries);
            }
            $flags=check_flags($entries[1], $flags);
            if($flags && !$err[trim($entries[1])] && $action == "execute"){
              #echo $uid ."---". $group_id ."---". $flags[admin_flags] ."---". $flags[bug_flags];
              if(user_add_to_group($uid, $group_id, $flags[admin_flags],
              $flags[bug_flags], $flags[forum_flags], $flags[project_flags], $flags[patch_flags],
              $flags[support_flags], $flags[doc_flags])){
              }
              else{
                $err[$entries[1]] .= "project_add_user_failed ";
              }
            }
            break;

          /*------------------------*/
          case "project_update_user":
            if(count($entries) != 9){
              $err[trim($entries[1])] .= " bad_syntax ";
              break;
            }
            $uid_arr=check_user_exists($entries[1]);
            $uid=$uid_arr['user_id'];
            check_user_in_group($entries[1]);
            # Same as above
            $tmp_entries=$entries;
            $flag_names=array("admin_flags", "bug_flags", "forum_flags",
            "project_flags", "patch_flags", "support_flags", "doc_flags");
            $flags=array();
            foreach($flag_names as $flagname){
              $flags[$flagname] = $tmp_entries[2];
              array_shift($tmp_entries);
            }
            $flags=check_flags($entries[1], $flags);
            if($flags && !$err[trim($entries[1])] && $action == "execute"){
              if(user_add_to_group($uid, $group_id, $flags[admin_flags],
              $flags[bug_flags], $flags[forum_flags], $flags[project_flags], $flags[patch_flags],
              $flags[support_flags], $flags[doc_flags])){
              }
              else{
                $err[$entries[1]] .= "project_update_user_failed ";
              }
            }
            break;

          /*------------------------*/
            case "project_remove_user":
            $uid_arr=check_user_exists($entries[1]);
            $uid=$uid_arr['user_id'];
            check_user_in_group($entries[1]);
            if(!$err[trim($entries[1])] && $action == "execute"){
              if(user_remove_from_group($uid, $group_id)){
              }
              else{
                $err[$entries[1]] .= "project_remove_user_failed ";
              }
            }
            break;

          /*------------------------*/
          default:
            $err[$entries[1]] .= "bad_syntax ";

       }


    }
  }
  if(count($err)>1 || count($arr)==0 || !$ret){
    return 0;
  }
  else{
    return 1;
  }
}

check_file($_FILES['file']);

update_users($_FILES['file']['tmp_name']);

site_project_header(array('title'=>"Project Members Management",'group'=>$group_id,'context'=>'ahome'));


echo "You have uploaded a file: ".$_FILES['file']['name'].".<br />";
echo "Temporary location: ".$_FILES['file']['tmp_name'].".<br />";
echo "Mime type: ".$_FILES['file']['type'].".<br />";
echo "Size in bytes: ".$_FILES['file']['size'].".<br />";

$message="";

echo "<br />";
foreach($err as $key => $value){
  $key = trim($key);
  $value = trim($value);
  $message .= "$key => $value"."\n";
  echo "$key => $value";
  echo "<br />";
}

if(trim($message)==""){
  $message="All actions completed succesfully.";
}

echo "<p class=error>An email with feedback will be sent to ".
user_getemail(user_getid())."</p>";

mail(user_getemail(user_getid()), $GLOBALS['sys_name'] . " User Administration",$message,"From: " . $GLOBALS['sys_replyto'] . "@".$GLOBALS['sys_lists_domain']);

#destroy the file
unlink($_FILES['file']['tmp_name']);

$HTML->footer(array());

?>
