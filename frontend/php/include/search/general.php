<?php
# Functions used by /search/index.php
#
# Copyright (C) 2003-2006 Stéphane Urbanovski <s.urbanovski--ac-nancy-metz.fr>
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007, 2008  Sylvain Beucler
# Copyright (C) 2008  Nicodemo Alvaro
# Copyright (C) 2017, 2018, 2021, 2022 Ineiev
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

# Check if the group uses any trackers to search in.
function search_has_group_anything_to_search ($gid)
{
  $cases = ['support', 'bugs', 'task', 'patch'];
  $group = project_get_object ($gid);
  foreach ($cases as $tracker)
    if ($group->Uses ($tracker))
      return true;
  return false;
}

# List tracker search options for given group and kind of search;
# used in search_box ().
function search_list_tracker_options ($gid, $type_of_search, $is_small)
{
   $sel = '';
   if (!empty ($gid))
     {
       $group_realname = substr (group_getname ($gid), 0, 10) . "...";
       $group = project_get_object ($gid);
     }
   $cases = [
     'support' => [
       # TRANSLATORS: this string is used in the context
       # of "Search [...] in Support"; the HTML comment is used
       # to differentiate the usages of the same English string.
       _("<!-- Search... in -->Support"),
       # TRANSLATORS: this string is used in the context
       # of "Search [...] in %s Support"; the argument is group name
       # (like GNU Coreutils).
       _("%s Support"),
     ],
     'bugs'  => [
       # TRANSLATORS: this string is used in the context
       # of "Search [...] in Bugs"; the HTML comment is used
       # to differentiate the usages of the same English string.
       _("<!-- Search... in -->Bugs"),
       # TRANSLATORS: this string is used in the context of
       # "Search [...] in %s Bugs"; the argument is group name
       # (like GNU Coreutils).
       _("%s Bugs"),
     ],
     'task' => [
       # TRANSLATORS: this string is used in the context of
       # "Search [...] in Tasks"; the HTML comment is used to differentiate
       # the usages of the same English string.
       _("<!-- Search... in -->Tasks"),
       # TRANSLATORS: this string is used in the context
       # of "Search [...] in %s Tasks"; the argument is group name
       # (like GNU Coreutils).
       _("%s Tasks"),
     ],
     'patch' => [
       # TRANSLATORS: this string is used in the context
       # of "Search [...] in Patches"; the HTML comment is used
       # to differentiate the usages of the same English string.
       _("<!-- Search... in -->Patches"),
       # TRANSLATORS: this string is used in the context
       # of "Search [...] in %s Patches"; the argument is group name
       # (like GNU Coreutils).
       _("%s Patches"),
     ],
   ];

   foreach ($cases as $key => $msg)
     {
       $text = '';
       if (empty ($gid) || ($is_small && $group->Uses ($key)))
         $text = $msg[0];
       elseif ($group->Uses ($key))
         $text = sprintf ($msg[1], $group_realname);
       if (!$text)
         continue;
       $sel_attr = '';
       if ($type_of_search == $key)
          $sel_attr = ' selected="selected"';
       $sel .= "<option value='$key' $sel_attr>$text</option>\n";
     }
  return $sel;
}

# Build a search box.
# $search_words: terms to look for.
# $scope defines the search area:
#   empty      - all trackers
#   'sitewide' - trackers of all groups (used in the left menu)
#   artifact   - given tracker
# $size: width of the text field used for $search_words.
function search_box ($searched_words = '', $scope = null, $size = 15)
{
  global $words, $group_id, $exact, $type_of_search, $type, $max_rows;
  global $only_group_id;

  $gid = null;
  if (!empty ($group_id))
    $gid = $group_id;

  if ($only_group_id)
    $gid = $only_group_id;

  if ($size > 15)
    $is_small = 0;
  else
    $is_small = 1;

  # If it is the left menu, small box, then make sure any group_id info
  # is ignored, because we want to keep the left menu site-wide.
  if ($scope == "sitewide")
    $gid = $scope = null;

  # If there is no search currently, set the default.
  if (!isset ($type_of_search))
    $exact = 1;
  if (!isset ($max_rows))
    $max_rows = "25";

  # If the wildcard '%%%' is searched, replace it with the more usual '*'.
  if ($words == "%%%")
    $words = "*";

  $ret =
    "<form action=\"{$GLOBALS['sys_home']}search/#options\" method='get'>\n";

  if (!$is_small)
    {
      # If it's a big form, we want the submit button on the right.
      $ret .= '<span class="boxoptionssubmit">'
        . '<input type="submit" name="Search" value="' . _("Search")
        . "\" />&nbsp;</span>\n";
    }

  $ret .= '<input type="text" title="' . _("Terms to look for") . '" '
    . "size=\"$size\" name=\"words\" value=\""
    . htmlspecialchars ($searched_words) . "\" />\n";

  if ($is_small)
    $ret .= "<br />\n";

  if (empty ($scope))
    {
      $sel = '<select title="' . _("Area to search in") . '" '
        . "name='type_of_search'>\n";

      # If the search is restricted to a given group, remove the possibility
      # to search another group, unless we're showing the left box.
      if (empty ($gid))
        {
          $sel .= '<option value="soft"'
            . (($type_of_search == "soft")||($type_of_search == "") ?
               ' selected="selected"' : "")
            . '>'
            # TRANSLATORS: this string is used in the context
            # of "Search [...] in Projects"
            . _("Projects") . "</option>\n";

          $sel .= '<option value="people"'
            . (($type_of_search == "people") ? ' selected="selected"' : "")
            . '>'
            # TRANSLATORS: this string is used in the context
            # of "Search [...] in People"
            . _("People") . "</option>\n";
        }
      $sel .=
        search_list_tracker_options ($gid, $type_of_search, $is_small)
        . "</select>\n";

      # TRANSLATORS: this word is used in the phrase "Search [...] in
      # [Projects|People|Support|Bugs|Tasks|Patches]"
      # in the main menu on the left side.
      # Make sure to put this piece in agreement with the following strings.
      $ret .= sprintf (' ' . _("in %s"), $sel);
    }
  else # !empty ($scope)
    {
      $ret .= '<input type="hidden" name="type_of_search" value="'
        . "$scope\" />\n";
    }
  if ($is_small)
    $ret .= "<br />\n";

  if (isset ($gid))
    $ret .= "<input type='hidden' value='$gid' name='only_group_id' />\n";

  if ($is_small)
    {
      # If it's a small form, the submit button has not already been inserted.
      $ret .= '&nbsp;&nbsp;&nbsp;<input type="submit" name="Search" value="'
        . _("Search") . "\" />&nbsp;\n";
    }

  if ($is_small)
    $ret .= "<input type='hidden' name='exact' value='1' />\n";
  else
    {
      $ck_mark = " checked='checked'";
      $ck = $exact? '': $ck_mark;
      $ret .= "<br />\n&nbsp;<input type='radio' name='exact' value='0'$ck/>"
        . _("with at least one of the words") . "\n";
      $ck = $exact? $ck_mark: '';
      $ret .= "<br />\n&nbsp;<input type='radio' name='exact' value='1'$ck/>"
        . _("with all of the words") . "\n";
      $ret .= "<br />\n&nbsp;"
        . sprintf (
            ngettext (
              "%s result per page", "%s results per page", intval ($max_rows)
            ),
            "<input type='text' name='max_rows' value='$max_rows' title=\""
            . _("Number of items to show per page") . '" size="4" />'
          )
        . "\n";
      if (!isset ($gid))
        {
          # Add the functionality to restrict the search to a group type.
          $ret .= "<br />\n&nbsp;";

          $select = '<select name="type" title="' . _("Group type to search in")
            . '" size="1"><option value="">'
            # TRANSLATORS: this string is used in the context
            # of "Search [...] in any group type"
            . _("any") . "</option>\n";
          $result =
            db_query("SELECT type_id,name FROM group_type ORDER BY type_id");
          while ($eachtype = db_fetch_array($result))
            {
              $sel_attr = '';
              if ($type == $eachtype['type_id'])
                 $sel_attr = ' selected="selected"';
              $select .=
                "<option value=\"{$eachtype['type_id']}\"$sel_attr>"
                . "{$eachtype['name']}</option>\n";
            }
          $select .= "</select>\n";

          # TRANSLATORS: the argument is group type
          # (like Official GNU software).
          $ret .=
             sprintf (
               _("Search in %s group type, when searching for a Project."),
               $select
             );
        } # !isset ($gid)
      $ret .= '<p>'
        . _("Notes: You can use the wildcard *, standing for everything. "
            . "You can also\nsearch items by number.")
        . "</p>\n";
    } # !$is_small
  return "$ret</form>\n";
}

function search_send_header ()
{
  global $words,$type_of_search,$only_group_id;

  if ($type_of_search == "soft" || $type_of_search == "people")
    {
      # There cannot be a group id specific if we are looking for a group
      # group id is meaningless when looking for someone.
      $group_id = 0;
    }
  site_header(array('title'=>_("Search"),'context'=>'search'));
# Print the form.
  if (!$only_group_id)
    $title = _("Search Criteria:");
  else
# TRANSLATORS: the argument is group name (like GNU Coreutils).
    $title = sprintf(_("New search criteria for the Group %s:"),
                     group_getname($only_group_id));

  print html_show_boxoptions($title, search_box($words, '', 45));
}

# Search results for XXX (in YYY):
# e.g.: Search results for emacs (in Projects):
function print_search_heading()
{
  global $words,$type_of_search,$only_group_id;
  # Print the result
  print '<h2 id="results">'._('Search results')."</h2>\n";
  if (!($words && $type_of_search))
    return;
  print "<p>";
  # Print real words describing the type of search.
  if ($type_of_search == "soft")
# TRANSLATORS: this string is the section to look in; it is used as the second
# argument in 'Search results for %1$s (in %2$s)'.
    $type_of_search_real = _("Projects");
  elseif ($type_of_search == "support")
# TRANSLATORS: this string is the section to look in; it is used as the second
# argument in 'Search results for %1$s (in %2$s)'.
# The HTML comment is used to differentiate the usages of the same English string.
    $type_of_search_real = _("<!-- Search... in -->Support");
  elseif ($type_of_search == "bugs")
# TRANSLATORS: this string is the section to look in; it is used as the second
# argument in 'Search results for %1$s (in %2$s)'.
# The HTML comment is used to differentiate the usages of the same English string.
    $type_of_search_real = _("<!-- Search... in -->Bugs");
  elseif ($type_of_search == "task")
# TRANSLATORS: this string is the section to look in; it is used as the second
# argument in 'Search results for %1$s (in %2$s)'.
# The HTML comment is used to differentiate the usages of the same English string.
    $type_of_search_real = _("<!-- Search... in -->Tasks");
  elseif ($type_of_search == "patch")
# TRANSLATORS: this string is the section to look in; it is used as the second
# argument in 'Search results for %1$s (in %2$s)'.
# The HTML comment is used to differentiate the usages of the same English string.
    $type_of_search_real = _("<!-- Search... in -->Patches");
  elseif ($type_of_search == "people")
# TRANSLATORS: this string is the section to look in; it is used as the second
# argument in 'Search results for %1$s (in %2$s)'.
# The HTML comment is used to differentiate the usages of the same English string.
    $type_of_search_real = _("<!-- Search... in -->People");

  if (!$only_group_id)
    {
# TRANSLATORS: the first argument is string to look for,
# the second argument is section (Project/Group|Support|Bugs|Task
#   |Patch|People).
      printf(_('Search results for %1$s in %2$s:'),
             '<strong>'.htmlspecialchars($words).'</strong>',
             $type_of_search_real);
    }
  else
    {
# TRANSLATORS: the first argument is string to look for,
# the second argument is section (Support|Bugs|Task
#   |Patch|People), the third argument is
# group name (like GNU Coreutils).
      printf(_('Search results for %1$s in %2$s, for the Group %3$s:'),
             '<strong>'.htmlspecialchars($words).'</strong>',
             $type_of_search_real, group_getname($only_group_id));
    }
  print "</p>\n";
}

function result_no_match ()
{
  return search_failed();
}

function search_failed ()
{
  global $no_rows,$words;
  $no_rows = 1 ;
  search_send_header();
  print '<span class="warn">';
  print _("None found. Please note that only search words of more than two
characters are valid.");
  print '</span>';
  print db_error();
}

// Build
// "((field1 LIKE '%kw1%' and/or field1 LIKE '%kw2%' ...)
//   or (field2 LIKE '%kw1%' and/or field2 LIKE '%kw2%' ...)
//   ...)"
// + matching parameters array, suitable for db_execute()
// $and_or <=> 'AND'/'OR' <=> all/any word
function search_keywords_in_fields($keywords, $fields, $and_or='OR')
{
  $allfields_sql_bits = array();
  $allfields_sql_params = array();
  foreach ($fields as $field)
    {
      $thisfield_sql_bits = array();
      $thisfield_sql_params = array();
      foreach($keywords as $keyword)
        {
          $thisfield_sql_bits[] = "$field LIKE ?";
          if (preg_match('/_id$/', $field))
            {
              # Strip "#" from, eg, "#153".
              $thisfield_sql_params[] = '%'
                                        . str_replace ('#', '', $keyword)
                                        . '%';
            }
          else
            $thisfield_sql_params[] = '%'.$keyword.'%';
        }
      $allfields_sql_bits[] = '(' . implode(" $and_or ", $thisfield_sql_bits)
                              . ')';
      $allfields_sql_params = array_merge($allfields_sql_params,
                                          $thisfield_sql_params);
    }
  $allfields_sql = '(' . implode(' OR ', $allfields_sql_bits) . ')';
  return array($allfields_sql, $allfields_sql_params);
}

# Run a search in the database, by default in programs.
function search_run ($keywords, $type_of_search="soft", $return_error_messages=1)
{
  global $type, $exact, $crit, $offset, $only_group_id, $max_rows;
  $and_or = $crit;

  # Remove useless blank spaces, escape nasty characters.
  $keywords = trim($keywords);

  # Convert the wildcard * to the similar SQL one, when it is alone.
  if ($keywords == "*")
    $keywords = "%%%";

  # Replace the wildcard * to the similar SQL one, when included in a
  # word.
  $keywords = strtr($keywords, "*", "%");

  # Convert the crit form value to the SQL equiv.
  if ($exact)
    $and_or='AND';
  else
    $and_or='OR';

  # No offset defined? Start the search in the DB at 0.
  if (!$offset || $offset < 0)
    $GLOBALS['offset'] = 0;

  # Print 25 rows by default.
  if (!$max_rows)
    $GLOBALS['max_rows'] = 25;

  # Accept only to do a search for more than 2 characters.
  # Exit only if we were not told to avoid returning error messages.
  # Note: we tell user we want more than 3 characters, to incitate to
  # do clever searchs. But it will be ok for only 2 characters (limit
  # that conveniently allow us to search by items numbers).
  if ($keywords && (strlen($keywords) < 3) && $return_error_messages)
    {
      search_failed();
      exit;
    }

  $arr_keywords = explode(" ", $keywords);
  $sql = '';
  $sql_params = array();

  if ($type_of_search == "soft")
    {
      $sql = "SELECT group_name,unix_group_name,type,group_id,short_description "
             ."FROM groups WHERE status='A' AND is_public='1' ";
      if ($type)
        {
          $sql .= "AND type=? ";
          $sql_params[] = $type;
        }

      list($kw_sql, $kw_sql_params) = search_keywords_in_fields(
        $arr_keywords,
        array('group_name', 'short_description', 'unix_group_name', 'group_id'),
        $and_or);
      $sql .= " AND $kw_sql ORDER BY group_name,unix_group_name ";
      $sql_params = array_merge($sql_params, $kw_sql_params);
    }
  elseif ($type_of_search == "people")
    {
      $sql = "SELECT user_name,user_id,realname "
        . "FROM user WHERE status='A' ";

      list($kw_sql, $kw_sql_params) = search_keywords_in_fields(
        $arr_keywords,
        array('user_name', 'realname', 'user_id'),
        $and_or);

      $sql .= " AND $kw_sql ORDER BY user_name ";
      $sql_params = array_merge($sql_params, $kw_sql_params);
    }
  elseif ($type_of_search == 'bugs'
          || $type_of_search == 'support'
          || $type_of_search == 'patch'
          || $type_of_search == 'cookbook'
          || $type_of_search == 'task')
    {
      $sql = "SELECT " . $type_of_search . ".bug_id,"
        . $type_of_search . ".summary,"
        . $type_of_search . ".date,"
        . $type_of_search . ".privacy,"
        . $type_of_search . ".submitted_by, user.user_name,"
        . $type_of_search . ".group_id "
        . "FROM " . $type_of_search . ",user,groups "
        . "WHERE user.user_id=" . $type_of_search . ".submitted_by "
        . "AND groups.group_id=" . $type_of_search . ".group_id ";
     if ($type_of_search != 'cookbook')
       # As of 2021, we have no use_cookbook in the groups table.
       $sql .= "AND groups.use_" . $type_of_search . "=1";

      list($kw_sql, $kw_sql_params) = search_keywords_in_fields(
        $arr_keywords,
        array("{$type_of_search}.details", "{$type_of_search}.summary",
              "{$type_of_search}.bug_id"),
        $and_or);

      $sql .= " AND $kw_sql ";
      $sql_params = array_merge($sql_params, $kw_sql_params);

      if ($only_group_id)
        {
          # $search_without_group_id can be set to avoid restricting search
          # to a group even if group_id is set.
          $sql .= " AND ".$type_of_search.".group_id=? ";
          $sql_params[] = $only_group_id;
        }

      $sql .= " AND ".$type_of_search.".spamscore < 5 "
        . "GROUP BY bug_id,summary,date,user_name "
        . "ORDER BY ".$type_of_search.".date DESC ";
    }
  else
    exit_error(_("Invalid search."));

  $sql .= " LIMIT ?,?";
  $sql_params[] = intval($offset);
  $sql_params[] = $max_rows + 1;
  return db_execute($sql, $sql_params);
}

function search_exact ($keywords)
{
# Find the characters that maybe for a non-precise search. No need to continue
# if it they are present.
  $non_precise_key1 = strpos($keywords, '*' );
  $non_precise_key2 = strpos($keywords, '%' );

  if (!($non_precise_key1 === false && $non_precise_key2 === false))
    return;
  $arr_keywords = explode(' ', $keywords);
  $question_marks = implode(',', array_fill(0, count($arr_keywords), '?'));
  $sql = "SELECT group_name,unix_group_name,short_description,name
             FROM groups,group_type
          WHERE type=type_id AND group_name IN ($question_marks)
          AND status='A' AND is_public='1'";
  $result = db_execute($sql,$arr_keywords);
  $num_rows = db_numrows($result);

  if ($num_rows != 1)
    return;
  print "<h2>";
  print
# TRANSLATORS: this is a title for search results when exactly one item is found.
        _("Unique project search result");
  print "</h2>\n";
  printf('<p>'._("Search string was: %s.")."</p>\n",
         '<strong>'.htmlspecialchars($keywords).'</strong>');

  $title_arr = array();
  $title_arr[] = _("Project");
  $title_arr[] = _("Description");
  $title_arr[] = _("Type");

  print html_build_list_table_top($title_arr);
  print "\n";
  $row = db_fetch_array($result);
  print "<tr><td><a href=\"../projects/"
."${row['unix_group_name']}\">${row['group_name']}</a></td>
  <td>${row['short_description']}</td>
  <td>${row['name']}</td></tr>
  </table>\n";
}
?>
