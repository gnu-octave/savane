<?php

# Direction on how to check if the package is GNU software.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2010 Karl Berry
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

printf(_('To check if a package is official GNU software, see the file
fencepost.gnu.org:/gd/gnuorg/maintainers.  If it is not listed there,
and no official dubbing message has been sent, it must <b>not</b> be
approved as &ldquo;Official GNU Software&rdquo;.  The type should be
changed to non-GNU in this case.  If the submitter wants to offer the
project to GNU, please point them to %s.')."<br />
", '<a href="//www.gnu.org/help/evaluation.html">
https://www.gnu.org/help/evaluation.html</a>');
?>
