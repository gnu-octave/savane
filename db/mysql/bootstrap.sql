-- Initial values: the admin project and user. It's easier to create
-- them with static values than asking the user to register a user and
-- the 'siteadmin' project with a special handling in the web
-- interface.

-- user 'admin', password 'admin'
-- (account/register.php)
INSERT INTO user (user_name, user_pw, add_date, status, realname)
VALUES ('admin', '21232f297a57a5a743894a0e4a801fc3', UNIX_TIMESTAMP(NOW()), 'A', 'Administrator');

-- siteadmin project
-- (register/*)
INSERT INTO groups
  (unix_group_name, group_name, status, is_public, type,
   register_time, short_description)
VALUES
  ('siteadmin', 'Site Administration', 'A', 1, 1,
   UNIX_TIMESTAMP(NOW()), "This project is dedicated to the administration of this site.");

-- (include/member.php)
INSERT INTO user_group (user_id, group_id, admin_flags) VALUES (101, 101, 'A');


-- We also add a specific field for the task tracker (we need to copy the None field)
INSERT INTO task_field_value (bug_field_id, group_id, value_id, value, description, order_id, status)
  VALUES (103,101,100,'None','',10,'P'),
	 (103,101,1,'Project Approval','Pending project registration',11,'P');

-- We also need to make the task tracker post restriction of comment
-- accepting posting from logged-in users, otherwise they wont be able
-- to comment their registration
INSERT INTO groups_default_permissions (group_id,task_rflags) VALUES (101,300);

-- We add the default recipes grabbed from update/1.3/
INSERT INTO cookbook (group_id, status_id, severity, privacy, category_id, submitted_by, assigned_to, date, summary, details, resolution_id)
VALUES (101, '3', '5', '1', '100', '100', '100', '1133253163', 'Getting back lost password', '".addslashes("If you lose your password simply visit the login page and click \"Lost Your Password?\". 

A confirmation mail will be sent to the address we have on file for you. Then, load the URL in the email to reset your password.")."', '1');
INSERT INTO cookbook_context2recipe (recipe_id, group_id, audience_anonymous, audience_loggedin, audience_members, audience_technicians, audience_managers, context_project, context_homepage, context_cookbook, context_download, context_support, context_bugs, context_task, context_patch, context_news, context_mail, context_cvs, context_arch, context_svn, context_my, context_stats, context_siteadmin, context_people, subcontext_browsing, subcontext_postitem, subcontext_edititem, subcontext_search, subcontext_configure )
VALUES (LAST_INSERT_ID(), 101, '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '1', '0', '0');

INSERT INTO cookbook (group_id, status_id, severity, privacy, category_id, submitted_by, assigned_to, date, summary, details, resolution_id)
VALUES (101, '3', '5', '1', '100', '100', '100', '1133253163', 'Why log in?', 'The log-in mechanism used in these webpages is just a simple way of keeping track of users who work in projects hosted in this site. When a user logs in, she/he is conducted to a personal page that lists the projects she/he is collaborating with and any pending tasks that she/he might have.

If you are involved in any project, if you do not intend to post items on the site, you don\'t need to log in since it will make no difference. 
If you want to register a project of your own to be hosted in this site, you must first log in, because every project must have at least one administrator and we need to know your user name to make you the administrator of the project.

In order to log in, you must be registered (using "New User" in the menu) and give the user name and password selected during your registration.', '1');
INSERT INTO cookbook_context2recipe (recipe_id, group_id, audience_anonymous, audience_loggedin, audience_members, audience_technicians, audience_managers, context_project, context_homepage, context_cookbook, context_download, context_support, context_bugs, context_task, context_patch, context_news, context_mail, context_cvs, context_arch, context_svn, context_my, context_stats, context_siteadmin, context_people, subcontext_browsing, subcontext_postitem, subcontext_edititem, subcontext_search, subcontext_configure )
VALUES (LAST_INSERT_ID(), 101, '1', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '1', '0', '0');

INSERT INTO cookbook (group_id, status_id, severity, privacy, category_id, submitted_by, assigned_to, date, summary, details, resolution_id)
VALUES (101, '3', '5', '1', '100', '100', '100', '1133253163', 'Delays on update', 'Several function related to mail aliases, external services access (SVN, CVS...), user additions, group member changes, CVS, etc, are performed via a cronjob on a regular basis. 

Changes made on the web site may appear to be live but will not take effect until the next cron update.', '1');
INSERT INTO cookbook_context2recipe (recipe_id, group_id, audience_anonymous, audience_loggedin, audience_members, audience_technicians, audience_managers, context_project, context_homepage, context_cookbook, context_download, context_support, context_bugs, context_task, context_patch, context_news, context_mail, context_cvs, context_arch, context_svn, context_my, context_stats, context_siteadmin, context_people, subcontext_browsing, subcontext_postitem, subcontext_edititem, subcontext_search, subcontext_configure )
VALUES (LAST_INSERT_ID(), 101, '0', '1', '1', '0', '0', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '0', '0', '0', '0', '0', '0', '1');

# We add the default recipes grabbed from update/3.0/
INSERT INTO cookbook (group_id, status_id, severity, privacy, category_id, submitted_by, assigned_to, date, summary, details, resolution_id) VALUES (101, '3', '5', '1', '100', '100', '100', '1133253163', 'Markup Reminder', 'Savane provides a markup langage that enables you to format text you post in items or items comments. HTML is not allowed for security reasons.


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

items references like _tracker #nnn_ will be made links to, like in:
+verbatim+
here is a link to recipe #101.
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

This tag diverges from the verbatim tag in the sense that it will not cause the relevant text to be formatted as it would be in a text editor, a pure verbatim environment, but simply unformatted. As result, for example, text indentation would be ignored because HTML by default ignores it. So to copy/paste bits of code, software output, you are advised to always use the verbatim tag instead.', '1');
INSERT INTO cookbook_context2recipe (recipe_id, group_id, audience_anonymous, audience_loggedin, audience_members, audience_technicians, audience_managers, context_project, context_homepage, context_cookbook, context_download, context_support, context_bugs, context_task, context_patch, context_news, context_mail, context_cvs, context_arch, context_svn, context_my, context_stats, context_siteadmin, context_people, subcontext_browsing, subcontext_postitem, subcontext_edititem, subcontext_search, subcontext_configure ) VALUES (LAST_INSERT_ID(), 101, '1', '1', '1', '0', '0', '1', '0', '1', '0', '1', '1', '1', '1', '1', '0', '0', '0', '0', '1', '0', '0', '1', '0', '1', '1', '0', '1');

INSERT INTO cookbook (group_id, status_id, severity, privacy, category_id, submitted_by, assigned_to, date, summary, details, resolution_id) VALUES (101, '3', '3', '1', '100', '100', '100', '1133253163', 'Fighting Spam', 'Savane provides several ways to protect trackers from spam.

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

If your site runs checks with SpamAssassin, *flagged spams will be used to improves bayesian filtering*.', '1');
INSERT INTO cookbook_context2recipe (recipe_id, group_id, audience_anonymous, audience_loggedin, audience_members, audience_technicians, audience_managers, context_project, context_homepage, context_cookbook, context_download, context_support, context_bugs, context_task, context_patch, context_news, context_mail, context_cvs, context_arch, context_svn, context_my, context_stats, context_siteadmin, context_people, subcontext_browsing, subcontext_postitem, subcontext_edititem, subcontext_search, subcontext_configure ) VALUES (LAST_INSERT_ID(), 101, '0', '1', '1', '0', '0', '0', '0', '1', '0', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '1');

-- TODO?
-- (siteadmin/triggercreation.php)
