<?php
# Savannah - Menus

# Copyright (C) 2002, 2003 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2004, 2005, 2006, 2009, 2011 Sylvain Beucler
# Copyright (C) 2005 Sebastian Wieseler
# Copyright (C) 2008, 2011, 2012, 2013 Karl Berry
# Copyright (C) 2010, 2011, 2012 Michael J. Flickinger
# Copyright (C) 2017 Bob Proulx
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

global $HTML;
$HTML->menu_entry('//savannah.gnu.org/maintenance/FaQ', _('User Docs: FAQ'));
$HTML->menuhtml_top(_('GNU Project'));
$HTML->menu_entry('//www.gnu.org/help/help.html',_('Help GNU'));
$HTML->menu_entry("//www.gnu.org/software/software.html",_('All GNU Packages'));
$HTML->menu_entry('//www.gnu.org/software/devel.html',_('Dev Resources'));
$HTML->menu_entry('//www.gnu.org/licenses/license-list.html',_('License List'));
$HTML->menu_entry('//www.gnu.org/prep/ftp.html',_('GNU Mirrors'));
$HTML->menuhtml_bottom();

print '
<li>
 <center>
  <br />
';
  print '<a href="//www.fsf.org/associate/support_freedom/join_fsf?referrer=2442">'
.'<img style="width: 100%; margin-bottom: 0.2em"
  src="//static.fsf.org/fsforg/img/thin-image.png" alt="'
        ._('Support freedom').'" title="'
        ._('Help protect your freedom, join the Free Software Foundation')
        .'" /></a>';
print '
 </center>
</li>

';

$HTML->menuhtml_top(_('Free Software Foundation'));
$HTML->menu_entry('//www.fsf.org/events/', _('Coming Events'));
$HTML->menu_entry("//www.fsf.org/directory/",
                  _('Free Software Directory'));
$HTML->menu_entry('//savannah.gnu.org/maintenance/SavannahCryptographicRestrictions',
                  _('Cryptographic software legal notice'));
$HTML->menu_entry('//www.fsf.org/about/dmca-notice',
                  _('Copyright infringement notification'));
$HTML->menuhtml_bottom();

$HTML->menuhtml_top(_('Related Forges'));
if (isset ($_SERVER['HTTP_HOST'])
    && $_SERVER['HTTP_HOST'] == 'savannah.gnu.org')
   $HTML->menu_entry('//savannah.nongnu.org/',
                     _('Savannah Non-GNU'));
else
   $HTML->menu_entry('//savannah.gnu.org/',
                     _('GNU Savannah'));
$HTML->menu_entry('//puszcza.gnu.org.ua/',
                  _('Puszcza'));
$HTML->menuhtml_bottom();

// You can create other menus here, following the model above. They will be
// shown in every page. You can also delete the above menu and leave
// this page empty if you want to.
?>
