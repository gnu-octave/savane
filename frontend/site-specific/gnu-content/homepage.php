<?php
# Home page.

# Copyright (C) 2002 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2003 Jaime E. Villate
# Copyright (C) 2004, 2005, 2006, 2007, 2008, 2009, 2010 Sylvain Beucler
# Copyright (C) 2006 Michael J. Flickinger
# Copyright (C) 2008, 2011 Karl Berry
# Copyright (C) 2013, 2017, 2020 Ineiev <ineiev@gnu.org>
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

printf ('<p>'._('Welcome to <strong>Savannah</strong>, the software forge for people
committed to <a href="%s">free software</a>:').'</p>

', '//www.gnu.org/philosophy/free-sw.html');

print '<p><ul>
<li>'._('We host free projects that run on free operating systems and without
    any proprietary software dependencies.')
 . '<br /> <a href="/register/requirements.php">'
 . _("Hosting requirements") . '</a></li>
<li>'.sprintf(
_('Our service runs with 100%% free software, including <a href="%s">itself</a>.'),
 '/projects/administration/').'</li>
<li><a href="/maintenance/WhyChooseSavannah">'
._('Why choose Savannah?').'</a></li>
</ul></p>

<p>'.sprintf(
_('Savannah aims to be a central point for development, maintenance and
distribution of <a href="%s">official GNU software</a>.  In addition,
for projects that support free software but are not part of GNU,
we provide <a href="%s">savannah.nongnu.org</a>.'),
'//www.gnu.org/software/', '//savannah.nongnu.org/').'</p>

<p>'._('If you would like to use Savannah to host your project, then go to
the <b>Register New Project</b> menu entry.').'</p>

<p>'._('We strongly recommend all Savannah users subscribe to this
mailing list:').'</p>
<ul>
<li>'
.sprintf(
# TRANSLATORS: the argument is a link to mailing list.
_('%s:
    low-volume notifications of important issues and changes at Savannah.'),
'<a href="//lists.gnu.org/mailman/listinfo/savannah-announce">'
.'savannah-announce</a>').'</li>
</ul>

<p>'._('And this mailing list is a place for Savannah users to communicate and
ask questions:').'</p>
<ul>
<li>'
# TRANSLATORS: the argument is a link to mailing list.
.sprintf(_('%s:
    help with using Savannah in general (not with a specific project).'),
'<a href="//lists.gnu.org/mailman/listinfo/savannah-users">savannah-users</a>')
.'</li>
</ul>
</p>

<p>'._('Happy hacking!').'</p>';
?>
