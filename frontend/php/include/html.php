<?php
# Common reusable HTML code.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Pogonyshev <pogonyshev--gmx.net>
# Copyright (C) 2007, 2008  Sylvain Beucler
# Copyright (C) 2008  Aleix Conchillo Flaque
# Copyright (C) 2013, 2017, 2018, 2020, 2021, 2022 Ineiev <ineiev--gnu.org>
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

$dir_name = dirname (__FILE__);
require_once ("$dir_name/sane.php");
require_once ("$dir_name/markup.php");
require_once ("$dir_name/form.php");

# Display browsing/display options: should be on top of pages, after the
# specific content/page description.
# The form should end by #options so it the user does not have to scroll down
# too much.
function html_show_displayoptions ($content, $form_opening=0, $submit=0)
{
  return html_show_boxoptions (
    _("Display Criteria"), $content, $form_opening, $submit
  );
}

function html_show_boxoptions ($legend, $content, $form_opening=0, $submit=0)
{
  $ret = "\n<fieldset id='options' class='boxoptions'>\n<legend>";

  extract(sane_import('request', ['true' => 'boxoptionwanted']));

  if ($boxoptionwanted != 1)
    $boxoptionwanted = 0;
  else
    $boxoptionwanted = 1;

  $ret .= "\n<script type='text/javascript' src=\"/js/show-hide.php?"
  . "deploy=$boxoptionwanted&amp;legend=" . urlencode ($legend)
  . "&amp;box_id=boxoptions&amp;suffix=\"></script>";
  $ret .= "\n<noscript>\n<span id='boxoptionslinkshow'>$legend</span>\n"
    . "</noscript>\n</legend>\n";

  $ret .= "<span id='boxoptionscontent'>\n";
  if ($boxoptionwanted != 1)
    $ret .= "\n<script type=\"text/javascript\" "
      . "src=\"/js/hide-span.php?box_id=boxoptionscontent\"></script>\n";

  if ($form_opening && $submit)
    $ret .= "\n$form_opening <span class='boxoptionssubmit'>$submit</span>";

  # We add boxoptionwanted to be able to determine if a boxoption was used
  # to update the page, in which case the boxoption must appear deployed.
  $ret .= "<span class='smaller'>$content"
     . form_input ("hidden", "boxoptionwanted", "1")
     . '</span>';

  if ($form_opening && $submit)
    $ret .= "\n</form>\n";

  return "$ret\n</span>\n</fieldset>\n";
}

# Function to create a an area in the page that can be hidden or shown
# in one click with a JavaScript.
# Per policy, this must work with a browser that does not support at all
# JavaScript.
# This is useful on item pages because we have some info that is not
# essential to be shown (like CC list etc), but still very nice be able to
# access easily.
function html_hidsubpart_header ($uniqueid, $title, $deployed=false)
{
  global $is_deployed;

  # Try to find a deployed value that match the unique id.
  # If found, override the deployed setting (the deployed setting should be
  # used to set a default behavior, but if in the case we explicitely
  # use an array to determine what is deployed, this matters more).
  if (is_array ($is_deployed)
      && array_key_exists ($uniqueid, $is_deployed))
    $deployed = $is_deployed[$uniqueid];
  if ($deployed != 1)
    $deployed = 0;

  $ret = "\n<h2 id=\"$uniqueid\">\n"
    . '<script type="text/javascript" src="/js/show-hide.php?'
  . "deploy=$deployed&amp;legend=" . urlencode ($title)
  . "&amp;box_id=hidsubpart&amp;suffix=$uniqueid\"></script>\n";
  $ret .= "\n<noscript>\n<a href=\"#$uniqueid\">$title</a>\n</noscript>"
    . "</h2>\n\n";
  $ret .= "<div id=\"hidsubpartcontent$uniqueid\">\n";
  if (!$deployed)
    {
      $ret .= '<script type="text/javascript" src="/js/hide-span.php'
        . "?box_id=hidsubpartcontent$uniqueid\"></script>\n";
    }
  return $ret;
}

function html_hidsubpart_footer ()
{
  return "\n</div><!-- closing hidsubpart -->\n";
}

function html_splitpage ($how)
{
  if ($how == 'start' || $how == '1')
    return "\n<div class='splitright'>\n";
  if ($how == 'middle' || $how == '2')
    return  "\n</div><!-- end  splitright -->\n<div class='splitleft'>\n";
  return "\n</div><!-- end  splitleft -->\n";
}

function html_nextprev ($search_url, $rows, $rows_returned, $varprefix = false)
{
  global $offset, $max_rows, $sys_home;
  $img_base = "${sys_home}images" . SV_THEME . ".theme/arrrows";

  if (!$varprefix)
    $varprefix = '';
  else
    $varprefix .= '_';

  if (($rows_returned > $rows) || ($offset != 0))
    {
      print "\n<br /><p class=\"nextprev\">\n";

      if ($offset != 0)
        {
          print "<a href=\"$search_url&amp;${varprefix}offset="
            . ($offset - $rows) . "&amp;${varprefix}max_rows="
            . htmlspecialchars ($max_rows) . "#${varprefix}results\">";
          print "<img src=\"$img_base/previous.png\" border='0' alt='' />"
            . _("Previous Results") . '</a>';
        }
      else
        {
          print
            "<img src=\"$img_base/previousgrey.png\" border='0' alt='' /><em>"
             . _("Previous Results") . '</em>';
        }
      print "&nbsp; &nbsp; &nbsp;";

      if ($rows_returned > $rows)
        {
          print "<a href=\"$search_url&amp;${varprefix}offset="
            . ($offset + $rows) . "&amp;${varprefix}max_rows="
            . htmlspecialchars ($max_rows) . "#${varprefix}results\">";
          print _("Next Results")
            . " <img src=\"$img_base/next.png\" border='0' alt='' /></a>";
        }
      else
        {
          print '<em>' . _("Next Results")
            . "</em> <img src=\"$img_base/nextgrey.png\" border='0' alt='' />";
        }
      print "</p>\n";
    }
}

function html_anchor ($content, $name)
{
  if (!$name)
    $name = $content;
  return "<a id=\"$name\" href=\"#$name\">$content</a>";
}

# Print out the feedback.
function html_feedback ($bottom)
{
  global $feedback, $ffeedback, $sys_home;

  # Escape the html special chars, active markup.

  # Ugh... Actually this is because feedback may be passed through
  # $_GET[] in some situations, which can lead to XSS if the content
  # is not escaped. We need a proper way to display formatted text to
  # the user - OR, we need to properly replace pages that pass
  # 'feedback' as a GET argument (grep 'feedback=').

  # Be quiet when there is no feedback.
  if (!($ffeedback || $feedback))
    return;

  $feedback = markup_basic (htmlspecialchars ($feedback));
  $ffeedback = markup_basic (htmlspecialchars ($ffeedback));

  $suffix = '';
  if ($bottom)
    $suffix = '_bottom';

  $script_hide = '<script type="text/javascript" '
    . "src=\"/js/hide-feedback.php?suffix=$suffix\"></script>\n";

  $class_hide = 'feedback';

  # With MSIE  the feedback will be
  # in relative position, so the hiding link will not make sense.
  if (is_broken_msie () && empty ($_GET['printer']))
    $script_hide = '';
  # Users can choose the same behavior, disallowing the fixed positionning
  # of the feedback (less convenient as the feedback gets easily hidden,
  # requires to scroll to be accessed, but seems prefered by users of
  # mozilla that slow scrolling down/up when there is such fixed box on the
  # page).
  if (user_get_preference ("nonfixed_feedback"))
    {
      $class_hide = 'feedback feedback-hide';
      $script_hide = '';
    }

  print "<div id=\"feedbackback$suffix\" class='feedbackback'>"
    . _("Show feedback again") . "</div>\n";
  print '<script type="text/javascript" src="/js/show-feedback.php?suffix='
    . "$suffix\"></script>\n";

  $img_base = "${sys_home}/images/" . SV_THEME . ".theme";
  $img_ok = "$img_base/bool/ok.png";
  $img_wrong = "$img_base/bool/wrong.png";
  # Only success.
  if ($feedback && !$ffeedback)
    print "<div id=\"feedback$suffix\" class=\"$class_hide\">"
      . '<span class="feedbacktitle">'
      . "<img src=\"$img_ok\" class=\"feedbackimage\" alt='' /> "
      . _("Success:") . "</span> $feedback</div>\n$script_hide";

  # Only errors.
  if ($ffeedback && !$feedback)
    {
      print "<div id=\"feedback$suffix\" class=\"feedbackerror $class_hide\">"
        . "<span class='feedbackerrortitle'>"
        . "<img src=\"$img_wrong\" class='feedbackimage' alt='' /> "
        . _("Error:") . "</span><br/>\n$ffeedback</div>\n";
    }

  # Errors and success.
  if ($ffeedback && $feedback)
    {
      print "<div id=\"feedback$suffix\" class=\"feedbackerrorandsuccess "
        . "$class_hide\"><span class='feedbackerrorandsuccesstitle'>"
        . "<img src=\"$img_wrong\" class='feedbackimage' alt='' /> "
        . _("Some Errors:") . "</span> $feedback $ffeedback</div>\n";
    }

  # We empty feedback so there will be a bottom feedback only if something
  # changed. It may confuse users, however I would find more confusing to
  # have two lookalike feedback information providing most of the time the
  # same information AND (that is the problem) sometimes more information
  # in the second one.
  $feedback = $ffeedback = '';
}

function html_feedback_top ()
{
  html_feedback (0);
}

function html_feedback_bottom ()
{
  html_feedback (1);
}

function html_image ($src, $args)
{
  GLOBAL $img_attr, $sys_home, $sys_www_topdir;
  $base = "images/" . SV_THEME . ".theme/$src";
  $path = "$sys_www_topdir/$base";

  $return = "<img src=\"$sys_home$base\"";
  foreach ($args as $k => $v)
    $return .= " $k=\"$v\"";

  # Insert a border tag if there isn't one.
  if (empty ($args['border']))
    $return .= ' border="0"';

  # If there is neither height nor width tag, insert them both.
  if (empty ($args['height']) && empty ($args['width']))
    {
     # Check to see if we've already fetched the image data.
     if (empty ($img_attr[$src]) && is_file ($path))
        {
          list ($width, $height, $type, $img_attr[$src]) =
            @getimagesize ($path);
        }
      $return .= ' ' . $img_attr[$src];
    }
  if (!$args['alt'])
    $return .= " alt=\"\"";

  return "$return />";
}

# Start a list table from an array of titles and builds.
# The first row of a new table.
#
# Optionally take a second array of links for the titles.
function html_build_list_table_top (
  $title_arr, $links_arr = false, $table = true
)
{
  global $HTML;
  $return = '';

  if ($table)
    $return = "\n<table class='box'>\n";

  $return .= "<tr>\n";

  $count = count ($title_arr);
  if ($links_arr)
    {
      for ($i = 0; $i < $count; $i++)
        {
          $ln = $links_arr[$i]; $ti = $title_arr[$i];
          $return .= "<th class='boxtitle'>"
            . "<a class='sortbutton' href=\"$ln\">$ti</a></th>\n";
        }
    }
  else
    {
      for ($i = 0; $i < $count; $i++)
        {
          $return .= "<th class='boxtitle'>{$title_arr[$i]}</th>\n";
        }
    }
  return "$return</tr>\n";
}

function html_get_alt_row_color ($i)
{
  if ($i % 2)
    return 'boxitem';
  return 'boxitemalt';
}

# Auxiliary function to use in html_build_select*box*.
function html_title_attr ($title)
{
  if ($title == "")
    return "";
  return "title=\"$title\" ";
}

# Build a select box from arrays, with the first array being the "id"
# or value and the array being the text you want displayed.
#
# The second parameter is the name you want assigned to this form element.
#
# The third parameter is the value of the item that should be checked.
function html_build_select_box_from_array (
  $vals, $select_name, $checked_val = 'xzxz', $samevals = 0, $title = ""
)
{
  $return = "<select " . html_title_attr($title) . "name=\"$select_name\">\n";
  $rows = count ($vals);
  for ($i = 0; $i < $rows; $i++)
    {
      $v = $i;
      if ($samevals)
        $v = $vals[$i];
      $return .= "  <option value=\"$v\"";
      if ($v == $checked_val)
        $return .= ' selected="selected"';
      $return .= ">{$vals[$i]}</option>\n";
    }
  return "$return\n</select>\n";
}

# The infamous '100 row' has to do with the
# SQL Table joins done throughout all this code.
# There must be a related row in users, categories, etc, and by default that
# row is 100, so almost every pop-up box has 100 as the default
# Most tables in the database should therefore have a row with an id of 100 in it
# so that joins are successful.
#
# There is now another infamous row called the Any row. It is not
# in any table as opposed to 100. it's just here as a convenience mostly
# when using select boxes in queries (bug, task, ...). The 0 value is reserved
# for Any and must not be used in any table.
#
# Takes two arrays, with $vals being the "id" or value
# and $texts being the text you want displayed.
#
# $select_name is the name you want assigned to this form element.
#
# $checked_val is the value of the item that should be checked.
#
# $show_100 is a boolean - whether or not to show the '100 row'.
#
# $text_100 is what to call the '100 row', defaults to none.
#
# $show_any is a boolean - whether or not to show the 'Any row'.
#
# $text_any is what to call the 'Any row' defaults to 'Any'.
#
# $show_unknown is a boolean - whether to show "Unknown" row.
#
# $title is the title for the box.
function html_build_select_box_from_arrays (
  $vals, $texts, $select_name, $checked_val = 'xzxz', $show_100 = true,
  $text_100 = 'None', $show_any = false, $text_any = 'Any',
  $show_unknown = false, $title = ""
)
{
  if ($text_100 == 'None')
    $text_100 = _('None');
  if ($text_any == 'Any')
    $text_any = _('Any');
  if ($title != '')
    $id_attr = '';
  else
    $id_attr = " id=\"$select_name\"";

  $return = "\n<select " . html_title_attr ($title)
    . "name=\"$select_name\"$id_attr >\n";

  # We want the "Default" on item initial post, only at this momement.
  if ($show_unknown)
    {
      $return .= "<option value=\"!unknown!\">" . _("Unknown") . "</option>\n";
    }

  # We don't always want the default any  row shown.
  if ($show_any)
    {
      $selected = ( $checked_val == 0 ? 'selected="selected"':'');
      $return .= "<option value=\"0\" $selected>$text_any </option>\n";
    }

  # We don't always want the default 100 row shown.
  if ($show_100)
    {
      $selected = ( $checked_val == 100 ? 'selected="selected"':'');
      $return .= "<option value=\"100\" $selected>$text_100 </option>\n";
    }

  $rows = count ($vals);
  if (count ($texts) != $rows)
    $return .= _('ERROR - number of values differs from number of texts');

  for ($i = 0; $i < $rows; $i++)
    {
      #  Uggh - sorry - don't show the 100 row and Any row.
      #  If it was shown above, otherwise do show it.
      if ((($vals[$i] != '100') && ($vals[$i] != '0'))
           || ($vals[$i] == '100' && !$show_100)
           || ($vals[$i] == '0' && !$show_any))
        {
          $return .= "\n<option value=\"{$vals[$i]}\"";
          if ($vals[$i] == $checked_val)
            {
              $return .= ' selected="selected"';
            }
          $return .= ">{$texts[$i]}</option>\n";
       }
    }
  $return .= "</select>\n";
  return $return;
}

# Build a select box from a result set, with the first column being the "id"
# or value and the second column being the text you want displayed.
#
# The second parameter is the name you want assigned to this form element.
#
# The third parameter is the value of the item that should
# be checked.
#
# The fourth parameter is a boolean - whether or not to show
# the '100 row'.
#
# The fifth parameter is what to call the '100 row' defaults to none.
function html_build_select_box (
  $result, $name, $checked_val = "xzxz", $show_100 = true, $text_100 = 'None',
  $show_any = false, $text_any = 'Any', $show_unknown = false, $title = ""
)
{
  return html_build_select_box_from_arrays (
    utils_result_column_to_array ($result),
    utils_result_column_to_array ($result, 1), $name, $checked_val, $show_100,
    $text_100, $show_any, $text_any, $show_unknown, $title
  );
}

# The same as html_build_select_box, but the items are localized.
function html_build_localized_select_box (
  $result, $name, $checked_val = "xzxz", $show_100 = true, $text_100 = 'None',
  $show_any = false, $text_any = 'Any', $show_unknown = false, $title = ""
)
{
  return html_build_select_box_from_arrays (
    utils_result_column_to_array($result),
    utils_result_column_to_array($result, 1, true),
    $name, $checked_val, $show_100, $text_100, $show_any, $text_any,
    $show_unknown, $title
  );
}

# Build a select box from a result set, with the first column being the "id"
# or value and the second column being the text you want displayed.
#
# The second parameter is the name you want assigned to this form element.
#
# The third parameter is an array of checked values.
#
# The fourth parameter is the size of this box.
#
# Fifth to eigth params determine whether to show None and Any.
#
# Ninth param determine whether to show numeric values next to
# the menu label (default true for backward compatibility.
function html_build_multiple_select_box (
  $result, $name, $checked_array, $size = '8', $show_100 = true,
  $text_100 = 'None', $show_any = false, $text_any = 'Any', $show_value = true,
  $title = ""
)
{
  $checked_count = count ($checked_array);

  $return = "\n<select " . html_title_attr ($title)
    . "name=\"$name\" multiple size='$size'>\n";
  # Put in the Any box.
  if ($show_any)
    {
      $return .= '<option value="0"';
      for ($j = 0; $j < $checked_count; $j++)
        {
          if ($checked_array[$j] == '0')
            {
              $return .= ' selected="selected"';
            }
        }
      $return .= ">$text_any</option>\n";
    }
  # Put in the default NONE box.
  if ($show_100)
    {
      $return .= '<option value="100"';
      for ($j = 0; $j < $checked_count; $j++)
        {
          if ($checked_array[$j] == '100')
            {
              $return .= ' selected="selected"';
            }
        }
      $return .= ">$text_100</option>\n";
    }
  $rows = db_numrows ($result);
  for ($i = 0; $i < $rows; $i++)
    {
      if (db_result($result, $i, 0) != '100')
        {
          $return .= '<option value="' . db_result ($result, $i, 0) . '"';
          # Determine if it's checked.
          $val = db_result($result, $i, 0);
          for ($j = 0; $j<$checked_count; $j++)
            {
              if ($val == $checked_array[$j])
                {
                  $return .= ' selected="selected"';
                }
            }
          $val .= '-';
          if (!$show_value)
            $val = '';
          $return .= ">$val"
             . substr (db_result ($result, $i, 1), 0, 35) . "</option>\n";
        }
    }
  return "$return</select>\n";
}

function html_select_permission_box ($artifact, $row, $level = "member")
{
  $num = '';
  if ($level == "type")
    {
      $value = $row;
      $default = 0;
    }
  elseif ($level == "group")
    {
      $value = $row;
      $default = _("Group Type Default");
    }
  else
    {
      $num = $row['user_id'];
      $value = $row["${artifact}_flags"];
      $default = _("Group Default");
    }

  print "<td align=\"center\">\n"
    . '<select title="' . _("Roles of members")
    . "\" name=\"${artifact}_user_$num\">\n";
  if ($default)
    {
      $sel = ' selected="selected"';
      if ($value)
        $sel = '';
      print "  <option value='NULL'$sel>$default</option>\n";
    }
  $labels = [
    [9, _("None")],
    [1, _("Technician"), 1],
    [3, _("Manager")],
    [2, _("Techn. & Manager"), 1],
  ];
  foreach ($labels as $vl)
    {
      if ($artifact == 'news' && count ($vl) > 2)
        continue;
      $v = $vl[0]; $l = $vl[1];
      $sel = '';
      if ($value == $v)
        $sel = ' selected="selected"';
      print "  <option value='$v'$sel>$l</option>\n";
    }
  print "</select>\n";
  if (!$value && $level == "group")
    {
      $value = group_gettypepermissions ($GLOBALS['group_id'], $artifact);
      print "<br />\n(";
      foreach ($labels as $vl)
        if ($value == $vl[0])
          print $vl[1];
      print ")\n";
    }
  print "</td>\n";
}

# Build restriction select box, in a <td> unless $notd.
# $artifact: tracker name
# $row: current selection
# $level = 'type': edit group types
#        = 'group': configure restrictions in a group
# $event = 1: posting items
#        = 2: posting comments
function html_select_restriction_box (
  $artifact, $row, $level = "group", $notd = 0, $event = 1
)
{
  $value = $row;
  $default = 0;
  if ($level != "type")
    {
      $default = _("Group Type Default");
      if ($event == 2)
        $default = _("Same as for new items");
    }

  if (!$notd)
    print "<td align='center'>\n";

  print '<select title="' . _("Permission level")
    . "\" name=\"${artifact}_restrict_event$event\">\n";

  if ($default)
    {
      $sel = ' selected="selected"';
      if ($value)
        $sel = '';
      print "<option value='NULL'$sel>$default</option>\n";
    }
  $labels = [
    [6, _("Nobody")], [5, _("Project Member")],  [3, _("Logged-in User")],
    [2, _("Anonymous")],
  ];
  foreach ($labels as $vl)
    {
      $v = $vl[0]; $l = $vl[1];
      $sel = '';
      if ($value == $v)
        $sel = ' selected="selected"';
      print "<option value='$v'$sel>$l</option>\n";
    }
  print "</select>\n";

  if (!$value && $level == "group" && $event == 1)
    {
      $value = group_gettyperestrictions ($GLOBALS['group_id'], $artifact);
      print "<br />\n(";
      foreach ($labels as $vl)
        if ($value == $vl[0])
          print $vl[1];
      print ")\n";
    }
  if (!$notd)
    print "</td>\n";
}

# This function must know every type of directory that can be built by the
# backend.
function html_select_typedir_box ($input_name, $current_value)
{
  # The strings are not localized because they are for siteadmin's eyes only.
  print "<br />&nbsp;&nbsp;\n";
  print "<select title='directories' name=\"$input_name\">\n";
  foreach (
    [
      'basicdirectory' => "Basic Directory",
      "basiccvs" => "Basic CVS Directory",
      "basicsvn" => "Basic Subversion Directory",
      "basicgit" => "Basic Git Directory",
      "basichg" => "Basic Mercurial Directory",
      "basicbzr" => "Basic Bazaar Directory",
      "cvsattic" => "CVS Attic/Gna",
      "svnattic" => "Subversion Attic/Gna",
      "svnatticwebsite" => "Subversion Subdirectory Attic/Gna",
      "savannah-gnu" => "Savannah GNU",
      "savannah-nongnu" => "Savannah non-GNU",
    ] as $dir => $title
  )
    {
      $sel = '';
      if ($current_value == $dir)
        $sel = ' selected="selected"';
      print "<option value='$dir'$sel>$title</option>\n";
    }
  print "</select> [BACKEND SPECIFIC]\n";
  print "<p><span class='smaller'>Basic directory will make the backend"
    . "using DownloadMakeArea(), defined in Savannah::Download; <br />\n"
    . "CVS directory will make the backend using CvsMakeArea(), defined "
    . "in Savannah::Cvs.\n</span><p>";
}

# Print an theme select box. This function will add the special rotate
# and random meta-themes. This function will hide disallowed theme.
# That is said that theme are not strictly forbidden, someone can
# forge a form and choose a forbidden theme.
# But it is really not a big deal and it is better to have making to
# many checks in theme_list(), which is frequently ran, unlike this
# one.
function html_select_theme_box ($input_name = "user_theme", $current = 0)
{
  print '<select title="' . _("Website theme") . "\" name=\"$input_name\">\n";
  $theme_option = function ($theme, $label = null) use ($current)
  {
    print "  <option value=\"$theme\"";
    if ($theme == $current)
      print ' selected="selected"';
    print ">";
    if (empty ($label))
      print $theme;
    else
      print "&gt; $label";
    if ($theme == $GLOBALS['sys_themedefault'])
      print ' ' . _("(default)");
    print "</option>\n";
  };
  # Fixed themes.
  foreach (theme_list() as $theme)
    $theme_option ($theme);
  # Two special themes.
  $theme_option ("rotate", _("Pick theme alphabetically every day"));
  $theme_option ("random", _("Pick random theme every day"));
  print "</select>\n";
}

function html_build_checkbox ($name, $is_checked = 0, $title = "")
{
  $attr = [];
  if ($title !== '')
    $attr['title'] = $title;
  print  form_checkbox ($name, $is_checked, $attr);
}

# Catch all header functions.
function html_header ($params)
{
  global $HTML;
  print $HTML->header ($params);
  print html_feedback_top ();
}

function html_footer ($params)
{
  global $HTML, $feedback;
  print html_feedback_bottom ();
  $HTML->footer ($params);
}

# Aliases of catch all header functions.
function site_header ($params)
{
  html_header ($params);
}

function site_footer ($params)
{
  html_footer ($params);
}

# Project page functions.

# Everything required to handle security and state checks for a project web page.
# Params array() must contain $context and $group.
# Result - prints HTML directly.
function site_project_header ($params)
{
  global $group_id;
  $project = project_get_object ($group_id);

  if ($project->isError())
    {
      exit_error (
        # TRANSLATORS: the argument is group id (a number).
        sprintf (_("Invalid Group %s"), $group_id),
        _("That group does not exist.")
      );
    }

  if (!$project->isPublic())
    {
      # If it's a private group, you must be a member of that group.
      session_require (['group' => $group_id]);
    }

  # For dead projects must be member of admin project.
  if (!$project->isActive())
    {
      # Only sys_group people can view non-active, non-holding groups.
      session_require (['group' => $GLOBALS['sys_group_id']]);
    }
  html_header ($params);
}

# Currently a simple shim that should be on every project page,
# rather than a direct call to site_footer() or theme_footer().
# Params array() empty.
# Result - print HTML directly.
function site_project_footer ($params)
{
  html_footer ($params);
}

# User page functions.

# Everything required to handle security and
# add navigation for user pages like /my/ and /account/.
# Params array() must contain $user_id.
# Result - print HTML directly.
function site_user_header ($params)
{
  session_require (['isloggedin' => '1']);
  html_header ($params);
}

# Currently a simple shim that should be on every user page,
# rather than a direct call to site_footer() or theme_footer().
# Params array() empty.
# Result - print HTML directly.
function site_user_footer ($params)
{
  html_footer ($params);
}

# Administrative page functions.
function site_admin_header ($params)
{
  session_require (['group' => '1','admin_flags' => 'A']);
  html_header ($params);
}

function site_admin_footer ($params)
{
  html_footer ($params);
}

function show_group_type_box (
  $name = 'group_type', $checked_val = 'xzxz', $show_select_one = false
)
{
  $result = db_query("SELECT * FROM group_type");
  return html_build_select_box (
    $result, 'group_type', $checked_val, $show_select_one,
    "> " . _("Choose one below"), false, 'Any', false, _('Group type')
  );
}

function html_member_explain_roles ()
{
  print '<p>'
    . _("Technicians, and only technicians, can be assigned items of "
        . "trackers. They\ncannot reassign items, change the status or "
        . "priority of items.");
  print "</p>\n<p>";
  print _("Tracker Managers can fully manage items of trackers, including\n"
          . "assigning items to technicians, reassigning items over trackers "
          . "and projects,\nchanging priority and status of items&mdash;but "
          . "they cannot configure the\ntrackers.");
  print "</p>\n<p>";
  print _("Project Admins can manage members, configure the trackers, post\n"
          . "jobs, and add mailing lists. They actually also have manager "
          . "rights on every\ntracker and are allowed to read private items.");
  print "</p>\n";
}
?>
