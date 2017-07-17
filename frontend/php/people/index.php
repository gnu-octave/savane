<?php
# Page showing selected job posts.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2006-2008 Beucler <beuc--beuc.net>
# Copyright (C) 2013, 2017 Ineiev <ineiev--gnu.org>
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

extract(sane_import('get', array('category_id', 'type_id',
                                 'types', 'categories', 'show_any')));
# Provide compatibility with old requests.
if($category_id && !$categories)
  {
    $categories = array("0" => $category_id);
  }
if($type_id && !$types)
  {
    $types = array("0" => $type_id);
  }
if($show_any)
  {
    $categories = array();
    $types = array();
  }

if ($group_id)
  {
    site_project_header(array('title'=>_('Project Help Wanted'),
                        'group'=>$group_id,'context'=>'people'));
    utils_get_content("people/index_group");
    print people_show_project_jobs($group_id);
  }
else if ($categories || $types || $show_any)
  {
    # Do check first
    $error = 0;
    $cat_names = '';
    $cat_name = '';
    for ($i = 0; $i < count($categories); ++$i)
      {
        $cat_name = people_get_category_name($categories[$i]);
        if ($cat_name == 'Invalid ID')
          {
            $error = 1;
            break;
          }
        if($cat_names != '')
          $cat_names .= ', ';
        $cat_names .= $cat_name;
      }
    $group_types = '';
    for ($i = 0; $i < count($types); ++$i)
      {
        $cat_name = people_get_type_name($types[$i]);
        if ($cat_name == 'Invalid ID')
          {
            $error = 1;
            break;
          }
        if($group_types != '')
          $group_types .= ', ';
        $group_types .= $cat_name;
      }
    if ($error)
      {
        print site_header(array('title'=>_('Project Help Wanted'),
                                'context'=>'people'));
        fb(_("That category does not exist"),1);
      }
    else
      {
        if($cat_names == '')
          $cat_names = 'people';
        if($group_types == '')
          $group_types = 'Groups';
        # TRANSLATORS: The first %s is enumeration of group types,
        # the second %s is enumeration of job categories.
        $title = sprintf(_('%1$s looking for %2$s'), $group_types, $cat_names);
        print site_header(array('title'=>$title));
        print people_show_jobs($categories, $types, $show_any);
      }
  }
else
  {
    print site_header(array('title'=>_('Projects Needing Help'),
                            'context'=>'people'));
    utils_get_content("people/index");
    print people_show_table();
  }
site_project_footer(array());
?>
