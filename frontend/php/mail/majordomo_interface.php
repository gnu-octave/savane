<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2002-2006 (c) CERN LCG/SPI.
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


require "../include/pre.php";

global $feedback;

# this module enables Savannah to communicate with the
# Majordomo (http://www.greatcircle.com/majordomo) mailing list
# manager. Commands to the manager are submitted via email.


#----------------------------------------------------------------
# compose message body containing the commands
# for the mailing list server
function maillist_body($addresses,$list_name,$task) 
{
	
  
  switch($task) 
    {

    case 'subscribe': 
      {
	$command="subscribe";
	break;
      }
    case 'unsubscribe': 
      {
	$command="unsubscribe";
	break;
      }
    default: 
      {
	$command="error";
      }
      
    }

  # FIXME: foreach exists only in  PHP >= 4
  foreach($addresses as $name) 
    {
      $body .= $command." ".$list_name." ".$name."\n";
    }

  $body .= "end\n";
  return($body);
}
#----------------------------------------------------------------

$addresses=array();

$sql="SELECT * FROM mail_group_list WHERE list_name='$list' ";
$result = db_query ($sql);
$group_id = db_result($result,0,'group_id');
$list_name=$list;

$res_grp = db_query("SELECT unix_group_name FROM groups WHERE group_id='$group_id'");
$group_name = db_result($res_grp,0,'unix_group_name');

if (!$group_id) 
{ exit_no_group(); }

$project = project_get_object($group_id);
exit_test_usesmail($group_id);

 
if(!$result || db_numrows($result) != 1) 
{
  exit_error('Data Base error (numrows='.db_numrows($result).')');
}

if (!$mailserver)
{
  exit_error("No mailserver configured for the group type this group belongs. Please, contact administrators");
}
# Current, mailserver cannot be guessed, it must be given in the link
#$mailserver=$project->getTypeMailingListHost().$project->getTypeMailingListAddress();  
$subject="mailing list order";


if (user_isloggedin()) 
{
  if($func== 'send') 
    {
    mail($mailserver,$subject,$body,"From: ".$user_email);
    
    #    $location .= "/".$location.$GLOBALS['sys_default_dir']."/mail/index.php";
    #$location .= "?group_id=".$group_id;
    #print $location;
    #exit;
    session_redirect($GLOBALS[sys_home]."mail/?group=".$group_name);
    exit;
    
    } 
  else 
    {
      
      $addresses[]=user_getemail(user_getid());
      $body=maillist_body($addresses,$list_name,$func);
      
      # Display mail form
      # FIXME: does not follow the CODING_STYLE (was written before the
      # CODING STYLE)
     site_project_header(array('group'=>$group_id,'title'=>'Mailing Lists for '.$project->getName(),'context'=>'mail'));

	?>
	&nbsp;
	<P>
	<H3>Send a Message to the Mailing List Server</H3>
	<P>
	<FORM ACTION="<?php print $PHP_SELF ?>?func=send" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php print $group_id ?>">
	<INPUT TYPE="HIDDEN" NAME="list_id" VALUE="<?php print $list_id ?>">
	<INPUT TYPE="HIDDEN" NAME="list" VALUE="<?php print $list_name ?>">
	<INPUT TYPE="HIDDEN" NAME="mailserver" VALUE="<?php print $mailserver ?>">

	<strong>Your Email Address:</strong><BR>
	<strong><?php print user_getemail(user_getid())?></strong>
	<INPUT TYPE="HIDDEN" NAME="user_email" VALUE="<?php print user_getemail(user_getid()); ?>">
	<P>
	<strong>Your Name:</strong><BR>
	<strong><?php 

	$my_name=user_getrealname(user_getid());

	print $my_name; ?></strong>
	<INPUT TYPE="HIDDEN" NAME="name" VALUE="<?php print $my_name; ?>">
	<P>
        <strong>Mail Server Address:</strong> <?php print $mailserver ?>
	<P>
	<strong>Subject:</strong> <?php print $subject ?> <BR>
	<P>
	<strong>Message:</strong><BR>
	<TEXTAREA NAME="body" ROWS="15" COLS="60" WRAP="HARD"><?php print $body ?></TEXTAREA>
	<P>
	<CENTER>
	<INPUT TYPE="SUBMIT" NAME="func" VALUE="send">
	</CENTER>
	</FORM>
	<?php
			     
			     }

} 
else 
{
  $HTML->header(array('title'=>_("Error")));
  
  print '<H3>For security reasons you can only send a message through
        this interface if you are logged in as a user!</H3>
        <P>
        You can subscribe to this list without logging in by
        sending an email message containing the following
        lines to the mail server <strong>'.$mailserver.'</strong>
        (you will have to replace the generic address with your email
        address).';

  $addresses[]="your.address@your.domain";
  $body=maillist_body($addresses,$list_name,$func);
  print '<pre>'.$body.'</pre>';

}
site_project_footer(array());
?>