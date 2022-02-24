<?php
# Page showing selected job posts.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2006-2008 Beucler <beuc--beuc.net>
# Copyright (C) 2013, 2017, 2018, 2022 Ineiev <ineiev--gnu.org>
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
register_globals_off();
require_once('../include/people/general.php');

extract (sane_import ('get',
  [
    'true' => 'submit',
    'array' => [['categories', 'types', [null, 'digits']]],
  ]
));

if (!isset ($types))
  $types = [];
if (!isset ($categories))
  $categories = [];

$finish_page = function ()
{
  site_project_footer ([]);
  exit (0);
};

if ($group_id)
  {
    site_project_header (
      [
        'title' => _('Project Help Wanted'),
        'group' => $group_id, 'context' => 'people',
      ]
    );
    utils_get_content ("people/index_group");
    print people_show_project_jobs ($group_id);
    $finish_page ();
  }

site_header (['title' => _('Groups Needing Help'), 'context' => 'people']);

if (!($categories || $types || $submit))
  {
    utils_get_content ("people/index");
    print people_show_table ();
    $finish_page ();
  }

foreach ($categories as $cat)
  if (people_get_category_name ($cat) == 'Invalid ID')
    {
      fb (sprintf (_("Job category #%s does not exist"), $cat), 1);
      $finish_page ();
    }
foreach ($types as $ty)
  if (people_get_type_name ($ty) == 'Invalid ID')
    {
      fb (sprintf (_("Group type #%s does not exist"), $ty), 1);
      $finish_page ();
    }
print people_show_jobs ($categories, $types);
$finish_page ();
?>
