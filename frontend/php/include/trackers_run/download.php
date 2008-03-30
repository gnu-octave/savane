<?php
# <one line to give a brief idea of what this does.>
# 
#  Copyright 2001-2002 (c) Laurent Julliard, CodeX Team, Xerox
#
# Copyright 2002-2005 (c) Mathieu Roy <yeupou--gnu.org>
#
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


# init.php was already loaded
register_globals_off();

extract(sane_import('get', array('file_id', 'item_file_id')));

# Backward compat?
$file_id = $file_id or $item_file_id;

# check if the provided file_id is a valid numerical id
if (empty($file_id) or !ctype_digit($file_id))
{
  exit_missing_param();
}

# ---
# check privacy of the item this file is attached to and reject access by
# non-authorized users

$result = db_execute("SELECT trackers_file.item_id, ".ARTIFACT.".group_id FROM trackers_file, ".ARTIFACT." WHERE trackers_file.file_id=? AND ".ARTIFACT.".bug_id=trackers_file.item_id", array($file_id));
                                                                                
if ($result && db_numrows($result) > 0) {
  $item_id  = db_result($result,0,'item_id');
  $group_id = db_result($result,0,'group_id');
}
$result = db_execute("SELECT privacy FROM ".ARTIFACT." WHERE bug_id=? AND group_id=?",
		     array($item_id, $group_id));

# print "FID = ".$file_id." ITID = ".$item_id." UID = ".user_getid()."\n";
                                                                                
if (db_numrows($result) > 0) {
  if ((db_result($result,0,'privacy') == '2') &&
      !member_check_private(0, $group_id)) {
    exit_error(_("Non-authorized access to file attached to private item"));
  }
}
# ---

$result = db_execute("SELECT filename,filesize FROM trackers_file WHERE file_id=? LIMIT 1",
		     array($file_id));

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
