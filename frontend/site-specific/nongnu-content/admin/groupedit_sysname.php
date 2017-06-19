<?php

# Copyright (C) 2002 Loic Dachary
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

printf ('<p>'
._('The /webcvs subdirectory on which the project will have write access.
Empty value means that no directory of /webcvs will be given to this project.
Make sure you fully understand the <a href="%s">
rationale</a> associated with this value.
Make sure the directory you enter here does <b>not overlap</b> with a directory
already assigned to a project.
Every non-GNU project must be located in the <b>/non-gnu/</b> subdirectory.')
.'</p>

', '/savannah.html#Web%20CVS%20repositories');
print '<p>'
._('Only change the project name <b>if it already exists</b> under another
name in the GNU project. For instance if <code>gnuedma</code> was submitted,
it should be changed to <code>edma</code> since the pages exists in
<code>www.gnu.org/software/edma</code>.').'</p>

<p>'
._('<i>auto</i> means that everything will be set according to the project type.')
.'</p>

<p>'
._('Note: if you use the text field to set the Subdirectory, you need to
give the complete url.  In this particular case, the System Name will
not be added as subsubdirectory.  This field should be used only for
web project. In fact, this field generally must be used for web
project.').'</p>
';
?>
