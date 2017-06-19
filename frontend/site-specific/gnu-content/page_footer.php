<?php

# Copyright (C) 2002, 2003 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2005, 2006, 2007, 2008, 2009 Sylvain Beucler
# Copyright (C) 2010, 2011, 2012, 2013, 2014, 2015, 2016, 2017 Karl Berry
# Copyright (C) 2013, 2017 Ineiev <ineiev@gnu.org>
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

print '
<span style="float:right">
<a href="';

echo 'http://git.savannah.gnu.org/cgit/administration/savane.git/plain/'
  . preg_replace(':/usr/src/savane/:', '', realpath($_SERVER['SCRIPT_FILENAME']));

print'">'._('Source Code').'</a>
</span><br />

'._('Copyright &copy; 2017 &nbsp;Free Software Foundation, Inc.').'

<br />

'._('Verbatim copying and distribution of this entire article is
permitted in any medium, provided this notice is preserved.').'

<br />

';
printf (_('The <a href="%s">Levitating,
Meditating, Flute-playing Gnu</a> logo is a GNU GPL\'ed image provided
by the Nevrax Design Team.'), '//www.gnu.org/graphics/meditate.html');

?>
