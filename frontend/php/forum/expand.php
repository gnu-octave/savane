<?php
// This file is part of the Savane project
// <http://gna.org/projects/savane/>
//
// $Id$
//
// Copyright 1999-2000 (c) The SourceForge Crew
//
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


require "../include/pre.php";

if (user_isloggedin()) {

	user_set_preference('forum_expand',$et);

	/*
		Set up navigation vars
	*/
	$result=db_query("SELECT group_id,forum_name,is_public FROM forum_group_list WHERE group_forum_id='$forum_id'");

	$group_id=db_result($result,0,'group_id');
	$forum_name=db_result($result,0,'forum_name');

	forum_header(array('title'=>'Expand/Collapse Threads'));

	echo '
		<H1>Preference Set</H!>';

	if ($et==1) {
		echo '<P>Threads will now be expanded';
	} else {
		echo '<P>Threads will now be collapsed';
	}

	forum_footer(array());

} else {
	exit_not_logged_in();
}

?>
