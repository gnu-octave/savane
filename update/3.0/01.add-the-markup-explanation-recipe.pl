#!/usr/bin/perl
#
# Copyright (C) 2006  Mathieu Roy
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

# Find out the current max recipe id, add 15 so we do not risk any overlap
my $recipe_id = GetDBSettings("cookbook",
			      "1",
			      "max(bug_id) as max");
$recipe_id += 15;

my @recipes = ("INSERT INTO cookbook (bug_id, group_id, status_id , severity , privacy , category_id , submitted_by , assigned_to , date , summary , details , resolution_id) VALUES ('$recipe_id', '$sys_group_id', '3', '5', '1', '100', '100', '100', '1133253163', 'Markup Reminder', ".$dbd->quote("Savane provides a markup langage that enables you to format text you post in items or items comments. HTML is not allowed for security reasons.


= Basic Text Tags =

Basic Text Tags are available almost everywhere.

*bold* markup is:
+verbatim+
*bold*
-verbatim- 

_italic_ markup is:
+verbatim+
_italic_
-verbatim- 

urls are automatically made links, additionnaly you can give them a title:
+verbatim+
[http://linkurl Title]
-verbatim- 

items references like _tracker #nnn_ will be made links to, like in
+verbatim+
this is recipe #$recipe_id.
-verbatim-


= Rich Text Tags =

Rich Text Tags are available in comments.

Unnumbered list markup is:
+verbatim+
* item 1\r
* item 2\r
** item 2 subitem 1\r
** item 2 subitem 2\r
-verbatim-

Numbered list markup is:
+verbatim+
0 item 1\r
0 item 2\r
-verbatim-

Horizontal ruler markup is:
+verbatim+
----
-verbatim-

Verbatim markup (useful for code bits) is:
+verbatim+
+verbatim+\r
The piece of code\r
The piece of code, line 2\r
-verbatim-\r
-verbatim-


= Heading Tags =

Heading Text Tags are available in rare places like items original submission, news item content, project description and users resume.

First Level heading markup is:
+verbatim+
= Title =
-verbatim-

Second Level heading markup is:
+verbatim+
== Subtitle ==
-verbatim-

Third Level heading markup is:
+verbatim+
=== Subsubtitle ===
-verbatim-

Fourth Level heading markup is:
+verbatim+
==== Subsubsubtitle ====
-verbatim-

= The Special _No Markup_ Tag =

If for some reason, you want to completely deactivate the markup on a part of a text, you can always use:
+verbatim+
+nomarkup+ Piece of text that will be printed unformatted -nomarkup-
-verbatim-

This tag diverges from the verbatim tag in the sense that it will not cause the relevant text to be formatted as it would be in a text editor, a pure verbatim environment, but simply unformatted. As result, for example, text indentation would be ignored because HTML by default ignores it. So to copy/paste bits of code, software output, you are advised to always use the verbatim tag instead.
").", '1')",
"INSERT INTO cookbook_context2recipe (recipe_id, group_id, audience_anonymous , audience_loggedin , audience_members , audience_technicians , audience_managers , context_project , context_homepage , context_cookbook , context_download , context_support , context_bugs , context_task , context_patch , context_news , context_mail , context_cvs , context_arch , context_svn , context_my , context_stats , context_siteadmin , context_people , subcontext_browsing , subcontext_postitem , subcontext_edititem , subcontext_search , subcontext_configure ) VALUES ('$recipe_id', '$sys_group_id', '1', '1', '1', '0', '0', '1', '0', '1', '0', '1', '1', '1', '1', '1', '0', '0', '0', '0', '1', '0', '0', '1', '0', '1', '1', '0', '1')");	       

foreach my $recipe (@recipes) {
   # print $recipe."\n\n";
    my $sthinsert=$dbd->prepare($recipe);
    $sthinsert->execute;
}
