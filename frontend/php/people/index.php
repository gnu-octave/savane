<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                          Sylvain Beucler <beuc--beuc.net>
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


extract(sane_import('get', array('category_id', 'type_id')));

if ($group_id)
{

  site_project_header(array('title'=>_('Project Help Wanted'),'group'=>$group_id,'context'=>'people'));


  # we get site-specific content
  utils_get_content("people/index_group");

  print people_show_project_jobs($group_id);

}
else if ($category_id)
{

  # Do check first
  if (!ctype_digit($category_id))
    {
      $cat_name = 'Invalid ID';
    }
  else
    {
      $cat_name = people_get_category_name($category_id);
    }

  if ($cat_name == 'Invalid ID') # not a digit or not a good category
    {
      print site_header(array('title' =>_('Project Help Wanted'), 'context'=>_('people')));
      
      fb(_("That category does not exist"),1);
    } else {
      print site_header(array('title'=>sprintf(_('Projects looking for %s'), $cat_name), 'context'=>_('people')));
      
      # we get site-specific content
      utils_get_content("people/index_cat");
      
      print people_show_category_jobs($category_id);
    }
}
else if ($type_id)
{
  print site_header(array('title'=>_('Project Help Wanted'), 'context'=>_('people')));

  # Do check first
  $cat_name = people_get_category_name($type_id);

  # Do check first
  if (!ctype_digit($type_id))
    {
      fb(_("That group type does not exist"),1);
    }
  else
    {
      # Add <br> to add overlaps
      print '<br />'.
	people_show_grouptype_jobs($type_id);
    }
}
else
{
  print site_header(array('title'=>_('Projects Needing Help'), 'context'=>_('people')));

  # we get site-specific content
  utils_get_content("people/index");

  print people_show_category_table();

  print people_show_grouptype_table();
}

site_project_footer(array());
