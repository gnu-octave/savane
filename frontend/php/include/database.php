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
# Copyright (C) 2000-2006 John Lim (ADOdb)
# Copyright (C) 2007  Cliss XXI (GCourrier)
# Copyright (C) 2006, 2007  Sylvain Beucler
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

define('DB_AUTOQUERY_INSERT', 1);
define('DB_AUTOQUERY_UPDATE', 2);

function db_connect() 
{
  global $sys_dbhost,$sys_dbuser,$sys_dbpasswd,$conn,$sys_dbname;


  // Test the presence of php-mysql - you get a puzzling blank page
  // when it's not installed
  if (!extension_loaded('mysql')) {
    echo "Please install the MySQL extension for PHP:
    <ul>
      <li>Debian-based: <code>aptitude install php4-mysql</code>
        or <code>aptitude install php5-mysql</code></li>
      <li>Fedora Core: <code>yum install php-mysql</code></li>
      <li>Check the <a href='{$GLOBALS['sys_url_topdir']}/testconfig.php'>configuration
        page</a> and the <a href='http://php.net/mysql'>PHP website</a>
        for more information.</li>
    </ul>";
    exit;
  }

  $conn = @mysql_connect($sys_dbhost,$sys_dbuser,$sys_dbpasswd);
  if (!$conn or !mysql_select_db($sys_dbname, $conn)) {
    echo "Failed to connect to database: " . mysql_error() . "<br />";
    echo "Please contact as soon as possible server administrators {$GLOBALS['sys_email_adress']}.<br />";
    echo "Until this problem get fixed, you will not be able to use this site.";
    exit;
  }
}

// sprinf-like function to auto-escape SQL strings
// db_query_escape("SELECT * FROM user WHERE user_name='%s'", $_GET['myuser']);
function db_query_escape()
{
  $num_args = func_num_args();
  if ($num_args < 1)
    die(_("db_query_escape: Missing parameter"));
  $args = func_get_args();

  // Escape all params except the query itself
  for ($i = 1; $i < $num_args; $i++)
    $args[$i] = mysql_real_escape_string($args[$i]);

  $query = call_user_func_array('sprintf', $args);
  return mysql_query($query);
}

// Substitute '?' with one of the values in the $inputarr array,
// properly escaped for inclusion in an SQL query
function db_variable_binding($sql, $inputarr=null) {
  if ($inputarr) {
    $sqlarr = explode('?', $sql);
    
    $sql = '';
    $i = 0;
    //Use each() instead of foreach to reduce memory usage -mikefedyk
    while(list(, $v) = each($inputarr)) {
      $sql .= $sqlarr[$i];
      // from Ron Baldwin <ron.baldwin#sourceprose.com>
      // Only quote string types
      $typ = gettype($v);
      if ($typ == 'string')
	$sql .= "'" . mysql_real_escape_string($v) . "'";
      else if ($typ == 'double')
	$sql .= str_replace(',','.',$v); // locales fix so 1.1 does not get converted to 1,1
      else if ($typ == 'boolean')
	$sql .= $v ? '1' : '0';
      else if ($typ == 'object')
	exit("Don't use db_execute with objects.");
      else if ($v === null)
	$sql .= 'NULL';
      else
	$sql .= $v;
      $i += 1;
    }
    if (isset($sqlarr[$i])) {
      $sql .= $sqlarr[$i];
      if ($i+1 != sizeof($sqlarr))
	exit("db_variable_binding: input array does not match query: ".htmlspecialchars($sql));
    } else if ($i != sizeof($sqlarr))
      exit("db_variable_binding: input array does not match query: ".htmlspecialchars($sql));
  }
  return $sql;
}

/* Like ADOConnection->AutoExecute, without ignoring non-existing
 fields (you'll get a nice mysql_error() instead) and with a modified
 argument list to allow variable binding in the where clause

eg: 

Check http://phplens.com/adodb/reference.functions.getupdatesql.html ,
http://phplens.com/adodb/tutorial.generating.update.and.insert.sql.html
and adodb.inc.php
*/
function db_autoexecute($table, $dict, $mode=DB_AUTOQUERY_INSERT,
			$where_condition=false, $where_inputarr=null)
{
  // table name validation
  if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]+$/', $table))
    die("db_autoexecute: invalid table name: " . htmlspecialchars($table));

  switch((string) $mode) {
  case 'INSERT':
  case '1':
    $fields = implode(',', array_keys($dict)); // date,summary,...
    $question_marks = implode(',', array_fill(0, count($dict), '?')); // ?,?,?,...
    return db_execute("INSERT INTO $table ($fields) VALUES ($question_marks)",
		     array_values($dict));
    break;
  case 'UPDATE':
  case '2':
    $sql_fields = '';
    $values = array();
    while (list($field,$value) = each($dict)) {
      $sql_fields .= "$field=?,";
      $values[] = $value;
    }
    $sql_fields = rtrim($sql_fields, ',');
    $values = array_merge($values, $where_inputarr);
    $where_sql = $where_condition ? "WHERE $where_condition" : '';
    return db_execute("UPDATE $table SET $sql_fields $where_sql", $values);
    break;
  default:
    // no default
  }
  die("db_autoexecute: unknown mode=$mode");
}

/* Like ADOConnection->Execute, with variables binding emulation for
MySQL, but simpler (not 2D-array, namely). Example:

db_execute("SELECT * FROM utilisateur WHERE name=?", array("Gogol d'Algol"));

Check http://phplens.com/adodb/reference.functions.execute.html and
adodb.inc.php
*/
function db_execute($sql, $inputarr=null)
{
  $expanded_sql = db_variable_binding($sql, $inputarr);
#  print "<pre>";
#  print_r($expanded_sql);
#  print "</pre>";
  return mysql_query($expanded_sql);
}

function db_query($qstring,$print=0) 
{
  #	global $QUERY_COUNT;
  #	$QUERY_COUNT++;
  if ($print) print "<br />Query is: $qstring<br />";
  #	if ($GLOBALS[IS_DEBUG]) $GLOBALS[G_DEBUGQUERY] .= $qstring . "<BR>\n";
  $GLOBALS['db_qhandle'] = mysql_query($qstring);
  if (!$GLOBALS['db_qhandle']) {
    // throw new Exception('db_query: SQL query error in ['.$qstring.']: ' . mysql_error());
    die('db_query: SQL query error in [' . htmlspecialchars($qstring) . ']: ' . mysql_error());
  }
  return $GLOBALS['db_qhandle'];
}

function db_numrows($qhandle) 
{
  # return only if qhandle exists, otherwise 0
  if ($qhandle) {
    return mysql_numrows($qhandle);
  } else {
    return 0;
  }
}

function db_free_result($qhandle) 
{
  return mysql_free_result($qhandle);
}

function db_result($qhandle,$row,$field) 
{
  return mysql_result($qhandle,$row,$field);
}

function db_numfields($lhandle) 
{
  return mysql_numfields($lhandle);
}

function db_fieldname($lhandle,$fnumber) 
{
  return mysql_field_name($lhandle,$fnumber);
}

function db_affected_rows($qhandle) 
{
  return mysql_affected_rows();
}
	
function db_fetch_array($qhandle = 0) 
{

  if ($qhandle) {
    return mysql_fetch_array($qhandle);
  } else {
    if ($GLOBALS['db_qhandle']) {
      return mysql_fetch_array($GLOBALS['db_qhandle']);
    } else {
      return (array());
    }
  }
}
	
function db_insertid($qhandle) 
{

  return mysql_insert_id();
}

function db_error() 
{
  return mysql_error();
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
