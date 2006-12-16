<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: export_html.php 4567 2005-06-30 17:19:37Z toddy $
#
#  Copyright 2002-2003 (c) Mathieu Roy <yeupou--at--gnu.org>
#  Copyright 2005      (c) Sylvain Beucler <beuc--at--beuc.net>
#
# The Savane project is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# The Savane project is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


require "../include/pre.php";
require "../include/faq.php";

$group_id = $GLOBALS[sys_group_id];

$project=project_get_object($group_id);
$FAQ_DIR = $GLOBALS['sys_incdir'].'/faq';


# function readFileIntoBuffer($filename)
# {
##   $GLOBALS[sys_debug_where] = __FILE__.':'.__LINE__.':readFileIntoBuffer($filename)';

#   @$fp = fopen($filename, "r");
#   if ($fp)
#     {
#       $val = fread($fp, filesize($filename));
#       fclose ($fp);
#       return $val;
#     }
#   return false;
# }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head>
    <title><?php print $GLOBALS[sys_name]." Frequently Asked Questions"; ?></title>
    <meta name="Generator" content="The Savannah FAQ system http://savannah.gnu.org">
</head>
<body>

<h1><?php print $GLOBALS[sys_name]." Frequently Asked Questions"; ?></h1>

<p><?php print $project->getDescription();?>

<p><?php
# TOC
foreach (faq_get_files() as $file)
{
  print '<a href="#'.$file.'">'.faq_filename2question($file)."</a><br />\n";
}
?>
<p>
<?php
foreach (faq_get_files() as $question)
{
  faq_print_html($question);
}
?>

</body>
</html>
