<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: faq.php 4567 2005-06-30 17:19:37Z toddy $
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

function faq_print_html($question)
{
   $GLOBALS[sys_debug_where] = __FILE__.':'.__LINE__.':print_faq($faqdir, $gr_id, $question)';

   $faqdir = $GLOBALS['sys_incdir'].'/faq';

   if (!($question_text = utils_read_file($faqdir."/".$question)))
     return false;
   $question_text = ereg_replace("\\\\\"", "\"", $question_text);
   $question_text = ereg_replace("\\\\'", "'", $question_text);
   $question_text = ereg_replace("@", "at###at", $question_text);
   $question_text = ereg_replace("\\\$sys_https_url", $GLOBALS['sys_https_url'], $question_text);
   $question_text = ereg_replace("\\\$sys_email_adress", $sys_email_adress, $question_text);
   $question_text = utils_make_links($question_text);
   $question_text = ereg_replace("at###at", "@", $question_text);

   if($question_text)
     {
       print '<h3><a name="'.$question.'"></a>'.faq_filename2question($question).'</h3>';
       echo $question_text;
     }
   return true;
}

# Get a list of names of files that contain a frequently asked question
function faq_get_files()
{
  static $result = array();
  if ($result)
    return $result;
  $files = array();
  $faqdir = $GLOBALS['sys_incdir'].'/faq';

  # f 'files.txt' exist, use the file names in it
  if(file_exists("$faqdir/files.txt"))
    {
      $handle = fopen("$faqdir/files.txt", 'r');
      while ($file = fgets($handle, 1024))
	{
	  $file = rtrim($file, "\n");
	  array_push($files, $file);
	}
      fclose($handle);
    }
  elseif(file_exists($faqdir))
    {
      $dir = opendir($faqdir);
      while ($file = readdir($dir))
	{
	  array_push($files, $file);
	}
      closedir($dir);
      sort($files);
      reset($files);
    }

  # Filters uninteresting files
  foreach ($files as $file)
    {
      if ($file != "." &&
	  $file != ".." &&
	  $file != "CVS" &&
	  $file != "admin" && 
	  $file != "files.txt" && 
	  $file != "index.php" &&
	  substr($file, 0, 1) != '#') 
	{
	  array_push($result, $file);
	}
    }
  return $result;
}

function faq_filename2question($filename) {
  $question = ereg_replace(".txt", "", $filename);	
  $question = ereg_replace("_", " ", $question);
  return $question . "?";
}
