<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#  Copyright 2000-2003 (c) Free Software Foundation
#
#  Copyright 2004      (c) ...
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

exit;

if (user_isloggedin()) {

	if ($subfunc=="mod") {

		if ($delete_filter) {

			$result=db_query("DELETE FROM bug_filter WHERE filter_id='$filter_id' AND user_id='".user_getid()."' AND group_id='$group_id'");

			if (!$result) {
				bug_header(array ("title"=>"Filter Delete Failed"));
				echo "<H1>Delete failed</H2>";
				echo db_error();
				bug_footer(array());
				exit;
			} else {
				" Successfully Deleted Filter ";
			}

		} else {
			$sql_clause=eregi_replace('drop','',$sql_clause);
			$sql_clause=eregi_replace('update','',$sql_clause);
			$sql_clause=eregi_replace('insert','',$sql_clause);
			$sql_clause=eregi_replace('delete','',$sql_clause);

			/*
				Set other filters for this user/group to inactive
			*/
			$toss=db_query("UPDATE bug_filter SET is_active='0' WHERE user_id='".user_getid()."' AND group_id='$group_id'");

			/*
				Update the sql_clause and make it active
			*/
			$sql="UPDATE bug_filter SET sql_clause='$sql_clause',is_active='1' WHERE filter_id='$filter_id' AND user_id='".user_getid()."'";
			$result=db_query($sql);
			if (!$result) {
				bug_header(array ("title"=>"Filter Update Failed"));
				echo "<H1>Update failed</H2>";
				echo db_error();
				bug_footer(array());
				exit;
			} else {
				" Successfully Modified Filter ";
			}

		}

	} else if ($subfunc=="add") {

		/*
			Set other filters for this user/group to inactive
		*/
		$toss=db_query("UPDATE bug_filter SET is_active='0' WHERE user_id='".user_getid()."' AND group_id='$group_id'");

		$sql_clause=eregi_replace('drop','',$sql_clause);
		$sql_clause=eregi_replace('update','',$sql_clause);
		$sql_clause=eregi_replace('insert','',$sql_clause);
		$sql_clause=eregi_replace('delete','',$sql_clause);

		/*
			Add the new filter
		*/
		$sql="INSERT INTO bug_filter (user_id,group_id,sql_clause,is_active) VALUES ('".user_getid()."','$group_id','$sql_clause','1')";
		$result=db_query($sql);
		if (!$result) {
			bug_header(array ("title"=>"Filter Add Failed"));
			echo "<H1>Add failed</H2>";
			echo db_error();
			bug_footer(array());
			exit;
		} else {
			" Successfully Added Filter ";
		}

	} else if ($subfunc=="turn_off") {
		/*
			Set all filters for this user/group to inactive
		*/
		$toss=db_query("UPDATE bug_filter SET is_active='0' WHERE user_id='".user_getid()."' AND group_id='$group_id'");

		" Turned Off Filters ";

	} else {

		bug_header(array ("title"=>"Filter Update Failed"));
		echo "<H1>We are in a F.U.B.A.R. state</H2>";
		bug_footer(array());
		exit;

	}

} else {

	exit_not_logged_in();

}
