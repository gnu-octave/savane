<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
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


define('ARTIFACT', 'task');
$no_redirection=1;
require_once('../include/init.php');
require_once('../include/Group.class');
require_once('../include/sendmail.php');

session_require(array('isloggedin' => '1'));

require_once('../include/vars.php');

extract(sane_import('request',
  array('show_confirm', 'rand_hash', 'i_agree', 'i_disagree',
	'form_purpose', 'form_required_sw', 'form_comments',
	'form_full_name', 'form_license', 'form_license_other',
	'group_type')));

# No group type = only 1 available, no choice
if (empty($group_type))
     $group_type = 1;

$project=project_get_object($group_id);
if ($show_confirm && $rand_hash)
{
  # Forgot to select the group type
  if ($group_type == 100) 
    {
      exit_error(_("No group type has been selected. Use the back button."));
    }
  
  # Create the page header just like if there was not yet any group_id
  $group_id_not_yet_valid = $group_id;
  unset($group_id);
  $HTML->header(array('title'=>_("Final Step: Confirmation")));
  $group_id = $group_id_not_yet_valid;


  db_query_escape("UPDATE groups SET type='%s' WHERE group_id='%s' AND rand_hash='__%s'",
		  $group_type, $group_id, $rand_hash);

  $result=db_query_escape("SELECT * FROM groups WHERE group_id='%s' AND rand_hash='__%s'",
		   $group_id, $rand_hash);

  print '
<div align="center"><span class="warn">'._("Do not click the back button").'</span></div>

<form action="'.$_SERVER['PHP_SELF'].'" method="post">
<input type="hidden" name="group_id" value="'.$group_id.'" />
<input type="hidden" name="rand_hash" value="'.$rand_hash.'" />

<h5>'._("Description:").'</h5>
<textarea name="form_purpose" wrap="virtual" cols="70" rows="15">'.db_result($result,0,'register_purpose').'</textarea>

<h5>'._("Other Software Required:").'</h5>
<textarea name="form_required_sw" wrap="virtual" cols="70" rows="6">'.db_result($result,0,'required_software').'</textarea>

<h5>'._("Other Comments:").'</h5>
<textarea name="form_comments" wrap="virtual" cols="70" rows="4">'.db_result($result,0,'other_comments').'</textarea>

<h5>'._("Full Name:").'</h5>
<input size="40" maxlength="40" type="text" name="form_full_name" value="'.db_result($result,0,'group_name').'" />

<h5>'._("System Name:").'</h5>'
    . db_result($result,0,'unix_group_name')
    . '
<h5>'._("Project Type").':</h5>
   ';
  print show_group_type_box('group_type',$group_type);
  print '
<h5>'._("License").':</h5>
<select name="form_license">
   ';

  while (list($k,$v) = each($LICENSE))
    {
      print "<option value=\"$k\"";
      if ($k==db_result($result,0,'license'))
	{
	  print ' selected';
	}
      print ">$v</option>\n";
    }
  print '</select>';
  print '
<h5>'._("Other License:").'</h5>
<textarea name="form_license_other" wrap="virtual" cols="60" rows="10">'
    . db_result($result,0,'license_other') . '</textarea>
<p>'
    ._("If you confirm it, your project registration will be saved, waiting for a system administrator to approve it. If you reject it, this registration will be discarded.").'
</p>
<div class="center">
<input type="submit" name="i_agree" value="'
    ._("Confirm").'">
<input type="submit" name="i_disagree" value="'
    ._("Reject").'">
</div>
</form>';

  $HTML->footer(array());

}
else if ($i_agree && $group_id && $rand_hash)
{
  # complete the db entries
  $result = db_query_escape(
    "UPDATE groups SET status='P',
       register_purpose='%s',
       required_software='%s',
       other_comments='%s',
       group_name='%s', license='%s',
       license='%s',
       license_other='%s',
       type='%s'
     WHERE group_id='%s' AND rand_hash='__%s'",
      htmlspecialchars($form_purpose),
      htmlspecialchars($form_required_sw),
      htmlspecialchars($form_comments),
      $form_full_name, $form_license,
      htmlspecialchars($form_license),
      htmlspecialchars($form_license_other),
      htmlspecialchars($group_type),
    $group_id, $rand_hash
  );

  if (db_affected_rows($result) < 1)
    {
      exit_error(_("Unable to update database, please contact administrators"));
    }

  # make the current user an admin
  $result = member_add(user_getid(), $group_id, "A");

  # admin    bugs  forums
  if (!$result)
    {
      exit_error(_("Setting you as project admin failed"));
    }

  $user_realname = user_getrealname(user_getid());
  $user_email = user_getemail(user_getid());
  $unix_name = group_getunixname($group_id);
  $sql_type = db_execute("SELECT name FROM group_type WHERE type_id=?", array($group_type));
  $type = db_result($sql_type,0,'name');
  $type_base_host = $project->getTypeBaseHost();
  $type_admin_email_address = $project->getTypeAdminEmailAddress();

  # get site-specific content. It will define confirmation_gen_email()
  utils_get_content("register/confirmation_mail");

  $message = confirmation_gen_email ($type_base_host, $user_realname, $user_email, $type_admin_email_address, $form_license, $form_license_other, $form_full_name, $unix_name, $type, $form_purpose, $form_required_sw, $form_comments);

  $message_user = "$message"
     ."\n\n\n\n"
     ."*"._("In case you have to register your project again").".\n\n"
     .sprintf(_("Please be aware that if your registration does not fulfill all the requirements, the %s administrators may ask you to register your project again."),$GLOBALS['sys_name'])
     ._("You can use the following URL to do a new registration starting with the values used in this registration process.")."\n"
     ._("Copy and paste AS ONE SINGLE URL the following content:")."\n"
     ."----RERegistration-URL-BEGIN-----\n"
     ."http://".$project->getTypeBaseHost().$GLOBALS['sys_home']."register/basicinfo.php?re_purpose=".rawurlencode($form_purpose)."&re_require_sw=".rawurlencode($form_required_sw)."&re_comments=".rawurlencode($form_comments)."&re_full_name=".rawurlencode($form_full_name)."&re_unix_name=".rawurlencode($unix_name)."\n"
     ."----RERegistration-URL-END-------\n";

  $message_admin = "A new project has been registered at ".$GLOBALS['sys_name']." 
This project account will remain inactive until a site admin approves or discards the registration.


= Registration Administration =

While this item will be useful to track the registration process, *approving or discarding the registration must be done using the specific [".$GLOBALS['sys_https_url'].$GLOBALS['sys_home']."siteadmin/groupedit.php?group_id=".$group_id." Group Administration] page*, accessible only to site administrators, effectively *logged as site administrators* (superuser):

* [".$GLOBALS['sys_https_url'].$GLOBALS['sys_home']."siteadmin/groupedit.php?group_id=".$group_id." Group Administration]


= Registration Details =

* Name: *".$form_full_name."*
* System Name:  *".$unix_name."*
* Type: ".$type."
* License: ".$LICENSE[$form_license];

  if ($form_license_other) {
    $message_admin .= " (".$form_license_other.")";
  }
  
  $message_admin .= "

----

==== Description: ====
".$form_purpose."\n\n";

 if ($form_required_sw) {
   $message_admin .= "\n==== Other Software Required: ====\n".$form_required_sw."\n\n";
 }
 
 if ($form_comments) {
   $message_admin .= "\n==== Other Comments: ====\n".$form_comments."\n\n";
 }
 
  # a mail for the submitter
  sendmail_mail($type_admin_email_address,
		$user_email,
		"submission of $form_full_name - $type_base_host",
		$message_user,
		0,0,0,
		$type_admin_email_address);


  # a mail for the moderators staff!
# Done automatically by the task tracker
#  sendmail_mail($user_email,
#		$type_admin_email_address,
#		"submission of $form_full_name - $type_base_host",
#		$message_admin,
#		0,0,0,
#		$user_email);

  # special case: the unix_group_name fit the sys_unix_group_name:
  #   this project must be activated.
  if ($GLOBALS['sys_group_id'] == $group_id)
    {
      # short desc not translated; not sure if we want this database info
      # to appear translated, it may be misleading for site admins 
      # (they may suppose it is a translated string like the rest of the 
      # interface, that would appear in another language to another user)
      db_query_escape(
        "UPDATE groups SET status='A',
          short_description='%s'
         WHERE group_id='%s' AND rand_hash='__%s'",
	"This project is dedicated to the administration of this site.",
	$group_id, $rand_hash);


      # We also add a specific field for the task tracker
      # (we need to copy the None field)
      db_query("INSERT INTO task_field_value (bug_field_id,group_id,value_id,value,description,order_id,status)
        VALUES (103,$group_id,100,'None','',10,'P')");
      db_query("INSERT INTO task_field_value (bug_field_id,group_id,value_id,value,description,order_id,status)
        VALUES (103,$group_id,1,'Project Approval','Pending project registration',11,'P')");

      # We also need to make the task tracker post restriction of comment
      # accepting posting from logged-in users, otherwise they wont be able
      # to comment their registration
      db_query("INSERT INTO groups_default_permissions (group_id,task_rflags) VALUES ($group_id,300)");

      
      # We add the default recipes grabbed from update/1.3/
      $query = db_query("INSERT INTO cookbook (group_id, status_id , severity , privacy , category_id , submitted_by , assigned_to , date , summary , details , resolution_id) VALUES ('$group_id', '3', '5', '1', '100', '100', '100', '1133253163', 'Getting back lost password', '".mysql_real_escape_string("If you lose your password simply visit the login page and click \"Lost Your Password?\". 

A confirmation mail will be sent to the address we have on file for you. Then, load the URL in the email to reset your password.")."', '1')");
      $item_id = db_insertid($query);
      db_query("INSERT INTO cookbook_context2recipe (recipe_id, group_id, audience_anonymous , audience_loggedin , audience_members , audience_technicians , audience_managers , context_project , context_homepage , context_cookbook , context_download , context_support , context_bugs , context_task , context_patch , context_news , context_mail , context_cvs , context_arch , context_svn , context_my , context_stats , context_siteadmin , context_people , subcontext_browsing , subcontext_postitem , subcontext_edititem , subcontext_search , subcontext_configure ) VALUES ('$item_id', '$group_id', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '1', '0', '0')");

      $query = db_query("INSERT INTO cookbook (group_id, status_id , severity , privacy , category_id , submitted_by , assigned_to , date , summary , details , resolution_id) VALUES ('$group_id', '3', '5', '1', '100', '100', '100', '1133253163', 'Why log in?', '".mysql_real_escape_string("The log-in mechanism used in these webpages is just a simple way of keeping track of users who work in projects hosted in this site. When a user logs in, she/he is conducted to a personal page that lists the projects she/he is collaborating with and any pending tasks that she/he might have.

If you are involved in any project, if you do not intend to post items on the site, you don't need to log in since it will make no difference. 
If you want to register a project of your own to be hosted in this site, you must first log in, because every project must have at least one administrator and we need to know your user name to make you the administrator of the project.

In order to log in, you must be registered (using \"New User\" in the menu) and give the user name and password selected during your registration.

If you lost your password, read recipe #$item_id.")."', '1')");
      $item_id = db_insertid($query);
      db_query("INSERT INTO cookbook_context2recipe (recipe_id, group_id, audience_anonymous , audience_loggedin , audience_members , audience_technicians , audience_managers , context_project , context_homepage , context_cookbook , context_download , context_support , context_bugs , context_task , context_patch , context_news , context_mail , context_cvs , context_arch , context_svn , context_my , context_stats , context_siteadmin , context_people , subcontext_browsing , subcontext_postitem , subcontext_edititem , subcontext_search , subcontext_configure ) VALUES ('$item_id', '$group_id', '1', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '1', '0', '0')");


      $query = db_query("INSERT INTO cookbook (group_id, status_id , severity , privacy , category_id , submitted_by , assigned_to , date , summary , details , resolution_id) VALUES ('$group_id', '3', '5', '1', '100', '100', '100', '1133253163', 'Delays on update', '".mysql_real_escape_string("Several function related to mail aliases, external services access (SVN, CVS...), user additions, group member changes, CVS, etc, are performed via a cronjob on a regular basis. 

Changes made on the web site may appear to be live but will not take effect until the next cron update.")."', '1')");
      $item_id = db_insertid($query);
      db_query("INSERT INTO cookbook_context2recipe (recipe_id, group_id, audience_anonymous , audience_loggedin , audience_members , audience_technicians , audience_managers , context_project , context_homepage , context_cookbook , context_download , context_support , context_bugs , context_task , context_patch , context_news , context_mail , context_cvs , context_arch , context_svn , context_my , context_stats , context_siteadmin , context_people , subcontext_browsing , subcontext_postitem , subcontext_edititem , subcontext_search , subcontext_configure ) VALUES ('$item_id', '$group_id', '0', '1', '1', '0', '0', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '0', '0', '0', '0', '0', '0', '1')");

      # We add the default recipes grabbed from update/3.0/
      $query = db_query("INSERT INTO cookbook (group_id, status_id , severity , privacy , category_id , submitted_by , assigned_to , date , summary , details , resolution_id) VALUES ('$group_id', '3', '5', '1', '100', '100', '100', '1133253163', 'Markup Reminder', '".mysql_real_escape_string("Savane provides a markup langage that enables you to format text you post in items or items comments. HTML is not allowed for security reasons.


= Basic Text Tags =

Basic Text Tags are available almost everywhere.

*bold* markup is:
+verbatim+
*bold*
-verbatim- 

_italic_ markup is:
+verbatim+
_italic_
-verbatim- 

urls are automatically made links, additionnaly you can give them a title:
+verbatim+
[http://linkurl Title]
-verbatim- 

items references like _tracker #nnn_ will be made links to, like in:
+verbatim+
here is a link to recipe #$item_id.
-verbatim-


= Rich Text Tags =

Rich Text Tags are available in comments.

Unnumbered list markup is:
+verbatim+
* item 1\r
* item 2\r
** item 2 subitem 1\r
** item 2 subitem 2\r
-verbatim-

Numbered list markup is:
+verbatim+
0 item 1\r
0 item 2\r
-verbatim-

Horizontal ruler markup is:
+verbatim+
----
-verbatim-

Verbatim markup (useful for code bits) is:
+verbatim+
+verbatim+\r
The piece of code\r
The piece of code, line 2\r
-verbatim-\r
-verbatim-


= Heading Tags =

Heading Text Tags are available in rare places like items original submission, news item content, project description and users resume.

First Level heading markup is:
+verbatim+
= Title =
-verbatim-

Second Level heading markup is:
+verbatim+
== Subtitle ==
-verbatim-

Third Level heading markup is:
+verbatim+
=== Subsubtitle ===
-verbatim-

Fourth Level heading markup is:
+verbatim+
==== Subsubsubtitle ====
-verbatim-

= The Special _No Markup_ Tag =

If for some reason, you want to completely deactivate the markup on a part of a text, you can always use:
+verbatim+
+nomarkup+ Piece of text that will be printed unformatted -nomarkup-
-verbatim-

This tag diverges from the verbatim tag in the sense that it will not cause the relevant text to be formatted as it would be in a text editor, a pure verbatim environment, but simply unformatted. As result, for example, text indentation would be ignored because HTML by default ignores it. So to copy/paste bits of code, software output, you are advised to always use the verbatim tag instead.
")."', '1')");
      $item_id = db_insertid($query);
      db_query("INSERT INTO cookbook_context2recipe (recipe_id, group_id, audience_anonymous , audience_loggedin , audience_members , audience_technicians , audience_managers , context_project , context_homepage , context_cookbook , context_download , context_support , context_bugs , context_task , context_patch , context_news , context_mail , context_cvs , context_arch , context_svn , context_my , context_stats , context_siteadmin , context_people , subcontext_browsing , subcontext_postitem , subcontext_edititem , subcontext_search , subcontext_configure ) VALUES ('$item_id', '$group_id', '1', '1', '1', '0', '0', '1', '0', '1', '0', '1', '1', '1', '1', '1', '0', '0', '0', '0', '1', '0', '0', '1', '0', '1', '1', '0', '1')");	       


      $query = db_query("INSERT INTO cookbook (group_id, status_id , severity , privacy , category_id , submitted_by , assigned_to , date , summary , details , resolution_id) VALUES ('$group_id', '3', '3', '1', '100', '100', '100', '1133253163', 'Fighting Spam', '".mysql_real_escape_string("Savane provides several ways to protect trackers from spam.

= Preventing Spam =

Savane runs *DNS blacklists* checks on all forms submitted by non-project members. 

Apart from that, there are a few options that can allow a project admin to prevent many spams.

Spam are usually caused by anonymous robots.

* A good starting point to avoid spam is first to set trackers *Posting Restrictions* to a tough policy:
** On every trackers that you feel dedicated to manage the project workflow, without end-users interaction, like the task manager, set _project membership_ as minimal level of authentication.
** On every trackers that need input from non-members, like the support manager and the bug tracker, set _logged-in user_ as minimal level of authentication, if you can afford to forbid anonymous post (it means that external contributors will have to create an account)

* Another good idea is too use the special *Lock Discussion* field. This field, that can be modified only by trackers managers, is complementary to the Posting Restrictions. When an item is set as _Locked_, only technicians and managers are still be able to post further comments. While it may be used to end a flamewar, it will obviously reduce the number of targets available to spam robots if you set one (or more) automatic transition update so whenever an item is closed, the item get additionnally locked. Obviously, this is useless on trackers where only project members can post.

= Automatically Checking Potential Spam =

Savane allows to *automatically check posted content with SpamAssassin*. 

Any post that Savane feels needs to be crosschecked automatically by SpamAssassin (depends on site configuration) will be delayed, temporarily flagged as spam, when posted until it is checked in the following minutes. If it is found to be spam, no notification will ever be sent, it will stay flagged as spam.

= Removing Spam, Spam Scores =

=== Spam Scores ===

Any logged-in user is able, when he sees content (comment or item) that he believes to be spam, to *flag it as spam*. This will increment the spam score of the item.

* If the reporter is _project admin_ on which the suspected spam have been posted, the spam score of the content will grow of 5
* If the reporter is _project member_ on which the suspected spam have been posted, the spam score of the content will grow of 3
* If the reporter is _not project member_ on which the suspected spam have been posted, the spam score of the content will grow of 1

Any *content with a spam score superior or equal to 5 is considered to be spam*.

Each user have also his own spam score. Each time an user got one of his post flagged as spam (spam score > 4), his own score grows of 1. User own spam score is used to determine the spam score of any new post. In other words, someone caught 5 times posting spam will get all his further post automatically flagged as spam as soon as posted.

Site administrators have a specific interface that will allow them to check if spam reports against a user were legitimate and will be able to take necessary actions accordingly (like banning account used to spam or to maliciously report as spam perfectly valid content).

It is also possible to project admins and site admins to unflag content, which means they can reset the spam score of some content if they think there is a mistake.

=== Removing Spam ===

When content is considered to be spam (spam score > 4), it is not removed from the database. We do not want to risk loosing data in case of false positives.

However, comments that are spam are automatically removed from items pages, only a link remains for checking purpose.

Also, when browsing items, items that are spams are not shown, unless you change the related display criteria. 

If the content is an item, it is automatically set to _Locked_ so further post are impossible.

If your site runs checks with SpamAssassin, *flagged spams will be used to improves bayesian filtering*.")."', '1')");
      $item_id = db_insertid($query);
      db_query("INSERT INTO cookbook_context2recipe (recipe_id, group_id, audience_anonymous , audience_loggedin , audience_members , audience_technicians , audience_managers , context_project , context_homepage , context_cookbook , context_download , context_support , context_bugs , context_task , context_patch , context_news , context_mail , context_cvs , context_arch , context_svn , context_my , context_stats , context_siteadmin , context_people , subcontext_browsing , subcontext_postitem , subcontext_edititem , subcontext_search , subcontext_configure ) VALUES ('$item_id', '$group_id', '0', '1', '1', '0', '0', '0', '0', '1', '0', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '1')");
    }
  else
    {
      require_directory("trackers");
      trackers_init($GLOBALS['sys_group_id']);

      # Otherwise, create a new item on the admin task tracker
      # (planned close date: 10 days later)
      $vfl = array();
      $vfl['category_id'] = '1';
      $vfl['summary'] = 'Submission of '.$form_full_name;
      $vfl['details'] = $message_admin; 
      $vfl['planned_starting_date'] = date("Y")."-".date("m")."-".date("d");
      $vfl['planned_close_date'] = date("Y")."-".date("m")."-".(date("d")+10);
	
      $address = "";
      $item_id = trackers_data_create_item($GLOBALS['sys_group_id'],$vfl,$address);
      # send an email to notify the admins of the ite update
      list($additional_address, $sendall) = trackers_data_get_item_notification_info($item_id, ARTIFACT, 1);
      if ((trim($address) != "") && (trim($additional_address) != "")) 
	{ $address .= ", "; }
      $address .= $additional_address;
      # exclude the submitter from the notification, he got a specific mail
      # for himself
      trackers_mail_followup($item_id, $address, false, user_getname());
#      fb(sprintf(_("Task #%s opened"),$item_id));
    }

  if ($GLOBALS['sys_group_id'] != $group_id)
    {
      # get site-specific content, if it is not the localadmin project
      # Create the page header just like if there was not yet any group_id
      $group_id_not_yet_valid = $group_id;
      unset($group_id);
      site_header(array('title'=>_("Registration Complete")));
      $group_id = $group_id_not_yet_valid;

      utils_get_content("register/confirmation");
      site_footer(array());
    }
  else
    {
      # redirect to the trigger page
      session_redirect($GLOBALS['sys_home']."siteadmin/triggercreation.php?group_id=".$group_id);
    }

}
else if ($i_disagree && $group_id && $rand_hash)
{
  # Create the page header just like if there was not yet any group_id
  $group_id_not_yet_valid = $group_id;
  unset($group_id);
  $HTML->header(array('title'=>_("Registration Deleted")));
  $group_id = $group_id_not_yet_valid;

  $result=db_query_escape(
    "DELETE FROM groups
     WHERE group_id='%s' AND rand_hash='__%s'",
    $group_id, $rand_hash);

  print '
<h3>'._("Project Deleted").'</h3>
<p>'._("Please try again any other time.").'</p>';

  $HTML->footer(array());

}
else
{
  unset($group_id);
  exit_error('Error',_("This is an invalid state.").' '
	     ._("Some form variables were missing.").' '
	     .sprintf(_("If you are certain you entered everything, PLEASE report to %s including info on your browser and platform configuration."),$GLOBALS['sys_email_address']));
}
