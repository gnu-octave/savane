#!/usr/bin/perl
#
# Copyright (C) 2005  Mathieu Roy
#
# This file is part of Savane.
# 
# Savane is free software; you can redistribute it and/or modify it
# under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Savane; if not, write to the Free Software Foundation,
# Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
# Add a tailing '###' in table user, field authorized_keys

# These recipes should be created at system group creation
# On running installation, they must be add

use strict;
our $dbd;
use Savane;


my $sys_group_id = GetGroupSettings(GetConf("sys_unix_group_name"), 
				    "group_id");

my @recipes = ("INSERT INTO cookbook (bug_id, group_id, status_id , severity , privacy , category_id , submitted_by , assigned_to , date , summary , details , resolution_id) VALUES (101, '$sys_group_id', '3', '5', '1', '100', '100', '100', '1133253163', 'Why log in?', ".$dbd->quote("The log-in mechanism used in these webpages is just a simple way of keeping track of users who work in projects hosted in this site. When a user logs in, she/he is conducted to a personal page that lists the projects she/he is collaborating with and any pending tasks that she/he might have.

If you are involved in any project, if you do not intend to post items on the site, you don't need to log in since it will make no difference. 
If you want to register a project of your own to be hosted in this site, you must first log in, because every project must have at least one administrator and we need to know your user name to make you the administrator of the project.

In order to log in, you must be registered (using \"New User\" in the menu) and give the user name and password selected during your registration.

If you lost your password, read recipe #102.").", '1')",
	       "INSERT INTO cookbook_context2recipe (recipe_id, group_id, audience_anonymous , audience_loggedin , audience_members , audience_technicians , audience_managers , context_project , context_homepage , context_cookbook , context_download , context_support , context_bugs , context_task , context_patch , context_news , context_mail , context_cvs , context_arch , context_svn , context_my , context_stats , context_siteadmin , context_people , subcontext_browsing , subcontext_postitem , subcontext_edititem , subcontext_search , subcontext_configure ) VALUES ('101', '$sys_group_id', '1', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '1', '0', '0')",
	       "INSERT INTO cookbook (bug_id, group_id, status_id , severity , privacy , category_id , submitted_by , assigned_to , date , summary , details , resolution_id) VALUES (102, '$sys_group_id', '3', '5', '1', '100', '100', '100', '1133253163', 'Getting back lost password', ".$dbd->quote("If you lose your password simply visit the login page and click \"Lost Your Password?\". 

A confirmation mail will be sent to the address we have on file for you. Then, load the URL in the email to reset your password.").", '1')",
	       "INSERT INTO cookbook_context2recipe (recipe_id, group_id, audience_anonymous , audience_loggedin , audience_members , audience_technicians , audience_managers , context_project , context_homepage , context_cookbook , context_download , context_support , context_bugs , context_task , context_patch , context_news , context_mail , context_cvs , context_arch , context_svn , context_my , context_stats , context_siteadmin , context_people , subcontext_browsing , subcontext_postitem , subcontext_edititem , subcontext_search , subcontext_configure ) VALUES ('102', '$sys_group_id', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '1', '0', '0')",
	       "INSERT INTO cookbook (bug_id, group_id, status_id , severity , privacy , category_id , submitted_by , assigned_to , date , summary , details , resolution_id) VALUES (103, '$sys_group_id', '3', '5', '1', '100', '100', '100', '1133253163', 'Delays on update', ".$dbd->quote("Several function related to mail aliases, external services access (SVN, CVS...), user additions, group member changes, CVS, etc, are performed via a cronjob on a regular basis. 

Changes made on the web site may appear to be live but will not take effect until the next cron update.").", '1')",
	       "INSERT INTO cookbook_context2recipe (recipe_id, group_id, audience_anonymous , audience_loggedin , audience_members , audience_technicians , audience_managers , context_project , context_homepage , context_cookbook , context_download , context_support , context_bugs , context_task , context_patch , context_news , context_mail , context_cvs , context_arch , context_svn , context_my , context_stats , context_siteadmin , context_people , subcontext_browsing , subcontext_postitem , subcontext_edititem , subcontext_search , subcontext_configure ) VALUES ('103', '$sys_group_id', '0', '1', '1', '0', '0', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '0', '0', '0', '0', '0', '0', '1')");
	       

foreach my $recipe (@recipes) {
#    print $recipe."\n\n";
    my $sthinsert=$dbd->prepare($recipe);
    $sthinsert->execute;
}
