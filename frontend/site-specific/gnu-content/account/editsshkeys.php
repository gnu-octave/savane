<?php
# Note about SSH keys.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2004 Rudy Gevaert
# Copyright (C) 2005, 2006 Sylvain Beucler
# Copyright (C) 2010 Karl Berry
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


print '<p>'
. _("Public keys should look like this (their typical
length is hundreds of characters):")
."<br />
<code>
ssh-rsa AAAAB3NzaC1kc3M<i>...</i>Fjfir4BJyPC/uJc3yxuD0Q==
user@localhost.localdomain</code>"
."</p>\n<p>".sprintf(
_('When unsure, check <a href="%s">Savannah documentation on SSH access</a>.'),
                    "https://savannah.gnu.org/maintenance/SshAccess/")
."</p>\n";
?>
