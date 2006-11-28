<?php
// This file is part of the Savane project
// <http://gna.org/projects/savane/>
//
// $Id$
//
// Copyright 1999-2000 (c) The SourceForge Crew
//
// The Savane project is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// The Savane project is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with the Savane project; if not, write to the Free Software
// Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
//
//
//
//

require "../include/pre.php";

if (!$group_id)
{
	exit_no_group();	
}
site_project_header(array('group'=>$group_id,'context'=>'forum'));


if (user_isloggedin() && user_ismember($group_id)) {
	$public_flag='0,1';
} else {
	$public_flag='1';
}

//$sql="SELECT * FROM forum_group_list WHERE group_id='$group_id' AND is_public IN ($public_flag);";

$sql="SELECT forum_group_list.* ".
"FROM forum_group_list ".
"LEFT JOIN news_bytes ".
"ON forum_group_list.group_forum_id=news_bytes.forum_id ".
"WHERE news_bytes.forum_id IS  NULL ".
"AND forum_group_list.group_id='$group_id' ".
"AND is_public IN ($public_flag)";

$result = db_query ($sql);

$rows = db_numrows($result); 

if (!$result || $rows < 1) {
	echo '<H1>No forums found for '.group_getname($group_id).'</H1>';
	forum_footer(array());
	exit;
}

echo '<H3>Discussion Forums</H3>
		<P>Choose a forum and you can browse, search, and post messages.<P>';

/*
		Put the result set (list of forums for this group) into a column with folders
*/

for ($j = 0; $j < $rows; $j++) { 
	echo '<A HREF="'.$GLOBALS['sys_home'].'forum/forum.php?forum_id='.db_result($result, $j, 'group_forum_id').'">'.
		'<IMG SRC="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/directory.png" HEIGHT=24 WIDTH=24 BORDER=0> &nbsp;'.
		db_result($result, $j, 'forum_name').'</A> ';
	//message count
	echo '('.db_result(db_query("SELECT count(*) FROM forum WHERE group_forum_id='".db_result($result, $j, 'group_forum_id')."'"),0,0).' msgs)';
	echo "<BR>\n";
	echo db_result($result,$j,'description').'<P>';
}
forum_footer(array());

?>
