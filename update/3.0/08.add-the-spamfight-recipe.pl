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

my @recipes = ("INSERT INTO cookbook (bug_id, group_id, status_id , severity , privacy , category_id , submitted_by , assigned_to , date , summary , details , resolution_id) VALUES ('$recipe_id', '$sys_group_id', '3', '3', '1', '100', '100', '100', '1133253163', 'Fighting Spam', ".$dbd->quote("Savane provides several ways to protect trackers from spam.

= Preventing Spam =

Savane runs *DNS blacklists* checks on all forms submitted by non-project members. 

Apart from that, there are a few options that can allow a project admin to prevent many spams.

Spam are usually caused by anonymous robots.

* A good starting point to avoid spam is first to set trackers *Posting Restrictions* to a tough policy:
** On every trackers that you feel dedicated to manage the project workflow, without end-users interaction, like the task manager, set _project membership_ as minimal level of authentication.
** On every trackers that need input from non-members, like the support manager and the bug tracker, set _logged-in user_ as minimal level of authentication, if you can afford to forbid anonymous post (it means that external contributors will have to create an account)

* Another good idea is too use the special *Lock Discussion* field. This field, that can be modified only by trackers managers, is complementary to the Posting Restrictions. When an item is set as _Locked_, only technicians and managers are still be able to post further comments. While it may be used to end a flamewar, it will obviously reduce the number of targets available to spam robots if you set one (or more) automatic transition update so whenever an item is closed, the item get additionnally locked. Obviously, this is useless on trackers where only project members can post.

= Automatically Checking Potential Spam =

Savane allows to *automatically check posted content with SpamAssassin*. 

Any post that Savane feels needs to be crosschecked automatically by SpamAssassin (depends on site configuration) will be delayed, temporarily flagged as spam, when posted until it is checked in the following minutes. If it is found to be spam, no notification will ever be sent, it will stay flagged as spam.

= Removing Spam, Spam Scores =

=== Spam Scores ===

Any logged-in user is able, when he sees content (comment or item) that he believes to be spam, to *flag it as spam*. This will increment the spam score of the item.

* If the reporter is _project admin_ on which the suspected spam have been posted, the spam score of the content will grow of 5
* If the reporter is _project member_ on which the suspected spam have been posted, the spam score of the content will grow of 3
* If the reporter is _not project member_ on which the suspected spam have been posted, the spam score of the content will grow of 1

Any *content with a spam score superior or equal to 5 is considered to be spam*.

Each user have also his own spam score. Each time an user got one of his post flagged as spam (spam score > 4), his own score grows of 1. User own spam score is used to determine the spam score of any new post. In other words, someone caught 5 times posting spam will get all his further post automatically flagged as spam as soon as posted.

Site administrators have a specific interface that will allow them to check if spam reports against a user were legitimate and will be able to take necessary actions accordingly (like banning account used to spam or to maliciously report as spam perfectly valid content).

It is also possible to project admins and site admins to unflag content, which means they can reset the spam score of some content if they think there is a mistake.

=== Removing Spam ===

When content is considered to be spam (spam score > 4), it is not removed from the database. We do not want to risk loosing data in case of false positives.

However, comments that are spam are automatically removed from items pages, only a link remains for checking purpose.

Also, when browsing items, items that are spams are not shown, unless you change the related display criteria. 

If the content is an item, it is automatically set to _Locked_ so further post are impossible.

If your site runs checks with SpamAssassin, *flagged spams will be used to improves bayesian filtering*.").", '1')",
"INSERT INTO cookbook_context2recipe (recipe_id, group_id, audience_anonymous , audience_loggedin , audience_members , audience_technicians , audience_managers , context_project , context_homepage , context_cookbook , context_download , context_support , context_bugs , context_task , context_patch , context_news , context_mail , context_cvs , context_arch , context_svn , context_my , context_stats , context_siteadmin , context_people , subcontext_browsing , subcontext_postitem , subcontext_edititem , subcontext_search , subcontext_configure ) VALUES ('$recipe_id', '$sys_group_id', '0', '1', '1', '0', '0', '0', '0', '1', '0', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '1')");
	       

foreach my $recipe (@recipes) {
   # print $recipe."\n\n";
    my $sthinsert=$dbd->prepare($recipe);
    $sthinsert->execute;
}
