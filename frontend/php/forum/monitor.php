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


require "../include/pre.php";

if (user_isloggedin()) {
	/*
		User obviously has to be logged in to monitor
		a thread
	*/

	if ($forum_id) {
		/*
			First check to see if they are already monitoring
			this thread. If they are, say so and quit.
			If they are NOT, then insert a row into the db
		*/

		/*
			Set up navigation vars
		*/
		$result=db_query("SELECT group_id,forum_name,is_public FROM forum_group_list WHERE group_forum_id='$forum_id'");

		$group_id=db_result($result,0,'group_id');
		$forum_name=db_result($result,0,'forum_name');

		forum_header(array('title'=>'Monitor a forum'));

		echo '
			<H2>Monitor a Forum</H2>';

		$sql="SELECT * FROM forum_monitored_forums WHERE user_id='".user_getid()."' AND forum_id='$forum_id';";

		$result = db_query($sql);

		if (!$result || db_numrows($result) < 1) {
			/*
				User is not already monitoring thread, so 
				insert a row so monitoring can begin
			*/
			$sql="INSERT INTO forum_monitored_forums (forum_id,user_id) VALUES ('$forum_id','".user_getid()."')";

			$result = db_query($sql);

			if (!$result) {
				echo "<p style=error>Error inserting into forum_monitoring</p>";
			} else {
				echo "<p style=error>Forum is now being monitored</p>";
				echo "<P>You will now be emailed followups to this entire forum.";
				echo "<P>To turn off monitoring, simply click the <strong>Monitor Forum</strong> link again.";
			}

		} else {

			$sql="DELETE FROM forum_monitored_forums WHERE user_id='".user_getid()."' AND forum_id='$forum_id';";
			$result = db_query($sql);
			echo "<p style=error>Monitoring has been turned off</p>";
			echo "<P>You will not receive any more emails from this forum.";
		}
		forum_footer(array());
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
