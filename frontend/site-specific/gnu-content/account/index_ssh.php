<?php

# Note about password.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002 Rudy Gevaert
# Copyright (C) 2005 Sylvain Beucler
# Copyright (C) 2015 Bob Proulx
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

printf(_('Your %s password is used only for
logging into the Savannah web interface.  It is not used for bzr, cvs,
git, hg, rsync, scp, sftp or other services.  Only SSH keys are
used for those purposes.'), $GLOBALS['sys_name']);
?>
