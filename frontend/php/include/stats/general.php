<?php
# Get statistics.
#
# Copyright (C) 2004 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2004 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2017 Ineiev
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


function stats_get_generic($res_count)
{
  if (db_numrows($res_count) > 0)
    {
      $row_count = db_fetch_array($res_count);
      return $row_count['count'];
    }
  else
    {
      return _("Error");
    }
}

function stats_getprojects_active($type_id="")
{
  return stats_getprojects($type_id);
}

function stats_getprojects_bytype_active($type_id)
{
  return stats_getprojects_active($type_id);
}

function stats_getprojects_pending()
{
  return stats_get_generic(
           db_query("SELECT count(*) AS count FROM groups WHERE status='P'"));
}

function stats_getprojects_total()
{
  return stats_getprojects();
}

function stats_getprojects($type_id="", $is_public="",$period="")
{
  $params = array();
  $type_id_sql = '';
  $is_public_sql = '';
  $period_sql = '';
  if ($type_id)
    {
      $type_id_sql = " AND type=?";
      array_push($params, $type_id);
    }
  if ($is_public != "")
    {
      $is_public_sql = " AND is_public=?";
      array_push($params, $is_public);
    }
  if ($period)
    {
      $period_sql = " AND ?";
      array_push($params, $period);
    }

  return stats_get_generic(
    db_execute("SELECT count(*) AS count FROM groups WHERE status='A'
                $type_id_sql $is_public_sql $period_sql",
	       $params));
}

function stats_getusers($period="")
{
  $param = array();
  $period_sql = '';
  if ($period)
    {
      $period_sql = " AND ?";
      $param = array($period);
    }

  return stats_get_generic(
    db_execute("SELECT count(*) AS count FROM user WHERE status='A'
                $period_sql", $param));
}

function stats_getitems($tracker, $only_open="",$period="")
{
  $params = array();
  $only_open_sql = '';
  $period_sql = '';
  if ($only_open)
    {
      $only_open_sql = " AND status_id=?";
      array_push($params, $only_open);
    }

  if ($period)
    {
      $period_sql = " AND ?";
      array_push($params, $period);
    }


  return stats_get_generic(
    db_execute("SELECT count(*) AS count FROM $tracker "
               . "WHERE group_id<>'100' AND spamscore < 5"
	       . " $only_open_sql $period_sql", $params));
}

function stats_getthemeusers($theme="")
{
  return stats_get_generic(db_execute("SELECT count(*) AS count FROM user "
                                     ."WHERE status='A' AND theme=?",
				      array($theme)));
}
?>
