<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2006 (c) Mathieu Roy <yeupou--gnu.org>
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

require_once('../include/init.php');
require_once('../include/sane.php');
require_once('../include/news/forum.php');
require_once('../include/news/general.php');
register_globals_off();

extract(sane_import('request', array('msg_id')));

if ($msg_id) {
	$msg_id = intval($msg_id);
	/*
		Figure out which group this message is in, for the sake of the admin links
	*/
	$result=db_execute("SELECT forum_group_list.group_id,forum_group_list.forum_name,forum.group_forum_id,forum.thread_id ".
			   "FROM forum_group_list,forum WHERE forum_group_list.group_forum_id=forum.group_forum_id AND forum.msg_id=?",
			   array($msg_id));

	$forum_id=db_result($result,0,'group_forum_id');
	$thread_id=db_result($result,0,'thread_id');
	$forum_name=db_result($result,0,'forum_name');

	forum_header(array('title'=>db_result($result,0,'forum_name')));

	print "<p>";

	$sql="SELECT user.user_name,forum.group_forum_id,forum.thread_id,forum.subject,forum.date,forum.body ".
		"FROM forum,user WHERE user.user_id=forum.posted_by AND forum.msg_id=?;";

	$result = db_execute($sql, array($msg_id));

	if (!$result || db_numrows($result) < 1) {
		/*
			Message not found
		*/
		return 'message not found.\n';
	}

	$title_arr=array();
	$title_arr[]='Message: '.$msg_id;

	print html_build_list_table_top ($title_arr);

	print "<tr><td>\n";
	print '<strong>'.db_result($result,0, "subject").'</strong>';
	print ' ('._("posted by").' '.utils_user_link(db_result($result,0, "user_name")).", ";
	print utils_format_date(db_result($result,0, "date")).')';
	print '<p>';
	print markup_rich(db_result($result,0, 'body'));
	print '</p></td></tr></table>';

	/*
		Show entire thread
	*/

	#highlight the current message in the thread list
	$current_message=$msg_id;
	print show_thread(db_result($result,0, 'thread_id'));

	/*
		Show post followup form
	*/

	print '<p>&nbsp;<p>';
	print '<a name="followup"></a>';
	print '<center><h3>'._("Post a followup to this message").'</h3></center>';

	show_post_form(db_result($result, 0, 'group_forum_id'),db_result($result, 0, 'thread_id'), $msg_id, db_result($result,0, 'subject'));

} else {

	forum_header(array('title'=>'Must choose a message first'));
	print '<h1>You must choose a message first</H1>';

}

forum_footer(array()); 
