<?php
# List of tracker items, with various sorts & filters
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2003-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2007  Sylvain Beucler
# Copyright (C) 2014, 2017-2022  Ineiev
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

# There are parameters that defined before, in the pages that include browse.
# For instance, $sober is defined by the cookbook/index.php page.

require_once(dirname(__FILE__).'/../trackers/show.php');

$preference_prefix = ARTIFACT;
if ($sober)
  $preference_prefix .= "-sober";

extract (sane_import ('get',
  [
    'digits' =>
      [
        'chunksz', 'offset', 'report_id',
        ['msort', 'sumORdet', 'advsrch', 'history_search', [0, 1]],
        ['spamscore', [1, null]],
        ['history_date_yearfd', [1900, null]],
        ['history_date_monthfd', [1, 12]],
        ['history_date_dayfd', [1, 31]],
      ],
    'name' => 'history_field',
    'strings' =>
      [
        ['func', ['default' => 'browse', 'digest']],
        ['set', ['custom', 'my', 'open']],
        ['history_event', ['modified', 'not modified']]
      ],
    'preg' =>
      [
        ['history_date', '/^\d{4}-\d{1,2}-\d{1,2}$/'],
        ['order', '/^([_a-zA-Z-][_[:alnum:]-]*)?$/'],
        ['morder', '/^[,<>_[:alnum:]-]*$/']
      ],
    'true' => 'printer'
  ]
));

# Number of search criteria (boxes) displayed in one row.
$fields_per_line=5;

# Avoid undesired user input.
$browse_preamble = '';

# Number of bugs displayed on screen in one chunk.
# Default 50.
if (!$chunksz)
  $chunksz = 50;
$chunksz = intval ($chunksz);

# Digest mode? Set the digest variable to one.
$digest = $func == 'digest';

# Make sure offset is defined and has a correct value.
$offset = intval ($offset);

if ($history_field === null)
  $history_field = '0';

$hdr = _('Browse Items');

# Make sure spamscore has a numeric value between 1 and 20
# (we will search for items that have score inferior to $spamscore).
# Default is five. Under 5, an item is not considered to be spam by the
# system. But users can however decide to use a tougher limit.
if ($spamscore === null)
  $spamscore = 5;
if ($spamscore > 20)
  $spamscore = 20;
$spamscore = intval ($spamscore);

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
# used further down.
$url_params = trackers_extract_field_list(false);
unset($url_params['group_id']);

# Get rid of url_params['history_date'] which has been included
# by trackers_extract_field_list.
unset($url_params['history_date']);

# Make safe for inclusion in an URL (replace quotes with dots).
function sanitize_field (&$x)
{
  $x = strtr ($x, '"' . '"', '..');
  return $x;
}

function sanitize_value_id ($value_id)
{
  if (!is_array ($value_id))
    return [sanitize_field ($value_id)];
  $ret = [];
  foreach ($value_id as $key => $val)
    $ret[sanitize_field ($key)] = sanitize_field ($val);
  return $ret;
}

#  Make sure all URL arguments are captured as array. For simple
#  search they'll be arrays with only one element at index 0 (this
#  will avoid to deal with scalar in simple search and array in
#  advanced which would greatly complexifies the code).

foreach ($url_params as $field => $value_id)
  {
    unset ($url_params[$field]);
    sanitize_field ($field);
    $url_params[$field] = sanitize_value_id ($value_id);

    if (trackers_data_is_date_field($field))
      {
        $co_field = $field . ($advsrch? '_end': '_op');
        $names = ['strings' => [[$co_field, ['>', '=', '<']]]];
        if ($advsrch)
          $names = ['preg' => [[$co_field, '/^\d{4}-\d{1,2}-\d{1,2}$/']]];
        $in = sane_import ('post', $names);
        $url_params[$co_field] = $in[$co_field]; 
        if (!$advsrch && !$url_params[$co_field])
          $url_params[$co_field] = ['='];
      }
  }

# If history event additional constraint is used, add it.
if ($history_search)
  # Dates must numeric date, even can be only modified or unmodified
  # If there is crap in there, ignore silently
  if ($history_date_yearfd !== null
      && $history_date_monthfd !== null
      && $history_date_dayfd !== null)
    {
      $history_date = "$history_date_yearfd-"
                      ."$history_date_monthfd-$history_date_dayfd";
      $url_params['history'][] = $history_search.'>'.$history_field.'>'
                                 .$history_event.'>'.$history_date;
    }

# ==================================================
#   Memorize order by field as a user preference if explicitly specified.
#
#   $morder = comma separated list of sort criteria followed by < for
#     DESC and > for ASC order
#   $order = last sort criteria selected in the UI
#   $msort = 1 if multicolumn sort activated.
#  ==================================================
# if morder not defined then reuse the one in preferences.
if (user_isloggedin() && !isset($morder))
  $morder = user_get_preference($preference_prefix.'_browse_order'.$group_id);

if ($order !== null)
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
  # current.
    if (user_isloggedin())
      if ($morder != user_get_preference($preference_prefix
                                         .'_browse_order'.$group_id))
        user_set_preference($preference_prefix.'_browse_order'.$group_id,
                            $morder);
  }

#  If the report type is not defined then get it from the user preferences.
#  If it is set then update the user preference.  Also initialize the
#  bug report structures.
if (user_isloggedin())
  {
    if (!isset($report_id))
      $report_id = user_get_preference($preference_prefix.'_browse_report'
                                       .$group_id);
    elseif ($report_id != user_get_preference ($preference_prefix
                                               .'_browse_report'.$group_id))
      user_set_preference($preference_prefix.'_browse_report'.$group_id,
                          $report_id);
  }

# If the report type is not defined then get it from group preferences.
if (!$report_id)
  $report_id = group_get_preference ($group_id, ARTIFACT . "_default_query");

# If still not defined then force it to system 'Basic' report
# of to 'Sober Basic' in sober mode, 103. This is hardcoded, if at some point
# we need to put the sober output of existing trackers where 103 may already
# be used, we will have to think of an other way to put it.
if (!$report_id)
  {
    if ($sober)
      $report_id = 103;
    else
      $report_id = 100;
  }

if (!trackers_report_init($report_id))
  {
    fb(sprintf(_("Query form #%s doesn't exist"), $report_id), 1);
    # Fall back to 'Basic' report.
    $report_id = 100;
    trackers_report_init ($report_id);
  }

#  Now see what type of bug set is requested (set is one of none,
#  'my', 'open', 'custom').
#    - if no set is passed in, see if a preference was set ('custom' set).
#    - if no preference and logged in then use 'my' set
#    - if no preference and not logged in the use 'open' set
#     (Prefs is a string of the form
#     &amp;field1[]=value_id1&amp;field2[]=value_id2&amp;.... )
if (!$set)
  {
    $set = 'open';
    if (user_isloggedin())
      {
        $custom_pref =
          user_get_preference ($preference_prefix . '_brow_cust' . $group_id);
        if ($custom_pref)
          {
            $set = 'custom';
            $pref_arr = explode('&amp;', substr($custom_pref, 5));
            foreach ($pref_arr as $expr)
              {
                # Extract left and right parts of the assignment
                # and remove the '[]' array symbol from the left part.
                list($field,$value_id) = explode('=',$expr);
                $field = str_replace('[]','',$field);
                if ($field == 'advsrch' || $field == 'msort'
                    || $field == 'spamscore' || $field == 'report_id'
                    || $field == 'sumORdet'
                )
                  $$field = $value_id;
                elseif ($field == 'chunksz')
                  $chunksz = intval($value_id);
                elseif ($field == 'history')
                  {
                    $history = $value_id;
                    $hist_pref = explode('>', $history);
                    $history_search = $hist_pref[0];
                    $history_field = $hist_pref[1];
                    $history_event = $hist_pref[2];
                    $history_date = $hist_pref[3];

                    # If not args in URL (means not after post) ...
                    # set $url_params['history'] explicitly since 'history'
                    # is not a tracker field and thus won't be set.
                    $url_params['history'][] = $history_search.'>'
                      .$history_field.'>'.$history_event.'>'.$history_date;
                  }
                else
                  $url_params[$field][] = $value_id;
              }
          } # $custom_pref
      } # user_isloggedin ()
  } # !$set

if ($set == 'my')
  {
    #  My bugs - backwards compat can be removed 9/10.
    $url_params['status_id'][]=1;
    $url_params['assigned_to'][]=user_getid();
  }
elseif ($set == 'custom')
  {
  # Use the list of fields built from the arguments and used by the project
  # (the group_id parameter has been excluded).
  # NB: Note that trackers_extract_field_list function did build and
  #     return date arguments (using _dayfd|monthfd|yearfd boxes) whether
  #     or not they were tracker fields used by the project.

    $pref_stg = '';
    foreach ($url_params as $field => $arr_val)
      {
        foreach ($arr_val as $value_id)
          $pref_stg .= "&amp;$field" . "[]=$value_id";
      }
    $pref_stg .= '&amp;advsrch=' . $advsrch;
    $pref_stg .= '&amp;msort=' . $msort;
    $pref_stg .= '&amp;chunksz=' . $chunksz;
    $pref_stg .= '&amp;spamscore=' . $spamscore;
    $pref_stg .= '&amp;report_id=' . $report_id;
    $pref_stg .= '&amp;sumORdet=' . $sumORdet;

    if ($pref_stg != user_get_preference($preference_prefix.'_brow_cust'
                                         .$group_id))
      {
        user_set_preference($preference_prefix.'_brow_cust'.$group_id,
                            $pref_stg);
      }
  }
else
  {
    # We want to reset to all open items?
    # Force the status_id to open, set nothing else, trash the prefs.
    $url_params['status_id'][]=1;
    user_unset_preference($preference_prefix.'_brow_cust'.$group_id);
  }

# At this point make sure that all paramaters are defined
# as well as all the arguments that serves as selection criteria
# If not defined then defaults to ANY (0).
if ($advsrch === null)
  $advsrch = 0;
if ($msort === null)
  $msort = 0;

# Will be used later to find out if it make sense to look for items of the
# system group (meaningful on the cookbook).
$not_group_specific = 1;

while ($field = trackers_list_all_fields())
  {
  # The select boxes for the bug DB search first.
    if (!(trackers_data_is_showed_on_query($field)
        && trackers_data_is_select_box($field)))
      continue;
    if (!isset($url_params[$field]))
      $url_params[$field][] = 0;

    # If we are about to generate the sober output, find out if we can
    # look for items of the site admin project or not.
    # All fields that have preconfigured values that can be changed by
    # projects could end up in flawed results, because the value id and the
    # actual value label (and so, meaning) are likely to be out of sync.
    # The most obvious case if the category field case.
    if (!(ARTIFACT == 'cookbook' && $sober))
      continue;
    if (trackers_data_is_project_scope($field)
        && $url_params[$field][0] != '0'
        && $url_params[$field][0] != '100')
      $not_group_specific = 0;
  }

# Start building the SQL query (select and where clauses).

# Force the selection of priority because it is always shown as color code.
# Force the selection of privacy, we always want to be sure that no private
# item title is provided to everybody.
$full_field_list = $col_list = $width_list = $lbl_list = array();
$select_count = 'SELECT count(DISTINCT '.ARTIFACT.'.bug_id) AS count ';
$select = 'SELECT DISTINCT '.ARTIFACT.'.group_id,'.ARTIFACT.'.priority,'
  .ARTIFACT.'.privacy,'.ARTIFACT.'.status_id,'.ARTIFACT.'.submitted_by ';
$from = 'FROM '.ARTIFACT.' ';
$from_params = array();

# On the cookbook in sober mode, we want the system wide recipes,
# as long as there is no field that comes as select boxes with values
# configurable per project set.
if (ARTIFACT == 'cookbook' && $sober && $not_group_specific)
  {
    $where = 'WHERE ('.ARTIFACT.'.group_id=? OR '.ARTIFACT.'.group_id=?) ';
    $where_params = array($group_id, $sys_group_id);
  }
else
  {
    $where = 'WHERE '.ARTIFACT.'.group_id=? ';
    $where_params = array($group_id);
  }

# Take into account the spamscore limit (always shows
# item posted by the logged in user).
$spamscore_additional = '';
$spamscore_additional_params = array();
if (user_getid() != 100)
  {
    $spamscore_additional = ' OR submitted_by=?';
    $spamscore_additional_params = array(user_getid());
  }
$where .= 'AND ('.ARTIFACT.'.spamscore < ? '.$spamscore_additional.') ';
$spam_params = array_merge(array($spamscore), $spamscore_additional_params);
$where_params = array_merge($where_params, $spam_params);

# If the user asked for more than 150 items to be shown but is not in printer
# mode, restrict arbitrarily to 150:
# It would be too heavy on the database if this was done very frequently
# and we already found some project giving direct links to 500 the browse
# item page with 500 items shown by default.
# Save the wanted number of chunksz, for later.
$wanted_chunksz = $chunksz;
if ($chunksz > 150 && !$printer && !$digest)
  $chunksz = 150;

# No limit on sober output, we want all recipes.
$limit = '';
$limit_params = array();
if (!$sober)
  {
    $limit = " LIMIT ?,?";
    $limit_params = array($offset,$chunksz);
  }

# Prepare the where clause with the selection criteria given by the user.
reset($url_params);

# Prepare for summary and original submission as 'special' criteria.
$summary_search = 0;
$details_search = 0;

foreach ($url_params as $field => $value_id)
  {
  # If the criteria is not in the field showed on query screen then
  # skip it. This is a sanity check to make sure that the SQL
  # query we run actually matches the displayed search criteria.
    if (!trackers_data_is_showed_on_query($field))
      continue;

    if (trackers_data_is_select_box($field)
        && !trackers_isvarany($url_params[$field]))
      {
        # Only select box criteria to where clause if argument is not ANY.
        $where .= ' AND '.ARTIFACT.'.'.$field.' IN ('
          .implode(',', array_fill(0, count($url_params[$field]), '?')).') ';
        $where_params = array_merge($where_params, $url_params[$field]);
      }
    elseif (trackers_data_is_date_field ($field) && $url_params[$field][0])
      {
      # Transform a date field into a unix time and use <, > or =.
        list($time,$ok) = utils_date_to_unixtime($url_params[$field][0]);
        preg_match("/\s*(\d+)-(\d+)-(\d+)/", $url_params[$field][0],$match);
        list(,$year,$month,$day) = $match;

        if ($advsrch)
          {
            list($time_end,$ok_end) =
              utils_date_to_unixtime($url_params[$field.'_end'][0]);
            if ($ok)
              {
                $where .= ' AND '.ARTIFACT.'.'.$field.' >= ?';
                $where_params[] = $time;
              }
            if ($ok_end)
              {
                $where .= ' AND '.ARTIFACT.'.'.$field.' <= ?';
                $where_params[] = $time_end;
              }
          }
        else
          {
            $operator = $url_params[$field.'_op'][0];
            # '=' means that day between 00:00 and 23:59.
            if ($operator == '=')
              {
                $time_end = mktime(23, 59, 59, $month, $day, $year);
                $where .= ' AND '.ARTIFACT.'.'.$field.' >= ? AND '
                          .ARTIFACT.'.'.$field.' <= ? ';
                $where_params[] = $time;
                $where_params[] = $time_end;
              }
            else
              {
                $time = mktime(0,0,0, $month, ($day+1), $year);
                $where .= ' AND '.ARTIFACT.'.'.$field." $operator= ? ";
                $where_params[] = $time;
              }
          }

      # Always exclude undefined dates (0).
        $where .= ' AND '.ARTIFACT.'.'.$field." <> 0 ";
      }
    elseif ((trackers_data_is_text_field($field)
             || trackers_data_is_text_area($field))
             && $url_params[$field][0])
      {
      # Buffer summary and original submission (details) to handle them later
      # in case we have an OR to do between the two, instead of the usual
      # AND.
        if ($sumORdet == 1 &&
            ($field == 'summary' || $field == 'details'))
          {
            if ($field == 'summary')
              $summary_search = 1;
            if ($field == 'details')
              $details_search = 1;
          }
        else
          {
          # It s a text field accept. Process INT or TEXT,VARCHAR fields differently.
            list($expr, $params) =
              trackers_build_match_expression($field, $url_params[$field][0]);
            $where .= " AND $expr ";
            $where_params = array_merge($where_params, $params);
          }
      }
  }

# Handle summary and/or original submission now, if a AND is required.
if ($sumORdet == 1)
  {
  # We will process the usual normal AND case: there was something for both
  # fields.
    if ($details_search == 1 && $summary_search == 1)
      {
        $where .= ' AND ';
        $where .= '( ( ';
        list($expr, $params) = trackers_build_match_expression('details',
                                                     $url_params['details'][0]);
        $where .= $expr;
        $where_params = array_merge($where_params, $params);
        $where .= ' ) OR ( ';
        list($expr, $params) = trackers_build_match_expression('summary',
                                                     $url_params['summary'][0]);
        $where .= $expr;
        $where_params = array_merge($where_params, $params);
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
            list($expr, $params) = trackers_build_match_expression('details',
                                                     $url_params['details'][0]);
            $where .= $expr;
            $where_params = array_merge($where_params, $params);
          }
        if ($summary_search == 1 && $url_params['summary'][0])
          {
            $where .= ' AND ';
            list($expr, $params) = trackers_build_match_expression('summary',
                                                     $url_params['summary'][0]);
            $where .= $expr;
            $where_params = array_merge($where_params, $params);
          }
      }
  }

# Loop through the list of used fields to define label and fields/boxes
# used as search criteria.

$ib=0;
$is=0;
$load_cal=false;

# Check if summary and original submission are criteria.
$summary_search = 0;
$details_search = 0;

$labels = '';
$boxes = '';
$html_select = '';
while ($field = trackers_list_all_fields('cmp_place_query'))
  {
  # Skip unused field.
    if (!trackers_data_is_used($field))
      continue;

  # Skip fields not part of this query form.
    if (!trackers_data_is_showed_on_query($field))
      continue;

  # Beginning of a new row.
    if ($ib % $fields_per_line == 0)
      {
        $align = ($printer ? "left" : "center");
        $labels .= "\n".'<tr align="'.$align.'" valign="top">';
        $boxes .= "\n".'<tr align="'.$align.'" valign="top">';
      }

    $labels .= '<td>'
      .trackers_field_label_display($field,$group_id,false,false)."</td>\n";
    $boxes .= '<td><span class="smaller">';

    if (trackers_data_is_select_box($field))
      {
        $boxes .=
           trackers_field_display($field,$group_id,
                                  ($advsrch ?
                                   (isset($url_params[$field]) ?
                                      $url_params[$field] : null)
                                   : (isset($url_params[$field][0]) ?
                                      $url_params[$field][0] : null)),
                                  false,false,($printer?true:false),false,true,
                                 'None', true,'Any');
      }
    elseif (trackers_data_is_date_field($field))
      {
        $end_value = '';
        if (isset ($url_params[$field . '_end'])
            && isset ($url_params[$field . '_end'][0]))
          $end_value = $url_params[$field . '_end'][0];

        $op_value = '';
        if (isset ($url_params[$field . '_op'])
            && isset ($url_params[$field . '_op'][0]))
          $op_value = $url_params[$field . '_op'][0];

        $value = '';
        if (isset ($url_params[$field])
            && isset($url_params[$field][0]))
          $value = $url_params[$field][0];

        if ($advsrch)
          {
            $boxes .= trackers_multiple_field_date ($field, $value, $end_value,
                                                    0, 0, $printer);
          }
        else
          {
            $boxes .= trackers_field_date_operator($field, $op_value, $printer)
              . trackers_field_date($field, $value, 0, 0, $printer);
          }
      }
    elseif (trackers_data_is_text_field($field)
            || trackers_data_is_text_area($field))
      {
        if ($field == 'summary')
          $summary_search = 1;
        if ($field == 'details')
          $details_search = 1;

        if (!isset($url_params[$field]))
          # Not passed as parameter yet, field just appeared due to
          # a change of the query form.
          $url_params[$field] = array(null);

        $boxes .=
          ($printer ? $url_params[$field][0]
           : trackers_field_text($field,$url_params[$field][0],15,80));
      }
    $boxes .= "</span></td>\n";
    $ib++;

  # End of this row.
  if ($ib % $fields_per_line == 0)
    {
      $html_select .= $labels."</tr>\n".$boxes."</tr>\n";
      $labels = $boxes = '';
    }
  } # while ($field = trackers_list_all_fields('cmp_place_query'))

# Make sure the last few cells are in the table.
if ($labels)
  $html_select .= $labels."</tr>\n".$boxes."</tr>\n";

# Fill the relevant sql bit to be used later.
# Sensible default case: order by item_id from the recent to the older
# (only if not in multiple column sort, otherwise dont mess with it because
# the first thing to be set will matters a lot).
if ($morder == '' && !$msort)
  $morder = "bug_id<";
$order_by = '';
if ($morder != '')
  {
    $matching_morder = '';
  # Workaround the case when the list is sorted by a column that has been
  # removed from the query form (multicolumn sorting is not affected),
  # Savannah SR #107879.
    if (!$msort)
      {
        $matching_morder = preg_replace('/[<>]$/', '', $morder);
        while ($field = trackers_list_all_fields('cmp_place_result'))
          {
            if (!trackers_data_is_used($field))
              continue;

            if (!trackers_data_is_showed_on_result($field))
              continue;

            if (strcmp($field, $matching_morder) == 0)
              $matching_morder = '';
          }
      }
    if ($matching_morder == '')
      {
        $fields = trackers_criteria_list_to_query($morder);
        if (!empty($fields))
          $order_by = ' ORDER BY '.$fields;
      }
  }

# Loop through the list of used fields to see what fields are in the
# result table and complement the SQL query accordingly.

# Add extra digest column, if necessary.
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

    if (!trackers_data_is_used($field)
        || !trackers_data_is_showed_on_result($field))
      continue;

    $col_list[] = $field;
    $width_list[] = trackers_data_get_col_width($field);

    if ($msort)
      {
        # Less simple in multicolumn, indeed.
        $morder_icon_is_set = 0;
        $morder_arr = explode(',', $morder);

        foreach ($morder_arr as $crit)
          {
            if (!($crit == "$field<" || $crit == "$field>"))
              continue;
            $so = trackers_sorting_order ($crit);
            $lbl_list[] = trackers_data_get_label ($field) . ' '
              . '<img class="icon" src="' . $GLOBALS['sys_home'] . 'images/'
              . SV_THEME . '.theme/arrows/' . $so['image']
              . '.png" alt="' . $so['text'] . '" border="0" />';

            # If we found a criteria, go deal with the next column.
            $morder_icon_is_set = 1;
          }

        # If this field is not a sort criteria, we still have to create
        # the column.
        if (!$morder_icon_is_set)
          {
            $lbl_list[] = trackers_data_get_label($field);
          }
      }
    else
      {
        # If we have the field that defines the order, add an icon.
        # Quite simple in monolcolumn.
        if ($morder_icon_is_set)
          $lbl_list[] = trackers_data_get_label($field);
        else
          {
            if ($morder == "$field<" || $morder == "$field>")
              {
                $so = trackers_sorting_order ($morder);
                $lbl_list[] = trackers_data_get_label($field).' '
                  .'<img class="icon" src="'.$GLOBALS['sys_home'].'images/'
                  .SV_THEME.'.theme/arrows/'
                  .$so['image'].'.png" alt="'.$so['text']
                  .'" border="0" />';
                $morder_icon_is_set = 1;
              }
            else
              {
                $lbl_list[] = trackers_data_get_label($field);
              }
          }
      }

    if (trackers_data_is_username_field($field))
      {
      # User names requires some special processing to display the username
      # instead of the user_id.
        $select .= ",user_$field.user_name AS $field";
        $from = "FROM user user_$field, " . substr ($from, 5);
        $where .= " AND user_$field.user_id=".ARTIFACT.".$field ";
      }
    else
      {
      # Otherwise just select this column as is.
        $select .= ",".ARTIFACT.".$field";
      }
  } # while ($field = trackers_list_all_fields('cmp_place_result'))

if ($history_search)
  {
    list($unix_history_date,$ok) = utils_date_to_unixtime($history_date);
    if ($history_event == "modified")
      {
        $from = $from.', '.ARTIFACT.'_history ';
        $where .= 'AND '.ARTIFACT.'_history.bug_id = '.ARTIFACT.'.bug_id ';
        $where .= ' AND '.ARTIFACT.'_history.date >= ? ';
        $where_params[] = $unix_history_date;
        if ($history_field != '0')
          {
            $where .= ' AND '.ARTIFACT."_history.field_name = ? ";
            $where_params[] = $history_field;
          }
      }
    else
      {
        $from .= ' LEFT JOIN '.ARTIFACT.'_history ON (('.ARTIFACT
          .'_history.bug_id = '.ARTIFACT.'.bug_id ) AND ('.ARTIFACT
          .'_history.date >= ?) ';
        $from_params[] = $unix_history_date;
        if ($history_field != '0')
          {
            $from .= 'AND ('.ARTIFACT."_history.field_name = ?)) ";
            $from_params[] = $history_field;
          }
        else
          $from .= ') ';
        $where .= ' AND '.ARTIFACT.'_history.bug_id IS NULL';
      }
  }

# Run 2 queries : one to count the total number of results, and the second
# one with the LIMIT argument. It is faster than selecting all
# rows (without LIMIT) because when the number of bugs is large it takes
# time to transfer all the results from the server to the client.
# It is also faster than using the SQL_CALC_FOUND_ROWS/FOUND_ROWS()
# capabilities of MySQL.
$sql_count = "$select_count $from $where";
$result_count = db_execute($sql_count, array_merge($from_params, $where_params));
$totalrows = db_result($result_count,0,'count');

$sql = "$select $from $where $order_by $limit";
$result = db_execute($sql, array_merge($from_params, $where_params,
                                       $limit_params));

# Build the array that will be given to the function that make the item
# list. We cannot simply return the SQL results, since we have to remove
# private items if necessary and set $totalrows accordingly.
$result_array = array();
while ($thisarray = db_fetch_array($result))
  {
    if (!isset ($thisarray['bug_id']))
      {
        $error_msg = 'trackers_run/browse.php: no bug_id in result; $thisarray: ';
        foreach ($thisarray as $idx => $val)
          $error_msg .= " [$idx] => '$val'";
        $error_msg .= "; URI: '" . ($_SERVER['REQUEST_URI']) . "'";
        error_log ($error_msg);
      }
  # Get the id.
    $thisitem_id = $thisarray['bug_id'];

  # Do not show private item, apart to technician level members
  # and submitter.
    if ($thisarray['privacy'] == '2'
        && !member_check_private(0,$group_id)
        && $thisarray['submitted_by'] != user_getname())
      {
        $totalrows--;
        continue;
      }

  # Build a specific array for each item.
  $result_array[$thisitem_id] = array();

  # Always store the group, it may be necessary later, in case we actually
  # look for items from different projects.
  $result_array[$thisitem_id]["group_id"] = $thisarray["group_id"];

  # Store each field that will be necessary later.
  $nb_of_fields = count($full_field_list);
  for ($k=0; $k<$nb_of_fields; $k++)
    {
      if (isset($thisarray[$full_field_list[$k]]))
        $result_array[$thisitem_id][$full_field_list[$k]] =
          $thisarray[$full_field_list[$k]];
      else
        $result_array[$thisitem_id][$full_field_list[$k]] = null;
    }
  }

# Display the HTML search form.

$form_submit = '';
if ($printer)
  $hdr = _("Browse Items") . ' - ' . utils_format_date (time ());
trackers_header(array('title' => $hdr));

if ($browse_preamble)
  print $browse_preamble;

$form_opening = '<form action="'.htmlentities ($_SERVER['PHP_SELF'])
                .'#options" method="get" name="bug_form">';
$form = '
          <input type="hidden" name="group" value="'. $group . '" />
          <input type="hidden" name="func" value="'. $func . '" />
          <input type="hidden" name="set" value="custom" />
          <input type="hidden" name="msort" value="'. $msort . '" />';

# Show the list of available bug reports kind.
$res_report = trackers_data_get_reports($group_id,user_getid(),$sober);
if ($printer)
  {
    $form_query_type = null;
    while (list ($f, $v) = db_fetch_array ($res_report))
      {
        if ($f != $report_id)
          continue;
        $form_query_type = $v;
        break;
      }
  }
else
  {
  # In sober mode, there is no relevant query form that have reportid = 100.
    $show_100 = true;
    if ($sober)
      $show_100 = false;
    $form_query_type = html_build_select_box($res_report, 'report_id',
                                             $report_id,
                                             $show_100,
# TRANSLATORS: this string is as argument in "Browse with the %s query form".
                                             _('Basic'), false, 'Any', false,
                                             _('query form'));
  }

# Start building the URL that we use to for hyperlink in the form.
$url = $GLOBALS['sys_home'].ARTIFACT."/?group="
       . "$group&amp;func=$func&amp;set=$set&amp;msort=$msort";
if ($set == 'custom')
  $url .= $pref_stg;
else
  $url .= '&amp;advsrch=' . $advsrch;

$url_nomorder = $url;
$url .= "&amp;morder=$morder";

# Build the URL for alternate Search.
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

# Select 'list form' or 'select' form.
if ($printer)
  {
    if ($advsrch)
      # TRANSLATORS: this string is used to specify kind of selection.
      $advsrch_x = _("Multiple");
    else
      # TRANSLATORS: this string is used to specify kind of selection.
      $advsrch_x =  _("Simple");

    $form_sel_type = $advsrch_x;
  }
else
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
    $form_sel_type = '<select title="'._("type of search")
      . '" name="advsrch"><option value="0"' . $advsrch_0 . '>'
      # TRANSLATORS: this string is used to specify kind of selection.
      . _("Simple") . '</option><option value="1"' . $advsrch_1 . '>'
      # TRANSLATORS: this string is used to specify kind of selection.
     . _("Multiple") . "</option></select>\n";
    $form_submit = '<input class="bold" value="' . _("Apply")
      .'" name="go_report" type="submit" />' . "\n";
  }
if ($form_query_type !== null)
  $form .=
     sprintf (
       # TRANSLATORS: the first argument is kind of query form (like Basic),
       # the second argument is kind of selection (Simple or Multiple).
       _('Browse with the %1$s query form and %2$s selection.'),
       $form_query_type, $form_sel_type
     ) . "\n";

$form .= '<table cellpadding="0" cellspacing="5">
        <tr><td colspan="'.$fields_per_line.'" nowrap="nowrap">';
$form .= $html_select;
$form .= "</table>\n";

# If both 'summary' and 'original submission' are searched,
# propose an OR instead of AND.
if (($details_search == 1) && ($summary_search == 1))
  {
    $sum = rtrim (
      trackers_field_label_display ("summary", $group_id, false, true),
      ': '
    );
    $det = rtrim (
      trackers_field_label_display ("details", $group_id, false, true),
       ': '
    );
    if ($printer)
      {
        $conj =
          $sumORdet?
            # TRANSLATORS: this is a logical operator, used in string
            # "Use logical %s between '%s' and '%s' searches.
            _("OR"):
            # TRANSLATORS: this is a logical operator, used in string
            # "Use logical %s between '%s' and '%s' searches.
            _("AND");
      }
    else
      {
        $conj =
            '<select title="' . _("logical operation to apply")
            . '" name="sumORdet">' . "\n" . '<option value="0" '
            . ($sumORdet? '': 'selected="selected"') . '>'
            # TRANSLATORS: this is a logical operator, used in string
            # "Use logical %s between '%s' and '%s' searches.
            . _("AND") . "</option>\n<option " . 'value="1" '
            . ($sumORdet? 'selected="selected"': '') . '>'
            # TRANSLATORS: this is a logical operator, used in string
            # "Use logical %s between '%s' and '%s' searches.
            . _("OR") . "</option>\n</select>\n";
      }
    $form .= '<p class="smaller">';
    $form .=
      # TRANSLATORS: the first argument is operator (AND or OR),
      # the second argument is label for 'summary' field,
      # the third argument is label for 'details field.
      sprintf(
        _('Use logical %1$s between \'%2$s\' and \'%3$s\' searches.'),
        $conj, $sum, $det
      );
    $form .= "</p>\n";

    # Update the URL.
    $url .= "&amp;sumOrdet=$sumORdet";
  }

# Propose to search for field updated since a certain date.
$fextracted = array();
$fname = array();
$flabel = array();

# Extract the list of relevant fields.
while ($field = trackers_list_all_fields())
  {
  # Do not show fields not used by the project.
    if ( !trackers_data_is_used($field))
      continue;

  # Special fields are usually not modifiable by users, it is the system
  # that defines their content. As such, they cannot be used as
  # additional constraint.
    if (trackers_data_is_special($field))
      continue;

    $fextracted[$field] = trackers_data_get_label($field);
  }

# Order them by name: in a select box, following the configure output order
# is not user-friendly.
asort($fextracted);
foreach ($fextracted as $field => $label)
  {
    $fname[] = $field;
    $flabel[] = $label;
  }

$hist_ev_text  = array();
# TRANSLATORS: the string is used like "<field-name> %s since <date>"
$hist_ev_text[] = _("modified");
# TRANSLATORS: the string is used like "<field-name> %s since <date>"
$hist_ev_text[] = _("not modified");
$hist_ev_value  = array();
$hist_ev_value[] = "modified";
$hist_ev_value[] = "not modified";

# If we are in sober mode, additional constraint is not proposed.
if (!$sober)
  {
    $form_separator = '';
    if (!$printer)
      {
        $form_activated =
                   '<select title="'
                   ._("whether additional constraint is activated")
                   .'" name="history_search"><option value="0" '
                   .(!$history_search ? 'selected="selected"':'').'>'
# TRANSLATORS: this string is used as the argument in
# 'Additional constraint %s'.
                   ._("deactivated").'</option>
<option value="1" '.($history_search ? 'selected="selected"':'').'>'
# TRANSLATORS: this string is used as the argument in
# 'Additional constraint %s'.
                   ._("activated")."</option></select>\n";
        $form_separator = "<br />\n&nbsp;&nbsp;&nbsp;";
        $form_fieldname = html_build_select_box_from_arrays ($fname,
                                                             $flabel,
                                                             'history_field',
                                                             $history_field,
                                                             false,
                                                             '',
                                                             true,
                                                             'Any',
                                                             false,
                                                             _("Field for criteria"));
        $form_modified = html_build_select_box_from_arrays ($hist_ev_value,
                                                            $hist_ev_text,
                                                            'history_event',
                                                            $history_event,
                                                            false,
                                                            '',
                                                            false,
                                                            '',
                                                            false,
                                                            _("modified or not"));
        $form_since = trackers_field_date('history_date',
                                          $history_date,
                                          0,
                                          0,
                                          false);
      }
    elseif ($history_search)
      # In printer mode, if the additional constraint is off,
      # no need to print it.
      {
        $form_activated =
          # TRANSLATORS: this string is used as the argument in
          # 'Additional constraint %s'.
          _("activated");
        # TRANSLATORS: this is the argument in a string like
        #  "%s [modified/not modified] since [date]"
        $form_fieldname = _('Any field');
        if ($history_field !== '0')
          $form_fieldname = $fextracted[$history_field];
        $form_modified = $hist_ev_text[0];
        $rows = count($hist_ev_text);
        for ($i = 0; $i < $rows; $i++)
          if ($hist_ev_value[$i] == $history_event)
            {
              $form_modified = $hist_ev_text[$i];
              break;
            }
        $form_since = trackers_field_date('history_date',
                                          $history_date,
                                          0,
                                          0,
                                          true);
        $form_separator = ' ';
      }
    if ($form_separator != '')
      $form .= '<p class="smaller"><span class="preinput">'
# TRANSLATORS: the argument is 'activated' or 'deactivated'.
            .sprintf(_('Additional constraint %1$s:'), $form_activated)
            .'</span>'.$form_separator
# TRANSLATORS: the first argument is field name, the second argument is
# either 'modified' or 'not modified', the third argument is date.
            .sprintf(_('%1$s %2$s since %3$s'),
                     $form_fieldname,
                     $form_modified,
                     $form_since)."</p>\n";
  } # !$sober

if ($history_search)
  # Update the url
  $url .= "&amp;history_search=$history_search"
    . "&amp;history_field=$history_field&amp;history_event=$history_event"
    . "&amp;history_date=$history_date";

# Number of items printed at once.
# In sober mode, they are all printed.
if (!$sober)
  {
    $form .= '<p class="smaller">';
    if ($printer)
      {
        $form .=
         sprintf (
           ngettext(
             'Show %1$s item at once with a spam score lower than %2$s.',
             'Show %1$s items at once with a spam score lower than %2$s.',
             $chunksz
           ),
           $chunksz, $spamscore
         );
      }
    else
      {
        $form .=
          sprintf (
            _("Items to show at once: %s."),
            form_input ("text", "chunksz", $wanted_chunksz,
              'size="3" maxlength="5" title="'
              . _("Number of items to show at once") . '"')
          )
          . ' '
          . sprintf(
              _("Show items with a spam score lower than %s."),
              form_input ("text", "spamscore", $spamscore,
                'size="3" maxlength="2" title="'
                . _("Spam level of items to hide") . '"')
            );
        if ($wanted_chunksz != $chunksz)
          {
            # No use of ngettext as $chunksz will never be below 10, otherwise
            # it would mean that Savane would be modified to never list more
            # than 10 items at once, which is almost nothing.
            $form .= ' <span class="warn">'
              . sprintf (ngettext (
"Warning: only %s item can be shown at once, unless using Printer Version.",
"Warning: only %s items can be shown at once, unless using Printer Version.",
                   $chunksz), $chunksz
                )
              . '</span>';
          }
      }
    $form .= "</p>\n";
  }

# In sober mode, do not talk about sorting order, we will anyway not use
# tables to sort the items.
if ($totalrows > 0 && !$sober)
  {
    $form .= '<p class="smaller">';
    if ($msort)
      {
        $url_alternate_sort = str_replace('msort=1','msort=0',$url)
                              .'&amp;order=#results';
        $form .= sprintf(_('Column heading links sort results (up or
down), you can also <a href="%1$s">sort by priority</a> or <a href="%2$s">reset
sort</a>.'),
                         $url.'&amp;order=priority#results',
                         $url.'&amp;order=#results');
	$form .= sprintf(
_("You can also <a href=\"%s\">deactivate multicolumn sort</a>."),
                         $url_alternate_sort);
      }
    else
      {
        $url_alternate_sort = str_replace('msort=0','msort=1',$url)
                              .'&amp;order=#results';
        $form .= sprintf(_('Column heading links sort results (up or
down), you can also <a href="%1$s">sort by priority</a> or <a href="%2$s">reset
sort</a>.'),
                         $url.'&amp;order=priority#results',
                         $url.'&amp;order=#results');
	$form .= ' '.sprintf(
_("You can also <a href=\"%s\">activate multicolumn sort</a>."),
                         $url_alternate_sort);
      }
    if ($morder)
# TRANSLATORS: the argument is comma-separated list of field labels.
      $form .= " "
        . sprintf(_("Currently, results are sorted by %s."),
            trackers_criteria_list_to_text($morder, $url_nomorder)
          );
    $form .= "</p>\n";
  }

# Print the form.
print html_show_displayoptions($form,$form_opening,$form_submit);

# Finally display the result table.

if ($digest)
  print '<form action="'.htmlentities ($_SERVER['PHP_SELF']).'" method="get">
<input type="hidden" name="group" value="' . $group . '" />
<input type="hidden" name="func" value="digestselectfield" />
';

if ($totalrows > 0)
  {
    if ($sober)
      {
        # Sober got it is own list design.
        show_item_list_sober($result_array, $totalrows, $width_list, $url,
          false);
      }
    else
      {
        show_item_list($result_array, $offset, $totalrows, $col_list,
          $lbl_list, $width_list, $url, false);
        if ($digest)
          print form_footer (_("Proceed to Digest next step"));
        show_priority_colors_key ();
      }
  }
else
  {
    fb (
      _("No matching items found. The display criteria may be too restrictive.")
      . ' ' . db_error(),
      1
    );
  }

trackers_footer(array());
?>
