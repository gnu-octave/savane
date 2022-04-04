<?php
# Define onclick action for showing feedback.
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

require_once('../include/sane.php');
header('Content-Type: text/javascript');
extract(sane_import('request', ['preg' => [['suffix', '/^\w*$/']]]));

if ($suffix === null)
  $suffix = "";

print
"document.getElementById('feedbackback').onclick =
function ()
{
  document.getElementById('feedback$suffix').style.visibility='visible';
  document.getElementById('feedbackback$suffix').style.visibility='hidden';
}\n";
?>
