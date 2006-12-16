<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: export_texinfo.php 4567 2005-06-30 17:19:37Z toddy $
#
#  Copyright 2002-2003 (c) Mathieu Roy <yeupou--at--gnu.org>
#  Copyright 2005      (c) Sylvain Beucler <beuc--at--beuc.net>
#  Copyright 2005      (c) Aaron S. Hawley <aaron.hawley--at--uvm.edu>
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

header("Content-type: text/plain");

require "../include/pre.php";
require "../include/faq.php";

$group_id = $GLOBALS[sys_group_id];

$project=project_get_object($group_id);
$FAQ_DIR = $GLOBALS['sys_incdir'].'/faq';
$project_title = $GLOBALS[sys_name];

?>
\input texinfo                @c -*- Texinfo -*-
@c %**start of header
@setfilename <?php print str_replace(" ", "-", $project_title." FAQ"); ?>.info
@settitle @code{<?php print $project_title." FAQ"; ?>}
@c %**end of header

@copying
<?php print $project->getDescription() . "\n";?>

Copyright @copyright{} <?php print date("Y") ?>  <?php print $project->getPublicName()."'s project author(s)\n"; ?>
@end copying

@titlepage
@title <?php print $project_title." Frequently Asked Questions"; ?>

@subtitle <?php print $project->getDescription(); ?>

@subtitle @code{<?php print $project_title." FAQ"; ?>} Version <?php print date("Ymd"); ?>

@subtitle <?php print date("F j, Y"); ?>

@author the <?php print $project->getPublicName()."'s project author(s)"; ?>

@page
@vskip 0pt plus 1filll
@insertcopying
@end titlepage

@contents

@ifnottex
@node Top
@top <?php print $project_title." FAQ"; ?>


@insertcopying
@end ifnottex

@menu
<?php
$files = faq_get_files();
for ($i = 0; $i < count($files); $i++)
{
  $question = faq_filename2question($files[$i]);
  echo '* Q'.($i+1)."::$question\n";
}
?>
@end menu

<?php
$files = faq_get_files();
for ($i = 0; $i < count($files); $i++)
{
  $question = faq_filename2question($files[$i]);
  echo '@node Q'.($i+1)."\n";
  echo '@chapter '.$question."\n";
  
  $content = utils_read_file($FAQ_DIR."/".$files[$i]);
  # remove HTML tags
  $content = eregi_replace("<[^>]*>", "", $content);
  # quote special Texinfo characters
  $content = eregi_replace("([@\{\}])", "@\\1", $content);
  # put a link for emails
  $content = eregi_replace("([_a-z0-9-]+(\.[_a-z0-9-]+)*@@[a-z0-9-]+(\.[a-z0-9-]+)+)", "@email{\\1}", $content);
  # put a link for urls
  $content = eregi_replace("([[:alnum:]]+)://([^[:space:]]*)([[:alnum:]#?/&=])", "@uref{\\1://\\2\\3,,\\1://\\2\\3}", $content);
  # enforce empty-line space
  echo $content . "\n\n";
}
?>

@bye
