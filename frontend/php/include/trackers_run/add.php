<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
#  Copyright 2001-2002 (c) Laurent Julliard, CodeX Team, Xerox
#
# Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                          Yves Perrin <yves.perrin--cern.ch>
#
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


extract(sane_import('request',
  array('form_id')));

if (!group_restrictions_check($group_id, ARTIFACT))
    {
      exit_error(sprintf(_("Action Unavailable: %s"), group_getrestrictions_explained($group_id, ARTIFACT)));
    }

trackers_header(array('title'=>_("Submit Item")));
$fields_per_line=2;
$max_size=40;

# First display the message preamble
$res_preamble = db_execute("SELECT ".ARTIFACT."_preamble FROM groups WHERE group_id=?", array($group_id));

$preamble = db_result($res_preamble,0,ARTIFACT.'_preamble');
if ($preamble)
{ 
  # The h3 is necessary to keep the layout correct in every case
  print '<h3>'._("Preamble").'</h3>'.
    markup_rich($preamble); 
}


print '<h3>'._("Details").'</h3>';

# Beginning of the submission form with fixed fields
print form_header($_SERVER['PHP_SELF'], $form_id, "post", 'enctype="multipart/form-data" name="trackers_form"');
print form_input("hidden", "func", "postadditem");
print form_input("hidden", "group_id", $group_id);
print '
     <table cellpadding="0" width="100%">';


# Now display the variable part of the field list (depend on the project)

$i=0;
$j=0;
$is_trackeradmin = member_check(0,$group_id,member_create_tracker_flag(ARTIFACT).'2');

while ($field_name = trackers_list_all_fields()) 
{

  # if the field is a special field (except summary and original description)
  # or if not used by this project  then skip it. 
  # Plus only show fields allowed on the bug submit_form 
  if ((!trackers_data_is_special($field_name) || $field_name=='summary' || $field_name=='details') &&
      trackers_data_is_used($field_name)) 
    {
      
      if  (($is_trackeradmin && trackers_data_is_showed_on_add_members($field_name)) ||
	   (!$is_trackeradmin && trackers_data_is_showed_on_add($field_name)) ||
	   (!user_isloggedin() && trackers_data_is_showed_on_add_nologin($field_name)) ) 
	{
	  
	  # display the bug field with its default value
	  # if field size is greatest than max_size chars then force it to
	  # appear alone on a new line or it wont fit in the page

	  # We allow people to make urls with predefined values,
	  # if the values are in the url, we override the default value.
	  if (empty($$field_name))
	    { $field_value = trackers_data_get_default_value($field_name); }
	  else
	    { $field_value = htmlspecialchars(stripslashes($$field_name)); }

	  list($sz,) = trackers_data_get_display_size($field_name);

	  $label = trackers_field_label_display($field_name,
						$group_id,
						false,
						false);

	  # Add markup info for some fields
	  unset($markup_info);
	  if ($field_name == 'details')
	    { $label .= ' <span class="preinput">'.markup_info("full").'<span>'; }

          # check if the field is mandatory
	  $star = '';
	  $mandatory_flag = trackers_data_mandatory_flag($field_name);
	  if ($mandatory_flag == 3 || $mandatory_flag == 0)	    
	    {
	      $star = '<span class="warn"> *</span>';
	      $mandatory_flag = 0;
	    }

	  # Field display with special Unknown option, only for fields that
	  # are no mandatory
	  $value = trackers_field_display($field_name,
					  $group_id,
					  $field_value,
					  false, #4
					  false,
					  false, #6
					  false,
					  false, #8
					  false,
					  false, #10				  
					  false,
					  true, #12
					  $mandatory_flag);
	  
          # Fields colors
	  $field_class = '';
	  $row_class = '';
	  if ($j % 2 && $field_name != 'details')
	    {
		  # We keep the original submission with the default
		  # background color, for lisibility sake
		  #
		  # We also use the boxitem background color only one time
		  # out of two, to keep the page light
	      $row_class = ' class="'.utils_altrow($j+1).'"'; 
	    }

          # If we are working on the cookbook, present checkboxes to
          # defines context before the summary line;
	  if (CONTEXT == 'cookbook' && $field_name == 'summary' && $is_trackeradmin)
	    {
	      cookbook_print_form();
	    }
	  
          # We highlight fields that were not properly/completely 
	  # filled.
	  if (!empty($previous_form_bad_fields) && array_key_exists($field_name, $previous_form_bad_fields))
	    {  
	      $field_class = ' class="highlight"';
	    }

	  if ($sz > $max_size) 
	    {
              # Field getting one line for itself 

              # Each time prepare the background color change
	      $j++;

	      print "\n<tr".$row_class.">".
		'<td valign="middle"'.$field_class.' width="15%">'.$label.'</td>'.
		'<td valign="middle"'.$field_class.' colspan="'.(2*$fields_per_line-1).'" width="75%">'.
		$value.$star.'</TD>'.		      
		"\n</tr>";
	      $i=0;
	    } 
	  else 
	    {
              # Field getting half of a line for itself 
	      
	      if (!($i % $fields_per_line))
		{
		  # Every one out of two, prepare the background color change.
		  # We do that at this moment because we cannot be sure
		  # there will be another field on this line.
		  $j++;		  
		}

	      print ($i % $fields_per_line ? "\n":"\n<tr".$row_class.">");
	      print '<td valign="middle"'.$field_class.' width="15%">'.$label.'</td>'.
		'<td valign="middle"'.$field_class.' width="35%">'.$value.$star.'</td>';
	      $i++;
	      print ($i % $fields_per_line ? "\n":"\n</tr>");
	    }
	}
    }
}


print '</table>';
print '<p class="warn"><span class="smaller">* '._("Mandatory Fields").'</span></p>';
	     
#  possibility of attachment


print '<p>&nbsp;</p>';
print '<h3>'._("Attached Files").'</h3>';
print sprintf(_("(Note: upload size limit is set to %s kB, after insertion of the required escape characters.)"), $GLOBALS['sys_upload_max']);

print '<p><span class="preinput"> '._("Attach File(s):").'</span><br />
      &nbsp;&nbsp;&nbsp;<input type="file" name="input_file1" size="10" /> <input type="file" name="input_file2" size="10" />
      <br />
      &nbsp;&nbsp;&nbsp;<input type="file" name="input_file3" size="10" /> <input type="file" name="input_file4" size="10" />
      <br />
      <span class="preinput">'._("Comment:").'</span><br />
      &nbsp;&nbsp;&nbsp;<input type="text" name="file_description" size="60" maxlength="255" />
      </p><p>';
	  

# Cc addresses
if (user_isloggedin())
{
  
  print '<p>&nbsp;</p>';
  print '<h3>'._("Mail Notification CC").'</h3>';
  
  print sprintf(_("(Note: for %s users, you can use their login name rather than their email addresses.)"), $GLOBALS['sys_name']);
  
  print '<p><span class="preinput">'
    ._("Add Email Addresses (comma as separator):").'</span><br />&nbsp;&nbsp;&nbsp;<input type="text" name="add_cc" size="40" value="'.htmlspecialchars(stripslashes($add_cc)).'" />&nbsp;&nbsp;&nbsp;
        <br />
        <span class="preinput">'
    ._("Comment:").'</span><br />&nbsp;&nbsp;&nbsp;<input type="text" name="cc_comment" value="'.htmlspecialchars(stripslashes($cc_comment)).'" size="40" maxlength="255" />';
  print '<p></p>';
}

# Minimal anti-spam
if (!user_isloggedin()) {
  print '<p class="noprint">Please enter 421 here (basic anti-spam test): <input type="text" name="check" /></p>';
}

print '<p>&nbsp;</p>';
print '<p><span class="warn">'._("Did you check to see if this item has already been submitted?").'</span></p>';
print '<div align="center">';
print form_submit(false, "submit", 'class="bold"');
print '</div>';
print '</form>';


trackers_footer(array());

?>
