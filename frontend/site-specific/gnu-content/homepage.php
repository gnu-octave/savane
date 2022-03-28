<?php
# Home page.

# Copyright (C) 2002 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2003 Jaime E. Villate
# Copyright (C) 2004, 2005, 2006, 2007, 2008, 2009, 2010 Sylvain Beucler
# Copyright (C) 2006 Michael J. Flickinger
# Copyright (C) 2008, 2011 Karl Berry
# Copyright (C) 2013, 2017, 2020, 2022 Ineiev <ineiev@gnu.org>
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

$is_nongnu_org = substr ($GLOBALS['sys_default_domain'], -8) != ".gnu.org";
print '<p>';
printf (
  _("Welcome to <strong>Savannah</strong>, the software forge for people\n"
    . "committed to <a href=\"%s\">free software</a>:"),
  '//www.gnu.org/philosophy/free-sw.html'
);
print "</p>\n\n";

print "<p><ul>\n<li>"
  . _("We host free projects that run on free operating systems and without\n"
      . "    any proprietary software dependencies.")
  . "<br />\n<a href='/register/requirements.php'>"
  . _("Hosting requirements") . "</a></li>\n<li>";
printf (
  _('Our service runs with 100%% free software, including '
    . '<a href="%s">itself</a>.'),
  '/projects/administration/'
);
print "</li>\n<li><a href='/maintenance/WhyChooseSavannah'>"
  . _('Why choose Savannah?') . "</a></li>\n</ul></p>\n<p>";
if ($is_nongnu_org)
  {
    printf (
      _("<strong>Savannah.nongnu.org</strong> is a central point for\n"
        . 'development, maintenance and distribution of '
        . '<a href="%s">free software</a>.'),
      '//www.gnu.org/philosophy/free-sw.html'
    );
  }
else
  {
    printf (
      _("Savannah aims to be a central point for development, maintenance "
        . "and\ndistribution of <a href=\"%s\">official GNU software</a>.  "
        . "In addition,\nfor projects that support free software but are not "
        . "part of GNU,\nwe provide <a href=\"%s\">savannah.nongnu.org</a>."),
      '//www.gnu.org/software/', '//savannah.nongnu.org/');
  }

print "</p>\n\n<p>"
  . _('If you would like to use Savannah to host your project, then go to
the <b>Register New Project</b> menu entry.')
  . "</p>\n\n<p>";

if ($is_nongnu_org)
  {
    print '<p>';
    printf (
      _("It's not necessary for using Savannah, but if you would like"
        . " to make\nyour project part of the GNU system, please see the <a\n"
        . "href=\"%s\">GNU Software Evaluation</a> web page.\n"
        . "New packages are welcome in GNU."),
      '//www.gnu.org/help/evaluation.html'
    );
    print "</p>\n";
  }

print
  _("We strongly recommend all Savannah users subscribe to this\n"
    . 'mailing list:') . "</p>\n<ul>\n<li>";
$list_base_url = '//lists.gnu.org/mailman/listinfo/';
$list_name = 'savannah-announce';
# TRANSLATORS: the argument is a link to mailing list.
printf (
  _("%s:\n    low-volume "
    . 'notifications of important issues and changes at Savannah.'),
  "<a href=\"$list_base_url$list_name\">$list_name</a>"
);
print "</li>\n</ul>\n\n<p>"
 . _("And this mailing list is a place for Savannah users "
     . "to communicate and\nask questions:")
 . "</p>\n<ul>\n<li>";

$list_name = 'savannah-users';
# TRANSLATORS: the argument is a link to mailing list.
printf(
  _('%s:
    help with using Savannah in general (not with a specific project).'),
  "<a href=\"$list_base_url$list_name\">$list_name</a>"
);
print "</li>\n</ul>\n</p>\n\n<p>" . _('Happy hacking!') . "</p>\n";
?>
