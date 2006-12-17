<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                          BBN Technologies Corp
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


## Note about status of list: 
##   - Status 0: list is deleted (ie, does not exist).
##   - Status 1: list is marked for creation.
##   - Status 2: list is marked for reconfiguration.
##   - Status 5: list has been created (ie, it exists). 
##
##   This frontend php script sets status to:
##      0 if user deletes a list before the backend ever actually created it.
##      1 if user adds a list
##      2 if user reconfigures an _existing_ list (ie, status was 5)
##
##   The backend sv_mailman.pl script sets status to:
##      0 when a list is actually deleted
##      5 when a list is actually created
##
##   - when we create an alias, which mean someone was able, according to
##     group type restriction to add to his project a list that was already
##     inside the database, we add the list inside the database with a status
##     of 5, so sv_mailman does not try to recreate it.
##     In the worse case, if two persons creates the same list at the same

##   The field password will not contact real password, it will contain
##   '1' when the backend is supposed to reset it. 

require "../../include/pre.php";

register_globals_off();
$group_id = sane_all("group_id");
$group_name = sane_all("group_name");

if (!member_check(0, $group_id))
{ exit_permission_denied(); }

exit_test_usesmail($group_id);

$grp=project_get_object($group_id);

# FIXME: GNU specific
unset($isgnu);
if ($project->getTypeName() == 'GNU' || $project->getTypeName() == 'www.gnu.org' && $GLOBALS[sys_default_domain] == "savannah.gnu.org")
{ $isgnu = true; }


if (sane_post("post_changes"))
{
#
# Update the DB to reflect the changes
#
  
# Add a new list
  if (sane_post("add_list"))
    {
      # Need account related functions
      require "../../include/account.php";


      # Generates a password
      $list_password = substr(md5($GLOBALS['session_hash'] . time() . rand(0,40000)),0,16);
      
      # Defines the list name:
      $list_name = strtolower(sane_post("list_name"));
      $description = sane_post("description");
      $is_public = sane_post("is_public");

      # Name shorter than two characters are not acceptable
      $type = sane_post("type");
      if ((($isgnu == true && $type == "0") || $isgnu == false) && (!$list_name || strlen($list_name) < 2))
	{ exit_error(_('Error'),_('Must provide list name that is two or more characters long')); }

      # Site may have a strict policy on list names: checks now
      $new_list_name = $project->getTypeMailingListFormat($list_name);
      
      # Check if it is a valid name
      if (!account_namevalid($new_list_name, 1, 1, 1, _("list name"),80))
	{ exit_error(); }


      # gnu specific:
      if ($type != "0" && $type!="1" && $type!="2" && $type!="3" && $isgnu==true)
	{ exit_error(_('Error'),_('Radio button not checked')); }
      # gnu specific:
      if ($type=="0" && ($list_name=="help" || $list_name=="info" || $list_name=="bug") && $isgnu==true)
	{ exit_error(_('Error'),_('Invalid list name')); }

      # gnu specific:
      if ( $isgnu == true )
	{
	  switch ($type)
	    {
	    case "0":
	      $new_list_name=strtolower(group_getunixname($group_id).'-'.$list_name);
	      break;

	    case "1":
	      $new_list_name=strtolower('bug-'.group_getunixname($group_id));
	      break;

	    case "2":
	      $new_list_name=strtolower('help-'.group_getunixname($group_id));
	      break;

	    case "3":
	      $new_list_name=strtolower('info-'.group_getunixname($group_id));
	    }

	}

# Check on the list_name: must not be equal to a user account,
# otherwise it can mess up the mail develivery for the list/user
      if (db_numrows(db_query("SELECT user_id FROM user WHERE "
			      . "user_name LIKE '".$new_list_name."'")) > 0)
	{
	  exit_error(_('Error'), _("That list name is reserved, to avoid conflicts with user accounts."));
	}

# Check if the list does not exists already
      $result=db_query("SELECT * FROM mail_group_list WHERE lower(list_name)='$new_list_name'");
	  
      if (db_numrows($result) > 0)
	{
# If the list exists already, we create an alias, assuming
# that group type configuration is well-done and disallow
# list name to persons not supposed to use some names
	  fb(_("This list is already in the database. We will create an alias"));
	  $status = 5;
	}
      else
	{
	  $status = 1;
	}
	  
	  
	  
      $sql = "INSERT INTO mail_group_list "
	. "(group_id,list_name,is_public,password,list_admin,status,description) VALUES ("
	. "$group_id,"
	. "'$new_list_name',"
	. "'$is_public',"
	. "'$list_password',"
	. "'".user_getid()."',"
	. $status.","
	. "'". htmlspecialchars($description) ."')";
	  
      $result=db_query($sql);
	  
      if (!$result)
	{
	  fb(_("Error Adding List"),1);
	}
      else
	{
	  fb(_("List Added"));
	}
	  
    }
      
  else if (sane_post("change_status")) 
    {
      # We must through all possible list we have in the form 
      # First, we get the number of existing lists
      $rows = db_numrows(db_query("SELECT list_name,group_list_id FROM mail_group_list WHERE group_id='$group_id'"));

      # If there are no rows in the database, there is nothing to do      
      if ($rows == 0)
	{
	  # No lists? There is something weird here. The user probably waited
	  # long time before posting his form, enough for the rest of the
	  # lists to be deleted.
	  # (use sprintf to just to benefit of translation already made in
          # mail/index)
	  exit_error(sprintf(_("No Lists found for %s"), ("this project")));
	}
	  
      # If the number of rows specified in the database is smaller than the
      # one in the form, take this one into account to be sure that we wont
      # miss a still accurate list because another one was deleted (hence 
      # decrementing the number of rows)
      if ($rows < sane_post("lists_count"))
	{
	  fb(_("There are less lists in the database than in the submitted form"), 1);
	  $rows == sane_post("lists_count"); 
	}

      #  Go through each possible list looking for update
      for ($i=0; $i<$rows; $i++)
	{
	  # Extract the list id
	  $list_id = sane_post("list_id_".$i);

	  # Not a valid list id? Skip it, it was obviously not on the form
	  if (!$list_id)
	    { continue; }

	  # Extract other fields
	  $is_public = sane_post("is_public_".$i);
	  $list_name = sane_post("list_name_".$i);
	  $description = sane_post("description_".$i);
	  $reset_password = sane_post("reset_password_".$i);

	  # Now get the current database data for this list
	  # (yes, it means one SQL SELECT per list, but we dont expect to
          # have project with 200 lists so it should scale)
	  $res_status = db_query("SELECT * FROM mail_group_list " .
				 "WHERE group_list_id='$list_id' AND group_id='$group_id'");
	  $num = db_numrows($res_status);
	  
	  if (!$num)
	    {
	      fb(sprintf(_("List %s not found in the database"), $list_name), 1);
	      continue;
	    }
	  
	  $row_status = db_fetch_array($res_status);

          # Armando L. Caro, Jr. <acaro--at--bbn--dot--com> 2/23/06
          # Change the status based on what status is in mysql and what
          # is_public is being set to. We need to account for when 
          # multiple changes are entered into mysql before the backend has
          # the opportunity to act on them.
	  switch($row_status['status'])
	    {
            # Status of 0 or 1, means the mailing list doesnt exist. So
            # signal to backend to create as long as is_public is not set 
            # to "deleted" (ie, 9).
	    case '0':
	    case '1':
	      if ($is_public != 9)
		{
		  $status = 1;
		}
	      else
		{
		  $status = 0;
		}
	      break;

            # Status of 2 or 5, means the mailing list does exist, and 
            # user is making a change. The change has to be signaled to
            # backend no matter what.
	    case '2':
	    case '5':
	      $status = 2;
	      break;
	    }
	  
	  # We need an update only if there is at least one change
	  if ($status == $row_status['status'] &&
	      $description == $row_status['description'] &&
	      $is_public == $row_status['is_public'] &&
	      $reset_password == $row_status['password'])
	    {
	      continue;
	    }

	  $sql="UPDATE mail_group_list SET is_public='$is_public', ".
	    "status='$status', ".
	    "password='$reset_password', ".
	    "description='$description' ".
	    "WHERE group_list_id='$list_id' AND group_id='$group_id'";

	  $result=db_query($sql);
	  if (!$result || db_affected_rows($result) < 1)
	    {
	      fb(_("Error Updating List"),1);
	    }
	  else
	    {
	      fb(sprintf(_("List %s updated"), $list_name));
	    }
       
	}
    }
  
}

#
#	Show forms to make changes
#

# Show the form for adding lists
if (sane_get("add_list"))
{

  # Check first if the group type set up is acceptable. Otherwise, the form
  # will probably be puzzling to the user (ex: no input text for the list
  # name)
  if (!$project->getTypeMailingListAddress($project->getTypeMailingListFormat("testname")) || $project->getTypeMailingListAddress($project->getTypeMailingListFormat("testname")) == "@")
    {
      exit_error("Mailing-list are misconfigured. Post a support request to ask your site administrator to review group type setup.");
    }


  site_project_header(array('title'=>_("Add a Mailing List"),'group'=>$group_id,'context'=>'amail'));


  utils_get_content("mail/about_list_creation");

  $result = db_query("SELECT list_name,group_list_id FROM mail_group_list WHERE group_id='$group_id' ORDER BY list_name ASC");
  $rows=db_numrows($result);

  # If there are already some list, show them so the user knows names already
  # picked
  if ($rows > 0)
    {
      print '<h3>'._('Mailing Lists already in use').'</h3>';
      for ($k=0; $k < $rows; $k++)
	{
	  print '<div class="'.utils_get_alt_row_color($k).'">'.db_result($result, $k, "list_name").'</div>';
	}
    }
  
  print '
			<p>
			<form method="post" action="'.$_SERVER['PHP_SELF'].'">
			<input type="hidden" name="post_changes" value="y" />
			<input type="hidden" name="add_list" value="y" />
			<input type="hidden" name="group_id" value="'.$group_id.'" />
			<h3>'._('Mailing List Name:').'</h3> ';

  if ($isgnu == true)
    { print '<INPUT TYPE="radio" NAME="type" VALUE="0" CHECKED>'; }

  print $project->getTypeMailingListAddress($project->getTypeMailingListFormat('<input type="text" name="list_name" value="" size="25" maxlenght="70" />'));

  if ($isgnu == true)
    {
      print '
			<BR>
			<INPUT TYPE="radio" NAME="type" VALUE="1">bug-'.group_getunixname($group_id).'<BR>
			<INPUT TYPE="radio" NAME="type" VALUE="2">help-'.group_getunixname($group_id).'<BR>
			<INPUT TYPE="radio" NAME="type" VALUE="3">info-'.group_getunixname($group_id).'<BR>';
    }


  print '
			<P></P>
			<h3>'._('Is Public?').'</h3> '._('(visible to non-members)').'<BR>
			<INPUT TYPE="RADIO" NAME="is_public" VALUE="1" CHECKED> Yes<BR>
			<INPUT TYPE="RADIO" NAME="is_public" VALUE="0"> No<P></P>
			<strong>'._('Description:').'</strong><BR>
			<INPUT TYPE="TEXT" NAME="description" VALUE="" SIZE="40" MAXLENGTH="80"><BR>
			<P></P>
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'._('Add This List').'">
			</FORM>';

  site_project_footer(array());

}
else if (sane_get("change_status"))
{
  $sql="SELECT list_name,group_list_id,is_public,description,password,status ".
    "FROM mail_group_list ".
    "WHERE group_id='$group_id' ORDER BY list_name ASC";
  $result=db_query($sql);
  $rows=db_numrows($result);

  if (!$result || $rows < 1)
    {
      exit_error(_("No lists found"));
    }
      
  # Show the form to modify lists status
  site_project_header(array('title'=>_("Update Mailing List"),'group'=>$group_id,'context'=>'amail'));
    
  
  print '<p>';
  print _("You can administer lists information from here. Please note that private lists are only displayed for members of your project, but not for visitors who are not logged in.")."<br />\n";
  print "</p>\n";
      

      # Start form
  print form_header($_SERVER['PHP_SELF']);
  print form_input("hidden", "post_changes", "y");
  print form_input("hidden", "change_status", "y");
  print form_input("hidden", "group_id", $group_id);
  print form_input("hidden", "lists_count", $rows);
  
  for ($i=0; $i<$rows; $i++)
    {
      print '
    <h4>'.db_result($result,$i,'list_name')._(':').'</h4>';
      
	  # Description
      print '<span class="preinput">'._("Description:").'</span>';
      print '<br />&nbsp;&nbsp;&nbsp;'.form_input("text", "description_".$i, db_result($result,$i,'description'), 'maxlenght="120" size="50"');
      
      # Status: private or public list, or planned for deletion.
      # It may be weird to have the last one here, but that is how things
      # are in the database and it is simpler to follow the same idea.
      print '<br /><span class="preinput">'._("Status:").'</span>';
      unset($checked);
      if (db_result($result,$i,'is_public') == "1")
	{ $checked = ' checked="checked"'; }
      print '<br />&nbsp;&nbsp;&nbsp;'.form_input("radio", "is_public_".$i, '1', $checked).' '._("Public List");
      
      unset($checked);
      if (db_result($result,$i,'is_public') == "0")
	{ $checked = ' checked="checked"'; }
      print '<br />&nbsp;&nbsp;&nbsp;'.form_input("radio", "is_public_".$i, '0', $checked).' '._("Private List (not advertised, suscribing requires approval)");;
      
      unset($checked);
      if (db_result($result,$i,'is_public') == "9")
	{ $checked = ' checked="checked"'; }
      print '<br />&nbsp;&nbsp;&nbsp;'.form_input("radio", "is_public_".$i, '9', $checked).' '._("To be deleted (warning, this cannot be undone!)");
      
      # At this point we have no way to know if the backend brigde to 
      # mailman is used or not. We will propose the password change only
      # if the list is marked as created. 
      # Do not heavily check this, just skip this in the form.
      if (db_result($result,$i,'status') == "5" || 
	  db_result($result,$i,'status') == "2")
	{
	  print '<br /><span class="preinput">'._("Reset List Admin Password:").'</span>';
	  unset($checked);
	  if (db_result($result,$i,'password') == "1")
	    { $checked = ' checked="checked"'; }
	  print '<br />&nbsp;&nbsp;&nbsp;'.form_input("checkbox", "reset_password_".$i, "1", $checked).' '._("Requested").' - <em>'._("this will have no effect if this list is not managed by Mailman via Savane").'</em>';
      
	}
      else
	{
	  print form_input("hidden", "reset_password_".$i, db_result($result,$i,'password'));
	}
      
      print form_input("hidden", "list_name_".$i, db_result($result,$i,'list_name'));
      
      print form_input("hidden", "list_id_".$i, db_result($result,$i,'group_list_id'));
	  	
    }
  
  print '<br /><br />'.form_footer();
 
  site_project_footer(array());
}
else
{

#
# Show default page
# 
 


  site_project_header(array('group'=>$group_id,'context'=>'amail'));
 
  # the <br /> in front is here to put some space with the menu
  # Please, keep it
  print '<br />
 <a href="'.$_SERVER['PHP_SELF'].'?group='.$group_name.'&add_list=1">'._("Add Mailing List").'</a><p class="text">'._("You can create mailing lists for your project using the web interface.").'</p>
 
 <a href="'.$_SERVER['PHP_SELF'].'?group='.$group_name.'&change_status=1">'._("Administer/Update Lists").'</A><p class="text">'._("Update information on existing mailing lists and change their policy.").'</p>
  ';
 
  site_project_footer(array());
     
}

?>