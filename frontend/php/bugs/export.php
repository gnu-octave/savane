<?php
# Export trackers.
#
# Copyright (C) 2019, 2022 Ineiev
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

require_once ('../include/init.php');
require_once ('../include/trackers/general.php');
extract (sane_import ('request', ['true' => 'download']));

if (!$group_id)
  print exit_no_group ();

$project = project_get_object ($group_id);

if (!member_check_private (0, $group_id))
  exit_error (
    _("Data Export requires an access to private data of the group")
  );

trackers_init ($group_id);

if (!$download)
  {
    trackers_header (['title' => _("Data Export")]);
     print "<p>" . _("Here you can export data from this tracker.") . "</p>\n";
     print "<p><a href=\"export.php?group=$group&amp;download=1\">"
       . _("Download tracker data") . "</a></p>\n";

    trackers_footer ([]);
    exit (0);
  }
$art = ARTIFACT;
header ('Content-Type: text/html');
header ("Content-Disposition: attachment; filename=$group-$art.html");
header ("Content-Description: $art tracker data export of $group");
print "<html>\n<head>\n";
print
"<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />\n";
print "<title>$art tracker data export of $group</title>\n";
print "</head>\n";
print "<body>\n";
print "<h1>$group $art tracker data: " . date ("Y-m-d H:i:s e") . "</h1>\n";
$finalize_page = function ()
{
  print "</body>\n</html>\n";
  exit (0);
};
$result = db_execute ("
  SELECT * FROM $art WHERE group_id = ? ORDER BY bug_id",
  [$group_id]
);
if (!$result)
  $finalize_page ();

$rows = db_numrows ($result);
$cols = db_numfields ($result);
$prev_bug = $prev_comment = [];

for ($i = 0; $i < $rows; $i++)
  {
    $item = db_fetch_array ($result);
    if ($item === FALSE)
      continue;
    $bug_id = "";
    print "<h2>Item " . ($i + 1) . "</h2>\n";
    for ($j = 0; $j < $cols; $j++)
      {
        $field_name = db_fieldname ($result, $j);
        $val = $item [$j];
        if ($field_name == "bug_id")
          $bug_id = $val;
        if ($i != 0 && $val == $prev_bug[$j])
          continue;
        $prev_bug[$j] = $val;
        print "<h3>" . htmlentities ($field_name) . "</h3>\n";
        print "<p>" . htmlentities ($val) . "</p>\n";
      }
    if ($bug_id === "")
      continue;
    $res = db_execute ("
      SELECT * FROM ${art}_history WHERE bug_id = ? ORDER BY bug_history_id",
      [$bug_id]
    );
    if (!$res)
      continue;
    print "<h3>Comments</h3>\n";
    $r = db_numrows ($res);
    $c = db_numfields ($res);

    for ($k = 0; $k < $r; $k++)
      {
        $comment = db_fetch_array ($res);
        if ($comment === FALSE)
          continue;
        print "<h4>Comment " . ($k + 1) . "</h4>\n";
        for ($l = 0; $l < $c; $l++)
          {
            $val = $comment [$l];
            if (($k != 0 || $i != 0) && $val == $prev_comment[$l])
              continue;
            $prev_comment[$l] = $val;
            print "<h5>" . htmlentities (db_fieldname ($res, $l)) . "</h5>\n";
            print "<p>" . htmlentities ($val) . "</p>\n";
          }
      }
  }
$finalize_page ();
?>
