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

# There are parameters that defined before, in the pages that include browse.
# For instance, $sober is defined by the cookbook/index.php page


$preference_prefix = ARTIFACT;
if ($sober)
{
  $preference_prefix .= "-sober";
}


# Number of search criteria (boxes) displayed in one row
$fields_per_line=5;

# Avoid undesired user input
$browse_preamble = '';

# Number of bugs displayed on screen in one chunk.
# Default 50
$chunksz = sane_get("chunksz");
if (!$chunksz)
{ $chunksz = 50; }

# Digest mode? Set the digest variable to one
$func = sane_get("func");
if ($func == "digest")
{ $digest = 1; }
else
{
  $digest = 0;
  $func = "browse";
}

# Make sure offset is defined and has a correct value
$offset = sane_get("offset");
if (!$offset || $offset < 0)
{ $offset = 0; }


$msort = sane_get("msort");
$sumORdet = sane_get("sumORdet");
$order = sane_get("order");
$printer = sane_get("order");

$hdr = '';

# Make sure spamscore has a numeric value between 1 and
# 20.
# (we will search for items that have score inferior to $spamscore)
# Default is five. Under 5, an item is not considered to be spam by the 
# system. But users can however decide to use a tougher limit
$spamscore = sane_get("spamscore");
if (!ctype_digit($spamscore) || $spamscore < 1)
{ $spamscore = 5; }
if ($spamscore > 20)
{ $spamscore = 20; }



# ==================================================
#    Get the list of bug fields used in the form (they are in the URL - GET method)
#    and then build the preferences array accordingly
#    Exclude the group_id parameter# Extract the list of bug fields  
#
#  NB: Note that trackers_extract_field_list function does build and
#      return date arguments (using _dayfd|monthfd|yearfd boxes) whether
#      or not they are tracker fields used by the project.  
# ================================================== 

# $prefs renamed $url_params to avoid confusion with $pref_arr and $pref_stg
# used further down 
$url_params = trackers_extract_field_list(false);
unset($url_params['group_id']);

# Get rid of url_params['history_date'] which has been included
# by trackers_extract_field_list
unset($url_params['history_date']);


# ==================================================
#   Make sure all URL arguments are captured as array. For simple
#   search they'll be arrays with only one element at index 0 (this
#   will avoid to deal with scalar in simple search and array in
#   advanced which would greatly complexifies the code)
# ================================================== */
$advsrch = sane_get("advsrch");
while (list($field,$value_id) = each($url_params))
{
  if (!is_array($value_id))
    {
      unset($url_params[$field]);
      $url_params[$field][] = $value_id;
      #print '<br /> DBG Setting $url_params['.$field.'] [] = '.$value_id;
    }
  else
    {
      #print '<br /> DBG $url_params['.$field.'] = ('.implode(',',$value_id).')';
    }

  if (trackers_data_is_date_field($field))
    {
      if ($advsrch)
	{
	  $field_end = $field.'_end';
	  $url_params[$field_end] = $$field_end;
	  #print '<br /> DBG Setting $url_params['.$field.'_end]= '.$prefs[$field.'_end'];
	}
      else
	{
	  $field_op = $field.'_op';
	  $url_params[$field_op] = $$field_op;
	  if (!$url_params[$field_op])
	    { $url_params[$field_op] = '='; }
	  #print '<br /> DBG Setting $url_params['.$field.'_op]= '.$url_params[$field.'_op'];
	}
    }
}

# If history event additional constraint is used, add it
$history_search = sane_get("history_search");
$history_field = sane_get("history_field");
$history_event = sane_get("history_event");
$history_date = sane_get("history_date");
$history_date_yearfd = sane_get("history_date_yearfd");
$history_date_monthfd = sane_get("history_date_monthfd");
$history_date_dayfd = sane_get("history_date_dayfd");
if ($history_search)
{
  # Dates must numeric date, even can be only modified or unmodified
  # If there is crap in there, ignore silently
  if (ctype_digit($history_date_yearfd) &&
      ctype_digit($history_date_monthfd) &&
      ctype_digit($history_date_dayfd))
    {
      $history_date = "$history_date_yearfd-$history_date_monthfd-$history_date_dayfd";
      $url_params['history'][] = addslashes($history_search).'>'.addslashes($history_field).'>'.addslashes($history_event).'>'.$history_date;
    }
}

# ==================================================
#   Memorize order by field as a user preference if explicitly specified.
#
#   $morder = comma separated list of sort criteria followed by - for
#     DESC and + for ASC order
#   $order = last sort criteria selected in the UI
#   $msort = 1 if multicolumn sort activated.
#  ================================================== 

#print "<br />DBG \$morder at top: [$morder ]";

# if morder not defined then reuse the one in preferences
if (user_isloggedin() && !isset($morder))
{
  $morder = user_get_preference($preference_prefix.'_browse_order'.$group_id);
}

if (isset($order))
{
  if (($order != '') && ($order != 'digest'))
    {
      # Add the criteria to the list of existing ones
      $morder = trackers_add_sort_criteria($morder, $order, $msort);
    }
  else
    {
      # Reset list of sort criteria 
      $morder = '';
    }
}

if ($morder != '')
{
  # If the user is logged in, save the wanted order if different from the
  # current
  if (user_isloggedin())
    {
      if ($morder != user_get_preference($preference_prefix.'_browse_order'.$group_id))
	{
	  user_set_preference($preference_prefix.'_browse_order'.$group_id, $morder);
	}
    }
}

# Fill the relevant sql bit to be used later
# Sensible default case: order by item_id from the recent to the older
# (only if not in multiple column sort, otherwise dont mess with it because
# the first thing to be set will matters a lot)
if ($morder == '' && !$msort)
{ $morder = "bug_id<"; }
if ($morder != '')
{ $order_by = ' ORDER BY '.trackers_criteria_list_to_query($morder); }

#print "<BR>>DBG Order by = $order_by";


# ==================================================
#  If the report type is not defined then get it from the user preferences.
#  If it is set then update the user preference.  Also initialize the
#  bug report structures.
#  ================================================== 
$report_id = sane_get("report_id");
if (user_isloggedin())
{
  if (!isset($report_id))
    {
      $report_id = user_get_preference($preference_prefix.'_browse_report'.$group_id);
    }
  else
    {
      if ($report_id != user_get_preference($preference_prefix.'_browse_report'.$group_id))
	user_set_preference($preference_prefix.'_browse_report'.$group_id, $report_id);
    }
}

# If still not defined then force it to system 'Basic' report
# of to 'Sober Basic' in sober mode, 103. This is hardcoded, if at some point
# we need to put the sober output of existing trackers where 103 may already
# be used, we will have to think of an other way to put it.
if (!$report_id)
{
  if (!$sober)
    { $report_id = 100; }
  else
    { $report_id = 103; }
} 

trackers_report_init($group_id, $report_id);


# ==================================================
#  Now see what type of bug set is requested (set is one of none,
#  'my', 'open', 'custom').
#    - if no set is passed in, see if a preference was set ('custom' set).
#    - if no preference and logged in then use 'my' set
#    - if no preference and not logged in the use 'open' set
#     (Prefs is a string of the form
#     &amp;field1[]=value_id1&amp;field2[]=value_id2&amp;.... )
#  ================================================== 
$set = sane_get("set");
$msort = sane_get("msort");
if (!$set)
{

  if (user_isloggedin())
    {

      $custom_pref=user_get_preference($preference_prefix.'_brow_cust'.$group_id);

      if ($custom_pref)
	{
	  $pref_arr = explode('&amp;', substr($custom_pref, 5));
	  while (list(,$expr) = each($pref_arr))
	    {
	      # Extract left and right parts of the assignment
	      # and remove the '[]' array symbol from the left part
	      list($field,$value_id) = explode('=',$expr);
	      $field = str_replace('[]','',$field);
	      if ($field == 'advsrch')
		{ $advsrch = $value_id; }
	      else if ($field == 'msort')
		{ $msort = $value_id; }
	      else if ($field == 'chunksz')
		{ $chunksz = $value_id; }
	      else if ($field == 'spamscore')
		{ $spamscore = $value_id; }
	      else if ($field == 'report_id')
		{ $report_id = $value_id; }
	      else if ($field == 'sumORdet')
		{ $sumORdet = $value_id; }
	      else if ($field == 'history') 
		{
		  $history = $value_id;
		  $hist_pref = explode('>', $history);
		  $history_search = $hist_pref[0];
		  $history_field = $hist_pref[1];
		  $history_event = $hist_pref[2];
		  $history_date = $hist_pref[3];
		
                  # if not args in url (means not after post) ... 
                  # set $url_params['history'] explicitly since 'history' 
                  # is not a tracker field and thus wont have been set
		  $url_params['history'][] = $history_search.'>'.$history_field.'>'.$history_event.'>'.$history_date;

		}
	      else
		{ 
		  $url_params[$field][] = $value_id; 
		}

	      #print '<br />DBG restoring prefs : $url_params['.$field.'] []='.$value_id;
	    }
	  $set='custom';

	}
      else
	{
	  $set='open';
	}

    }
  else
    {
      $set='open';
    }
}


if ($set=='my')
{
  #  My bugs - backwards compat can be removed 9/10
  $url_params['status_id'][]=1;
  $url_params['assigned_to'][]=user_getid();

}
else if ($set=='custom')
{
  # Use the list of fields built from the arguments and used by the project
  # (the group_id parameter has been excluded)
  # NB: Note that trackers_extract_field_list function did build and
  #     return date arguments (using _dayfd|monthfd|yearfd boxes) whether
  #     or not they were tracker fields used by the project.

  reset($url_params);
  while (list($field,$arr_val) = each($url_params))
    {
      while (list(,$value_id) = each($arr_val))
	{
	  $pref_stg .= '&amp;'.$field.'[]='.$value_id;
	}

      # build part of the HTML title of this page for more friendly bookmarking
      # Do not add the criteria in the header if value is "Any"
      if ($value_id != 0)
	{
	  $hdr .= _(" By ").trackers_data_get_label($field).': '.
	     trackers_data_get_value($field,$group_id,$value_id);
	}
    }
  $pref_stg .= '&amp;advsrch='.$advsrch;
  $pref_stg .= '&amp;msort='.$msort;
  $pref_stg .= '&amp;chunksz='.$chunksz;
  $pref_stg .= '&amp;spamscore='.$spamscore;
  $pref_stg .= '&amp;report_id='.$report_id;
  $pref_stg .= '&amp;sumORdet='.$sumORdet;

  if ($pref_stg != user_get_preference($preference_prefix.'_brow_cust'.$group_id))
    {
      #print "<br /> DBG setting pref = $pref_stg";
      user_set_preference($preference_prefix.'_brow_cust'.$group_id,$pref_stg);
    }

}
else
{
  # We want to reset to all open items?
  # Force the status_id to open, set nothing else, trash the prefes
  $url_params['status_id'][]=1;
  user_unset_preference($preference_prefix.'_brow_cust'.$group_id);
  
}

# ==================================================
#   At this point make sure that all paramaters are defined
#   as well as all the arguments that serves as selection criteria
#   If not defined then defaults to ANY (0)
# ================================================== 
if (!isset($advsrch))
{ $advsrch = 0; }
if (!isset($msort))
{ $msort = 0; }

# Will be used later to find out if it make sense to look for items of the
# system group (meaningful on the cookbook)
$not_group_specific = 1;

global $BF_USAGE_BY_ID;
while ($field = trackers_list_all_fields())
{
  # the select boxes for the bug DB search first
  if (trackers_data_is_showed_on_query($field) &&
      trackers_data_is_select_box($field) )
    {
  if (array_key_exists('category_id', $BF_USAGE_BY_ID)) { temp_dbg("browse:BF_USAGE_BY_ID contains category_id after field=$field<br />"); }
      if (!isset($url_params[$field])) 
	{ $url_params[$field][] = 0; }

      # If we are about to generate the sober output, find out if we can
      # look for items of the site admin project or not. 
      # All fields that have preconfigured values that can be changed by
      # projects could end up in flawed results, because the value id and the
      # actual value label (and so, meaning) are likely to be out of sync.
      # The most obvious case if the category field case.
      if (ARTIFACT == 'cookbook' && $sober)
	{
	  if (trackers_data_is_project_scope($field) && 
	      $url_params[$field][0] != '0' &&
	      $url_params[$field][0] != '100')
	    {
	      $not_group_specific = 0;
	    }
	}         
    }
}

# ==================================================
#   Start building the SQL query (select and where clauses)
# ================================================== 

# Force the selection of priority because it is always shown as color code
# Force the selection of privacy, we always want to be sure that no private
# item title is provided to everybody.
$full_field_list = $col_list = $lbl_list = array();
$select_count = 'SELECT count(DISTINCT '.ARTIFACT.'.bug_id) AS count ';
$select = 'SELECT DISTINCT '.ARTIFACT.'.group_id,'.ARTIFACT.'.priority,'.ARTIFACT.'.privacy,'.ARTIFACT.'.status_id';
$from = 'FROM '.ARTIFACT.' ';

# On the cookbook in sober mode, we want the system wide recipes,
# as long as there is no field that comes as select boxes with values 
# configurable per project set.
if (ARTIFACT == 'cookbook' && $sober && $not_group_specific)
{
  $where = 'WHERE ('.ARTIFACT.'.group_id='.$group_id.' OR '.ARTIFACT.'.group_id='.$sys_group_id.') ';
}
else
{
  $where = 'WHERE '.ARTIFACT.'.group_id='.$group_id.' ';
}

# Take into account the spamscore limit (always shows
# item posted by the logged in user)
unset($spamscore_additional);
if (user_getid() != 100)
{ $spamscore_additional = ' OR submitted_by='.user_getid(); }
$where .= 'AND ('.ARTIFACT.'.spamscore < '.$spamscore.$spamscore_additional.') ';

# If the user asked for more than 150 items to be shown but is not in printer
# mode, restrict arbitrarily to 150: 
# It would be too heavy on the database if this was done very frequently
# and we already found some project giving direct links to 500 the browse
# item page with 500 items shown by default.
# Save the wanted number of chunksz, for later
$wanted_chunksz = $chunksz;
if ($chunksz > 150 && 
    !$printer &&
    !$digest)
{ $chunksz = 150; }

# No limit on sober output, we want all recipes
unset($limit);
if (!$sober)
{ $limit = " LIMIT $offset,$chunksz"; }


# prepare the where clause with the selection criteria given by the user
reset($url_params);

# prepare for summary and original submission as 'special' criteria
unset($summary_search, $details_search);

while (list($field,$value_id) = each($url_params))
{

  # If the criteria is not in the field showed on query screen then
  # skip it. This is a sanity check to make sure that the SQL
  # query we run actually matches the displayed search criteria
  if (!trackers_data_is_showed_on_query($field))
    { continue; }

  if (trackers_data_is_select_box($field) && !trackers_isvarany($url_params[$field]) )
    {

      # Only select box criteria to where clause if argument is not ANY
      $where .= ' AND '.ARTIFACT.'.'.$field.' IN ('.implode(',',$url_params[$field]).') ';

    }
  else if (trackers_data_is_date_field($field) && $url_params[$field][0])
    {

      # Transform a date field into a unix time and use <, > or =
      list($time,$ok) = utils_date_to_unixtime($url_params[$field][0]);
      preg_match("/\s*(\d+)-(\d+)-(\d+)/", $url_params[$field][0],$match);
      list(,$year,$month,$day) = $match;

      if ($advsrch)
	{
	  list($time_end,$ok_end) = utils_date_to_unixtime($url_params[$field.'_end'][0]);
	  if ($ok)
	    {
	      $where .= ' AND '.ARTIFACT.'.'.$field.' >= '. $time; }

	  if ($ok_end)
	    { $where .= ' AND '.ARTIFACT.'.'.$field.' <= '. $time_end; }

	}
      else
	{

	  $operator = $url_params[$field.'_op'][0];
          # '=' means that day between 00:00 and 23:59
	  if ($operator == '=')
	    {
	      $time_end = mktime(23, 59, 59, $month, $day, $year);
	      $where .= ' AND '.ARTIFACT.'.'.$field." >= $time ".'AND '.ARTIFACT.'.'.$field." <= $time_end ";
	    }
	  else
	    {
	      $time = mktime(0,0,0, $month, ($day+1), $year);
	      $where .= ' AND '.ARTIFACT.'.'.$field." $operator= $time ";
	    }
	}

      # Always exclude undefined dates (0)
      $where .= ' AND '.ARTIFACT.'.'.$field." <> 0 ";

    }
  elseif ((trackers_data_is_text_field($field) ||
	    trackers_data_is_text_area($field)) &&
	   $url_params[$field][0])
    {
      # Buffer summary and original submission (details) to handle them later
      # in case we have an OR to do between the two, instead of the usual
      # AND
      if ($sumORdet == 1 && 
	  ($field == 'summary' || $field == 'details'))
	{	  
	  if ($field == 'summary') 
	    { $summary_search = 1; }
	  if ($field == 'details') 
	    { $details_search = 1; }
	} 
      else 
	{
          # It s a text field accept. Process INT or TEXT,VARCHAR fields differently
	  $where .= ' AND '.trackers_build_match_expression($field, $url_params[$field][0]);
	}
    }
}
 

# Handle summary and/or original submission now, if a AND is required
if ($sumORdet == 1)
{
  # We will process the usual normal AND case: there was something for both
  # fields.
  if ($details_search == 1 && $summary_search == 1)
    {
      $where .= ' AND ';
      $where .= '( ( ';
      $where .= trackers_build_match_expression('details', $url_params['details'][0]);
      $where .= ' ) OR ( ';
      $where .= trackers_build_match_expression('summary', $url_params['summary'][0]);
      $where .= ') ) ';
    }
  else
    {
      # Now we take care of the unusual, possible though, case where and
      # AND was asked but not both fields set.
      # Since the AND was asked, the fields havent been taken care of before
      # and we need to do it now.
      # We do that in two IF, in case something went very wrong. In such case
      # we will proceed with a usual AND.
      if ($details_search == 1 && $url_params['details'][0]) 
	{  
	  $where .= ' AND ';
	  $where .= trackers_build_match_expression('details', $url_params['details'][0]);
	}
      if ($summary_search == 1 && $url_params['summary'][0]) 
	{
	  $where .= ' AND ';
	  $where .= trackers_build_match_expression('summary', $url_params['summary'][0]);
	}
    }
}

# ==================================================
#   Loop through the list of used fields to define label and fields/boxes
#   used as search criteria
# ================================================== 

$ib=0;
$is=0;
$load_cal=false;

# Check if summary and original submission are criteria
$summary_search = 0;
$details_search = 0;

$labels = '';
$boxes = '';
$html_select = '';
while ($field = trackers_list_all_fields('cmp_place_query'))
{
  # Skip unused field
  if (!trackers_data_is_used($field))
    { continue; }

  # Skip fields not part of this query form
  if (!trackers_data_is_showed_on_query($field))
    { continue; }

  # beginning of a new row
  if ($ib % $fields_per_line == 0)
    {
      $align = ($printer ? "left" : "center");
      $labels .= "\n".'<tr align="'.$align.'" valign="top">';
      $boxes .= "\n".'<tr align="'.$align.'" valign="top">';
    }

  $labels .= '<td>'.trackers_field_label_display($field,$group_id,false,false).'</td>';
  $boxes .= '<td><span class="smaller">';

  if (trackers_data_is_select_box($field))
    {
      $boxes .=
	 trackers_field_display($field,$group_id,
				($advsrch ? $url_params[$field] : $url_params[$field][0]),
				false,false,($printer?true:false),false,true,'None', true,'Any');

    }
  elseif (trackers_data_is_date_field($field))
    {

      if ($advsrch)
	{
	  $boxes .= trackers_multiple_field_date($field,$url_params[$field][0],
						 $url_params[$field.'_end'][0],0,0,$printer);
	}
      else
	{
	  $boxes .= trackers_field_date_operator($field,$url_params[$field.'_op'][0],$printer).
	     trackers_field_date($field,$url_params[$field][0],0,0,$printer);
	}

    }
  elseif (trackers_data_is_text_field($field) ||trackers_data_is_text_area($field))
    {
      if ($field == 'summary') 
	{ $summary_search = 1; }
      if ($field == 'details') 
	{ $details_search = 1; }
      
      $boxes .=
	($printer ? $url_params[$field][0] : trackers_field_text($field,$url_params[$field][0],15,80)) ;
    }

  $boxes .= "</span></td>\n";

  $ib++;

# end of this row
  if ($ib % $fields_per_line == 0)
    {
      $html_select .= $labels.'</tr>'.$boxes.'</tr>';
      $labels = $boxes = '';
    }

}

# Make sure the last few cells are in the table
if ($labels)
{
  $html_select .= $labels.'</tr>'.$boxes.'</tr>';
}

# ==================================================
#   Loop through the list of used fields to see what fields are in the
#   result table and complement the SQL query accordingly.
# ================================================== 

# Add extra digest column, if necessary
if ($digest)
{
  $col_list[] = "digest";
  $width_list[] = "";
  $lbl_list[] = _("Digest");
}

$morder_icon_is_set = '';
while ($field = trackers_list_all_fields('cmp_place_result'))
{
  # Need the full list of used fields
  $full_field_list[] = $field;

  if (!trackers_data_is_used($field) ||
      !trackers_data_is_showed_on_result($field))
    { continue; }

  $col_list[] = $field;
  $width_list[] = trackers_data_get_col_width($field);
  
  # If we have the field that defines the order, add an icon.
  # Quite simple in monolcolumn
  if (!$msort)
    {
      if (!$morder_icon_is_set)
	{
	  if ($morder == "$field<" ||
	      $morder == "$field>")
	    {
	      $lbl_list[] = trackers_data_get_label($field).' '.
		'<img class="icon" src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/'.((substr($morder, -1) == '>') ? 'down' : 'up').
		'.png" border="0" />';
	      $morder_icon_is_set = 1;
	    }
	  else
	    {	  
	      $lbl_list[] = trackers_data_get_label($field);
	    }
	}
      else
	{
	  $lbl_list[] = trackers_data_get_label($field);
	}
    }
  else
    {
      # Less simple in multicolumn, indeed
      unset($morder_icon_is_set);
      $morder_arr = explode(',',$morder);
      while (list(,$crit) = each($morder_arr))
	{
	  if ($crit == "$field<" ||
	      $crit == "$field>")
	    {
	      $lbl_list[] = trackers_data_get_label($field).' '.
		'<img class="icon" src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/'.((substr($crit, -1) == '>') ? 'down' : 'up').
		'.png" border="0" />';

	      # If we found a criteria, go deal with the next column
	      $morder_icon_is_set = 1;
	      continue;
	    }
	}

      # If this field is not a sort criteria, we still have to create 
      # the column
      if (!$morder_icon_is_set)
	{
	  $lbl_list[] = trackers_data_get_label($field);
	}      
    }

  if (trackers_data_is_username_field($field))
    {
      # user names requires some special processing to display the username
      # instead of the user_id
      $select .= ",user_$field.user_name AS $field";
      $from .= ",user user_$field";
      $where .= " AND user_$field.user_id=".ARTIFACT.".$field ";
      #dbg("ICI $field ");
    }
  else
    {
      # otherwise just select this column as is
      $select .= ",".ARTIFACT.".$field";
    }

}

/* ==================================================
    Run 2 queries : one to count the total number of results, and the second
    one with the LIMIT argument. It is faster than selecting all
    rows (without LIMIT) because when the number of bugs is large it takes
    time to transfer all the results from the server to the client. It is also faster
    than using the SQL_CALC_FOUND_ROWS/FOUND_ROWS() capabilities of
    MySQL
  ================================================== */
$sql_count = "$select_count $from $where";
$result_count = db_query($sql_count);
$totalrows = db_result($result_count,0,'count');

$sql = "$select $from $where $order_by $limit";

# If additional constraint history_event is modified, this result will be
# replaced, and so we dont have to run this sql
if (!$history_search || $history_event != "modified") 
{
  $result = db_query($sql);
}

# Additional constraint
 $history_arr = array();
if ($history_search) 
{
  list($unix_history_date,$ok) = utils_date_to_unixtime($history_date);

  # Do 2nd sql query (on history) as long as MINUS is not supported by mysql
  $select_hist = $select;
  $from_hist = $from.', '.ARTIFACT.'_history ';
  $where_hist = $where.'AND '.ARTIFACT.'_history.bug_id = '.ARTIFACT.'.bug_id ';
  $where_hist .= ' AND '.ARTIFACT.'_history.date >= '.$unix_history_date.' ';
  if ($history_field != '0') 
    {
      $where_hist .= 'AND '.ARTIFACT."_history.field_name = '".$history_field."' ";
    }
  $sql_history_without_limit = "$select_hist $from_hist $where_hist $order_by";
  $sql_history = "$sql_history_without_limit $limit";
  $result_history = db_query($sql_history);

  # If the even is "modified", we replace the original sql results.
  # Otherwise, we build up a list of result to substract
  if ($history_event == "modified")
    {
      $result = $result_history;

      # In this case, we must recount the total number of results, 
      # because we wont be able substract results 
      # (like we do for the history event "unmodified") 
      # from the total count as we have a completely different sql
      $totalrows = db_numrows(db_query($sql_history_without_limit));
    }
  else
    {
      $rows_history = db_numrows($result_history);
      $history_arr = array();
      
      for ($i=0; $i < $rows_history; $i++) 
	{
          # Store the entry to be removed later
	  $hist_bug_id = db_result($result_history, $i, 'bug_id');
	  $history_arr[$hist_bug_id] = 1;
	}
    }
}

# Build the array that will be given to the function that make the item
# list. We cannot simply return the SQL results, since we have to remove
# some entries from the list first:
#      - private items if necessary
#      - items depending on the additional constraint
#
# $totalrows must be set accordingly
$result_array = array();
while ($thisarray = db_fetch_array($result))
{
  # Get the id
  $thisitem_id = $thisarray['bug_id'];

  # Check if we must ignore it due to history additional constraint
  if (!empty($history_arr[$thisitem_id]))
    { 
      $totalrows--;
      continue; 
    }  

  # Do not show private item, apart to technician level members
  # and submitter
  if ($thisarray["privacy"] == "2" &&
      !member_check_private(0,$group_id) &&
      $thisarray["submitted_by"] != user_getname())
    { 
      $totalrows--;
      continue; 
    }

  # Build a specific array for each item
  $result_array[$thisitem_id] = array();
  
  # Always store the group, it may be necessary later, in case we actually
  # look for items from different projects  
  $result_array[$thisitem_id]["group_id"] = $thisarray["group_id"];   

  # Store each field that will be necessary later
  $nb_of_fields = count($full_field_list);
  for ($k=0; $k<$nb_of_fields; $k++)
    {
      if (isset($thisarray[$full_field_list[$k]]))
	$result_array[$thisitem_id][$full_field_list[$k]] = $thisarray[$full_field_list[$k]];
      else
	$result_array[$thisitem_id][$full_field_list[$k]] = null;
    }
}

# DBG
#while (list(,$thisitem) = each($result_array))
#{
#  $thisitem_id = $thisitem['bug_id'];
#  print "TEST ".$thisitem_id.", ".$result_array[$thisitem_id]["summary"].", ".$result_array[$thisitem_id]["resolution_id"].", ".$result_array[$thisitem_id]["group_id"]." -- ";
#}


/* ==================================================
   Display the HTML search form
  ================================================== */

if ($printer)
{
  trackers_header(array('title'=>_("Browse Items").' - '.format_date($sys_datefmt,time())));
}
else
{
  trackers_header(array('title'=>_("Browse Items").' '.$hdr));
}

if ($browse_preamble)
{ print $browse_preamble; }


$form_opening = '<form action="'.$_SERVER['PHP_SELF'].'#options" method="get" name="bug_form">';
$form = '
          <input type="hidden" name="group" value="'.$group_name.'" />
          <input type="hidden" name="func" value="'.$func.'" />
          <input type="hidden" name="set" value="custom" />
          <input type="hidden" name="msort" value="'.$msort.'" />
          '._("Browse").' ';

# Show the list of available bug reports kind
$res_report = trackers_data_get_reports($group_id,user_getid(),$sober);
if (!$printer)
{
  # In sober mode, there is no relevant query form that have reportid = 100
  $show_100 = true;
  if ($sober)
    { $show_100 = false; }

  $form .= sprintf(_("with the %s query form"), 
		   html_build_select_box($res_report,
					 'report_id',
					 $report_id,
					 $show_100,
					 'Basic'));  # basic == 100
}
else
{ 
  while (list($f,$v) = db_fetch_array($res_report))
    {
      if ($f != $report_id) 
	{ continue; }
      else
	{
	  $report_name = $v;
	  break;
	}
	   
    }
  $form .= sprintf(_("with the %s query form"), $report_name);
}

# Start building the URL that we use to for hyperlink in the form
$url = $GLOBALS['sys_home'].ARTIFACT."/?group=$group_name&amp;func=".$func."&amp;set=$set&amp;msort=$msort";
if ($set == 'custom')
     $url .= $pref_stg;
     else
     $url .= '&amp;advsrch='.$advsrch;

$url_nomorder = $url;
# the htmlspecialchars() is necessary, because $morder
# contains < and > for the sorting order.
$url .= '&amp;morder='.htmlspecialchars($morder);

# Build the URL for alternate Search
if ($advsrch)
{
  $url_alternate_search = str_replace('advsrch=1','advsrch=0',$url);
  $text = _("Simple Search");
}
else
{
  $url_alternate_search = str_replace('advsrch=0','advsrch=1',$url);
  $text = _("Advanced Search");
}

# Select 'list form' or 'select' form
if (!$printer)
{
  $advsrch_0 = '';
  $advsrch_1 = '';
  if ($advsrch)
    {
      $advsrch_1 = ' selected="selected"';
    }
  else
    {
      $advsrch_0 = ' selected="selected"';
    }
  $form .= sprintf(' '._("and %s selection."), '<select name="advsrch"><option value="0"'.$advsrch_0.'>'._("Simple").'</option><option value="1"'.$advsrch_1.'>'._("Multiple").'</option></select>');
  $form_submit = '<input class="bold" value="'._("Apply").'" name="go_report" type="submit" />';
}
else
{
  if ($advsrch)
    { $advsrch_x = _("Multiple");  }
  else
    { $advsrch_x =  _("Simple");  }

  $form .= sprintf(' '._("and %s selection."), $advsrch_x);
}

$form .= '<table cellpadding="0" cellspacing="5">
        <tr><td colspan="'.$fields_per_line.'" nowrap="nowrap">';
$form .= $html_select;

$form .= '</table>';


# If both summary and org subm are searched, propose an OR instead of AND
if (($details_search == 1) && ($summary_search == 1)) 
{
  if (!$printer)
    {
      $form .= '<p class="smaller">'.
	sprintf(_("Use logical %s between '%s' and '%s' searches."),
		'<select name="sumORdet"><option value="0" '.(!$sumORdet ? 'selected="selected"':'').'>'._("AND").'</option><option value="1" '.($sumORdet ? 'selected="selected"':'').'>'._("OR").'</option></select>',
		rtrim(trackers_field_label_display("summary", $group_id, false,true), ': '),
		rtrim(trackers_field_label_display("details", $group_id, false,true), ': ')).
	'</p>';
    }
  else
    {
      $form .= '<p class="smaller">'.
	sprintf(_("Use logical %s between '%s' and '%s' searches."),
		($sumORdet ? _("OR"):_("AND")),
		rtrim(trackers_field_label_display("summary", $group_id, false,true), ': '),
		rtrim(trackers_field_label_display("details", $group_id, false,true), ': ')).
	'</p>';
    }
  
  # Update the url
  $url .= "&amp;sumOrdet=$sumORdet";
}


## Propose to search for field updated since a certain date
$fextracted = array();
$fname = array();
$flabel = array();

# Extract the list of relevant fields
while ($field = trackers_list_all_fields()) 
{
  # Do not show fields not used by the project
  if ( !trackers_data_is_used($field)) 
    { continue; }

  # Special fields are usually not modifiable by users, it is the system
  # that defines their content. As such, they cannot be used as 
  # additional constraint.
  if (trackers_data_is_special($field)) 
    { continue; }
  
  $fextracted[$field] = trackers_data_get_label($field);
}

# Order them by name: in a select box, following the configure output order
# is not user-friendly.
asort($fextracted);
while (list($field,$label) = each($fextracted)) 
{
  $fname[] = $field;
  $flabel[] = $label;
}

$hist_ev_text  = array();
$hist_ev_text[] = _("modified");
$hist_ev_text[] = _("not modified");
$hist_ev_value  = array();
$hist_ev_value[] = "modified";
$hist_ev_value[] = "not modified";

# If we are in sober mode, additional constraint is not proposed
if (!$sober)
{
  if (!$printer)
    {
      $form .= '<p class="smaller"><span class="preinput">'.sprintf(_("Additional constraint %s:"),	'<select name="history_search"><option value="0" '.(!$history_search ? 'selected="selected"':'').'>'._("deactivated").'</option><option value="1" '.($history_search ? 'selected="selected"':'').'>'._("activated").'</option></select>').'</span><br />&nbsp;&nbsp;&nbsp;'.
	
     # I18N
     # This is for specifying additional constraints for the result list.
     # The first string is the fieldname 
     # (e.g. "category", "privacy", "status"),
     # the second is currently either "modified" or "not modified", and the
     # third string is a date.
     # Full example: "Severity not modified since 14. September 2005"
	sprintf(_("%s%s since %s"),
		html_build_select_box_from_arrays ($fname,
						   $flabel,
						   'history_field',
						   $history_field, 
						   false,
						   '',
						   true,
						   'Any', 
						   false),
		html_build_select_box_from_arrays ($hist_ev_value,
						   $hist_ev_text,
						   'history_event',
						   $history_event,
						   false,
						   '',
						   false,
						   '',
						   false),
		trackers_field_date('history_date',
				    $history_date,
				    0,
				    0,
				    false)).'</p>';
    }
  else
    {
      # In printer mode, if the additional constrait is off,
      # no need to print it
      if ($history_search)
	{
	  $form .= '<p class="smaller"><span class="preinput">'.sprintf(_("Additional constraint %s:"), _("activated")).
	    '</span> '.
	    sprintf(_("%s%s since %s"),
		    $flabel[$history_field],
		    $hist_ev_text[$history_event],
		    trackers_field_date('history_date',
					$history_date,
					0,
					0,
					true)).'</p>';
	}
    }
}
if ($history_search)
{ 
  # Update the url
  $url .= "&amp;history_search=$history_search&amp;history_field=$history_field&amp;history_event=$history_event&amp;history_date=$history_date";
}


# Number of items printed at once
# In sober mode, they are all printed
if (!$sober)
{
  if (!$printer)
    {
      # I18N
      # %s in this message is an input field, so it is impossible to use ngettext.
      $form .= '<p class="smaller">'.sprintf(_("Show: %s items at once ; items with a spam score lower than %s."), 
					     form_input("text", "chunksz", $wanted_chunksz, 'size="3" maxlength="5"'),
					     form_input("text", "spamscore", $spamscore, 'size="3" maxlength="2"'));
      if ($wanted_chunksz != $chunksz)
	{
          # No use of ngettext as $chunksz will never be below 10, otherwise
	  # it would mean that Savane would be modified to never list more
          # than 10 items at once, which is almost nothing.
	  $form .= ' <span class="warn">'.sprintf(_("Warning: only %s items can be shown at once, unless using Printer Version."), $chunksz).'</span>';
	}
      
      $form .= '</p>';
    }
  else
    {
      $form .= '<p class="smaller">'.sprintf(_("Show: %s items at once ; items with a spam score lower than %s."), $chunksz, $spamscore).'</p>';
    }
}

# In sober mode, do not talk about sorting order, we will anyway not use
# tables to sort the items
if ($totalrows > 0 && !$sober)
{
  $form .= '<p class="smaller">';
  if ($msort)
    {
      $url_alternate_sort = str_replace('msort=1','msort=0',$url).'&amp;order=#results';
      $form .= sprintf(_("Click a column heading to sort results (up or down), or %ssort by priority%s or %sreset sort%s. You can also %sdeactivate multicolumn sort%s."),
		       '<a href="'.$url.'&amp;order=priority#results">', '</a>',
		       '<a href="'.$url.'&amp;order=#results">', '</a>',
		       '<a href="'.$url_alternate_sort.'">', '</a>');
    }
  else
    {
      $url_alternate_sort = str_replace('msort=0','msort=1',$url).'&amp;order=#results';
      $form .= sprintf(_("Click a column heading to sort results (up or down), or %ssort by priority%s or %sreset sort%s. You can also %sactivate multicolumn sort%s."),
		       '<a href="'.$url.'&amp;order=priority#results">', '</a>',
		       '<a href="'.$url.'&amp;order=#results">', '</a>',
		       '<a href="'.$url_alternate_sort.'">', '</a>');
    }
  if ($morder)
    {
      $form .= " ".sprintf(_("Currently, results are sorted by %s."), trackers_criteria_list_to_text($morder, $url_nomorder));
    }
  $form .= '</p>';
}

# Print the form
print html_show_displayoptions($form,$form_opening,$form_submit);

/* ==================================================
  Finally display the result table
 ================================================== */

if ($digest)
{
  print '<form action="'.$_SERVER['PHP_SELF'].'" method="get">
<input type="hidden" name="group" value="'.$group_name.'" />
<input type="hidden" name="func" value="digestselectfield" />
';

}

if ($totalrows > 0)
{
  if (!$sober)
    {
      
      show_item_list($result_array,$offset,$totalrows,$col_list,$lbl_list,$width_list,$url,false);
      
      if ($digest)
	{
	  print form_footer(_("Proceed to Digest next step"));
	}

      show_priority_colors_key();
    }
  else
    {
      # Sober got it is own list design
      show_item_list_sober($result_array,$totalrows,$width_list,$url,false);
    }

}
else
{

  print '<h2>'._("No matching items found. The display criteria may be too restrictive.").'</h2>';
  print db_error();

}

trackers_footer(array());

?>
