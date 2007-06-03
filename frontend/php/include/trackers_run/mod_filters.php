<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
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

function show_filters ($group_id)
{
	/*
		The goal here is to show any existing bug filters for this user/group combo.
		In addition, we are going to show an empty row where a new filter can be created
	*/
	$sql="SELECT * FROM bug_filter WHERE user_id='".user_getid()."' AND group_id='$group_id'";
	$result=db_query($sql);

	echo '<TABLE BORDER="0" CELLSPACING="1" CELLPADDING="2">';

	if ($result && db_numrows($result) > 0) {
		for ($i=0; $i<db_numrows($result); $i++) {
			/*
				iterate and show the existing filters
			*/
			?>
			<FORM ACTION="<?php echo $_SERVER['PHP_SELF']; ?>" METHOD="POST">
			<INPUT TYPE="HIDDEN" NAME="func" VALUE="postmodfilters">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
			<INPUT TYPE="HIDDEN" NAME="subfunc" VALUE="mod">
			<INPUT TYPE="HIDDEN" NAME="filter_id" VALUE="<?php
				echo db_result($result,$i,"filter_id");
			?>">
			<TR class="<?php echo utils_get_alt_row_color($i); ?>">
				<TD>
					<span class="smaller"><INPUT TYPE="SUBMIT" NAME="delete_filter" VALUE="Delete"><BR>
					<INPUT TYPE="SUBMIT" NAME="submit" VALUE="Modify/Activate">
                                        </span>
				</TD>
				<TD NOWRAP><span class="smaller">SELECT * FROM bug WHERE<BR>bug.group_id='<?php echo $group_id; ?>' AND (</span></TD>
				<TD NOWRAP><span class="smaller"><INPUT TYPE="TEXT" SIZE="60" MAXLENGTH="250" NAME="sql_clause" VALUE="<?php
						echo stripslashes(db_result($result,$i,"sql_clause"));
					?>"></span></TD>
				<TD NOWRAP><span class="smaller">) LIMIT 0,50</span></TD>
			</TR></FORM>
			<?php

		}
	}

	/*
		empty form for new filter
	*/

	?>
	<FORM ACTION="<?php echo $_SERVER['PHP_SELF']; ?>" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="postmodfilters">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
	<INPUT TYPE="HIDDEN" NAME="subfunc" VALUE="add">
	<TR class="<?php echo utils_get_alt_row_color($i); ?>">
		<TD><span class="smaller"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Add"></span></TD>
		<TD NOWRAP><span class="smaller">SELECT * FROM bug WHERE<BR>bug.group_id='<?php echo $group_id; ?>' AND (</span></TD>
		<TD NOWRAP><span class="smaller"><INPUT TYPE="TEXT" SIZE="60" MAXLENGTH="250" NAME="sql_clause" VALUE="bug.status_id IN (1,2,3) OR bug.severity > 0 OR bug.bug_group_id IN (1,2,3,4) OR bug.resolution_id IN (1,2,3) OR bug.assigned_to IN (1,2,3,4,5,6) OR bug.category_id IN (1,2,3)"></span></TD>
		<TD NOWRAP><span class="smaller">) LIMIT 0,50</span></TD>
	</TR></FORM>
	</TABLE>
	<P>
	<FORM ACTION="<?php echo $_SERVER['PHP_SELF']; ?>" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="postmodfilters">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
	<INPUT TYPE="HIDDEN" NAME="subfunc" VALUE="turn_off">
	<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Deactivate Filters">
	</FORM>
<?php

}


bug_header(array ('title'=>'Create a Personal Filter'));

if (user_isloggedin()) {

	echo "<H2>Create a personal filter for ".user_getname()."</H2>";
	echo "<strong>Creating or modifying a filter makes it your active filter</strong><P>";
	echo "Be sure include 'bug.' before each field name, as in the example, as multiple tables are being joined in the query";

	show_filters($group_id);

	$sql="SELECT user.user_id,user.user_name FROM user,user_group WHERE user.user_id=user_group.user_id AND user_group.bug_flags IN (1,2) AND user_group.group_id='$group_id'";
	$result=db_query($sql);

	$sql="select * from bug_status";
	$result2=db_query($sql);

	$sql="select bug_category_id,category_name from bug_category WHERE group_id='$group_id'";
	$result3=db_query($sql);

	$sql="select * from bug_resolution";
	$result4=db_query($sql);

	$sql="select bug_group_id,group_name from bug_group WHERE group_id='$group_id'";
	$result5=db_query($sql);

	?>
	<TABLE WIDTH="100%" CELLPADDING="3">
		<TR>
			<TD  COLSPAN="3">
				<strong>The following tables show which statuses, technicians, and categories you can include in your filter.
			</TD>
		</TR>
		<TR>
			<TD  VALIGN="TOP"><?php utils_show_result_set($result,"Bug Techs for ".group_getname($group_id)); ?></TD>
			<TD  VALIGN="TOP"><?php utils_show_result_set($result2,"Bug Statuses"); ?></TD>
			<TD  VALIGN="TOP"><?php utils_show_result_set($result3,"Bug Categories for ".group_getname($group_id)); ?></TD>
		<TR>
		<TR>
			<TD  VALIGN="TOP"><?php utils_show_result_set($result4,"Bug Resolutions"); ?></TD>
			<TD  VALIGN="TOP"><?php utils_show_result_set($result5,"Bug Groups"); ?></TD>
			<TD>&nbsp;</TD>
		</TR>
	</TABLE>
	<?php

} else {

	echo '
		<H1>You must be logged in before you can create personal filters for any given group</H2>';

}

bug_footer(array());
