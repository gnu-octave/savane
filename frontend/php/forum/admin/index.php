<?php
// This file is part of the Savane project
// <http://gna.org/projects/savane/>
//
// $Id$
//
//
//
// modified U.Schwickerath (US), Dec. 2002
// modified M.Hardt, Nov 2003
//
// Copyright 1999-2000 (c) The SourceForge Crew
//
//
//  Copyright 2000-2001 (c) Free Software Foundation
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

require "../../include/pre.php";

// This require seems not to be required anylonger, but sourced from pre.php
// Anyways it wsa changed to 'include/news/forum.php'

$is_admin_page='y';

// Don't know if 'F2' is already supported.
// if ($group_id && (user_ismember($group_id, 'F2'))) {
if ($group_id && (user_ismember($group_id, 'A'))) {

	site_project_header(array('group'=>$group_id,'context'=>'aforum'));

	if ($post_changes) {
		/*
			Update the DB to reflect the changes
		*/

		if ($delete) {
			/*
				Deleting messages or threads
			*/

			/*
				Get this forum_id, checking to make sure this forum is in this group
			*/
			$sql="SELECT forum.group_forum_id FROM forum,forum_group_list WHERE forum.group_forum_id=forum_group_list.group_forum_id ".
				"AND forum_group_list.group_id='$group_id' AND forum.msg_id='$msg_id'";

			$result=db_query($sql);

			if (db_numrows($result) > 0) {
				recursive_delete($msg_id,db_result($result,0,'group_forum_id'))." messages deleted ";
			} else {
				" Message not found or message is not in your group ";
			}

		} else if ($add_forum) {
			/*
				Adding forums to this group
			*/
			forum_create_forum($group_id,$forum_name,$is_public,1,$description);

		} else if ($change_status) {
			/*
				Change a forum to public/private
			*/
			$sql="UPDATE forum_group_list SET is_public='$is_public',forum_name='". htmlspecialchars($forum_name) ."',".
				"description='". htmlspecialchars($description) ."' ".
				"WHERE group_forum_id='$group_forum_id' AND group_id='$group_id'";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				" Error Updating Forum Info ";
			} else {
				" Forum Info Updated Successfully ";
			}
		} else if ($member_delete) {
			/*
                                remove a member from monitoring the forum
			*/
			$sql="DELETE FROM forum_monitored_forums WHERE forum_id = '$forum_number' and user_id = '$member_delete'";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= " Error Removing User";
			} else {
				$feedback .= " User Removed Successfully ";
			}

		} else if ($member_add) {
			/*
                                add a member for monitoring the forum
			*/

			$error=0;

			$sql_u="SELECT user_id FROM user WHERE user_name='$user_name'";
			$result_u=db_query($sql_u);
			$rows_u=db_numrows($result_u);
			if (!$result_u || $rows_u < 1) {
				echo '
				<H2>No such user</H2>';
				$error=1;
			}

			$user_id=db_result($result_u,0,'user_id');

			/*		  $sql_t="SELECT group_id, user_id from user_group where user_id='$user_id' and group_id='$group_id'";
		  $result_t=db_query($sql_t);
		  $rows_t=db_numrows($result_t);
		  if (!$result_t || $rows_t < 1) {
			echo '
				<H2>This user is not a member in this project</H2>';
			$feedback .= " Error Adding User ";
			$error=1;
		  }
			*/

			$sql_e="SELECT forum_id, user_id from forum_monitored_forums where user_id='$user_id' and forum_id='$forum_number'";
			$result_e=db_query($sql_e);
			$rows_e=db_numrows($result_e);
			if (!$result_e || $rows_e > 0) {
				echo '
				<H2>This user is already monitoring this forum</H2>';
				$feedback .= " Did NOT Add User again";
				$error=1;
			}

			if (!$error) {
				$sql="INSERT INTO forum_monitored_forums (forum_id,user_id) VALUES ('$forum_number','$user_id')";
				$result=db_query($sql);

				if (!$result ) {
					$feedback .= " Error Adding User ";
				} else {
					$feedback .= " User Added ";
				}
			}
			$manage_members=1;
		}

	}

	if ($delete) {
		/*
			Show page for deleting messages
		*/
	//	forum_header(array('title'=>'Delete a message'));

		echo '
			<H2>Delete a message</H2>

			<p style=error>WARNING! You are about to permanently delete a
			message and all of its followups!</p>
			<FORM METHOD="POST" ACTION="'.$PHP_SELF.'">
			<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="delete" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
			<strong>Enter the Message ID</strong><BR>
			<INPUT TYPE="TEXT" NAME="msg_id" VALUE="">
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
			</FORM>';

	//	forum_footer(array());

	} else if ($add_forum) {
		/*
			Show the form for adding forums
		*/
	//	forum_header(array('title'=>'Add a Forum'));

	//	$sql="SELECT forum_name FROM forum_group_list WHERE group_id='$group_id'";
        $sql="SELECT forum_name, description ".
            "FROM forum_group_list ".
            "LEFT JOIN news_bytes ".
            "ON forum_group_list.group_forum_id=news_bytes.forum_id ".
            "WHERE news_bytes.forum_id IS  NULL ".
            "AND forum_group_list.group_id='$group_id' ";

        echo "sql: $sql<br />";


 		$result=db_query($sql);
		utils_show_result_set($result,'Existing Forums');

		echo '
			<P>
			<H2>Add a Forum</H2>

			<FORM METHOD="POST" ACTION="'.$PHP_SELF.'">
			<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="add_forum" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
			<strong>Forum Name:</strong><BR>
			<INPUT TYPE="TEXT" NAME="forum_name" VALUE="" SIZE="20" MAXLENGTH="30"><BR>
			<strong>Description:</strong><BR>
			<INPUT TYPE="TEXT" NAME="description" VALUE="" SIZE="40" MAXLENGTH="80"><BR>
			<strong>Is Public?</strong><BR>
			<INPUT TYPE="RADIO" NAME="is_public" VALUE="1" CHECKED> Yes<BR>
			<INPUT TYPE="RADIO" NAME="is_public" VALUE="0"> No<P>
			<P style=error>
			Once you add a forum, it cannot be modified or deleted!
      </P>
			<P>
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Add This Forum">
      </P>
			</FORM>';

	//	forum_footer(array());

	} else if ($change_status) {
		/*
			Change a forum to public/private
		*/
	//	forum_header(array('title'=>'Change Forum Status'));

	//	$sql="SELECT * FROM forum_group_list WHERE group_id='$group_id'";
        $sql="SELECT forum_group_list.* ".
            "FROM forum_group_list ".
            "LEFT JOIN news_bytes ".
            "ON forum_group_list.group_forum_id=news_bytes.forum_id ".
            "WHERE news_bytes.forum_id IS  NULL ".
            "AND forum_group_list.group_id='$group_id'";

		$result=db_query($sql);
		$rows=db_numrows($result);

		if (!$result || $rows < 1) {
			echo '
				<H2>No Forums Found</H2>
				<P>
				None found for this project';
		} else {
			echo '
			<H2>Update Forum Status</H2>
			<P>
			You can make forums private from here. Please note that private forums
			can still be viewed by members of your project, not the general public.<P>';

			$title_arr=array();
			$title_arr[]='Forum';
			$title_arr[]='Status';
			$title_arr[]='Update';

			echo html_build_list_table_top ($title_arr);

			for ($i=0; $i<$rows; $i++) {
				echo '
					<TR BGCOLOR="'. utils_get_alt_row_color($i) .'"><TD>'.db_result($result,$i,'forum_name').'</TD>';
				echo '
					<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
					<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
					<INPUT TYPE="HIDDEN" NAME="change_status" VALUE="y">
					<INPUT TYPE="HIDDEN" NAME="group_forum_id" VALUE="'.db_result($result,$i,'group_forum_id').'">
					<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
					<TD>
						<span class="smaller">
						<strong>Is Public?</strong><BR>
						<INPUT TYPE="RADIO" NAME="is_public" VALUE="1"'.((db_result($result,$i,'is_public')=='1')?' CHECKED':'').'> Yes<BR>
						<INPUT TYPE="RADIO" NAME="is_public" VALUE="0"'.((db_result($result,$i,'is_public')=='0')?' CHECKED':'').'> No<BR>
						<INPUT TYPE="RADIO" NAME="is_public" VALUE="9"'.((db_result($result,$i,'is_public')=='9')?' CHECKED':'').'> Deleted<BR>
                                                </span>
					</TD><TD>
						<span class="smaller">
						<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Update Status">
                                                </span>
					</TD></TR>
					<TR BGCOLOR="'. utils_get_alt_row_color($i) .'"><TD COLSPAN="3">
						<strong>Forum Name:</strong><BR>
						<INPUT TYPE="TEXT" NAME="forum_name" VALUE="'. db_result($result,$i,'forum_name').'" SIZE="20" MAXLENGTH="30"><BR>
						<strong>Description:</strong><BR>
						<INPUT TYPE="TEXT" NAME="description" VALUE="'. db_result($result,$i,'description') .'" SIZE="40" MAXLENGTH="80"><BR>
					</TD></TR></FORM>';
			}
			echo '</TABLE>';
		}

		//forum_footer(array());

	} else if ($manage_members) {
		/*
			Change the member of a forum
		*/

	  if ($forum_number) {
	    /*
                        got the forum id so now we can modify the members
	    */
	    forum_header(array('title'=>'Manage Forum Members'));

#	    $sql="SELECT user_id FROM forum_monitored_forums WHERE forum_id='$forum_number'";
	    $sql="SELECT user_id FROM user_group WHERE group_id='$group_id'";

	    $result=db_query($sql);
	    $rows=db_numrows($result);


	    if (!$result || $rows < 1)
		    {
			    echo '
				<H2>Nobody is monitoring this forum</H2>';
		    } else {
			    echo '<H2>Toggle members\' monitoring status</H2>';
			    echo '<TABLE>';

			    for ($i=0; $i<$rows; $i++) {
				    $uid=db_result($result,$i,'user_id');
				    $sql_guy="SELECT user_id, user_name, email, realname FROM user WHERE user_id='$uid'";
				    $result_guy=db_query($sql_guy);
				    $rows_guy=db_numrows($result_guy);
				    if($result_guy && $rows_guy > 0 ) {
					    $sql_yn="SELECT  user_id FROM forum_monitored_forums WHERE forum_id='$forum_number' and user_id='$uid'";
					    $result_yn=db_query($sql_yn);
					    $rows_yn=db_numrows($result_yn);
					    if ($rows_yn == 0) {
						    echo '<TR><TD> <A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&manage_members=1&forum_number='.$forum_number.'&member_add=1&user_name='.db_result($result_guy,0,'user_name').'&post_changes=1">ADD</A> </TD>';
					    } else {
						    echo '<TR><TD> <A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&manage_members=1&forum_number='.$forum_number.'&member_delete='.$uid.'&post_changes=1">DEL</A></TD>';
					    }
					    echo '
                                   <TD>'.db_result($result_guy,0,'user_name').'</TD>
                                   <TD>'.db_result($result_guy,0,'realname').'</TD>
			           </TR>';
				    }
			    }
			    echo '</TABLE>';
		    }

	    echo '<H2> Delete non members\' monitoring flag</H2>';
	    $sql="SELECT  user_id FROM forum_monitored_forums WHERE forum_id='$forum_number'";
	    $result=db_query($sql);
	    $rows=db_numrows($result);

	    if (!$result || $rows < 1) {
		    echo '&nbsp;None';
	    } else {
		    echo '<TABLE>';
		    // my shitty code puts a high load on the DB here!!!
		    // Either forget about non-member subscriptions or drop this code
		    for ($i=0; $i<$rows; $i++) {
			    $uid=db_result($result,$i,'user_id');
			    $sql_guy="SELECT user_id, user_name, email, realname FROM user WHERE user_id='$uid'";
			    $result_guy=db_query($sql_guy);
			    $rows_guy=db_numrows($result_guy);
			    if($result_guy && $rows_guy > 0 ) {
				    $sql_yn="SELECT user_id FROM user_group WHERE group_id='$group_id' AND user_id='$uid'";
				    $result_yn=db_query($sql_yn);
				    $rows_yn=db_numrows($result_yn);
				    if ($rows_yn == 0) {
					    echo '<TR><TD> <A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&manage_members=1&forum_number='.$forum_number.'&member_delete='.$uid.'&post_changes=1">DEL</A></TD>';
					    echo ' <TD>'.db_result($result_guy,0,'user_name').'</TD>
                                   <TD>'.db_result($result_guy,0,'realname').'</TD>
			           </TR>';
				    }
			    }
		    }
		    echo '</TABLE>';
	    }

	    echo '<H2> Add non members </H2>
			<FORM METHOD="POST" ACTION="'.$PHP_SELF.'">
			<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="member_add" VALUE="1">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
			<INPUT TYPE="HIDDEN" NAME="forum_number" VALUE="'.$forum_number.'">
			Enter User Name<BR>
			<INPUT TYPE="TEXT" NAME="user_name" VALUE="">
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="ADD">
			</FORM>';

	  } else {

		  forum_header(array('title'=>'Select Forum'));

		  //$sql="SELECT * FROM forum_group_list WHERE group_id='$group_id'";
          $sql="SELECT forum_group_list.* ".
              "FROM forum_group_list ".
              "LEFT JOIN news_bytes ".
              "ON forum_group_list.group_forum_id=news_bytes.forum_id ".
              "WHERE news_bytes.forum_id IS  NULL ".
              "AND forum_group_list.group_id='$group_id' ";
          	  	  $result=db_query($sql);
		  $rows=db_numrows($result);

		  if (!$result || $rows < 1) {
			  echo '
				<H2>No Forums Found</H2>
				<P>
				None found for this project';
		  } else {
			  echo '
			<H2>Pick a Forum </H2>
			<P>';
			  echo '<TABLE>';

			  for ($i=0; $i<$rows; $i++)
				  echo '<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&manage_members=1&forum_number='.db_result($result,$i,'group_forum_id').'">'.db_result($result,$i,'forum_name').'</A><BR>';

			  echo '</TABLE>';

		  }
	  }

	} else {
		/*
			Show main page for choosing
			either moderotor or delete
		*/
		//site_project_header(array('group'=>$group_id,'context'=>'aforum'));


		echo '
			<H2>Forum Administration</H2>
			<P>
			<H3><A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&add_forum=1">Add Forum</A></H3>
			Create a new discussion forum.<br />
			<H3><A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&delete=1">Delete Message</A></H3>
			Delete Forum entries and News items here, if you know the message ID.<br />
			<H3><A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&change_status=1">Update Forum Info/Status</A></H3>
			Change Forum names, desription and switch between public and private.
			<H3><A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&manage_members=1">Manage Forum Members</A></H3>
			Subscribe and unsubscribe Members and Users to a Forum';


		//forum_footer(array());
	}
	forum_footer(array());

} else {
	/*
		Not logged in or insufficient privileges
	*/
	if (!$group_id) {
		exit_no_group();
	} else {
		exit_permission_denied();
	}
	forum_footer(array());
}
?>
