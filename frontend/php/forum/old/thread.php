<?php
// This file is part of the Savane project
// <http://gna.org/projects/savane/>
//
// $Id$
//
//  Copyright 1999-2000 (c) The SourceForge Crew
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

if ($thread_id) {

	if ($post_message == "y") {
		post_message($thread_id, $is_followup_to, $subject, $body, $forum_id);
	}

	/*
		Set up navigation vars
	*/
	$result=db_query("SELECT forum_group_list.group_id,forum_group_list.forum_name,forum.group_forum_id ".
		"FROM forum_group_list,forum WHERE forum_group_list.group_forum_id=forum.group_forum_id AND forum.thread_id='$thread_id'");

	$group_id=db_result($result,0,'group_id');
	$forum_id=db_result($result,0,'group_forum_id');
	$forum_name=db_result($result,0,'forum_name');

	forum_header(array('title'=>'View Thread'));

	echo show_thread($thread_id,$et);

	echo '<P>&nbsp;<P>';
	echo '<CENTER><h3>Post to this thread</H3></CENTER>';
	show_post_form($forum_id,$thread_id,$is_followup_to,$subject);

	forum_footer(array());

} else {

	forum_header(array('title'=>'Choose Thread'));
	echo '<H2>Sorry, Choose A Thread First</H2>';
	forum_footer(array());

}

?>
