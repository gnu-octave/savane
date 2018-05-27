<?php

# Savannah - Misc add here details about special features you bring
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2004 Rudy Gevaert
# Copyright (C) 2004, 2005, 2007 Sylvain Beucler
# Copyright (C) 2017, 2018 Ineiev <ineiev@gnu.org>
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

print '

<h2>'._('Miscellaneous...').'</h2>

<h3>'._('Backup').'</h3>

<p>'._('You can get read-only access to your raw CVS files (the RCS
<code>,v</code> ones) using rsync:').'</p>

<pre>';

print 'rsync cvs.' . $project->getTypeBaseHost() . '::sources/' . $GLOBALS['group'] . "/\n";
print 'rsync cvs.' . $project->getTypeBaseHost() . '::web/' . $GLOBALS['group'] . "/\n";

print'
</pre>

<h3>'
# TRANSLATORS: this is a header (<h4>)
._('ftp.gnu.org area').'</h3>
';

printf ('<p>'
# TRANSLATORS: the argument is a mailto: link.
._('Each GNU project has a download area at ftp.gnu.org. This area is not
managed via Savannah.  Write to %s to get access.').'</p>
', '<a href="mailto:account@gnu.org">account@gnu.org</a>');

?>
