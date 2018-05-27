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

include $GLOBALS['sys_incdir'].'/php/fingerprints.php';

global $project;

print '<h2>'._('Anonymous Arch Access').'</h2>
<p>'
._("This project's Arch repository can be accessed through HTTP.")
.'</p>

';

print '<pre>tla register-archive http://arch.savannah.gnu.org/archives/'
        .$project->getUnixName().'</pre>

<h2>'._('Project Member Arch Access').'</h2>
<p>'
._("This project's Arch repository can be accessed throgh SFTP for project members.")
.'</p>

<p>'
._('The SSHv2 public key fingerprints for the machine hosting the Arch
trees are:').'</p>'.$vcs_fingerprints;

$username = user_getname();
if ($username == "NA")
  # For anonymous user.
  $username = '&lt;<i>'._('membername').'</i>&gt;';

print '<pre>tla register-archive sftp://'
      .$username.'@arch.sv.gnu.org/archives/'.$project->getUnixName()
      .'</pre>

';

print '<h2>'._('More Information').'</h2>

';
printf ('<p>'._('For more information, see %s.').'</p>',
   '<a href="//arch.sv.gnu.org/">https://arch.sv.gnu.org/</a>');
?>
