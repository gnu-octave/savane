<?php

# Instructions about Git usage.
#
# Copyright (C) 2007 Sylvain Beucler
# Copyright (C) 2013, 2017 Ineiev <ineiev@gnu.org>
# Copyright (C) 2017 Bob Proulx
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

exec ("grep -A 3 '^repo\.url=" . $project->getUnixName()
      . "/' /etc/savane/cgitrepos", $output);
$n = intval((count ($output) + 1) / 5);
if ($n > 0)
  {
    echo "<p>"._('Note: this group has multiple Git repositories.')."</p>";
    $main_desc = exec ("grep -A 2 '^repo\.url=" . $project->getUnixName()
                       . "\.git' /etc/savane/cgitrepos");
    $main_desc = preg_replace(':repo.desc=:', '', $main_desc) . "\n";
  }
print '
<h2>'._('Anonymous clone:').'</h2>

<pre>';

if ($n > 0)
  echo $main_desc;

echo 'git clone https://git.' . $project->getTypeBaseHost() . "/git"
  . preg_replace(':/srv/git:', '', $project->getTypeDir('git')). "\n";

for ($i = 0; $i < $n; $i++)
  {
    $url[$i] = preg_replace(':repo.url=:', '', $output[$i * 5]);
    $repo[$i] = preg_replace(':repo.path=:', '', $output[$i * 5 + 1]);
    $desc[$i] = preg_replace(':repo.desc=:', '', $output[$i * 5 + 2]);
  }
for ($i = 0; $i < $n; $i++)
  {
    echo "\n" . $desc[$i] . "\n";
    echo "git clone https://git."
         .  $project->getTypeBaseHost() . "/git/" . $url[$i] . "\n";
  }

print '</pre>

<h2>'._('Member clone:').'</h2>

<pre>';

$username = user_getname();
if ($username == "NA")
  # For anonymous user.
  $username = '&lt;<i>'._('membername').'</i>&gt;';
if ($n > 0)
  echo $main_desc;

echo "git clone " . $username . "@git.sv.gnu.org:"
     .  $project->getTypeDir('git') . "\n";
for ($i = 0; $i < $n; $i++)
  {
    echo "\n" . $desc[$i] . "\n";
    echo "git clone " . $username . "@git.sv.gnu.org:" . $repo[$i] . "\n";
  }
print '
</pre>

<h2>'._('More information').'</h2>
<a href="//savannah.gnu.org/maintenance/UsingGit">
https://savannah.gnu.org/maintenance/UsingGit</a>';

?>
