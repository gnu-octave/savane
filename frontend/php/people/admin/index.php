<?php
# Edit job categories and skills.
#
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2006, 2007, 2008 Sylvain Beucler
# Copyright 2017, 2018 Ineiev
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

require_once('../../include/init.php');

extract(sane_import('request', array('people_cat', 'people_skills')));
extract(sane_import('post', array('post_changes', 'cat_name', 'skill_name')));

# This page is for site admins only.
if (!user_ismember(1,'A'))
  exit_permission_denied();

if ($post_changes)
  {
    # Update the database.
    if ($people_cat)
      {
        $result = db_execute("INSERT INTO people_job_category (name) VALUES (?)",
                             array($cat_name));
        if (!$result)
          {
            print db_error();
            fb(_("Error inserting value"));
          }
        fb(_("Category Inserted"));
      }
    else if ($people_skills)
      {
        $result=db_execute("INSERT INTO people_skill (name) VALUES (?)",
                           array($skill_name));
        if (!$result)
          {
            print db_error();
            fb(_("Error inserting value"));
          }
        fb(_("Skill Inserted"));
      }
  }

# Show UI forms.
if ($people_cat)
  {
    # Show categories and blank row.
    print site_header(array('title'=>_('Change Categories')));
    print '<h1>'._("Add Job Categories").'</h1>
';
    # List of possible categories for this group.
    $result = db_query("SELECT category_id,name FROM people_job_category");
    if ($result && db_numrows($result) > 0)
      utils_show_result_set($result,_("Existing Categories"),'people_cat');
    else
      {
        print '<p>'._("No job categories")."</p>\n";
        print db_error();
      }
    print '<h2>'._("Add a new job category:").'</h2>
<form action="'.htmlentities ($_SERVER['PHP_SELF']).'" method="post">
<p><input type="hidden" name="people_cat" value="y" />
<input type="hidden" name="post_changes" value="y" /></p>
<h3>'._("New Category Name:").'</h3>
<input type="text" name="cat_name" value="" size="15" maxlength="30" /><br />
<p>
<strong><span class="warn">'
._("Once you add a category, it cannot be deleted")
.'</span></strong></p>
<p>
<input type="submit" name="submit" value="'._("Add").'" /></p>
</form>
';

    site_project_footer(array());
  } # $people_cat
else if ($people_skills)
  {
    # Show people_groups and blank row.
    print site_header(array('title'=>_('Change People Skills')));
    print '<h1>'._("Add Job Skills").'</h1>
';
    # List of possible people_groups for this group.
    $result = db_query("SELECT skill_id,name FROM people_skill");
    print "<p>";
    if ($result && db_numrows($result) > 0)
      utils_show_result_set($result,_("Existing Skills"),"people_skills");
    else
      {
        print db_error();
        print "<p>"._("No Skills Found").'</p>
';
      }
    print '<h2>'._("Add a new skill:").'</h2>
';
    print '<p>
<form action="'.htmlentities ($_SERVER['PHP_SELF']).'" method="post">
<input type="hidden" name="people_skills" value="y" />
<input type="hidden" name="post_changes" value="y" /></p>
<h3>'._("New Skill Name:").'</h3>
<input type="text" name="skill_name" value="" size="15" maxlength="30" /><br />
<p><strong><span class="warn">'._("Once you add a skill, it cannot be deleted")
.'</span></strong></p>
<p><input type="submit" name="submit" value="'._("Add").'" /></p>
</form>';
    site_project_footer(array());
  }
else # ! $people_skills
  {
    # Show main page.
    print site_header(array('title'=>_('People Administration')));
    print '<h1>'._("Help Wanted Administration").'</h1>';
    print '<p><a href="'.htmlentities ($_SERVER['PHP_SELF'])
          .'?people_cat=1">'._("Add Job Categories").'</a><br />';
    print "\n<a href=\"";
    print htmlentities ($_SERVER['PHP_SELF'])."?people_skills=1\">"
          ._("Add Job Skills").'</a><br />';
    site_project_footer(array());
  }
?>
