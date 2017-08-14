<?php

# Copyright (C) 2002, 2003 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2005, 2006, 2007, 2009 Sylvain Beucler
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

# With this file, you can adapt the hashes used by your savannah.
# For instance, you can modify here the set of license available.
# Remove the license you do not want to be available for your user.
#
# In the future, this file will disappear: you'll just type the short names
# of licenses availables for a type of project via the web interface.
# Every informations about it will be directly part of Savannah code.


# CAUTION: IT'S VERY IMPORTANT THAT NO HTML BLANK LINE APPEARS ON THIS
# FILE.
# DO NOT PUT ANY WHITESPACE OUTSIDE PHP TAGS
# OR MANY REDIRECTIONS WILL NOT WORK

$LICENSE['gpl'] = _('GNU General Public License v2 or later');
$LICENSE['lgpl'] = _('GNU Lesser General Public License');
$LICENSE['fdl'] = _('GNU Free Documentation License');
$LICENSE['mbsd'] = _('Modified BSD License');
$LICENSE['x11'] = _('X11 license');
$LICENSE['cryptix'] = _('Cryptix General License');
$LICENSE['zlib'] = _('The license of ZLib');
$LICENSE['imatrix'] = _('The license of the iMatix Standard Function Library');
$LICENSE['w3c'] = _('The W3C Software Notice and License');
$LICENSE['berkeley'] = _('The Berkeley Database License');
$LICENSE['python16'] = _('The License of Python 1.6a2 and earlier versions');
$LICENSE['python2'] = _('The License of Python 2.0.1, 2.1.1, and newer versions');
$LICENSE['cartistic'] = _('The Clarified Artistic License');
$LICENSE['perl'] =
  _('The license of Perl (disjunction of the Artistic License and the GNU GPL)');
$LICENSE['expat'] = _('Expat License (sometime refered to as MIT License)');
$LICENSE['affero'] = _('Affero General Public License v1 or later');
$LICENSE['classpath'] =
  _('GNU General Public License v2 or later with GNU Classpath special exception');
$LICENSE['public domain'] = _('Public domain');
$LICENSE['website'] = _('WebSite Only');
$LICENSE['other'] = _('Other license');
$LICENSE['dual-gpl'] =
  _('GNU General Public License v2 or later (+ dual licensing)');
$LICENSE['gplv3orlater'] = _('GNU General Public License v3 or later');
$LICENSE['agpl'] = _('GNU Affero General Public License v3 or later');
$LICENSE['apache2'] = _('Apache 2.0');

/* should be equal to '0' when no url exists */
$LICENSE_URL['gpl'] = '//www.gnu.org/licenses/gpl-2.0.html';
$LICENSE_URL['lgpl'] = '//www.gnu.org/licenses/lgpl-2.1.html';
$LICENSE_URL['fdl'] = '//www.gnu.org/copyleft/fdl.html';
$LICENSE_URL['mbsd'] = 'http://www.xfree86.org/3.3.6/COPYRIGHT2.html#5';
$LICENSE_URL['x11'] = 'http://www.xfree86.org/3.3.6/COPYRIGHT2.html#3';
$LICENSE_URL['cryptix'] = 'http://www.cryptix.org/docs/license.html';
$LICENSE_URL['zlib'] = 'ftp://ftp.freesoftware.com/pub/infozip/zlib/zlib_license.html';
$LICENSE_URL['imatrix'] = '0';
$LICENSE_URL['w3c'] = 'http://www.w3.org/Consortium/Legal/copyright-software.html';
$LICENSE_URL['berkeley'] = 'http://www.sleepycat.com/license.net';
$LICENSE_URL['python16'] = '//www.python.org/doc/Copyright.html';
$LICENSE_URL['python2'] = '//www.python.org/2.0.1/license.html';
$LICENSE_URL['cartistic'] = 'http://www.statistica.unimib.it/utenti/dellavedova/software/artistic2.html';
$LICENSE_URL['perl'] = '//www.gnu.org/philosophy/license-list.html#PerlLicense';
$LICENSE_URL['expat'] = '//www.gnu.org/licenses/license-list.html#Expat';
$LICENSE_URL['affero'] = '//www.affero.org/oagpl.html';
$LICENSE_URL['classpath'] = '//www.gnu.org/software/classpath/license.html';
$LICENSE_URL['public domain'] = '0';
$LICENSE_URL['website'] = '0';
$LICENSE_URL['other'] = '0';
$LICENSE_URL['dual-gpl'] = '//www.gnu.org/licenses/gpl-2.0.html';
$LICENSE_URL['gplv3orlater'] = '//www.gnu.org/licenses/gpl-3.0.html';
$LICENSE_URL['agpl'] = '//www.gnu.org/licenses/agpl-3.0.html';
$LICENSE_URL['apache2'] = '//directory.fsf.org/wiki/License:Apache2.0';

$DEVEL_STATUS[0] = _('0 - Undefined');
$DEVEL_STATUS[1] = _('1 - Planning');
$DEVEL_STATUS[2] = _('2 - Pre-Alpha');
$DEVEL_STATUS[3] = _('3 - Alpha');
$DEVEL_STATUS[4] = _('4 - Beta');
$DEVEL_STATUS[5] = _('5 - Production/Stable');
$DEVEL_STATUS[6] = _('6 - Mature');
# 7 must be kept untouched because it has been used on several installation
# for 'N/A'
# $DEVEL_STATUS[7] = 'N/A';
$DEVEL_STATUS[8] = _('? - Orphaned/Unmaintained');
