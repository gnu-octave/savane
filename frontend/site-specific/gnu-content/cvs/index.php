<?php

# Instructions about CVS usage.
#
# Copyright (C) 2005, 2006 Sylvain Beucler
# Copyright (C) 2009 Karl Berry
# Copyright (C) 2012 Michael J. Flickinger
# Copyright (C) 2017 Bob Proulx
# Copyright (C) 2017 Ineiev <ineiev@gnu.org>
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

include $GLOBALS['sys_incdir'].'/php/fingerprints.php';

global $project;

print '<h3>'._('Anonymous CVS Access').'</h3>

<p>'
._("This project's CVS repository can be checked out through anonymous
CVS with the following instruction set. The module you wish
to check out must be specified as the &lt;<i>modulename</i>&gt;.").'</p>

';

if ($project->Uses("cvs"))
  {
    print "<h4>"._('Software repository:')."</h4>\n";
    print "<pre>cvs -z3 -d:pserver:anonymous@cvs."
          . $project->getTypeBaseHost()
          . ":" . $project->getTypeDir('cvs')
          . " co "
          . $project->getUnixName()
          . "</pre>\n";
    print "<h5>"._('With other project modules:')."</h5>\n";
    print "<pre>cvs -z3 -d:pserver:anonymous@cvs."
          . $project->getTypeBaseHost()
          . ":"
          . $project->getTypeDir('cvs')
          . " co &lt;<i>"._('modulename')."</i>&gt;</pre>\n";
  }
if ($project->CanUse("homepage") || $project->UsesForHomepage("cvs"))
  {
    print "<h4>"._('Webpage repository:')."</h4>\n";
    print "<pre>cvs -z3 -d:pserver:anonymous@cvs."
    . $project->getTypeBaseHost()
    . ":"
    . $project->getTypeDir('homepage')
    . " co "
    . $project->getUnixName()
    . "</pre>\n";
  }

print '<p>'
._("<em>Hint:</em> When you update your working copy from within the
module's directory (with <code>cvs update</code>) you do not need the
<code>-d</code> option anymore.  Simply use").'</p>

<blockquote>
<pre>
cvs update
cvs -qn update
</pre>
</blockquote>

<p>'._('to preview and status check.').'</p>

<h3>'._('Project Member CVS Access via SSH').'</h3>

<p>'._('Member access is performed using the CVS over SSH method. The
pserver method can only be used for anonymous access.').'</p>

<p>'
._('The SSHv2 public key fingerprints for the machine hosting the CVS
trees are:').'</p>'. $vcs_fingerprints;

$username = user_getname();
if ($username == "NA")
  # For anonymous user.
  $username = '&lt;<i>'._('membername').'</i>&gt;';
if ($project->Uses("cvs"))
  {
    print "<h4>"._('Software repository:').'</h4>'."\n";
    print "<pre>cvs -z3 -d:ext:"
           . $username
           . "@cvs."
           . $project->getTypeBaseHost()
           . ":"
           . $project->getTypeDir("cvs")
           . " co "
           . $project->getUnixName()
           . "</pre></p>\n";
    print "<h5>"._('With other project modules:')."</h5>\n";
    print "<pre>cvs -z3 -d:ext:"
          . $username
          . "@cvs."
          . $project->getTypeBaseHost()
          . ":"
          . $project->getTypeDir("cvs")
          . " co ".'&lt;<i>'._('modulename').'</i>&gt;'."</pre></p>\n";
  }
if ($project->CanUse("homepage") || $project->UsesForHomepage("cvs"))
  {
    print "<h4>"._('Webpage repository:')."</h4>\n";
    print "<pre>cvs -z3 -d:ext:"
          . $username
          . "@cvs."
          . $project->getTypeBaseHost()
          . ":"
          . ereg_replace('/$', "", $project->getTypeDir("homepage"))
          . " co "
          . $project->getUnixName()
          . "</pre></p>\n";
  }

print '<h3>'._('CVS Newbies').'</h3>

';

printf ('<p>'
._("If you've never used CVS, you should read some documentation about
it; a useful URL is %s. Using
CVS is not complex but you have to understand what is going on. The
best way to start is to ask a friend to show you the way.").'</p>

', '<a href="http://www.nongnu.org/cvs/#documentation">
http://www.nongnu.org/cvs/#documentation</a>');

printf ('<p>'
._('The basic information described further on this page is detailed in
the <a href="%s">Savannah user doc</a>.').'</p>',
   $GLOBALS['sys_home'].'faq/?group='.$GLOBALS['sys_unix_group_name']);

if ($project->CanUse("cvs"))
  {
    print '<h3>'._('What are CVS modules?').'</h3>';
    printf ('<p>'
._('The CVS repository of each project is divided into modules which you can
download separately.  The list of existing modules for this project can be
obtained by looking at <a href="%s">the root of the CVS repository</a>; each
<code>File</code> listed there is the name of a module, which can substitute
the generic &lt;<i>modulename</i>&gt; used below in the examples of the
<code>co</code> command of CVS.  Note that <code>.</code> (dot) is always also
a valid module name which stands for &ldquo;all available modules&rdquo; in a project.  Most
projects have a module with the same name of the project, where the main
software development takes place.').'</p>

',
    $project->getTypeUrl("cvs_viewcvs"));
  }

print '<p>'._('The same applies to the Webpage Repository.').'</p>

<h3>'._('Import your CVS tree').'</h3>

<p>'._('If your project already has an existing CVS repository that you
want to move to Savannah, make an appointment with us for the
migration.').'</p>


<h3>'._('Symbolic Links in Webpage CVS').'</h3>

';

printf ('<p>'
._('As a special feature in CVS web repositories (only), a file named
<tt>.symlinks</tt> can be put in any directory where you want to make symbolic
links.  Each line of the file lists a real file name followed by the name of the
symbolic link. The symbolic links are built twice an hour.  More information in
<a href="%s">GNU Webmastering Guidelines</a>.').'</p>',
"//www.gnu.org/server/standards/README.webmastering.html#symlinks");

global $project;

if ($project->getTypeBaseHost() == "savannah.gnu.org")
  {
    print '<h3>'._('Web pages for GNU packages').'</h3>';
    printf ('<p>'
._('When writing web pages for official GNU packages, please keep the
<a href="%s"> guidelines</a> in mind.').'</p>
','//www.gnu.org/prep/maintain/maintain.html#Web-Pages');
  }
?>
