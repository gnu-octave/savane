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

if (user_isloggedin()) {
	/*
		User obviously has to be logged in to save place 
	*/

	if ($forum_id) {
		/*
			First check to see if they already saved their place 
			If they have NOT, then insert a row into the db

			ELSE update the time()
		*/

		/*
			Set up navigation vars
		*/
		$result=db_query("SELECT group_id,forum_name,is_public FROM forum_group_list WHERE group_forum_id='$forum_id'");

		$group_id=db_result($result,0,'group_id');
		$forum_name=db_result($result,0,'forum_name');

		forum_header(array('title'=>'Save your place'));

		echo '
			<H2>Save Your Place</H2>';

		$sql="SELECT * FROM forum_saved_place WHERE user_id='".user_getid()."' AND forum_id='$forum_id'";

		$result = db_query($sql);

		if (!$result || db_numrows($result) < 1) {
			/*
				User is not already monitoring thread, so 
				insert a row so monitoring can begin
			*/
			$sql="INSERT INTO forum_saved_place (forum_id,user_id,save_date) VALUES ('$forum_id','".user_getid()."','".time()."')";

			$result = db_query($sql);

			if (!$result) {
				echo "<p class=error>Error inserting into forum_saved_place</p>";
				echo db_error();
			} else {
				echo "<p class=error>Your place was saved</p>";
				echo "<P>New messages will be highlighted when you return.</p>";
			}

		} else {
			$sql="UPDATE forum_saved_place SET save_date='".time()."' WHERE user_id='".user_getid()."' AND forum_id='$forum_id'";
			$result = db_query($sql);

			if (!$result) {
				echo "<p class=error>Error updating time in forum_saved_place</p>";
				echo db_error();
			} else {
				echo "<p class=error>Your place was saved</p>";
				echo "<p>New messages will be highlighted when you return.</p>";
			}
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
