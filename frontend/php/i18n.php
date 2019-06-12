<?php
# Set preferred language (overriding possible browser preferences).
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2006, 2007  Sylvain Beucler
# Copyright (C) 2017, 2018  Ineiev
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

require_once('./include/init.php');
require_once('./include/sane.php');

Header("Expires: Wed, 11 Nov 1998 11:11:11 GMT");
Header("Cache-Control: no-cache");
Header("Cache-Control: must-revalidate");

# Input checks.
extract(sane_import('request',
                    array('language', 'lang_uri', 'cookie_test',
                          'cookie_for_a_year')));

# Check cookie support.
if (!isset($_COOKIE["cookie_probe"]))
  {
    if (!$cookie_test)
      {
        # Attempt to set a cookie to go to a new page to see
        # if the client will indeed send that cookie.
        session_cookie('cookie_probe', 1);
        header('Location: i18n.php?lang_uri='.urlencode($lang_uri).'&cookie_test=1');
        exit;
      }
    fb (sprintf(
# TRANSLATORS: the first argument is a domain (like "savannah.gnu.org"
# vs. "savannah.nongnu.org"); the second argument
# is a URL ("[URL label]" transforms to a link).
               _("Savane thinks your cookies are not activated for %s.
Please activate cookies in your web browser for this website
and [%s try again]."), $sys_default_domain,
$GLOBALS['sys_https_url'].$GLOBALS['sys_home'].'/i18n.php?lang_uri='.$lang_uri), 1);
  }

if (!empty($language) && ($language == 100 || isset($locale_names[$language])))
  {
    if ($language == 100)
      # Request to reset - clear cookie.
      utils_setcookie('LANGUAGE', "", time() - 3600 * 24);
    else
      {
        $period = 0;
        if ($cookie_for_a_year)
          $period = time() + 60 * 60 * 24 * 365;
        else
          utils_setcookie('LANGUAGE', $language, $period);
      }
    header("Location: ".$lang_uri);
    exit;
  }

if (!empty($language))
  fb (sprintf(_("Requested language code '%s' is unknown."),
              htmlentities($language)), 1);

$checked_language = "en";
if (isset($_COOKIE['LANGUAGE']))
  $checked_language = $_COOKIE['LANGUAGE'];

site_header(array('title'=>_("Set language")));

print "<p>"._("Savane uses language negotiation to automatically
select the translation the visitor prefers, and this is what we
generally recommend. In order to use this feature, you should
configure your preferred languages in your browser. However, this page
offers a way to override the mechanism of language negotiation for the
cases where configuring browser is hard or impossible. Note that each
domain (like savannah.gnu.org vs. savannah.nongnu.org) has its own
setting.")."</p>\n";

print '<form action="'.$GLOBALS['sys_https_url'].$GLOBALS['sys_home']
      .'i18n.php" method="post">';
print '<input type="hidden" name="lang_uri" value="'.htmlspecialchars($lang_uri, ENT_QUOTES)
      .'" />';
$checked = '';
if ($cookie_for_a_year)
  $checked = 'checked="checked" ';

print '<p><input type="checkbox" id="cookie_for_a_year" name="cookie_for_a_year"
tabindex="1" value="1" '."\n"
      .$checked.'/><span class="preinput"><label for="cookie_for_a_year">'
      ._("Keep for a year")."</label></span><br />\n";
print '<span class="text">'
      ._("Your language choice will be stored in a cookie for a year.
When unchecked, it will be cleared at the end of browser session.")
      ."</span></p>\n";

print "<p>\n&nbsp;&nbsp;<label for=\"language\">".("Language:")."</label>";
print html_build_select_box_from_arrays (array_keys ($locale_names),
                                         array_values($locale_names),
                                         "language",
                                         $checked_language,
                                         true,
                                         _("Reset"));
print "</p>\n<p><span class=\"text\">"
      ._("Use the topmost item (&ldquo;Reset&rdquo;) to clear the cookie
immediately.")."</span></p>\n";

print '<div class="center"><input type="submit" name="set" value="'
      ._("Set language").'" tabindex="1" /></div>';
print '</form>';
$HTML->footer(array());
?>
