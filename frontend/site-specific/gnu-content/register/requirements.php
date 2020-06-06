<?php
# Savannah hosting requirements.
#
# Copyright (C) 2002, 2003 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2003 Jaime E. Villate
# Copyright (C) 2005, 2006  Sylvain Beucler
# Copyright (C) 2011 Michael J. Flickinger
# Copyright (C) 2011, 2014, 2015, 2016 Karl Berry
# Copyright (C) 2017, 2019, 2020 Ineiev
# Copyright (C) 2005, 2020 Richard Stallman
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
print '<p>'._("Please read these usage terms carefully.  If you don't follow them,
we will not accept your project; if we don't have enough information
determine whether your project follows these terms, we will
have to ask you for more details.
Once your project is accepted, you are expected to continue following
these terms.") . '</p>';
if (substr ($GLOBALS['sys_default_domain'], -8) == ".gnu.org")
  print '<p>'
. sprintf(_('All packages registered in savannah.gnu.org are GNU packages,
  so they should follow the <a href="%s">GNU Coding Standards</a>.'),
  '//www.gnu.org/prep/standards/')
. "</p>\n<p>"
. _("Note that some parts of the GNU Coding standards are firm
  requirements, while some are just preferences/suggestions.")
. "</p>";

print "<p>"
. _('Our intent is to provide a permanent home for all versions of your project.
We do reserve the right, however, to discontinue hosting a project.').'</p>

<h3>'._('Use of project account').'</h3>

';
printf ('<p>'._('The space given to you on this server is given for the expressed
purpose of advancing free software that can run in free operating systems,
documenting such software, or creating free educational textbooks.
Using it to host or advertise nonfree software is considered harmful to
free software.  For more information, please read the
<a href="%s">Philosophy of the GNU Project</a>.').'</p>

', "//www.gnu.org/philosophy/philosophy.html");

print '<p>'
._('In order to preserve history and complete transparency, we will not
remove projects with substantive content.').'</p>

<h3>'._('No dependencies on nonfree software').'</h3>

<p>'
. sprintf(_('To be hosted on Savannah, your project must be free software, and it
must be kept independent of any nonfree software.  The package must
not refer the user to any nonfree software; in other words,
it must not say anything that in our judgment is likely to
lead or steer users towards any nonfree software.  In particular,
it must not automatically download or install any nonfree software.
For more info, see <a href=\"%s\">References to Non-Free Software and
Documentation</a> in the GNU Coding Standards.'),
"//www.gnu.org/prep/standards/html_node/References.html")
. '</p>

<p>'._('The program should deliver its full functionality and convenience on a
completely free platform based on a free operating system, such as
GNU/Linux, working entirely with other free software.  Otherwise, it
would be an inducement to install nonfree operating systems or other
nonfree software.').'</p>

<p>'._('It is ok for the program to run on nonfree platforms or nonfree
operating systems, and to work with well-known nonfree applications,
in addition to working with free software, provided it gives the free
software at least as good support as it gives to nonfree
counterparts.  In other words, at no time, in no way, should your
program put free software users at a disadvantage compared to those
willing to use proprietary software.').'</p>

<h3>'._('Regarding Android phones').'</h3>

<p>'._("Projects running on
Replicant may be hosted on Savannah.  Projects having dependencies on
nonfree software, such as proprietary software drivers or AndroidOS,
are not permissible.").'</p>

<h3>'._('No nonfree formats').'</h3>

<p>'._("Using a format such as Flash, RealPlayer and QuickTime, that can in
practice only be created or played using nonfree software is, in
effect, to recommend use of that nonfree player software.  When the
free software implementation is not as technically good as the proprietary one, using
such a format is also implicitly recommending the nonfree version.
Therefore, your package shouldn't contain or recommend materials in
these nonfree formats.").'</p>

<h3>'._('Advertisements').'</h3>

<p>'._('In general, you may not advertise anything commercial on a site hosted
here.  However, as exceptions, you can point people to commercial
support offerings for your free software project, and you can mention
fan items about your free software project that you sell directly to
the users.').'</p>

<h3>'._('Speaking about free software').'</h3>

<p>'._('Savannah is a free software hosting site: we host projects such as
yours for the sake of the ideals of freedom and community that the
free software movement stands for.  We offer Savannah hosting to free
software packages, as free software packages; therefore, please
describe your package clearly as a free software package.  Please
label it as &ldquo;free software&rdquo; rather than as &ldquo;open
source&rdquo;.').'</p>

<p>'._('Savannah is part of the GNU Project, developer of the free software
operating system GNU.  The GNU/Linux system (GNU with Linux as the
kernel) runs Savannah now.  While using our hosting services, please
acknowledge our work by referring to this system as &ldquo;GNU/Linux,&rdquo; not
just &ldquo;Linux,&rdquo; when you mention it in connection with this
package.').'</p>

';
printf ('<p>'._("If you'd like to help correct other confusions, you can find some
suggestions in <a href=\"%s\">Words to Use with Care</a>.").'</p>

', '//www.gnu.org/philosophy/words-to-avoid.html');

print '<h3>'._('Project naming').'</h3>

<p>'._('Project identifiers should be reasonably descriptive, rather than
terse abbreviations or confusingly general.  If we believe this to be an
issue, we will discuss it with you.').'</p>

<h3>'._('Free software licenses').'</h3>

<p>'._('You will be presented with a choice of free software licenses for
your project.  For hosting on Savannah, you must use one of these
licenses, which give the freedom to anyone to use, study, copy, and
distribute the source code and distribute modified versions of it, and
which are compatible with the GNU GPL version 3 and any later versions.
We recommend GPLv3-or-later; in
any case, we require the &ldquo;or any later version&rdquo; formulation
for the GNU GPL, GNU AGPL, and GNU LGPL.  You will remain the copyright
holder of whatever you create for your project.').'</p>

';
printf ('<p>'._('For manuals, we recommend GNU FDL version X-or-later, where X is the
latest released version of the <a href="%s">FDL</a>; other
licensing compatible with that is acceptable.').'</p>

', '//www.gnu.org/licenses/fdl.html');

printf ('<p>'._('Proper license notices should be applied to, at least, each source
(non-derived) file in your project.  For example, for the GPL, see the
page on <a href="%s">How to Use GNU Licenses</a>.
In the case of binary source files, such as images,
it is ok for the license to be stated in a companion <tt>README</tt> or
similar file.  It is desirable for derived files to also include license
notices.  A copy of the full text of all applicable licenses should also
be included in the project.').'</p>

', "//www.gnu.org/licenses/gpl-howto.html");

print '<p>'._('If you need to use another license that is not listed, let us know
and we, or most likely the FSF licensing group, will review these
requests on a case-by-case basis.  Software licenses must be
GPL-compatible.').'</p>
';
?>
