<?php

# Savannah - Project registration administration group type info.
#
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

# No need for i18n: this is for admins only.
print ('To check if a project is a GNU project, read the
fencepost.gnu.org:/gd/gnuorg/maintainers list. If the value of this flag is Y
before moderation it mean that the project is either already a GNU project (in
which case the user submitting the project probably added something in the
comment to specify this) or want to apply for inclusion in the GNU project. If
the value of this flag is N before moderation it means that this package does
not want to apply for inclusion in the GNU project. If this flag is set to N by
the moderator and the HTML repository field below is not empty, it must be
located under the <b>/non-gnu/</b> directory.');

?>
