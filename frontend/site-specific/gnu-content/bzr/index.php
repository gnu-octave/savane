<?php

# Instructions about Bzr usage.
#
# Copyright (C) 2008, 2010 Sylvain Beucler
# Copyright (C) 2010, 2011, 2013 Karl Berry
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

global $project;

print '<h4>'._('Anonymous read-only access').'</h4>

<p>'._('The Bazaar repositories for projects use separate directories for
each branch. You can see the branch names in the repository by pointing
a web browser to:').' <br />
<tt>http://bzr.'
  .$project->getTypeBaseHost(). "/r/" . $project->getUnixName().'</tt></p>

<ul>
<li><p>'._('For a repository with separate branch directories (<tt>trunk</tt>,
<tt>devel</tt>, &hellip;), use:').'</p>

<pre>bzr branch bzr://bzr.'
  .$project->getTypeBaseHost(). "/" . $project->getUnixName().'/<i>branch</i></pre>

<p>'._('where <i>branch</i> is the name of the branch you want.').'</p>
</li>

<li><p>'
._('For a repository with only a top-level <tt>.bzr</tt> directory, use:').'</p>

<pre>bzr branch bzr://bzr.'
  .$project->getTypeBaseHost(). "/" . $project->getUnixName().'</pre>
</li>

<li><p>'
._('If you need the low-performance HTTP access, this is the url:').'</p>
<pre>http://bzr.'
  .$project->getTypeBaseHost(). "/r/" . $project->getUnixName().'</pre>
</li>
</ul>

<h4>'._('Developer write access (ssh)').'</h4>

';

$username = user_getname();
if ($username == "NA") {
   // for anonymous user :
   $username = '&lt;<em>'._('membername').'</em>&gt;';
}
print '
<pre>bzr branch bzr+ssh://'
  .$username ?>@bzr.<?php echo $project->getTypeBaseHost(). "/"
  .$project->getUnixName()
  .'/<i>branch</i></pre>

<h4>'._('More introductory documentation').'</h4>

';

printf ('<p>'
._('Check the <a href="%s">UsingBzr</a> page at the documentation wiki.')
.'</p>', "//savannah.gnu.org/maintenance/UsingBzr");

?>
