<?php

# Instructions about Subversion usage.
#
# Copyright (C) 2007, 2009 Sylvain Beucler
# Copyright (C) 2011 Karl Berry
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

print '<h3>'._('Anonymous / read-only Subversion access').'</h3>

<p>'._("This project's Subversion repository can be checked out anonymously
as follows.  The module you wish to check out must be specified as the
<i>modulename</i>.").'</p>

';

print '<h4>'._('Access using the SVN protocol:').'</h4>
<tt>svn co svn://svn.'
                        . $project->getTypeBaseHost()
                        . "/"
                        . $project->getUnixName()
                        . "/<em>"._('modulename')."</em></tt><br />";
print '<h4>'._('Access using HTTP (slower):').'</h4>
<tt>svn co http://svn.'
                        . $project->getTypeBaseHost()
                        . "/svn/"
                        . $project->getUnixName()
                        . "/<em>"._('modulename')."</em></tt>";

print '<p>'._("Typically, you'll want to use <tt>trunk</tt> for
<em>modulename</em>. Refer to a project's specific instructions if
you're unsure, or browse the repository with ViewVC.").'</p>


<h3>'._('Project member Subversion access via SSH').'</h3>

<p>'
._('Member access is performed using the Subversion over SSH method.')
.'</p>

<p>
'._('The SSHv2 public key fingerprints for the machine hosting the source
trees are:').'

<pre>
RSA: 1024 80:5a:b0:0c:ec:93:66:29:49:7e:04:2b:fd:ba:2c:d5
DSA: 1024 4d:c8:dc:9a:99:96:ae:cc:ce:d3:2b:b0:a3:a4:95:a5
</pre>
</p>
';

$username = user_getname();
if ($username == "NA") {
        // for anonymous user :
        $username = '&lt;<em>'._('membername').'</em>&gt;';
}

print '<h4>'._('Software repository (over SSH):').'</h4>
<tt>svn co svn+ssh://'
              . $username
              . '@svn.'
              . $project->getTypeBaseHost()
              . "/"
              . $project->getUnixName()
              . "/<em>"._('modulename')."</em></tt>";
print '

<h3>'._('Importing into Subversion on Savannah').'</h3>

';

printf ('<p>'
._('If your project already has an existing source repository that you
want to move to Savannah, check the <a
href="%s">conversion
documentation</a> and then submit a request for the
migration in the <a
href="%s">Savannah
Administration</a> project.').'</p>

', '//savannah.gnu.org/maintenance/CvSToSvN',
   '//savannah.gnu.org/projects/administration');


print '<h3>'._('Exporting Subversion tree from Savannah').'</h3>

<p>'
._('You can access your subversion raw repository using read-only access via
rsync, and then use that copy as a local svn repository:').'</p>

<pre>
rsync -avHS rsync://svn.<?php echo $project->getTypeBaseHost(); ?>/svn/'
.$project->getUnixName().'/ /tmp/'.$project->getUnixName().'.repo/
svn co file:///tmp/'.$project->getUnixName().'.repo/ trunk
# ...
</pre>

<p>'._('If you want a dump you can also use svnadmin:').'</p>

<pre>
svnadmin dump /tmp/'.$project->getUnixName().'.repo/
</pre>
';
?>
