<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2004-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                          Yves Perrin <yves.perrin--cern.ch>
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
 
if ($func == "digest")
{
  $browse_preamble = '<p>'._("Select the items you wish to digest with the checkbox shown next to the \"Item Id\" field, on the table below. You will be able to select the fields you wish to include in your digest at the next step.").'</p><p class="warn">'._("Once your selection is made, click on the button \"Proceed to Digest next step\" at the bottom of this page.").'</p>';
}
elseif ($func == "digestselectfield")
{
  # Determines items to digest, if we are supposed to digest dependancies
  if ($dependencies_of_item && $dependencies_of_tracker)
    {

      $sql = "SELECT is_dependent_on_item_id FROM ".$dependencies_of_tracker."_dependencies WHERE item_id='".addslashes($dependencies_of_item)."' AND is_dependent_on_item_id_artifact='".ARTIFACT."' ORDER by is_dependent_on_item_id";
      $res_deps = db_query($sql);
      $items_for_digest = array();
      while ($deps = db_fetch_array($res_deps))
	{
	  $items_for_digest[] = $deps['is_dependent_on_item_id'];
	}
    }


  if (!is_array($items_for_digest))
    {
      exit_error(_("No items selected for digest"));
    }

  trackers_header(array('title'=>_("Digest Items: Fields Selection")));

  print '<form action="'.$PHP_SELF.'" method="get">
<input type="hidden" name="group" value="'.$group_name.'" />
<input type="hidden" name="func" value="digestget" />
';

  # Keep track of the selected items
  $count = 0;
  while (list(,$item) = each($items_for_digest))
    {
      print form_input("hidden", "items_for_digest[]", $item);
      $count++;
    }

  print "\n\n<p>";
  printf(ngettext("You selected %s item for this digest. Now you must unselect fields you do not want to be included in the digest.", "You selected %s items for this digest. Now you must unselect fields you do not want to be included in the digest.", $count), $count)."</p>\n";

  # Select fields
  while ($field_name = trackers_list_all_fields())
    {

      if (trackers_data_is_used($field_name))
	{
	  # Open/Close and Group id are meaningless in this context:
	  # they ll be on the output page in any cases
	  if ($field_name == 'group_id' ||
	      $field_name == 'status_id')
	    { continue; }

          # Item id is mandatory
	  if ($field_name == "bug_id")
	    {
	      print form_input("hidden", "field_used[".$field_name."]", "1").
		"\n";
	      continue;
	    }

	  print '<div class="'. utils_get_alt_row_color($i) .'">'.
	    '<input type="checkbox" name="field_used['.$field_name.']" value="1" checked="checked" />&nbsp;&nbsp;'.trackers_data_get_label($field_name).' <span class="smaller"><em>- '.trackers_data_get_description($field_name)."</em></span></div>\n";
	  $i++;
	}
    }
  # Comments is not an authentic field but could be useful. We allow
  # addition of the latest comment
  print '<div class="'. utils_get_alt_row_color($i) .'">'.
    '<input type="checkbox" name="field_used[latestcomment]" value="1" checked="checked" />&nbsp;&nbsp;'._("Latest Comment").' <span class="smaller"><em>- '._("Latest comment posted about the item.").'</em></span></div>'."\n";

  print form_footer(_("Submit"));

  trackers_footer(array());

}
elseif ($func == "digestget")
{

  if (!is_array($items_for_digest))
    {
      exit_error(_("No items selected for digest"));
    }

  if (!is_array($field_used))
    {
      exit_error(_("No fields selected for digest"));
    }


  trackers_header(array('title'=>_("Digest").' - '.format_date($sys_datefmt,time())));


  # Browse the list of selected item
  while (list(,$item) = each($items_for_digest))
    {
      $i++;

      $result = db_query("SELECT * FROM ".ARTIFACT." WHERE bug_id='$item' AND group_id='$group_id'");

      # Skip it is it is private but the user got no privilege.
      # Normally, the user should not even been able to select this item.
      # But someone nasty could forge the arguments of the script... So its
      # better to check everytime.
      if (db_result($result,0,'privacy') == "2" && !member_check_private(0, db_result($result, 0, 'group_id')))
	{ continue; }

      # Show summary if requested
      unset($summary);
      if ($field_used['summary'] == 1)
	{
	  $summary = db_result($result,0,'summary');
	}

      # Show if the item is closed with an icon
      unset($icon);
      if (db_result($result, 0, 'status_id') != 1)
	{ $icon = '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/ok.png" border="0" alt="'._("Closed Item").'" />'; }
      else
	{ $icon = '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/wrong.png" border="0" alt="'._("Open Item").'" />'; }

      print '<div class="'. utils_get_alt_row_color($i) .'">';
      print '<span class="large"><span class="'.utils_get_priority_color(db_result($result,0,'priority'), db_result($result,0,'status_id')).'">'.$icon.'&nbsp; '.utils_link("?func=detailitem&amp;item_id=".$item, ARTIFACT.' #'.$item).': &nbsp;'.$summary.' &nbsp;</span></span><br /><br />';

      $field_count = 0;
      while ($field_name = trackers_list_all_fields())
	{
	  # Some field can be ignored in any cases
	  if ($field_name == "status_id" ||
	      $field_name == "summary" ||
	      $field_name == "bug_id" ||
              $field_name == "details")
	    { continue; }

# Check the fields
	  if ($field_used[$field_name] != 1)
	    { continue; }

	  $field_count++;
	  if ($field_count == 2)
	    { $field_count = 0; }

	  if ($field_count == 1)
	    {  print '<span class="splitright">'; }
	  else
	    {  print '<span class="splitleft">'; }

	  # Extract value
	  $value = trackers_field_display($field_name,db_result($result, 0, 'group_id'),db_result($result,0,$field_name),false,false,true);
	  # If it is an user name field, show full user info
	  if ($field_name == "assigned_to" ||
	      $field_name == "submitted_by")
	    {
	      $value = utils_user_link($value, user_getrealname(user_getid($value)));
    }

	  print trackers_field_label_display($field_name,db_result($result, 0, 'group_id'),false,false).' '.$value;

	  print '</span>';

	  if ($field_count == 1)
	    {  print '<br />'; }

	}

      # finally include details + last comment, if asked
      if ($field_used["details"] == 1)
	{
	  print '<hr class="clearr" /><div class="smaller">'. trackers_field_display("details",db_result($result, 0, 'group_id'),db_result($result,0,"details"),false,true,true).'</div>';
	}
      if ($field_used["latestcomment"] == 1)
	{
#         $last_comment = db_result(db_query("SELECT old_value FROM ".ARTIFACT."_history WHERE bug_id='$item' AND field_name='details' ORDER BY bug_history_id DESC LIMIT 1"),0,'old_value');

          $detail_result = db_query("SELECT old_value, mod_by, realname, user_name FROM ".ARTIFACT."_history, user WHERE bug_id='$item' AND field_name='details' AND user_id=mod_by ORDER BY bug_history_id DESC LIMIT 1");
          $last_comment = db_result($detail_result, 0, 'old_value');
          $mod_by = db_result($detail_result, 0, 'mod_by');
          if ($mod_by != 100) {
            $realname = db_result($detail_result, 0, 'realname');
            $user_name = '&lt;'.db_result($detail_result, 0, 'user_name').'&gt;';
          } else {
            $realname = _("Anonymous");
            $user_name = "";
          }

	  if ($last_comment)
	    {
	      print '<hr class="clearr" /><div class="smaller"><span class="preinput">'.sprintf(_("Latest comment posted (by %s):"), "$realname $user_name").'</span> '.markup_rich($last_comment).'</div>';
	    }
	}

      print '<p class="clearr">&nbsp;</p>';
      print '</div>'."\n\n";

    }
  trackers_footer(array());
}


?>
