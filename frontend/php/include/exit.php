<?php
# Generic functions to clean exit on error.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2004-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017 Ineiev
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


# Base function. The alternatives below should be used whenever relevant,
# as they may wrap this one with additional useful things
# (set HTTP response, etc).
function exit_error($title, $text=0)
{
  exit_header();

  global $HTML;
  global $feedback;

  $content = $title;
  if ($text)
    $content .=
# TRANSLATORS: this string separates error title from further description,
# like _("Invalid User")._(': ')._("That user does not exist.")
      _(': ').$text;

  # Add the content in feedback only if there is actually something.
  if ($content)
    fb($content, 1);

  $HTML->header(array('title'=>_("Exiting with Error"),'notopmenu'=>1));
  html_feedback_top();

  $HTML->footer(array());
  exit;
}

function exit_permission_denied()
{
  exit_header("403 Forbidden");
  exit_log("permission denied");
  exit_error(_("Permission Denied"));
}

function exit_not_logged_in()
{
  # Instead of a simple error page, take user to the login page.
  global $REQUEST_URI, $sys_https_host, $sys_default_domain, $sys_home;

  if ($GLOBALS['sys_https_host'])
    {
      header ("Location: https://".$sys_https_host.$sys_home
              .'account/login.php?uri='.urlencode($REQUEST_URI));
    }
  else
    {
      header ("Location: http://".$sys_default_domain.$sys_home
              ."account/login.php?uri=".urlencode($REQUEST_URI));
    }
  exit;
}

function exit_no_group()
{
  exit_header();
  exit_error(_("No group chosen"),'nogroup');
}

function exit_missing_param()
{
  exit_header();
  exit_error(_("Missing Parameters"),'');
}

# Standardize the way we log important exit on error.
function exit_log($message)
{
  $username = "anonymous user";
  if (user_isloggedin())
    $username = "user ".user_getname();
  error_log($message." - ".$username." at ".$_SERVER['REQUEST_URI']);
}

# Standardize the HTTP error head
# (not cgi compliant, but Savane not supposed to run with PHP as CGI but
# as apache module).
function exit_header($status=false)
{
  if (headers_sent())
    return false;

  if (!$status)
    $status = "404 Not Found";

  header($_SERVER['SERVER_PROTOCOL'].' '.$status);
}

# Exit unless group uses mailing lists.
function exit_test_usesmail ($group_id)
{
  $project = project_get_object ($group_id);
  if (!$project->Uses ('mail'))
    exit_error (_("Error"), _("This project has turned off mailing lists"));
}
?>
