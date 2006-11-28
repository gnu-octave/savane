<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
# Copyright 2003      (c) ???
#
# Copyright 2003-2005 (c) Marcus Hardt
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

if (user_isloggedin()) {
 	/*
		User has to be logged in to see who's monitoring this forum as well.
	*/
	#	global $group_id,$forum_id;

	if ($forum_id) {
		/*
		              Set up navigation vars
		*/
		$sql="SELECT group_id,forum_name,is_public FROM forum_group_list WHERE group_forum_id='$forum_id'";
		$result=db_query($sql);
		$group_id=db_result($result,0,'group_id');
		$forum_name=db_result($result,0,'forum_name');

		/*
			Get the people who're monitoring this forum
		*/
		$sql="SELECT user.realname, user.user_name FROM forum_monitored_forums, user WHERE forum_monitored_forums.user_id=user.user_id AND forum_monitored_forums.forum_id='$forum_id'";
		$result=db_query($sql);
		$rows=db_numrows($result);
		if (!$result || db_numrows($result) < 1) {
			forum_header(array('title'=>'Error: Nobody monitoring'));
			echo 'Nobody is currently monitoring this forum';
			forum_footer(array());
		} else {
			forum_header(array('title'=>$forum_name));
	                utils_show_result_set($result,'Currently monitoring:');
		}
	} else {
		forum_header(array('title'=>'Choose a forum First'));
		echo '
			<H1>Error - Choose a forum First</H1>';
		forum_footer(array());
	}
} else {
	exit_not_logged_in();
}
?>


