<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2001-2002 (c) Laurent Julliard, CodeX Team, Xerox
#
#  Copyright 2002-2005 (c) Mathieu Roy <yeupou--gnu.org>
#
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

# Pre was already loaded
register_globals_off();

$file_id = sane_all("file_id");
if (sane_isset($item_file_id)) 
{ $file_id = sane_all("item_file_id"); }

# check if the provided file_id is a valid numerical id
if (!$file_id || !ctype_digit($file_id))
{
  exit_missing_param();
}

# ---
# check privacy of the item this file is attached to and reject access by
# non-authorized users

$sql="SELECT trackers_file.item_id, ".ARTIFACT.".group_id FROM trackers_file, ".ARTIFACT." WHERE trackers_file.file_id='$file_id' AND ".ARTIFACT.".bug_id=trackers_file.item_id";
$result=db_query($sql);
                                                                                
if ($result && db_numrows($result) > 0) {
  $item_id  = db_result($result,0,'item_id');
  $group_id = db_result($result,0,'group_id');
}
$sql="SELECT privacy FROM ".ARTIFACT." WHERE bug_id='$item_id' AND group_id='$group_id'";
$result=db_query($sql);

# print "FID = ".$file_id." ITID = ".$item_id." UID = ".user_getid()."\n";
                                                                                
if (db_numrows($result) > 0) {
  if ((db_result($result,0,'privacy') == '2') &&
      !member_check_private(0, $group_id)) {
    exit_error(_("Non-authorized access to file attached to private item"));
  }
}
# ---

$sql="SELECT filename,filesize FROM trackers_file WHERE file_id='$file_id' LIMIT 1";
$result=db_query($sql);

if ($result && db_numrows($result) > 0) 
{

  if (db_result($result,0,'filesize') == 0) 
    {
      
      exit_error(_("Nothing in here, file has a null size"));
      
    } 
  else 
    {
      # Redirect to an url that will pretend the file really exists with
      # this name, so all browsers will propose its name as filename when
      # saving it.
      session_redirect($GLOBALS['sys_home'].'file/'.rawurlencode(db_result($result,0,'filename')).'?file_id='.$file_id);

    }

} 
else
{
  exit_error(_("Couldn't find attached file")." (file #$file_id)");
}

?>
