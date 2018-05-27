<?php

# Instructions about mailing lists.
#
# Copyright (C) 2004 Sylvain Beucler
# Copyright (C) 2008 Karl Berry
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

print '<h2>'._('New mailing lists at Savannah').'</h2>

';
printf ('<p>'
._('New mailing lists created through Savannah have the following
features enabled by default, in addition to the usual
<a href="%s">GNU Mailman</a> defaults.').'</p>

', '//www.gnu.org/software/mailman/');

print '<ul>
<li>'._('No footer is added to messages (<tt>footer</tt>, under
&lsquo;Non-digest options&rsquo;).').'</li>

<li>'._("When a new member joins the list, their initial posting is held for
the list owner's review (<tt>default_member_moderation</tt>, under
&lsquo;Privacy &gt; Sender filters&rsquo;).  This is necessary because
smart spam systems can now automatically subscribe to mailman lists and
then start sending their junk mail.  However, provided the subscription
is legitimate (as it usually will be), we strongly recommend
<i>accept</i>ing their address for future postings.").'</li>

';
printf ('<li>'
._('Automated (though asynchronous) spam deletion, through the <a
href="%s">listhelper system</a>.  More details about this:').'</li>

', "//www.nongnu.org/listhelper/");

print '<ul>
<li>'._('The first time a person posts to a list under a particular email
address, the message is held for review, potentially by the mailman list
owners, the listhelper automated system, and the listhelper
human volunteers.  This is when spam is detected and deleted.').'</li>

<li>'._("Therefore, if you are concerned about your list's messages being
seen by the small group of listhelper volunteers, you should disable
listhelper (remove listhelper@nongnu.org from the <tt>moderator</tt>
field on the &lsquo;General Options&rsquo; page), and consequently deal
with any incoming spam yourself.").'</li>

<li>'._("By default, Mailman sends a &ldquo;request for approval&rdquo;
notification on every message, and this is critical for listhelper's
operation.  You will probably want to filter these from your inbox, with
a pattern such as this:").'<br />
<tt>^Subject: confirm [a-f0-9]{40}</tt>
<br />
'._('(Despite appearances, that needs to match in the body of the message,
not the headers.)  Alternatively, if you choose to turn off listhelper,
you may also want to turn off this option (<tt>admin_immed_notify</tt>
on the &lsquo;General Options&rsquo; page).').'</li>

<li>';

printf (_('For more information, see the <a href="%s">listhelper home page</a>.')
.'</li>
', "//www.nongnu.org/listhelper/");

print '</ul>
</ul>

<p>'._('Of course, as the list owner, you can make changes to the
configuration at any time, but if in any doubt, please ask.  The
defaults are set to minimize spam, list administrator overhead, and the
chance of our mail server being blacklisted.').'</p>';

?>
