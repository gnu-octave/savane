<?php
# Search items.
# 
# Copyright (C) 2003-2006 Frederik Orellana <frederik.orellana--cern.ch>
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017 Ineiev
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

trackers_header(array ('title'=>_("Search")));
print '<p>'
._("With the following form, you can perform a search in the items summaries
and details. If you need to perform more complex search, use the query forms in
Browse items page")
.'</p>';
# this must not be in a boxoption, it is no option, it is the only purpose of
# the page
print '<p>'.search_box('', ARTIFACT, 45).'</p>';
trackers_footer(array());
?>
