<?php

# Instructions about CVS usage.
#
# Copyright (C) 2005, 2006 Sylvain Beucler
# Copyright (C) 2009 Karl Berry
# Copyright (C) 2012 Michael J. Flickinger
# Copyright (C) 2017 Bob Proulx
# Copyright (C) 2017, 2021, 2022 Ineiev <ineiev@gnu.org>
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

include dirname (__FILE__) . '/../fingerprints.php';

global $project, $sys_home, $sys_unix_group_name;

print '<h3>' . _('Anonymous CVS Access') . "</h3>\n";

print '<p>'
 . _("This project's CVS repository can be checked out through anonymous\n"
      . "CVS with the following instruction set. The module you wish\n"
      . "to check out must be specified as the &lt;<i>modulename</i>&gt;.")
 . "</p>\n";

$proj_unix_name = $project->getUnixName ();

$cvs_cmd_base = "cvs -z3 -d:pserver:anonymous@cvs."
  . $project->getTypeBaseHost () . ":";

if ($project->Uses ("cvs"))
  {
    $cvs_cmd = "$cvs_cmd_base " . $project->getTypeDir ('cvs') . " co ";
    print "<h4>" . _('Software repository:') . "</h4>\n";
    print "<pre>$cvs_cmd$proj_unix_name</pre>\n";
    print "<p>" . _('With other project modules:') . "</p>\n";
    print "<pre>$cvs_cmd&lt;<i>" . _('modulename') . "</i>&gt;</pre>\n";
  }
if ($project->CanUse("homepage") || $project->UsesForHomepage("cvs"))
  {
    print "<h4>" . _('Webpage repository:') . "</h4>\n";
    print "<pre>$cvs_cmd_base" . $project->getTypeDir('homepage')
      . " co $proj_unix_name</pre>\n";
  }

print '<p>';
print _("<em>Hint:</em> When you update your working copy from within the
module's directory (with <code>cvs update</code>) you do not need the
<code>-d</code> option anymore.  Simply use");
print "</p>\n\n<blockquote>\n<pre>";
print "cvs update\ncvs -qn update\n";
print "</pre>\n</blockquote>\n\n";

print "<p>" . _('to preview and status check.') . "</p>\n";

print '<h3>' . _('Project Member CVS Access via SSH') . "</h3>\n";

print "<p>"
  . _("Member access is performed using the CVS over SSH method. The\n"
      . "pserver method can only be used for anonymous access.")
  . "</p>\n\n<p>"
  . _("The SSHv2 public key fingerprints for the machine hosting the CVS\n"
      . "trees are:")
  . "</p>\n" . $vcs_fingerprints;

$username = user_getname ();
if ($username == "NA")
  # For anonymous user.
  $username = '&lt;<i>' . _('membername') . '</i>&gt;';
$cvs_cmd_base = "cvs -z3 -d:ext:$username@cvs." . $project->getTypeBaseHost()
  . ":";
if ($project->Uses("cvs"))
  {
    $cvs_cmd = "$cvs_cmd_base" . $project->getTypeDir("cvs") . " co ";
    print "<h4>" . _('Software repository:') . '</h4>' . "\n";
    print "<pre>$cvs_cmd$proj_unix_name</pre></p>\n";
    print "<p>" . _('With other project modules:') . "</p>\n";
    print "<pre>$cvs_cmd&lt;<i>" . _('modulename') . '</i>&gt;'
      . "</pre></p>\n";
  }
if ($project->CanUse ("homepage") || $project->UsesForHomepage ("cvs"))
  {
    print "<h4>" . _('Webpage repository:') . "</h4>\n";
    print "<pre>$cvs_cmd_base"
      . preg_replace ('#/$#', "", $project->getTypeDir ("homepage"))
      . " co $proj_unix_name</pre></p>\n";
  }

if (member_check (0, $project->getGroupId (), 'A'))
  {
    print "<h2>" . _('Email Notifications') . "</h2>\n";
    print "<p>";
    printf (
      _('You can <a href="%s">configure commit notifications</a>.'),
      "/cvs/admin/?group=$proj_unix_name"
    );
    print "</p>\n";
  }

print '<h2>' . _('CVS Newbies') . "</h2>\n\n<p>";
printf (
  _("If you've never used CVS, you should read some documentation about\n"
    . "it; a useful URL is %s. Using\nCVS is not complex but you have "
    . "to understand what is going on. The\nbest way to start is to ask "
    . "a friend to show you the way."),
  "<a href=\"//www.nongnu.org/cvs/#documentation\">\n"
  . "https://www.nongnu.org/cvs/#documentation</a>"
);

print "</p>\n<p>";

printf (
  _("The basic information described further on this page is detailed in\n"
    . "the <a href=\"%s\">Savannah user doc</a>."),
  "${sys_home}faq/?group=$sys_unix_group_name"
);

print "</p>\n";

if ($project->CanUse ("cvs"))
  {
    print '<h2>' . _('What are CVS modules?') . "</h2>\n<p>";
    printf (
      _("The CVS repository of each project is divided into modules which you "
        . "can\ndownload separately.  The list of existing modules for this "
        . "project can be\nobtained by looking at <a href=\"%s\">the root of "
        . "the CVS repository</a>; each\n<code>File</code> listed there is "
        . "the name of a module, which can substitute\nthe generic "
        . "&lt;<i>modulename</i>&gt; used below in the examples of the\n"
        . "<code>co</code> command of CVS.  Note that <code>.</code> (dot) "
        . "is always also\na valid module name which stands for &ldquo;all "
        . "available modules&rdquo; in a project.  Most\nprojects have "
        . "a module with the same name of the project, where the main\n"
        . "software development takes place."),
      $project->getTypeUrl ("cvs_viewcvs")
    );
    print "</p>\n";
  }

print '<p>' . _('The same applies to the Webpage Repository.') . "</p>\n\n";
print '<h2>' . _('Import your CVS tree') . "</h2>\n<p>";

print
  _("If your project already has an existing CVS repository that you\n"
    . "want to move to Savannah, make an appointment with us for the\n"
    . "migration.");

print "</p>\n\n";

print '<h2>' . _('Symbolic Links in Webpage CVS') . "</h2>\n\n<p>";

printf (
  _("As a special feature in CVS web repositories (only), a file named\n"
    . "<tt>.symlinks</tt> can be put in any directory where you want to make "
    . "symbolic\nlinks.  Each line of the file lists a real file name "
    . "followed by the name of the\nsymbolic link. The symbolic links are "
    . "built twice an hour.  More information in\n<a href=\"%s\">GNU "
    . "Webmastering Guidelines</a>."),
  "//www.gnu.org/server/standards/README.webmastering.html#symlinks"
);

print "</p>\n";
if ($project->getTypeBaseHost () == "savannah.gnu.org")
  {
    print '<h2>' . _('Web pages for GNU packages') . "</h2>\n\n<p>";
    printf (
      _("When writing web pages for official GNU packages, please keep the\n"
        . "<a href=\"%s\"> guidelines</a> in mind."),
      '//www.gnu.org/prep/maintain/maintain.html#Web-Pages'
    );
    print "</p>\n";
  }
?>
