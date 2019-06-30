<?php
# Show items
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2019 Ineiev
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

require_once(dirname(__FILE__) . '/cookbook.php');

function show_item_list ($result_arr,
                         $offset,
                         $total_rows,
                         $field_arr, #4
                         $title_arr,
                         $width_arr,
                         $url,
                         $nolink=false)
{
  global $group_id, $chunksz, $morder;

  # Build the list of links to use for column headings.
  # Used to trigger sort on that column.
  if ($url)
    {
      $links_arr = array();
      while (list(, $field) = each($field_arr))
        {
          $links_arr[] = $url . '&amp;order=' . $field . '#results';
        }
    }
  # Show extra rows for <-- Prev / Next -->.

  $nav_bar = 'class="nextprev">';
  # If all bugs on screen so no prev/begin pointer at all.
  if ($total_rows > $chunksz)
    {
      if ($offset > 0)
        {
          $nav_bar .=
             '<span class="xsmall"><a href="' . $url
             . '&amp;offset=0#results"><img src="' . $GLOBALS['sys_home']
             . 'images/' . SV_THEME . '.theme/arrows/first.png" border="0" alt="'
             . '" />' . _("Begin") . '</a>&nbsp;&nbsp;&nbsp;&nbsp;'
             . '<a href="' . $url . '&amp;offset=' . ($offset - $chunksz)
             . '#results"><img src="' . $GLOBALS['sys_home'] . 'images/'
             . SV_THEME . '.theme/arrows/previous.png" border="0" alt="'
             . '" />' . _("Previous Results") . '</a></span>';
        }
      else
        {
          $nav_bar .=
             '<span class="xsmall"><img src="' . $GLOBALS['sys_home']
             . 'images/' . SV_THEME
             . '.theme/arrows/firstgrey.png" border="0" alt="" /><em>'
             . _("Begin") . '</em>&nbsp;&nbsp;&nbsp;&nbsp;'
             . '<img src="' . $GLOBALS['sys_home'] . 'images/' . SV_THEME
             . '.theme/arrows/previousgrey.png" border="0" alt="'
             . '" /><em>' . _("Previous Results")
             . '</em></span>';
        }
    }

  $offset_last = min($offset + $chunksz - 1, $total_rows - 1);
  $nav_bar .= " &nbsp;  &nbsp; &nbsp; &nbsp; "
              . sprintf(ngettext("%d matching item", "%d matching items",
                                 $total_rows), $total_rows);
  $nav_bar .= " - " . sprintf(
# TRANSLATORS: the arguments are offsets of items in the list.
                              _('Items %1$s to %2$s'), ($offset+1),
                            ($offset_last+1))
              . "  &nbsp; &nbsp; &nbsp; &nbsp; ";

  # If all items are on screen, no next/end pointer at all.
  # FIXME: it should not count private items.
  if ($total_rows > $chunksz)
    {
      if ( ($offset+$chunksz) < $total_rows )
        {
          $offset_end = ($total_rows - ($total_rows % $chunksz));
          if ($offset_end == $total_rows)
            $offset_end -= $chunksz;

          $nav_bar .=
             '<span class="xsmall"><a href="' . $url . '&amp;offset='
             . ($offset + $chunksz) . '#results">' . _("Next Results")
             . '<img src="' . $GLOBALS['sys_home'] . 'images/'
             . SV_THEME . '.theme/arrows/next.png" border="0" alt="" /></a>'
             . '&nbsp;&nbsp;&nbsp;&nbsp;'
             . '<a href="' . $url . '&amp;offset=' . ($offset_end)
             . '#results">' . _("End") . '<img src="' . $GLOBALS['sys_home']
             . 'images/'
             . SV_THEME . '.theme/arrows/last.png" border="0" alt="'
             . '" /></a></span>';
        }
      else
        {
          $nav_bar .= '<span class="xsmall"><em>' . _("Next Results")
             . '</em><img src="' . $GLOBALS['sys_home'] . 'images/'
             . SV_THEME . '.theme/arrows/nextgrey.png" border="0" alt="'
             . '" />&nbsp;&nbsp;&nbsp;&nbsp;<em>' . _("End")
             . '</em><img src="' . $GLOBALS['sys_home'] . 'images/'
             . SV_THEME . '.theme/arrows/lastgrey.png" border="0" alt="'
             . '" /></span>';
        }
    }
  $nav_bar .= "</h2>\n";

  # Print prev/next links.
  print '<h2 id="results" ' . $nav_bar . "<br />\n";
  print html_build_list_table_top ($title_arr, $links_arr);

  # See if the bugs are too old - so we can highlight them.
  $nb_of_fields = count($field_arr);

  while (list(, $thisitem) = each($result_arr))
    {
      $thisitem_id = $thisitem['bug_id'];
      print '<tr class="'
            . utils_get_priority_color($result_arr[$thisitem_id]["priority"],
                                       $result_arr[$thisitem_id]["status_id"])
            . '">' . "\n";

      for ($j = 0; $j < $nb_of_fields; $j++)
        {
           # If we are in digest mode, add the digest checkbox.
          if ($field_arr[$j] == "digest")
            {
              # Dirty workaround to have boxes selected by default in the
              # form_input.
              print '<td class="center">'
                    . form_input("checkbox", "items_for_digest[]",
                                 "$thisitem_id\" checked=\"checked") . "</td>\n";
              continue;
            }

          $value = $result_arr[$thisitem_id][$field_arr[$j]];
          if ($width_arr[$j])
            $width = ' width="' . $width_arr[$j] . '%"';
          else
            $width = '';

          if (trackers_data_is_date_field($field_arr[$j]) )
            {
              if ($value)
                {
                  if ($field_arr[$j] == 'planned_close_date' and $value < time())
                    $highlight_date = ' class="highlight"';
                  else
                    $highlight_date = '';
                  print "<td$width$highlight_date>";
                  print utils_format_date($value, 'natural');
                  print "</td>\n";
                }
              else
                print "<td align=\"middle\"$width>-</td>\n";

            }
          elseif ($field_arr[$j] == 'bug_id')
            {
              if ($nolink)
                print "<td$width>#$value</td>\n";
              else
                {
                  print "<td$width>";
                  print '<a href="?' . $value . '">';
                  print '&nbsp;#' . $value . "</a></td>\n";
                }

            }
          elseif (trackers_data_is_username_field($field_arr[$j]))
            {
              if ($value == 'None')
                $value = '';
              if ($nolink || $value === '')
                print "<td$width>$value</td>\n";
              else
                print "<td$width>" . utils_user_link($value) . "</td>\n";
            }
          elseif (trackers_data_is_select_box($field_arr[$j]))
            {
              $val = trackers_data_get_cached_field_value($field_arr[$j],
                                                          $group_id, $value);
              if ($val == 'None')
                $val = '';
              print "<td$width>$val</td>\n";
            }
          else
            {
              if ($nolink)
                print "<td$width>". $value . "&nbsp;</td>\n";
              else
                print "<td$width>" . '<a href="?' . $thisitem_id . '">'
                      . $value . "</a></td>\n";
            }
        } # for ($j = 0; $j < $nb_of_fields; $j++)
      print "</tr>\n";
    } # while (list(, $thisitem) = each($result_arr))
  print "</table>\n";
  # Print prev/next links.
  print "<br />\n<h2 $nav_bar<br />\n";
}

# Do the same a item list but in sober output.
function show_item_list_sober ($result_arr,
                               $total_rows,
                               $url)
{
  global $group_id, $sys_group_id, $sys_name;

  $possible_contexts = cookbook_context_possiblevalues();
  $possible_audiences = cookbook_audience_possiblevalues();

  # If we are on a project cookbook, take into account impossible values:
  # recipes of features unused by the project.
  $impossible_contexts = array();
  if ($group_id != $sys_group_id)
    {
      $impossible_contexts = cookbook_context_project_impossiblevalues();
    }

  # Add the unset case, when the item is actually not bound to any context
  # or action.
  # Build SQL-specific part for these.
  $sql_unboundcontext = '';
  $sql_unboundaudience = '';
  $thisarray = array_merge($possible_contexts, $impossible_contexts);
  while (list($context,) = each($thisarray))
    {
      if (!ctype_alnum($context))
        util_die('show_item_list_sober: invalid context <em>'
                 . htmlspecialchars($context) . '</em>');
      $sql_unboundcontext .= "AND context_$context=0 ";
    }
  while (list($audience,) = each($possible_audiences))
    {
      if (!ctype_alnum($audience))
        util_die('show_item_list_sober: invalid audience <em>'
                 . htmlspecialchars($audience) . '</em>');
      $sql_unboundaudience .= "AND audience_$audience=0 ";
    }
  # Built for scratch two groups of audiences possible for this page:
  # group members and non-group members.
  $possible_audiences = array();
  $possible_audiences['nonmembers'] = _("For Everybody");
  $possible_audiences['members'] = _("For Project Members Only");

  # Add unset cases to the arrays.
  $possible_contexts['unbound'] = _("Other audience");
  $possible_audiences['unbound'] = _("Undefined audience");

  # Build SQL-specific part to group audiences between:
  # project members / non project members.
  $sql_nonmembers = "AND (audience_anonymous=1 OR audience_loggedin=1)";
  $sql_members = "AND (audience_members=1 OR audience_technicians=1 "
                 . "OR audience_managers=1)";
  unset($sql_privateitem);

  # Go through the list of possible context and then possible actions.
  # Print relevant items.
  reset($possible_contexts);
  while (list($context, $context_label) = each($possible_contexts))
    {
      if (!ctype_alnum($context))
        util_die('show_item_list_sober: invalid context <em>'
                 . htmlspecialchars($context) . '</em>');
      $seen_before = array();
      $context_content = '';
      reset($possible_audiences);
      while (list($audience,$audience_label) = each($possible_audiences))
        {
          if (!ctype_alnum($audience))
            util_die('show_item_list_sober: invalid audience <em>'
                     . htmlspecialchars($audience) . '</em>');
          # Get recipes contextual data
          # (no limit argument, expecting people not to use terrible scales).

          if ($audience == 'nonmembers')
            $sql_audience = $sql_nonmembers;
          else
            $sql_audience = $sql_members;

          # Special deal for the item unbound.
          if ($audience != 'unbound' && $context != 'unbound')
            {
              # Normal case, binds for both context and audience.
              $sql_context = "SELECT * FROM cookbook_context2recipe
                              WHERE (group_id=? OR group_id=?)
                              AND context_$context=1 $sql_audience";
            }
          elseif ($audience == 'unbound' && $context != 'unbound')
            {
              # Bind only for the context.
              $sql_context = "SELECT * FROM cookbook_context2recipe
                              WHERE (group_id=? OR group_id=?)
                              AND context_$context='1' $sql_unboundaudience";
            }
          elseif ($context == 'unbound' && $audience != 'unbound')
            {
              # Bind only for the audience.
              $sql_context = "SELECT * FROM cookbook_context2recipe
                              WHERE (group_id=? OR group_id=?)
                              $sql_audience $sql_unboundcontext";
            }
          elseif ($context == 'unbound' && $audience == 'unbound')
            {
              # Not binded at all.
              $sql_context = "SELECT * FROM cookbook_context2recipe
                              WHERE (group_id=? OR group_id=?)
                              $sql_unboundcontext $sql_unboundaudience";
            }

          $sql_context_params = array($group_id, $sys_group_id);
          $result_context = db_execute($sql_context, $sql_context_params);
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
                  # Check if $thisitem_id exists in $result_array before adding
                  # to $thisaudience_results.
                   if(array_key_exists($thisitem_id, $result_arr))
                     $thisaudience_results[$thisitem_id] =
                       strtolower($result_arr[$thisitem_id]["summary"]);
                }
              asort($thisaudience_results);
              $audience_content = '';
              while (list($thisitem_id,$summary) = each($thisaudience_results))
                {
                  # Ignore if not approved.
                  if ($result_arr[$thisitem_id]["resolution_id"] != '1')
                    continue;

                  # Ignore if seen before (probably because it an item for
                  # for everybody and we are listing members or non-members
                  # items).
                  if (isset($seen_before[$thisitem_id]))
                    continue;

                  # Record that we seen it.
                  $seen_before[$thisitem_id] = true;

                  # Detect if it is a site wide doc item. Ignore that if we
                  # are on the site admin group.
                  $is_site_doc = false;
                  $url_extra_arg = '';
                  if ($group_id != $sys_group_id)
                    {
                      if ($result_arr[$thisitem_id]["group_id"] == $sys_group_id)
                        {
                          $is_site_doc = true;
                          $url_extra_arg = '&amp;comingfrom='.$group_id;
                        }
                    }

                  $audience_content .= '<li>';
                  # Show specific background color only for maximum priority.
                  $priority = $result_arr[$thisitem_id]["priority"];
                  if ($priority > 4)
                    {
                      $audience_content .= '<span class="'
                        . utils_get_priority_color(
                            $result_arr[$thisitem_id]["priority"]) . '">';
                    }

                  # In this link, we need to mention from where we come from
                  # so it is possible to know if we are actually inside a
                  # group cookbook if ever we look at a site wide documentation
                  # (We use the long item url, with "detailitem" because we may
                  # have extra arguments to include that would mess the short
                  # item url interpretation).
                  $audience_content .= utils_link($GLOBALS['sys_home']
                                                  . 'cookbook/?func=detailitem'
                                                  . $url_extra_arg
                                                  . '&amp;item_id='
                                                  . $thisitem_id,
                                         $result_arr[$thisitem_id]["summary"]);
                  if ($priority > 4)
                    $audience_content .= '</span>';

                  # If it comes from the site docs, mention it.
                  if ($is_site_doc)
                    $audience_content .= '&nbsp;&nbsp;<span class="smaller">('
                      . sprintf(
# TRANSLATORS: the argument is site name (like Savannah).
                               _("From %s User Docs"), $sys_name) . ')</span>';
                  $audience_content .= "</li>\n";
                }

              # If there was valid results, save the subcontext.
              if (!$audience_content)
                continue;

              $context_content .= '<li><span class="smaller">'
                                  . sprintf("%s:", $audience_label) . '</span>';
              $context_content .= "<ul>\n";
              $context_content .= $audience_content;
              $context_content .= "</ul>\n";
              $context_content .= "</li>\n";
            }
        }
      # If there was valid results, print the context.
      if (!$context_content)
        continue;

      print "\n<h2>"
        . html_anchor(sprintf("%s:", $context_label), $context)
        . "</h2>\n<ul>" . $context_content . "</ul>\n<br />\n";
    }
  return true;
}

# Show the changes of the tracker data we have for this item,
# excluding details.
function show_item_history ($item_id,$group_id, $no_limit=false)
{
  global $sys_datefmt;
  $result = trackers_data_get_history($item_id);
  $rows = db_numrows($result);

  if ($rows > 0)
    {
     # If no limit is not set, print only 25 latest news items
     # yeupou--gnu.org 2004-09-17: currently we provide no way to get the
     # full history. We will see if users request it.
      if (!$no_limit)
        {
          if ($rows > 25)
            $rows = 25;

          $title = sprintf(ngettext("Follows %s latest change.",
                                    "Follow %s latest changes.", $rows), $rows);
          print "\n<p>$title</p>\n";
        }

      $title_arr = array();
      $title_arr[] = _("Date");
      $title_arr[] = _("Changed by");
      $title_arr[] = _("Updated Field");
      $title_arr[] = _("Previous Value");
      $title_arr[] = "=>";
      $title_arr[] = _("Replaced by");

      print html_build_list_table_top ($title_arr);

      $j=0;
      $previous_date = null;
      $previous_user = null;
      for ($i = 0; $i < $rows; $i++)
        {
          $field = db_result($result, $i, 'field_name');

          # If the stored label is "realdetails", it means it is the details
          # field (realdetails is used because someone had the nasty idea to
          # use "details" to mean "comment").
          if ($field == "realdetails")
            $field = "details";

          $field_label = trackers_data_get_label($field);

          # If field_label is empty, no label was found, return as it is stored.
          if (!$field_label)
            $field_label = $field;

          $value_id =  db_result($result, $i, 'old_value');
          $new_value_id =  db_result($result, $i, 'new_value');

          $date = db_result($result, $i, 'date');
          $user = db_result($result, $i, 'user_name');

          # If the previous date and user are equal, do not print user
          # and date.
          if ($date == $previous_date && $user == $previous_user)
            print "\n" . '<tr class="'
              . utils_get_alt_row_color($j) . "\"><td>&nbsp;</td>\n"
              . "<td>&nbsp;</td>\n";
          else
            {
              $j++;
              print "\n" . '<tr class="' . utils_get_alt_row_color($j) . '">';
              print '<td align="center" class="smaller">'
                    . utils_format_date($date, 'natural') . "</td>\n";
              print '<td align="center" class="smaller">'
                    . utils_user_link($user) . "</td>\n";
            }

          $previous_date = $date;
          $previous_user = $user;

          # Updated Field.
          print '<td class="smaller" align="center">' . $field_label . '</td>';

          # Previous value.
          print '<td class="smaller" align="right">';
          if (trackers_data_is_select_box($field))
            {
              # Its a select box look for value in clear.
              # (If we hit case of transition automatique update, show it in
              # specific way).
              if ($value_id == "transition-other-field-update")
                print "-" . _("Automatic update due to transitions settings")
                      . "-";
              else
                print trackers_data_get_value($field, $group_id, $value_id);
            }
          elseif (trackers_data_is_date_field($field))
            {
              # For date fields do some special processing.
              print utils_format_date($value_id, 'natural');
            }
          else
            {
              # It's a text zone then display directly.
               print markup_basic($value_id);
            }

           print '</td>
<td class="smaller" align="center"><img src="'
                 . $GLOBALS['sys_home'] . 'images/' . SV_THEME
                 . '.theme/arrows/next.png" border="0" alt="=>" />' . "</td>\n"
                 . '<td class="smaller" align="left">';
          # New value.
          if (trackers_data_is_select_box($field))
            {
              # It's a select box look for value in clear.
              print trackers_data_get_value($field, $group_id, $new_value_id);
            }
          else if (trackers_data_is_date_field($field))
            {
              # For date fields do some special processing.
              print utils_format_date($new_value_id, 'natural');
            }
          else
            {
              # It's a text zone then display directly.
              print htmlspecialchars ($new_value_id);
            }
          print "</td>\n";
          print "</tr>\n";
        }
      print "</table>\n";
    }
  else
    {
      print "\n" . '<span class="warn">'
            . _("No changes have been made to this item") . '</span>';
    }
}

function show_item_details ($item_id, $group_id, $ascii=false,
                            $item_assigned_to=false,$quoted=false,
                            $new_comment = false)
{
  print format_item_details($item_id,$group_id,$ascii,$item_assigned_to,
                            $quoted, $new_comment);
}

function show_item_attached_files ($item_id,$group_id, $ascii=false, $sober=false)
{
  print format_item_attached_files ($item_id,$group_id, $ascii, $sober);
}

function show_item_cc_list ($item_id,$group_id, $ascii=false)
{
  print format_item_cc_list ($item_id,$group_id, $ascii);
}

# Look for items that $item_id depends on in all artifact.
function show_item_dependency ($item_id)
{
  return show_dependent_item($item_id, $dependson = 1);
}

# Look for items that depends on $item_id in all artifacts (default)
# or look for items that $item_id depends on in all artifact.
function show_dependent_item ($item_id, $dependson = 0)
{
  global $group_id;

  $artifacts = array("support", "bugs", "task", "patch");
  $is_manager = member_check(0, $group_id,
                             member_create_tracker_flag(ARTIFACT) . '1');
  if (!$dependson)
    $title = _("Items that depend on this one");
  else
    $title = _("Depends on the following items");

  # Create hash that will contain every relevant info
  # with keys like $date.$item_id so it will be sorted by date (first)
  # and item_id (second).
  $content = array();

  # Slurps the database.
  $item_exists = false;
  $item_exists_tracker = false;
  while (list(, $art) = each($artifacts))
    {
      if (!$dependson)
        {
          $sql = "SELECT " . $art . ".bug_id," . $art . ".date,"
             . $art . ".summary," . $art . ".status_id," . $art
             . ".resolution_id," . $art . ".group_id," . $art . ".priority,"
             . $art . ".privacy," . $art . ".submitted_by "
             . " FROM " . $art . "," . $art . "_dependencies "
             . " WHERE " . $art . ".bug_id=" . $art . "_dependencies.item_id "
             . " AND " . $art . "_dependencies.is_dependent_on_item_id = ?"
             . " AND " . $art
             . "_dependencies.is_dependent_on_item_id_artifact = ?"
             . " ORDER by " . $art . ".bug_id";
          $res_all = db_execute($sql, array($item_id, ARTIFACT));
        }
      else
        {
          $sql = "SELECT " . $art . ".bug_id," . $art . ".date," . $art
             . ".summary," . $art . ".status_id," . $art . ".resolution_id,"
             . $art . ".group_id," . $art . ".priority," . $art . ".privacy,"
             . $art . ".submitted_by"
             . " FROM " . $art . "," . ARTIFACT . "_dependencies "
             . " WHERE " . $art . ".bug_id=" . ARTIFACT
             . "_dependencies.is_dependent_on_item_id "
             . " AND " . ARTIFACT . "_dependencies.item_id = ?"
             . " AND " . ARTIFACT
             . "_dependencies.is_dependent_on_item_id_artifact = ?"
             . " ORDER by " . $art . ".bug_id ";
          $res_all = db_execute($sql, array($item_id, $art));
        }

      $numrows_all = db_numrows($res_all);
      for ($i = 0; $i < $numrows_all; $i++)
        {
          # Note for later that at least one item was found.
          $item_exists = 1;
          $item_exists_tracker[$art] = 1;

          # Generate unique key date.tracker#nnn.
          $key = db_result($res_all, $i, 'date') . '.'
            . $art . '#' . db_result($res_all, $i, 'bug_id');

          # Store relevant data.
          $content[$key]['item_id'] = db_result($res_all, $i, 'bug_id');
          $content[$key]['tracker'] = $art;
          $content[$key]['date'] = db_result($res_all, $i, 'date');
          $content[$key]['summary'] = db_result($res_all, $i, 'summary');
          $content[$key]['status_id'] = db_result($res_all, $i, 'status_id');
          $content[$key]['resolution_id'] = db_result($res_all, $i,
                                                      'resolution_id');
          $content[$key]['group_id'] = db_result($res_all, $i, 'group_id');
          $content[$key]['priority'] = db_result($res_all, $i,'priority');
          $content[$key]['privacy'] = db_result($res_all, $i, 'privacy');
          $content[$key]['submitted_by'] = db_result($res_all, $i,
                                                     'submitted_by');
        }
    }

  # No item found? Exit here.
  if (!$item_exists)
    {
      print '<p class="warn">' . sprintf(("%s: %s"), $title, _("None found"))
            . "</p>\n";
      return;
    }

  # Give back the HTML output, if we have some data.
  global $HTML;
  print $HTML->box_top($title,'',1);

  # Create a hash to avoid looking several times for the same info.
  $dstatus = array();
  $allowed_to_see = array();
  $group_getname = array();

  # Sort the content by key, which contain the date as first field
  # (so order by date).
  ksort($content);
  $i = 0;

  while (list($key,) = each($content))
    {
      $current_item_id = $content[$key]['item_id'];
      $tracker = $content[$key]['tracker'];
      $current_group_id = $content[$key]['group_id'];
      $link_to_item = $GLOBALS['sys_home'] . $tracker . '/?' . $current_item_id;

      # Found out the status full text name:
      # this is project specific. If there is no project setup for this
      # then go to the default for the site.
      if (!array_key_exists($current_group_id . $tracker
                            . $content[$key]['resolution_id'],
                            $dstatus))
        {
          $dstatus[$current_group_id . $tracker
                   . $content[$key]['resolution_id']] =
            db_result(db_execute(
"SELECT value FROM " . $tracker . "_field_value
WHERE bug_field_id='108' AND (group_id=? OR group_id=100) AND value_id=?
ORDER BY bug_fv_id DESC LIMIT 1",
                                 array($group_id,
                                       $content[$key]['resolution_id'])),
                      0, 'value');
        }
      $status =
        $dstatus[$current_group_id . $tracker . $content[$key]['resolution_id']];

      print '
  <div class="' . get_priority_color($content[$key]['priority'],
                                     $content[$key]['status_id']) . '">';

      # Ability to remove a dependency is only given to technician
      # level members of a project.
      if ($dependson && $is_manager)
        {
          print '<span class="trash"><a href="'
            . htmlentities ($_SERVER['PHP_SELF'])
            . '?func=delete_dependancy&amp;item_id=' . $item_id
            . '&amp;item_depends_on=' . $current_item_id
            . '&amp;item_depends_on_artifact=' . $tracker . '">'
            . '<img src="' . $GLOBALS['sys_home'] . 'images/' . SV_THEME
            . '.theme/misc/trash.png" alt="' . _("Delete this dependency")
            . '" class="icon" /></a></span>';
        }

      # Link to the item.
      print '
        <a href="' . $link_to_item . '" class="block">';

      # Show the item type with an icon.
      print '<img src="' . $GLOBALS['sys_home'] . 'images/' . SV_THEME
            . '.theme/contexts/' . utils_get_tracker_icon($tracker)
            . '.png" class="icon" alt="' . $tracker . '" /> ';

      # Print summary only if the item is not private.
      # Check privacy right (dont care about the tracker specific
      # rights, being project member is enough).
      if (!array_key_exists($current_group_id, $allowed_to_see))
        $allowed_to_see[$current_group_id] =
          member_check(0, $current_group_id,
                       member_create_tracker_flag(ARTIFACT) . '2');

      if ($content[$key]['privacy'] == "2"
          && !$allowed_to_see[$current_group_id]
          && $content[$key]['submitted_by'] != user_getid())
        print _("---- Private ----");
      else
        print $content[$key]['summary'];

      # Print group info if the item is from another group.
      $fromgroup = null;
      if ($current_group_id != $group_id)
        {
          if (!array_key_exists($current_group_id, $group_getname))
            $group_getname[$current_group_id] =
              group_getname($content[$key]['group_id']).', ';

          $fromgroup = $group_getname[$current_group_id];
        }

      # Mention the status.
      print '&nbsp;<span class="xsmall">('
            . utils_get_tracker_prefix($tracker) . ' #' . $current_item_id
            . ', ' . $fromgroup . $status . ')</span></a>';
      print "</div>\n";
      $i++;
    }
  print $HTML->box_bottom(1);

  # Add links to make digests.
  reset($artifacts);
  print '<p class="noprint"><span class="preinput">' . _("Digest:")
        . "</span>\n<br />&nbsp;&nbsp;&nbsp;";
  $content = '';
  while (list(, $tracker) = each($artifacts))
    {
      if (!empty($item_exists_tracker[$tracker]))
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
              $linktitle = sprintf(
# TRANSLATORS: the argument is tracker name, unlocalized
# (this string is a fallback that should never actually be used).
                                   _("%s dependencies"), $tracker);
            }
          $content .= utils_link($GLOBALS['sys_home'] . $tracker . '/?group_id='
                      . $group_id
                      . '&amp;func=digestselectfield&amp;dependencies_of_item='
                      . $item_id . '&amp;dependencies_of_tracker=' . ARTIFACT,
                      "$linktitle", 'noprint') . ', ';
        }
    }
  print rtrim($content, ', ') . ".</p>\n";
}
?>
