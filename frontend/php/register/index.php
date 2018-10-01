<?php
# Project registration wizard
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
# Copyright (C) 2017  Ineiev
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

# Tricks for automatically opening a task in the tracker (sigh..)
define('ARTIFACT', 'task');
$no_redirection=1;

# Initial db and session library, opens session
require_once('../include/GPLQuickForm.class');
require_once('../include/init.php');
require_once('../include/database.php');
require_once('../include/vars.php'); // $LICENSE
require_once('../include/account.php'); // account_groupnamevalid

require_once('../include/Group.class'); // getTypeBaseHost()
require_once('../include/sendmail.php');

/**
 * GPLQuickForm validation callback
 */
function project_does_not_already_exist($form_unix_name)
{
# make sure the name is not already taken, ignoring incomplete
# registrations: risks of a name clash seems near 0, while not doing that
# require maintainance, since some people interrupt registration and
# try to redoit later with another name. 
# And even if a name clash happens, admins will notice it during approval
  return (db_numrows(db_execute("SELECT group_id FROM groups "
                                ."WHERE unix_group_name LIKE ? AND status <> 'I'",
				array($form_unix_name))) == 0);
}


session_require(array('isloggedin'=>'1'));
# TRANSLATORS: the argument is site name (like Savannah).
$HTML->header(array('title' => sprintf(_("%s hosting request"),
                                       $GLOBALS['sys_name'])));

if (db_numrows(db_execute("SELECT type_id FROM group_type")) < 1) {
	# group_type is empty; it's not possible to register projects
	print _("No group type has been set. Admins need to create at least one
group type. They can make it so visiting the link &ldquo;Group Type
Admin&rdquo; on the Administration section of the left side menu, while logged
in as admin.");
} else
  utils_get_content("register/index");

$form = new GPLQuickForm('change_date');

$form->addElement('header', 'title_name', _('Project name'));
$form->addElement('text', 'full_name', _('Full name'));
$form->addElement('text', 'unix_name', _('Short/system name')
  . '<br /><span class="smaller">'
  . _('(used in URLs, mailing lists names, etc.)') . '</span>');

$form->addElement('header', 'title_information', _('Project information'));
$form->addElement('textarea', 'purpose', _('~20-lines technical description') 
  . '<br /><span class="smaller">'
  . _('What is your project?') . '<br />'
  . _('(purpose, topic, programming language...)') . '<br />'
  . _('What is special about it?') . '</span>');
$types = array();
$result = db_execute("SELECT type_id, name FROM group_type ORDER BY type_id");
while($line = db_fetch_array($result))
     $types[$line['type_id']] = $line['name'];
$form->addElement('select', 'group_type', _('Group type'), $types);
// TODO: default group type
$form->setDefaults(array('group_type' => 2));
$form->addElement('select', 'license', _('Project license'), $LICENSE);
$form->addElement('textarea', 'license_other', _('Other license, details'));

$form->addElement('header', 'title_checklist',
sprintf (
_('Checklist - see <a href="%s">How To Get Your Project Approved Quickly</a>'),
"https://savannah.gnu.org/maintenance/HowToGetYourProjectApprovedQuickly"));
# <savannah-specific>
$form->addElement('checkbox', 'cl1',
 _('My project runs primarily on a completely free OS'));
$form->addElement('checkbox', 'cl2',
 _('My license is compatible with the GNU GPL or GFDL'));
$form->addElement('checkbox', 'cl3',
 _('My dependencies are compatible with my project license'));
$form->addElement('checkbox', 'cl4',
 sprintf(_('All my files include <a href="%s">valid copyright notices</a>'),
         'https://www.gnu.org/prep/maintain/html_node/Copyright-Notices.html'));
$form->addElement('checkbox', 'cl5',
 sprintf (_('All my files include a license header [<a href="%s">1</a>]
[<a href="%s">2</a>]'),
'https://www.gnu.org/licenses/gpl-howto.html',
'https://www.gnu.org/licenses/fdl-howto.html'));
$form->addElement('checkbox', 'cl6',
 _('Origin and license of media files is specified'));
$form->addElement('checkbox', 'cl7',
 _('My tarball includes a copy of the license'));
# </savannah-specific>
$form->addElement('checkbox', 'cl_foolproof',
 _("I read carefully and don't check this one"));
$form->addElement('checkbox', 'cl_requirements',
 sprintf(_('I agree with the <a href="%s">hosting requirements</a>'),
         'requirements.php'));

$form->addElement('header', 'title_details', _('Details'));
$form->addElement('textarea', 'required_sw', _('Dependencies')
  . '<br /><span class="smaller">'
  . _('name + license + website for each dependency') . '</span>');
$form->addElement('textarea', 'comments', _('Other Comments'));
$form->addElement('text', 'tarball_url', _('Tarball (.tar.gz) URL')
  . '<br /><span class="smaller">'
  . sprintf (
_('(or <a href="%s" target="_blank">upload file</a> to Savannah.)'),
             'upload.php')
  . '</span>');
$form->addElement('submit', null, _("Register project"));
  
$form->addRule('full_name', _("Invalid full name"), 'minlength', 2);
$form->addRule('unix_name', _("Invalid Unix name"), 'callback',
               'account_groupnamevalid');
$form->addRule('unix_name', _("A project with that name already exists."),
               'callback', 'project_does_not_already_exist');

# <savannah-specific>
for ($i = 1; $i <= 7; $i++)
  $form->addRule("cl$i", _("Please recheck your project"), 'required');
# </savannah-specific>
$form->addRule("cl_foolproof", _(":)"), 'maxlength', 0);
$form->addRule("cl_requirements", _("Please accept the hosting requirements"),
               'required');

$form->addRule('purpose', _("This is too short!"), 'minlength', 30);
$form->addRule('tarball_url',
 _("Please give us a link to your project latest release"), 'minlength', 4);
  
if ($form->validate())
{
  $form_values = $form->exportValues();
  $form->freeze();

  $form_full_name = $form_values['full_name'];
  $form_purpose = $form_values['purpose'];
  $form_required_sw = $form_values['required_sw'];
  $form_comments = $form_values['comments'];
  $form_license = $form_values['license'];
  $form_license_other = $form_values['license_other'];
  $group_type = $form_values['group_type'];

  # complete the db entries
  db_autoexecute('groups',
    array(
      'group_name' => $form_full_name,
      'unix_group_name' => strtolower($form_values['unix_name']),
      'status' => 'P',
      'is_public' => 1,
      'register_time' => time(),
      'register_purpose' => htmlspecialchars($form_purpose),
      'required_software' => htmlspecialchars($form_required_sw),
      'other_comments' => htmlspecialchars($form_comments),
      'license' => $form_license,
      'license_other' => htmlspecialchars($form_license_other),
      'type' => $group_type,
    ),
    DB_AUTOQUERY_INSERT);
  $result = db_execute("SELECT group_id FROM groups WHERE unix_group_name = ?",
    array($form_values['unix_name']));
  $group_id = db_result($result, 0, 'group_id');
  $project=project_get_object($group_id);

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
  $sql_type = db_execute("SELECT name FROM group_type WHERE type_id=?",
                         array($group_type));
  $type = db_result($sql_type,0,'name');
  $type_base_host = $project->getTypeBaseHost();
  $type_admin_email_address = $project->getTypeAdminEmailAddress();

  # get site-specific content. It will define confirmation_gen_email()
  utils_get_content("register/confirmation_mail");

  $message = confirmation_gen_email ($type_base_host, $user_realname,
                                     $user_email, $type_admin_email_address,
                                     $form_license, $form_license_other,
                                     $form_full_name, $unix_name, $type,
                                     $form_purpose, $form_required_sw,
                                     $form_comments);

  $message_user = "$message";

  $message_admin = "A new project has been registered at ".$GLOBALS['sys_name']." 
This project account will remain inactive until a site admin approves
or discards the registration.


= Registration Administration =

While this item will be useful to track the registration process,
*approving or discarding the registration must be done using "
."the specific [".$GLOBALS['sys_https_url'].$GLOBALS['sys_home']
."siteadmin/groupedit.php?group_id=".$group_id
." Group Administration] page*, accessible only to site administrators,
effectively *logged as site administrators* (superuser):

* [".$GLOBALS['sys_https_url'].$GLOBALS['sys_home']
."siteadmin/groupedit.php?group_id=".$group_id." Group Administration]


= Registration Details =

* Name: *".$form_full_name."*
* System Name:  *".$unix_name."*
* Type: ".$type."
* License: ".$LICENSE_EN[$form_license];

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

 $message_admin .= "\n==== Tarball URL: ====\n".$form_values['tarball_url']."\n\n";
 
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
      list($additional_address, $sendall) =
        trackers_data_get_item_notification_info($item_id, ARTIFACT, 1);
      if ((trim($address) != "") && (trim($additional_address) != "")) 
	{ $address .= ", "; }
      $address .= $additional_address;
      # exclude the submitter from the notification, he got a specific mail
      # for himself
      trackers_mail_followup($item_id, $address, false, user_getname());
    }

    # get site-specific content, if it is not the localadmin project
    # Create the page header just like if there was not yet any group_id
    $group_id_not_yet_valid = $group_id;
    unset($group_id);
    $group_id = $group_id_not_yet_valid;

    utils_get_content("register/confirmation");
    echo "<hr />
";
}
else
{
  echo "<p>"
._("Please fill in this submission form. The Savannah Hackers will then review
it for hosting compliance.")."</p>\n";
  echo '<p class="smaller">'
.sprintf(_("Note: if you wish to submit your package for GNU Evaluation, please
check the <a href='%s'>GNU Software Evaluation</a> webpage instead."),
 'https://www.gnu.org/help/evaluation.html')."</p>\n";
}

$form->display();
$HTML->footer(array());
?>
