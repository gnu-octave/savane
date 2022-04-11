<?php
# Manage user preferences.
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
# Copyright (C) 2017, 2019, 2022 Ineiev
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

require_once ('../../include/sane.php');
extract (sane_import ('post',
  [
    'true' =>
      [
        'update', 'form_keep_only_one_session', 'theme_rotate_jump',
        'form_reverse_comments_order', 'form_stone_age_menu',
        'form_nonfixed_feedback', 'form_use_bookmarks', 'form_email_hide',
        'form_email_encrypted'
      ],
    'no_quotes' => ['form_timezone', 'user_theme']
  ]
));

if ($update)
  {
    # Define actions to do before selecting theme.
    function update_theme ()
    {
      global $user_theme, $theme_rotate_jump, $form_timezone, $form_email_hide;

      # Update theme.
      if ($user_theme == "Default")
        $user_theme = "";
      elseif ($user_theme !== 'rotate' && $user_theme !== 'random')
        $user_theme = theme_validate ($user_theme);

      if ($theme_rotate_jump == "1")
        theme_rotate_jump ($user_theme);

      if ($form_timezone == 100)
        $form_timezone = "GMT";

      $success = db_autoexecute (
        'user',
         [
           'email_hide' => ($form_email_hide ? "1" : "0"),
           'theme' => $user_theme, 'timezone' => $form_timezone,
         ],
         DB_AUTOQUERY_UPDATE, "user_id = ?", [user_getid ()]
      );
      if ($success)
        fb (_("Database successfully updated"));
      else
        fb (_("Failed to update the database"),1);
    }
  }
require_once ('../../include/init.php');
require_once ('../../include/timezones.php');
extract (sane_import ('request', ['pass' => 'feedback']));
session_require (['isloggedin' => 1]);

if ($update)
  {
    function sync_preference ($pref, $note = null)
    {
      if ($GLOBALS["form_$pref"] == "1")
        {
          user_set_preference ($pref, 1);
          if ($note !== null)
            fb ($note);
        }
      else
        user_unset_preference ($pref);
    }
    sync_preference ('use_bookmarks');
    sync_preference ('email_encrypted');
    sync_preference ('nonfixed_feedback');
    sync_preference (
      'stone_age_menu',
       _("Stone age menu activated, it will be effective the next time "
         . "a page is\nloaded")
    );
    sync_preference ('reverse_comments_order');
    sync_preference ('keep_only_one_session');
  }

# Print form and links.
site_user_header (['context' => 'account']);
# Get global user vars.
$res_user = db_execute (
  "SELECT * FROM user WHERE user_id = ?", [user_getid ()]
);
$row_user = db_fetch_array ($res_user);

print '<p>' . _("You can change all of your account features from here.")
  . "</p>\n";
utils_get_content ("account/index_intro");
print '<form action="' . htmlentities ($_SERVER["PHP_SELF"])
  . '" method="post">';
print '<h2>' . _("Significant Arrangements") . "</h2>\n";

print $HTML->box_top (_('Authentication Setup'));
print '<p><a href="change.php?item=password">' . _("Change Password")
  . "</a></p>\n";
print '<p class="smaller">'
  . _("This password gives access to the web interface.");
utils_get_content ("account/index_passwd");
print "</p>\n";

# Get shared key count from DB.
$expl_keys = explode ("###", $row_user['authorized_keys']);

# If the last 'key' is empty, then it is because of a trailing separator;
# so do not count it.
$keynum = (sizeof ($expl_keys));
if ($expl_keys[$keynum-1] == "")
  $keynum--;

$i = 0;
function print_box_next_item ()
{
  global $i, $HTML;
  print $HTML->box_nextitem (utils_altrow ($i));
}
print_box_next_item ();
print '<p><a href="editsshkeys.php">';
if ($keynum > 0)
  printf (
    ngettext (
      "Edit the %d SSH Public Key registered",
      "Edit the %d SSH Public Keys registered",
      $keynum),
    $keynum
  );
else
  print _("Register an SSH Public Key");

print "</a></p>\n<p class='smaller'>";
utils_get_content ("account/index_ssh");
print "</p>\n";

$i++;
print_box_next_item ();
print '<p><a href="change.php?item=gpgkey">' . _("Edit GPG Key")
  . "</a></p>\n";
print '<p class="smaller">';
utils_get_content ("account/index_gpg");
print "</p>\n";

$i++;
print_box_next_item ();
print '<p><a href="sessions.php">';
printf (
  ngettext (
    "Manage the %d opened session", "Manage the %d opened sessions",
    session_count (user_getid ())
  ),
  session_count (user_getid ())
);
print "</a></p>\n";
print_box_next_item ();

function pref_cbox ($name, $label, $checked = null)
{
  if ($checked === null)
    $checked = user_get_preference ($name);
  print "<p>" .form_checkbox ("form_$name", $checked) . "\n";
  print "<label for=\"form_$name\">$label</label></p>\n";
}

pref_cbox (
  'keep_only_one_session',
  _("Keep only one session opened at a time")
);
print '<p class="smaller">'
  . _("By default, you can open as many session concurrently as you want. "
      . "But you\nmay prefer to allow only one session to be opened at a "
      . "time, killing previous\nsessions each time you log in.");
print "</p>\n";
print $HTML->box_bottom ();

print $HTML->box_top (_('Identity Record'));

print '<p>';
printf (_("Account #%s"), $row_user['user_id']);
print "</p>\n<p class='smaller'>";
printf (_("Your login is %s."), "<strong>{$row_user['user_name']}</strong>");
# TRANSLATORS: the argument is registration date.
printf (
  ' ' . _("You registered your account on %s."),
  '<strong>' . utils_format_date ($row_user['add_date']) . '</strong>'
);
print "</p>\n";

$i = 0;
print_box_next_item ();
print '<p><a href="change.php?item=realname">' . _("Change Real Name")
  . "</a></p>\n";
print '<p class="smaller">';
# TRANSLATORS: the argument is full name.
printf (_("You are %s."), "<strong>{$row_user['realname']}</strong>");
print "</p>\n";

$i++;
print_box_next_item ();
print '<p><a href="resume.php">' . _("Edit Resume and Skills") . "</a></p>\n";
print '<p class="smaller">'
  . _("Details about your experience and skills may be of interest to other "
      . "users\nor visitors.")
  . "</p>\n";

$i++;
print_box_next_item ();
print "<p><a href=\"${sys_home}users/{$row_user['user_name']}\">"
  . _("View your Public Profile") . "</a></p>\n";
print '<p class="smaller">' . _("Your profile can be viewed by everybody.")
 . "</p>\n";

print $HTML->box_bottom ();

print $HTML->box_top (_('Mail Setup'));

print '<p><a href="change.php?item=email">' . _("Change Email Address")
  . "</a></p>\n";
print '<p class="smaller">';
printf (
  _("Your current address is %s. It is essential to us that this\n"
    . "address remains valid. Keep it up to date."),
  "<strong>{$row_user['email']}</strong>"
);
print "</p>\n";

$i = 0;
print_box_next_item ();

print '<p><a href="change_notifications.php">'
  . _("Edit Personal Notification Settings") . "</a></p>\n";
print '<p class="smaller">'
  . _("Here is defined when the trackers should send email notifications. It\n"
      . "permits also to configure the subject line prefix of sent mails.")
  . "</p>\n";

$i++;
print_box_next_item ();
print '<p><a href="cc.php">' . _("Cancel Mail Notifications") . "</a></p>\n";
print '<p class="smaller">' . _("Here, you can cancel all mail notifications.")
  . "</p>\n";

print $HTML->box_bottom ();

$update_btn = '<p class="center"><span class="clearr" />'
  . '<input type="submit" name="update" value="'
  . _("Update") . "\" /></span></p>\n";
print $update_btn;

print "\n<h2>" . _("Secondary Arrangements") . "</h2>\n";

print $HTML->box_top (_('Optional Features'));

pref_cbox ("use_bookmarks", _("Use integrated bookmarks"));
print '<p class="smaller">'
  . _("By default, integrated bookmarks are deactivated to avoid redundancy "
      . "with\nthe bookmark feature provided by most modern web browsers. "
      . "However, you may\nprefer integrated bookmarks if you frequently use "
      . "different workstations\nwithout web browsers bookmarks "
      . "synchronization.")
  . "</p>\n";

$i = 0;
print_box_next_item ();

pref_cbox (
  "email_hide", _("Hide email address from your account information"),
  $row_user['email_hide']
);
print '<p class="smaller">'
  . _("When checked, the only way for users to get in touch with you would be "
      . "to\nuse the form available to logged-in users. It is generally a bad "
      . "idea to choose\nthis option, especially if you are a project "
      . "administrator.")
  . "</p>\n";

$i++;
print_box_next_item ();
pref_cbox ('email_encrypted', _("Encrypt emails when resetting password"));
print '<p class="smaller">'
  . _("When checked, Savannah will encrypt email messages\nwith your "
      . "registered public GPG key when resetting password is requested.\n"
      . "If no suitable key is available, the messages still go unencrypted.")
  . "</p>\n";

print $HTML->box_bottom ();
print $HTML->box_top (_('Cosmetics Setup'));

# The select box comes before the name of the category so all the clickable
# part of the form stays on a same line (better UI design).
print '<p>'
  . html_build_select_box_from_arrays (
      $TZs, $TZs, 'form_timezone', $row_user['timezone'], true, 'GMT',
      false, 'Any', false, _('Timezone')
    );
print ' ' . _("Timezone") . "</p>\n";
print '<p class="smaller">'
  . _("No matter where you live, you can see all dates and times as if it "
      . "were in\nyour neighborhood.")
  . "</p>\n";

$i = 0;
print_box_next_item ();

print "<p>";
html_select_theme_box ("user_theme", $row_user['theme']);
print ' ' . _("Theme") . "</p>";

if ("rotate" === $row_user['theme'] || 'random' === $row_user['theme'])
  print "<p>\n" . form_checkbox ('theme_rotate_jump')
    . '<label for="theme_rotate_jump">' . _("Jump to the next theme")
    . "</label></p>\n";
print '<p class="smaller">'
  . _("Not satisfied with the default color theme of the interface?")
  . "</p>\n";

if (!theme_guidelines_check (SV_THEME))
  {
    print '<p class="smaller"><span class="warn">'
      . _("The theme you are currently using does not follow the latest "
          . "Savane CSS\nguidelines. As a result, page layout may be more "
          . "or less severely broken. It is\nnot advised to use this theme.")
      . ' ';
    # If the non-valid theme is the default one, tell users they should fill
    # a support request.
    if (SV_THEME == $GLOBALS['sys_themedefault'])
      {
        # TRANSLATORS: the argument is site name (like Savannah).
        $link_text =
          sprintf (
            _("%s administrators should be asked to take\ncare of "
              . "Savane CSS Guidelines, since it is the default theme."),
            $sys_name
          );
        print utils_link (
          "${sys_home}support/?group=$sys_unix_group_name",
          $link_text, "warn"
        );
      }
    print '</span></p>';
  }

$i++;
print_box_next_item ();

pref_cbox (
  "reverse_comments_order",
  _("Print comments from the oldest to the latest")
);
print '<p class="smaller">'
  . _("By default, comments are listed in reverse chronological order. This "
      . "means\nthat for a given item, comments are printed from the latest "
      . "to the oldest. If\nthis behavior does not suit you, select this "
      . "option.")
  . "</p>\n";

$i++;
print_box_next_item ();

pref_cbox ("stone_age_menu", _("Use the Stone Age menu"));
print '<p class="smaller">'
  . _("By default, the top menu includes links, via dropdown submenus, to "
      . "all\nrelevant pages in the current context (project area, personal "
      . "area). However,\nthe dropdown submenu mechanism may not work with "
      . "a few old or lightweight\nbrowsers, for instance very old Konqueror "
      . "versions (< 3.1, before 2003).\nSelecting this option enables "
      . "an old-fashioned submenu like the one shipped\nin older Savane "
      . "releases (< 2.0).")
  . "</p>\n";

$i++;
print_box_next_item ();

pref_cbox ("nonfixed_feedback", _("Show feedback in relative position"));
print '<p class="smaller">'
  . _("By default, the feedback box appear as a fixed box on top of the "
      . "window.\nIf you check this option, the feedback will\n"
      . "be added in the page flow, after the top menu.")
   . "</p>\n";

print $HTML->box_bottom ();

print $update_btn;
print "</form>\n";

print "\n<h2>" . _('Account Deletion') . "</h2>\n";

print '<p><a href="change.php?item=delete">' . _("Delete Account")
  . "</a></p>\n";
print '<p class="smaller">';
# TRANSLATORS: the argument is site name (like Savannah).
printf (
  _("If you are no longer member of any project and do not intend to use\n"
    . "%s further, you may want to delete your account. This action cannot "
    . "be undone\nand your current login will be forever lost."),
  "<strong>$sys_name</strong>"
);
print "</p>\n";

$HTML->footer ([]);
?>
