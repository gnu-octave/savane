<?php

# Savannah - Misc add here details about special features you bring
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2004 Rudy Gevaert
# Copyright (C) 2004, 2005, 2007 Sylvain Beucler
# Copyright (C) 2017, 2018, 2022 Ineiev <ineiev@gnu.org>
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

global $project;

print "\n\n<h2>" . _('Miscellaneous...') . "</h2>\n\n<h3>"
  . _('Backup') . "</h3>\n\n<p>"
  . _("You can get read-only access to your raw CVS files (the RCS\n"
      . '<code>,v</code> ones) using rsync:')
  . "</p>\n\n<pre>";

$host = $project->getTypeBaseHost();
$grp = $GLOBALS['group'];

print "rsync cvs.$host::sources/$grp/\n";
print "rsync cvs.$host::web/$grp/\n";

if (substr ($GLOBALS['sys_default_domain'], -8) == ".gnu.org")
  {
    # TRANSLATORS: this is a header (<h3>).
    print "\n</pre>\n\n<h3>" .  _('ftp.gnu.org area') . "</h3>\n<p>";
    # TRANSLATORS: the argument is a mailto: link.
    printf (
      _("Each GNU project has a download area at ftp.gnu.org. This area "
        . "is not\nmanaged via Savannah.  Write to %s to get access."),
     '<a href="mailto:account@gnu.org">account@gnu.org</a>'
    );
    print "</p>\n";
  }
?>
