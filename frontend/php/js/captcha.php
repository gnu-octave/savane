<?php
# Define onclick action for registration captcha.
#
# Copyright (C) 2018, 2022 Ineiev <ineiev--gnu.org>
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

require_once('../include/init.php');
header('Content-Type: text/javascript');

print
"document.getElementById('captcha_js_link').onclick =
function ()
{
  document.getElementById('captcha').src =
    '${GLOBALS['sys_home']}captcha.php?' + Math.random();
}\n";
?>
