<?php

# Instructions about Arch usage.
#
# Copyright (C) 2005, 2011 Michael J. Flickinger
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

  print '<h3>'._('Anonymous Arch Access').'</h3>
<p>'
._("This project's Arch repository can be accessed through http.")
.'</p>

';

  print '<pre>tla register-archive http://arch.savannah.gnu.org/archives/'
        .$project->getUnixName().'</pre>

<h3>'._('Project Member Arch Access').'</h3>
<p>'
._("This project's Arch repository can be accessed throgh sftp for project members.")
.'</p>

<p>'
._('The SSHv2 public key fingerprints for the machine hosting the cvs
trees are:').'</p>

<pre>
RSA: 1024 80:5a:b0:0c:ec:93:66:29:49:7e:04:2b:fd:ba:2c:d5
DSA: 1024 4d:c8:dc:9a:99:96:ae:cc:ce:d3:2b:b0:a3:a4:95:a5
</pre>

';

$username = user_getname();
if ($username == "NA") {
        // for anonymous user :
        $username = '&lt;<em>'._('membername').'</em>&gt;';
}

  print '<pre>tla register-archive sftp://'
        .$username.'@arch.sv.gnu.org/archives/'.$project->getUnixName()
        .'</pre>

';

  print '<h3>'._('More Information').'</h3>

';
  printf ('<p>'._('For more information, see %s.'
.'</p>
'), '<a href="http://arch.sv.gnu.org/">http://arch.sv.gnu.org/</a>');

?>
