<?php
# Atom feed generator for news items

# Copyright (C) 2008  Sylvain Beucler

# This file is part of Savane.

# Savane is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.

# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.

# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.


require_once('../include/init.php');
require_once('../include/news/general.php');

if (empty($group_id))
{
  exit_no_group();
}

$group_obj = project_get_object($group_id);

$result = db_execute("
  SELECT forum_id, summary, date, details,
    user.realname
  FROM news_bytes,user
  WHERE
    is_approved <> 4 AND is_approved <> 5
    AND group_id=?
    AND news_bytes.submitted_by = user.user_id
  ORDER BY date DESC
  LIMIT 20", array($group_id));

$news = array();
while ($row = db_fetch_array($result))
{
  array_unshift($news,
    array('id' => "http://$sys_default_domain{$sys_home}forum/forum.php?forum_id={$row['forum_id']}",
	  'title' => $row['summary'],
	  'updated' => date('c', $row['date']),
	  'author' => $row['realname'],
	  'content' => markup_full(trim($row['details']))));
}

$id = "http://$sys_default_domain{$sys_home}news/atom.php?group=$group";
$title = $group_obj->getPublicName()." - News";
if (count($news) != 0)
     $last_updated = $news[count($news)-1]['updated'];
else
     $last_updated = date('c', 0); # Epoch
     $is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? true : false;
     $myself = ($is_https ? 'https://' : 'http://')
     . $_SERVER['SERVER_NAME']
     . (((!$is_https && $_SERVER['SERVER_PORT'] == 80)
	 || ($is_https && $_SERVER['SERVER_PORT'] == 443))
	? '' : $_SERVER['SERVER_PORT'])
     . $_SERVER['REQUEST_URI'];

// Feed header
// Nice doc here: http://www.atomenabled.org/developers/syndication/
header('Content-type: application/atom+xml;charset=UTF-8');
print '<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <id>'.$id.'</id>
  <link rel="self" href="'.$myself.'"/>
  <title>'.$title.'</title>
  <updated>'.$last_updated.'</updated>

';

// All news entries
foreach ($news as $entry)
{
print "
  <entry>
    <id>{$entry['id']}</id>
    <link rel='alternate' href='{$entry['id']}'/>
    <title>{$entry['title']}</title>
    <updated>{$entry['updated']}</updated>
    <author>
      <name>{$entry['author']}</name>
    </author>
    <content type='xhtml' xml:base='{$entry['id']}'>
      <div xmlns='http://www.w3.org/1999/xhtml'>{$entry['content']}</div>
    </content>
  </entry>
";
}

// Feed footer
print "</feed>";
