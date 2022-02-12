<?php
# <one line to give a brief idea of what this does.>
#
# Copyright 2005 (c) Mathieu Roy <yeupou--gnu.org>
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
require_once('../include/trackers/general.php');

extract(sane_import('get',
  [
    'strings' => [['func', ['default' => 'default', 'search', 'detailitem']]],
    'digits' => 'item_id'
  ]
));

# If no group id is set, there is an error.
# The group is was supposed to be set by the system, so it is a system error
# more than a broken url.
if (!$group_id)
  exit_no_group();

trackers_init($group_id);
$project=project_get_object($group_id);

# Set $printer that may be used in later pages instead of PRINTER.
if (defined('PRINTER'))
  $printer = 1;

switch ($func)
{
  case 'search':
    {
      # Form to do a search on the item database.
      require('../include/trackers_run/search.php');
      break;
    }

  case 'detailitem':
    {
      # Show item in a sober way.

      # The call to register_globals_off here push us to put back var
      # initialization of things coming from user input.
      register_globals_off();
      require('../include/trackers_run/detail-sober.php');
      break;
    }

  default:
    {
      # Show browse but ask it to be sober.
      $sober = 1;
      require('../include/trackers_run/browse.php');
      break;
    }
}
?>
