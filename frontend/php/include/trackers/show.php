<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#  Copyright 2001-2002 (c) Laurent Julliard, CodeX Team, Xerox
#
#  Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
#
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

function show_item_list ($result_arr,
			 $offset,
			 $total_rows,
			 $field_arr, #4
			 $title_arr,
			 $width_arr,
			 $url,
			 $nolink=false)
{
  global $group_id,$chunksz,$morder;

  # Build the list of links to use for column headings
  # Used to trigger sort on that column
  if ($url)
    {
      $links_arr = array();
      while (list(,$field) = each($field_arr))
	{
	  $links_arr[] = $url.'&amp;order='.$field.'#results';
	}
    }

  /*
      Show extra rows for <-- Prev / Next -->
  */

  $nav_bar ='<h3 class="nextprev">';

  # If all bugs on screen so no prev/begin pointer at all
  if ($total_rows > $chunksz)
    {
      if ($offset > 0)
	{
	  $nav_bar .=
	     '<span class="xsmall"><a href="'.$url.'&amp;offset=0#results"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/first.png" border="0" alt="'._("Begin").'" />'._("Begin").'</a>'.
	     '&nbsp;&nbsp;&nbsp;&nbsp;'.
	     '<a href="'.$url.'&amp;offset='.($offset-$chunksz).
	     '#results"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/previous.png" border="0" alt="'._("Previous Results").'" />'._("Previous Results").'</a></span>';
	}
      else
	{
	  $nav_bar .=
	     '<span class="xsmall"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/firstgrey.png" border="0" alt="'._("Begin").'" /><em>'._("Begin").'</em>'.
	     '&nbsp;&nbsp;&nbsp;&nbsp;'.
	     '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/previousgrey.png" border="0" alt="'._("Previous Results").'" /><em>'._("Previous Results").'</em></span>';
	}
    }


  $offset_last = min($offset+$chunksz-1, $total_rows-1);

  #fb("$offset_last offset_last");

  $nav_bar .= " &nbsp;  &nbsp; &nbsp; &nbsp; ".sprintf(ngettext("%d matching item", "%d matching items", $total_rows), $total_rows);
  $nav_bar .= " - ".sprintf(_("Items %s to %s"), ($offset+1), ($offset_last+1))."  &nbsp; &nbsp; &nbsp; &nbsp; ";


  # If all items are on screen, no next/end pointer at all
  # FIXME: it should not count private items
  if ($total_rows > $chunksz)
    {
      if ( ($offset+$chunksz) < $total_rows )
	{

	  $offset_end = ($total_rows - ($total_rows % $chunksz));
	  if ($offset_end == $total_rows)
	    { $offset_end -= $chunksz; }

	  #fb("$offset_end offset_end");

	  $nav_bar .=
	     '<span class="xsmall"><a href="'.$url.'&amp;offset='.($offset+$chunksz).
	     '#results">'._("Next Results").'<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/next.png" border="0" alt="'._("Next Results").'" /></a>'.
	     '&nbsp;&nbsp;&nbsp;&nbsp;'.
	     '<a href="'.$url.'&amp;offset='.($offset_end).
	     '#results">'._("End").'<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/last.png" border="0" alt="'._("End").'" /></a></span>';
	}
      else
	{
	  $nav_bar .= '<span class="xsmall"><em>'._("Next Results").'</em><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/nextgrey.png" border="0" alt="'._("Next Results").'" />'.
	     '&nbsp;&nbsp;&nbsp;&nbsp;'.
	     '<em>'._("End").'</em><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/lastgrey.png" border="0" alt="'._("End").'" /></span>';
	}
    }
  $nav_bar .= '</h3>';

  # Print prev/next links
  print $nav_bar.'<a name="results"></a><br />';

  print html_build_list_table_top ($title_arr,$links_arr);

  #see if the bugs are too old - so we can highlight them
  $nb_of_fields = count($field_arr);

  while (list(,$thisitem) = each($result_arr))
    {
      $thisitem_id = $thisitem['bug_id'];

      print '<tr class="'.utils_get_priority_color($result_arr[$thisitem_id]["priority"], $result_arr[$thisitem_id]["status_id"]).'">'."\n";

      for ($j=0; $j<$nb_of_fields; $j++)
	{
           # If we are in digest mode, add the digest checkbox
	  if ($field_arr[$j] == "digest")
	    {
	      # Dirty workaround to have boxes selected by default in the
	      # form_input
	      print '<td class="center">'.form_input("checkbox", "items_for_digest[]", "$thisitem_id\" checked=\"checked").'</td>';
	      continue;
	    }

	  $value = $result_arr[$thisitem_id][$field_arr[$j]];
	  if ($width_arr[$j])
	    {
	      $width = 'width="'.$width_arr[$j].'%"';
	    }
	  else
	    {
	      $width = '';
	    }

	  if (trackers_data_is_date_field($field_arr[$j]) )
	    {
	      if ($value)
		{
		  if ($field_arr[$j] == 'planned_close_date' and $value < time())
		    { $highlight_date = ' class="highlight"'; }
		  else
		    { $highlight_date = ''; }
		  print "<td $width$highlight_date>";
		  print format_date('short',$value);
		  print "</td>\n";
		}
	      else
		{ print "<td align=\"middle\" $width>-</td>\n"; }

	    }
	  else if ($field_arr[$j] == 'bug_id')
	    {

	      if ($nolink)
		{ print "<td $width>#$value</td>\n"; }
	      else
		    {
		      print "<td $width>";

		      print '<a href="?'.$value.'">';

		      print '&nbsp;#'.$value .'</a></td>'."\n";

		    }

	    }
	  else if (trackers_data_is_username_field($field_arr[$j]))
	    {

	      if ($nolink)
		{ print "<td $width>$value</td>\n"; }
	      else
		{ print "<td $width>".utils_user_link($value)."</td>\n"; }

	    }
	  else if (trackers_data_is_select_box($field_arr[$j]))
	    {
	      print "<td $width>". trackers_data_get_cached_field_value($field_arr[$j], $group_id, $value) .'</td>'."\n";

	    }
	  else
		{
		  if ($nolink)
		    { print "<td $width>". $value .'&nbsp;</td>'."\n"; }
		  else
		    {
		      print "<td $width>".'<a href="?'.$thisitem_id.'">'.
			$value .'</a></td>'."\n";
		    }
		}

	}
      print "</tr>\n";
    }

  print '</table>';

  # Print prev/next links
  print "<br />".$nav_bar;

}

##
# Do the same a item list but in sober output
function show_item_list_sober ($result_arr,
			       $total_rows,
			       $url)
{
  global $group_id, $sys_group_id, $sys_name;

  $possible_contexts = cookbook_context_possiblevalues();
  $possible_audiences = cookbook_audience_possiblevalues();

  # If we are on a project cookbook, take into account impossible values:
  # recipes of features unused by the project
  $impossible_contexts = array();
  if ($group_id != $sys_group_id)
    {
      $impossible_contexts = cookbook_context_project_impossiblevalues();
    }


  # Add the unset case, when the item is actually not bound to any context
  # or action
  # Build sql specific part for these
  unset($sql_unboundcontext, $sql_unboundaudience);
  $thisarray = array_merge($possible_contexts, $impossible_contexts);
  while (list($context,) = each($thisarray))
    {
      $sql_unboundcontext .= "AND context_$context='0' ";
    }
  while (list($audience,) = each($possible_audiences))
    { $sql_unboundaudience .= "AND audience_$audience='0' "; }

  # Built for scratch two groups of audiences possible for this page:
  # group members and non-group members
  $possible_audiences = array();
  #$possible_audiences['everybody'] = _("For Everybody");
  $possible_audiences['nonmembers'] = _("For Everybody");
  $possible_audiences['members'] = _("For Project Members Only");

  # Add unset cases to the arrays
  $possible_contexts['unbound'] = _("Other");
  $possible_audiences['unbound'] = _("Undefined");

  # Build sql specific part to group audiences between:
  #   project members / non project members
  $sql_nonmembers = "AND (audience_anonymous='1' OR audience_loggedin='1')";
  $sql_members = "AND (audience_members='1' OR audience_technicians='1' OR audience_managers='1')";
  #$sql_everybody = $sql_nonmembers." ".$sql_members;

  unset($sql_privateitem);

  # Go through the list of possible context and then possible actions
  # print relevant items
  reset($possible_contexts);
  while (list($context,$context_label) = each($possible_contexts))
    {
      $seen_before = array();
      unset($context_content);
      reset($possible_audiences);
      while (list($audience,$audience_label) = each($possible_audiences))
	{
          # Get recipes contextual data
	  # (no limit argument, expecting people not to use terrible scales)

	  if ($audience == 'nonmembers')
	    {
	      $sql_audience = $sql_nonmembers;
	    }
	  else
	    {
	      $sql_audience = $sql_members;
	    }
	  #else
	  #  {
	  #    $sql_audience = $sql_everybody;
	  #  }

	  # Special deal for the item unbound
	  if ($audience != 'unbound' && $context != 'unbound')
	    {
	      # Normal case, binds for both context and audience
	      $sql_context = "SELECT * FROM cookbook_context2recipe WHERE (group_id='$group_id' OR group_id='$sys_group_id') AND context_$context='1' $sql_audience";
	    }
	  else if ($audience == 'unbound' && $context != 'unbound')
	    {
	      # Bind only for the context
	      $sql_context = "SELECT * FROM cookbook_context2recipe WHERE (group_id='$group_id' OR group_id='$sys_group_id') AND context_$context='1' $sql_unboundaudience";
	    }
	  else if ($context == 'unbound' && $audience != 'unbound')
	    {
	      # Bind only for the audience
	      $sql_context = "SELECT * FROM cookbook_context2recipe WHERE (group_id='$group_id' OR group_id='$sys_group_id') $sql_audience $sql_unboundcontext";
	    }
	  else if ($context == 'unbound' && $audience == 'unbound')
	    {
	      # Not binded at all
	      $sql_context = "SELECT * FROM cookbook_context2recipe WHERE (group_id='$group_id' OR group_id='$sys_group_id') $sql_unboundcontext $sql_unboundaudience";
	    }

	  $result_context = db_query($sql_context);
	  $result_rows = db_numrows($result_context);

	  if ($result_rows)
	    {
	      # We want to show items sorted by alphabetical order.
	      # We will first put the result in a an array
	      # we will sort the array and use it to print out results.
	      # We store the summary in lower case, to avoid having a case
	      # sensitive sort.
	      $thisaudience_results = array();
	      for ($i = 0; $i < $result_rows; $i++)
		{
		  $thisitem_id = db_result($result_context, $i, 'recipe_id');
		  $thisaudience_results[$thisitem_id] =
		    strtolower($result_arr[$thisitem_id]["summary"]);
		}
	      asort($thisaudience_results);
	      unset($audience_content);
	      while (list($thisitem_id,$summary) = each($thisaudience_results))
		{
		  # Ignore if not approved
		  if ($result_arr[$thisitem_id]["resolution_id"] != '1')
		    { continue; }

		  # Ignore if seen before (probably because it an item for
		  # for everybody and we are listing members or non-members
		  # items)
		  if (isset($seen_before[$thisitem_id]))
		    { continue; }

		  # Record that we seen it
		  $seen_before[$thisitem_id] = true;

		  # Detect if it is a site wide doc item. Ignore that if we
		  # are on the site admin group
		  unset($is_site_doc, $url_extra_arg);
		  if ($group_id != $sys_group_id)
		    {
		      if ($result_arr[$thisitem_id]["group_id"] == $sys_group_id)
			{
			  $is_site_doc = true;
			  $url_extra_arg = '&amp;comingfrom='.$group_id;
			}
		    }


		  $audience_content .= '<li>';
                  # Show specific background color only for maximum priority
		  $priority = $result_arr[$thisitem_id]["priority"];
		  if ($priority > 4)
		    {
		      $audience_content .= '<span class="'.utils_get_priority_color($result_arr[$thisitem_id]["priority"]).'">';
		    }

		  # In this link, we need to mention from where we come from
		  # so it is possible to know if we are actually inside a
		  # group cookbook if ever we look at a site wide documentation
		  # (We use the long item url, with "detailitem" because we may
		  # have extra arguments to include that would mess the short
		  # item url interpretation)
		  $audience_content .= utils_link($GLOBALS['sys_home'].'cookbook/?func=detailitem'.$url_extra_arg.'&amp;item_id='.$thisitem_id, $result_arr[$thisitem_id]["summary"]);
		  if ($priority > 4)
		    {
		      $audience_content .= '</span>';
		    }

		  # If it comes from the site docs, mention it
		  if ($is_site_doc)
		    {
		      $audience_content .= '&nbsp;&nbsp;<span class="smaller">('.sprintf(_("From %s User Docs"), $sys_name).')</span>';
		    }

		  $audience_content .= '</li>';
		}

	      # If there was valid results, save the subcontext
	      if (!$audience_content)
		{ continue; }

	      $context_content .= '<li><span class="smaller">'.sprintf(_("%s:"), $audience_label).'</span>';
	      $context_content .= '<ul>';
	      $context_content .= $audience_content;
	      $context_content .= '</ul>';
	      $context_content .= '</li>';
	    }
	}
      # If there was valid results, print the context
      if (!$context_content)
	{ continue; }

      print '
  <h3>'.html_anchor(sprintf(_("%s:"), $context_label), $context).'</h3>
  <ul>'.$context_content.'</ul>
  <br />
';

    }


  return true;
  while (list(,$thisitem) = each($result_arr))
    {
      $thisitem_id = $thisitem['bug_id'];

      print '<tr class="'.utils_get_priority_color($result_arr[$thisitem_id]["priority"], $result_arr[$thisitem_id]["status_id"]).'">'."\n";

      for ($j=0; $j<$nb_of_fields; $j++)


	{
           # If we are in digest mode, add the digest checkbox
	  if ($field_arr[$j] == "digest")
	    {
	      print '<td class="center">'.form_input("checkbox", "items_for_digest[]", $thisitem_id).'</td>';
	      continue;
	    }

	  $value = $result_arr[$thisitem_id][$field_arr[$j]];
	  if ($width_arr[$j])
	    {
	      $width = 'width="'.$width_arr[$j].'%"';
	    }
	  else
	    {
	      $width = '';
	    }

	  if (trackers_data_is_date_field($field_arr[$j]) )
	    {
	      if ($value)
		{
		  if ($field_arr[$j] == 'planned_close_date' and $value < time())
		    { $highlight_date = ' class="highlight"'; }
		  else
		    { $highlight_date = ''; }
		  print "<td $width$highlight_date>";
		  print format_date('short',$value);
		  print "</td>\n";
		}
	      else
		{ print "<td align=\"middle\" $width>-</td>\n"; }

	    }
	  else if ($field_arr[$j] == 'bug_id')
	    {

	      if ($nolink)
		{ print "<td $width>#$value</td>\n"; }
	      else
		    {
		      print "<td $width>";

		      print '<a href="?'.$value.'">';

		      print '&nbsp;#'.$value .'</a></td>'."\n";

		    }

	    }
	  else if (trackers_data_is_username_field($field_arr[$j]))
	    {

	      if ($nolink)
		{ print "<td $width>$value</td>\n"; }
	      else
		{ print "<td $width>".utils_user_link($value)."</td>\n"; }

	    }
	  else if (trackers_data_is_select_box($field_arr[$j]))
	    {
	      print "<td $width>". trackers_data_get_cached_field_value($field_arr[$j], $group_id, $value) .'</td>'."\n";

	    }
	  else
		{
		  if ($nolink)
		    { print "<td $width>". $value .'&nbsp;</td>'."\n"; }
		  else
		    {
		      print "<td $width>".'<a href="?'.$thisitem_id.'">'.
			$value .'</a></td>'."\n";
		    }
		}

	}
      print "</tr>\n";
    }

  print '</table>';

  # Print prev/next links
  print "<br />".$nav_bar;

}


# Show the changes of the tracker data we have for this item,
# excluding details
function show_item_history ($item_id,$group_id, $no_limit=false)
{
  global $sys_datefmt;
  $result=trackers_data_get_history($item_id);
  $rows=db_numrows($result);
  
  if ($rows > 0)
    {
      
     # If no limit is not set, print only 25 latest news items
     # yeupou--gnu.org 2004-09-17: currently we provide no way to get the
     # full history. We will see if users request it.
      if (!$no_limit)
	{
	  if ($rows > 25)
	    { $rows = 25; }
	  
	  $title = sprintf(ngettext("Follows %s latest change.", "Follow %s latest changes.", $rows), $rows);
	  print "\n".'<p>'.$title.'</p>';
	}    
      
      $title_arr=array();
      $title_arr[]=_("Date");
      $title_arr[]=_("Changed By");
      $title_arr[]=_("Updated Field");
      $title_arr[]=_("Previous Value");
      $title_arr[]="=>";
      $title_arr[]=_("Replaced By");

      print html_build_list_table_top ($title_arr);

      $j=0;
      for ($i=0; $i < $rows; $i++)
	{
          $field = db_result($result, $i, 'field_name');

          # If the stored label is "realdetails", it means it is the details
          # field (realdetails is used because someone had the nasty idea to
          # use "details" to mean "comment")
          if ($field == "realdetails")
            { $field = "details"; }

	  $field_label = trackers_data_get_label($field);

          # if field_label is empty, no label was found, return as it is stored
          if (!$field_label)
            { $field_label = $field; }

	  $value_id =  db_result($result, $i, 'old_value');
	  $new_value_id =  db_result($result, $i, 'new_value');

          $date = db_result($result, $i, 'date');
          $user = db_result($result, $i, 'user_name');

          # If the previous date and user are equal, do not print user
          # and date
          if ($date == $previous_date && $user == $previous_user)
            {
     	  print "\n".'<tr class="'. utils_get_alt_row_color($j).'"><td>&nbsp;</td><td>&nbsp;</td>';

            }
          else
            {

          $j++;
	  print "\n".'<tr class="'. utils_get_alt_row_color($j).'">';

	  # Date
	  print '<td align="center" class="smaller">'.format_date($sys_datefmt,$date).'</td>';

	  # Person
	  print '<td align="center" class="smaller">'.utils_user_link($user).'</td>';
            }

          $previous_date = $date;
          $previous_user = $user;

	  # Updated Field
	  print '<td class="smaller" align="center">'.$field_label.'</td>';

	  # Previous value
	  print '<td class="smaller" align="right">';
	  if (trackers_data_is_select_box($field))
	    {
	      # Its a select box look for value in clear
              # (If we hit case of transition automatique update, show it in
              # specific way)
              if ($value_id == "transition-other-field-update")
                {
	          print "-"._("Automatic update due to transitions settings")."-";
                }
              else
                {
                  print trackers_data_get_value($field, $group_id, $value_id);
                }
	    }
	  else if (trackers_data_is_date_field($field))
	    {
	      # For date fields do some special processing
	      print format_date($sys_datefmt,$value_id);
	    }
	  else
	    {
	      # It's a text zone then display directly
 	      print markup_basic($value_id);
	    }

           print '</td><td class="smaller" align="center"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/next.png" border="0" alt="=>" /></td><td class="smaller" align="left">';
	  # New value
	  if (trackers_data_is_select_box($field))
	    {
	      # It's a select box look for value in clear
	      print trackers_data_get_value($field, $group_id, $new_value_id);
	    }
	  else if (trackers_data_is_date_field($field))
	    {
	      # For date fields do some special processing
	      print format_date($sys_datefmt,$new_value_id);
	    }
	  else
	    {
	      # It's a text zone then display directly
	      print markup_basic($new_value_id);
	    }
	  print '</td>';

	  print '</tr>';

	}
      print '</table>';

    }
  else
    {
      print "\n".'<span class="warn">'._("No Changes Have Been Made to This Item").'</span>';
    }
}


function show_item_details ($item_id, $group_id, $ascii=false, $item_assigned_to=false,$quoted=false)
{
  print format_item_details($item_id,$group_id,$ascii,$item_assigned_to,$quoted);
}



function show_item_attached_files ($item_id,$group_id, $ascii=false, $sober=false)
{
  print format_item_attached_files ($item_id,$group_id, $ascii, $sober);
}


function show_item_cc_list ($item_id,$group_id, $ascii=false)
{
  print format_item_cc_list ($item_id,$group_id, $ascii);
}


# Look for items that $item_id depends on in all artifact
function show_item_dependency ($item_id)
{
  return show_dependent_item($item_id, $dependson=1);
}


# Look for items that depends on $item_id in all artifacts (default)
# or look for items that $item_id depends on in all artifact.
function show_dependent_item ($item_id, $dependson=0)
{
  global $group_id;

  $artifacts = array("support", "bugs", "task", "patch");
  $is_manager = member_check(0,$group_id,member_create_tracker_flag(ARTIFACT).'1');

  if (!$dependson)
    { $title = _("Items that depend on this one"); }
  else
    { $title = _("Depends on the following items"); }

  # Create hash that will contain every relevant info
  # with keys like $date.$item_id so it will be sorted by date (first)
  # and item_id (second)
  $content = array();

  # Slurps the database.
  $item_exists = false;
  $item_exists_tracker = false;
  while (list(,$art) = each($artifacts))
    {
      if (!$dependson)
	{
	  $sql = "SELECT ".$art.".bug_id,".$art.".date,".$art.".summary,".$art.".status_id,".$art.".resolution_id,".$art.".group_id,".$art.".priority,".$art.".privacy,".$art.".submitted_by ".
	     " FROM ".$art.",".$art."_dependencies ".
	     " WHERE ".$art.".bug_id=".$art."_dependencies.item_id ".
	     " AND ".$art."_dependencies.is_dependent_on_item_id='$item_id'".
	     " AND ".$art."_dependencies.is_dependent_on_item_id_artifact='".ARTIFACT."' ORDER by ".$art.".bug_id";
	}
      else
	{
	  $sql = "SELECT ".$art.".bug_id,".$art.".date,".$art.".summary,".$art.".status_id,".$art.".resolution_id,".$art.".group_id,".$art.".priority,".$art.".privacy,".$art.".submitted_by".
	     " FROM ".$art.",".ARTIFACT."_dependencies ".
	     " WHERE ".$art.".bug_id=".ARTIFACT."_dependencies.is_dependent_on_item_id ".
	     " AND ".ARTIFACT."_dependencies.item_id='$item_id'".
	     " AND ".ARTIFACT."_dependencies.is_dependent_on_item_id_artifact='".$art."' ORDER by ".$art.".bug_id ";

	}
      
      $res_all = db_query($sql);
      $numrows_all = db_numrows($res_all);
      for ($i=0; $i < $numrows_all; $i++)
	{
	  # Note for later that at least one item was found
	  $item_exists = 1;
	  $item_exists_tracker[$art] = 1;
	  
	  # Generate unique key date.tracker#nnn
	  $key = db_result($res_all, $i, 'date').'.'.
	    $art.'#'.
	    db_result($res_all,$i,'bug_id');
	  
          # Store relevant data
	  $content[$key]['item_id'] = db_result($res_all,$i,'bug_id');
	  $content[$key]['tracker'] = $art;
	  $content[$key]['date'] = db_result($res_all,$i,'date');
	  $content[$key]['summary'] = db_result($res_all,$i,'summary');
	  $content[$key]['status_id'] = db_result($res_all,$i,'status_id');
	  $content[$key]['resolution_id'] = db_result($res_all,$i,'resolution_id');
	  $content[$key]['group_id'] = db_result($res_all,$i,'group_id');
	  $content[$key]['priority'] = db_result($res_all,$i,'priority');
	  $content[$key]['privacy'] = db_result($res_all,$i,'privacy');
	  $content[$key]['submitted_by'] = db_result($res_all,$i,'submitted_by');
	}
      
    }

  # No item found? Exit here
  if (!$item_exists)
    {
      print '<p class="warn">'.sprintf(_("%s: %s"), $title, _("None found")).'</p>';
      return;
    }

  # Give back the HTML output, if we have some data.
  global $HTML;
  print $HTML->box_top($title,'',1);

  # Create a hash to avoid looking several times for the same
  # info 
  $dstatus = array();
  $allowed_to_see = array();
  $group_getname = array();

  # Sort the content by key, which contain the date as first field
  # (so order by date)
  ksort($content);
  unset($i);
  
  while (list($key,) = each($content))
    {
      $current_item_id = $content[$key]['item_id'];
      $tracker = $content[$key]['tracker'];
      $current_group_id = $content[$key]['group_id'];
      $link_to_item = $GLOBALS['sys_home'].$tracker.'/?'.$current_item_id;
	  
      # Found out the status full text name:
      # this is project specific. If there is no project setup for this
      # then go to the default for the site
      if (!array_key_exists($current_group_id.$tracker.$content[$key]['resolution_id'],
			    $dstatus))
	{
	  $dstatus[$current_group_id.$tracker.$content[$key]['resolution_id']] = 
	    db_result(db_query("SELECT value FROM ".$tracker."_field_value WHERE bug_field_id='108' AND (group_id='".$group_id."' OR group_id='100') AND value_id='".$content[$key]['resolution_id']."' ORDER BY bug_fv_id DESC LIMIT 1"), 0, 'value');
	}
      $status = $dstatus[$current_group_id.$tracker.$content[$key]['resolution_id']];
      
      print '
  <div class="'.get_priority_color($content[$key]['priority'], $content[$key]['status_id']).'">';
	  
      # Ability to remove a dependency is only given to technician
      # level members of a project.
      if ($dependson && $is_manager)
	{
	  print '<span class="trash"><a href="'.$_SERVER['PHP_SELF'].'?func=delete_dependancy&amp;item_id='.$item_id.'&amp;item_depends_on='.$current_item_id.
	    '&amp;item_depends_on_artifact='.$tracker.'">'.
	    '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/trash.png" alt="'._("Delete this dependancy?").'" class="icon" /></a></span>';
	}
	  
      # Link to the item
      print '
   	<a href="'.$link_to_item.'" class="block">';
      
      # Show the item type with an icon
      print '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/'.utils_get_tracker_icon($tracker).'.png" class="icon" alt="'.$tracker.'" /> ';
      
      # Print summary only if the item is not private
      # Check privacy right (dont care about the tracker specific
      # rights, being project member is enough)
      if (!array_key_exists($current_group_id, $allowed_to_see))
	{
	  $allowed_to_see[$current_group_id] = member_check(0,$current_group_id,member_create_tracker_flag(ARTIFACT).'2');
	  
	}
      
      if ($content[$key]['privacy'] == "2" &&
	  !$allowed_to_see[$current_group_id] &&
	  $content[$key]['submitted_by'] != user_getid())
	{ 
	      print _("---- Private ----");		
	}
      else
	{ 
	  print $content[$key]['summary']; 
	}
	  
       # Print group info if the item is from another group
      unset($fromgroup);
      if ($current_group_id != $group_id)
	{
	  if (!array_key_exists($current_group_id, $group_getname))
	    {
	      $group_getname[$current_group_id] = group_getname($content[$key]['group_id']).', ';
	    }
	  
	  $fromgroup = $group_getname[$current_group_id];
	}
      
      # Mention the status
      print '&nbsp;<span class="xsmall">('.utils_get_tracker_prefix($tracker).' #'.$current_item_id.', '.$fromgroup.$status.')</span></a>';
      print '</div>';
      
      $i++;
    }
  print $HTML->box_bottom(1);

      
  # Add links to make digests
  reset($artifacts);
  print '<p class="noprint"><span class="preinput">'._("Digest:").'</span><br />&nbsp;&nbsp;&nbsp;';
  unset($content);
  while (list(, $tracker) = each($artifacts))
    {
      if ($item_exists_tracker[$tracker])
	{
	  switch ($tracker)
	    {
	    case "support":
	      $linktitle = _("support dependencies");
	      break;
	    case "bugs":
	      $linktitle = _("bug dependencies");
	      break;
	    case "task":
	      $linktitle = _("task dependencies");
	      break;
	    case "patch":
		  $linktitle = _("patch dependencies");
		  break;
	    default:
	      $linktitle = sprintf(_("%s dependencies"), $tracker);
	    }
	  $content .= utils_link($GLOBALS['sys_home'].$tracker.'/?group_id='.$group_id.'&amp;func=digestselectfield&amp;dependencies_of_item='.$item_id.'&amp;dependencies_of_tracker='.ARTIFACT, "$linktitle", 'noprint').', ';
	      
	}
    }
  print rtrim($content, ', ').'.</p>';

}


?>