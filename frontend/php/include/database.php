<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2004-2005 (c) Elfyn McBratney <elfyn--emcb.co.uk>
#                          Mathieu Roy <yeupou--gnu.org>
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

function db_connect() 
{
  global $sys_dbhost,$sys_dbuser,$sys_dbpasswd,$conn;

  $conn = @mysql_connect($sys_dbhost,$sys_dbuser,$sys_dbpasswd);
  if (!$conn) {
    fb("Failed to connect to database. Please contact as soon as possible server administrators. Until this problem get fixed, you will not be able to use this site.", 1);
  }
}

function db_query($qstring,$print=0) 
{

  #	global $QUERY_COUNT;
  #	$QUERY_COUNT++;
  if ($print) print "<br />Query is: $qstring<br />";
  #	if ($GLOBALS[IS_DEBUG]) $GLOBALS[G_DEBUGQUERY] .= $qstring . "<BR>\n";
  global $sys_dbname;
  $GLOBALS['db_qhandle'] = @mysql($sys_dbname,$qstring);
  return $GLOBALS['db_qhandle'];
}

function db_numrows($qhandle) 
{
  # return only if qhandle exists, otherwise 0
  if ($qhandle) {
    return @mysql_numrows($qhandle);
  } else {
    return 0;
  }
}

function db_free_result($qhandle) 
{
  return @mysql_free_result($qhandle);
}

function db_result($qhandle,$row,$field) 
{
  return @mysql_result($qhandle,$row,$field);
}

function db_numfields($lhandle) 
{
  return @mysql_numfields($lhandle);
}

function db_fieldname($lhandle,$fnumber) 
{
  return @mysql_field_name($lhandle,$fnumber);
}

function db_affected_rows($qhandle) 
{
  return @mysql_affected_rows();
}
	
function db_fetch_array($qhandle = 0) 
{

  if ($qhandle) {
    return @mysql_fetch_array($qhandle);
  } else {
    if ($GLOBALS['db_qhandle']) {
      return @mysql_fetch_array($GLOBALS['db_qhandle']);
    } else {
      return (array());
    }
  }
}
	
function db_insertid($qhandle) 
{

  return @mysql_insert_id();
}

function db_error() 
{
  return @mysql_error();
}

# Return an sql insert command taking in input a qhandle:
# it is supposed to ease copy a a row into another, ignoring the autoincrement
# field + replacing another field value (like group_id)
function db_createinsertinto ($result, $table, $row, $autoincrement_fieldname, $replace_fieldname='zxry', $replace_value='axa')
{
  unset($fields,$values);
  for ($i = 0; $i < db_numfields($result); $i++) 
    { 
      $fieldname = db_fieldname($result, $i);
      # Create the sql by ignoring the autoincremental id
      if ($fieldname != $autoincrement_fieldname)
	    {
	      # If the value is empty
	      if (db_result($result, $row, $fieldname) != NULL)
		{
		  
		  if ($fields)
		    { 
		      $fields .= ",";
		      $values .= ",";
		    }

		  $fields .= $fieldname; 
                  # Replace another field
		  if ($fieldname == $replace_fieldname)
		    {
		      $values .= "'".$replace_value."'";
		}
		  else
		    { $values .= "'".db_result($result, $row, $fieldname)."'"; }
		}
	    }
    }
  # No fields? Ignore
  if (!$fields)
    { return 0; }

  return "INSERT INTO ".$table." ($fields) VALUES ($values)";
}	

?>
