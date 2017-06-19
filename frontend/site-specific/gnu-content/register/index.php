<?php
# Savannah - Project registration STEP 0
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002, 2003 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2003 Jaime E. Villate
# Copyright (C) 2005, 2006 Sylvain Beucler
# Copyright (C) 2007 Steven Robson
# Copyright (C) 2017 Ineiev <ineiev@gnu.org>
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

print '<p>'
._('Savannah is a hosting facility for the free software movement.  Our
mission is to spread the freedom to copy and modify software.').'</p>

';

printf ('<p>'._('<strong>Important</strong>: check <a
href="%s">
Project - How to get it approved quickly</a>.').'</p>

','//savannah.gnu.org/faq/?group_id=5802&question=Project_-_How_to_get_it_approved_quickly.txt');

print '<p>'._('It contains a few advices to get your package compliant with
our hosting policies. Following them will considerably speed up the
registration process.').'</p>

<p>'
._('You are welcome to host your project in Savannah if it falls within one
of these groups:').'</p>

<dl>
<dt>'._('<strong>Software Project</strong>').'</dt>
<dd>'._('A free software package that can run on a completely free operating
system, without depending on any nonfree software. You can only provide
versions for nonfree operating systems if you also provide free
operating systems versions with the same or more functionalities. Large
software distributions are not allowed; they should be split into separate
projects.').'</dd>

<dt>'._('<strong>Free Documentation Projects</strong>').'</dt>
<dd>'._('Documentation for free software programs, released under a free
documentation license.').'</dd>

<dt>'._('<strong>Free Educational Textbook Projects</strong>').'</dt>
<dd>'._('Projects aimed to create educational textbooks, released under a free
documentation license.').'</dd>

<dt>'._('<strong>FSF/GNU projects</strong>').'</dt>
<dd>'._('Internal projects of the FSF and projects that have been approved
by the GNU Project management.').'</dd>

<dt>'._('<strong>GNU/Linux User Groups (GUG)</strong>').'</dt>
';
printf ('<dd>'._('Organizational project for your user group. GUG need to be listed
at <a href="%s">GNU Users
Groups page<a> - contact %s for details.').'
</dd>
</dl>

', '//www.gnu.org/gnu/gnu-user-groups.html',
'<a href="mailto:user-groups@gnu.org">user-groups@gnu.org</a>');

print '<p>'
._('In the following 5 registration steps you will be asked to describe your
project and choose a free license for it. Your project does not have to be
part of the GNU project or be released under the GPL to be hosted here, but if
you want to take the opportunity to make your project part of GNU, you can
request that later on in the registration process.').'</p>

';
printf ('<p>'
._('To keep compatibility among Savannah projects, we only accept free software
licenses that are compatible with the GPL. The list of GPL-compatible licenses
covers several of the most commonly used licenses; if you are not familiar
with that list, please take some time to read <a
href="%s">GPL-compatible, free software licenses</a>.').'</p>

','//www.gnu.org/philosophy/license-list.html#GPLCompatibleLicenses');

print '<p>'
._('Keep in mind that your project is not approved automatically
after you follow the registration steps, but it will have to be evaluated
by one of the Savannah administrators. That process may take from one day
to a week, depending on the current number of pending projects.').'</p>

<p>'
._('To ease handling the large number of projects we receive, whenever we ask you
to provide more information that we think is missing from your registration
or when we ask you to make some changes, your registration will be removed
and you will have to register your project again after the changes are done.
This does not imply that we are reluctant to host your project; it is fairly
common for projects to undergo more than one registration before they
are finally approved, so please be prepared for that.').'</p>
';

?>
