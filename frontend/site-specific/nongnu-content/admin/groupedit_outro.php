<?php

# Savannah - Project registration administration sys name infos
#
# Hints for package evaluators.
#
# Copyright (C) 2002 Loic Dachary
# Copyright (C) 2004 Sylvain Beucler
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
print '<p><a href="gnuevalconfirm.php?group_id='.$GLOBALS['group_id'].'">'
.('Ask maintainers@gnu.org if this is a GNU package indeed').'</a></p>

<p><a href="gnueval.php?group_id='.$GLOBALS['group_id'].'">'
.('Ask gnueval-input@gnu.org to evaluate for inclusion in the GNU project').'</a></p>

<p><a href="send_registration_notification.php?group_id='.$GLOBALS['group_id'].'">'
.('Resend the admin mail notification').'</a></p>';

?>
