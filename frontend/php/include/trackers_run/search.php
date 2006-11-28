<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2003-2006 (c) Frederik Orellana <frederik.orellana--cern.ch>
#                          Mathieu Roy <yeupou--gnu.org>
#
# The Savane project is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# The Savane project is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


trackers_header(array ('title'=>_("Search")));
print '<p>'._("With the following form, you can perform a search in the items summaries and details. If you need to perform more complex search, use the query forms in Browse items page").'</p>';
# this must not be in a boxoption, it is no option, it is the only purpose of
# the page
print '<p>'.search_box('', ARTIFACT, 45).'</p>';
trackers_footer(array());

?>
