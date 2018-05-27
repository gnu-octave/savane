<?php

# Instructions about Hg usage.
#
# Copyright (C) 2008 Sylvain Beucler
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

print '<h2>'._('Anonymous checkout:').'</h2>

<pre>hg clone http://hg.'
  .$project->getTypeBaseHost() . '/hgweb'
  .preg_replace(':/srv/hg:', '', $project->getTypeDir('hg')).'
</pre>

<h2>'._('More information').'</h2>

<p><a href="//savannah.gnu.org/maintenance/UsingHg">
https://savannah.gnu.org/maintenance/UsingHg</a></p>

';

?>
