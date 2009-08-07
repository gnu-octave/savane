-- Fill remaining uids and gids

-- Generate uidNumber's
CREATE TEMPORARY TABLE temp_table (user_id int, uidNumber int auto_increment PRIMARY KEY);
-- Minimum uidNumber = 1000
INSERT INTO temp_table (user_id, uidNumber)
  VALUES (0, 1000-1);
-- Import existing uidNumber's
INSERT INTO temp_table (user_id, uidNumber)
  SELECT user_id, uidNumber FROM user WHERE uidNumber > 0;
-- Assign new uidNumber's
INSERT INTO temp_table (user_id)
  SELECT user_id FROM user WHERE uidNumber = 0;

-- Update uidNumber's
UPDATE user, temp_table
  SET user.uidNumber = temp_table.uidNumber
  WHERE user.user_id = temp_table.user_id
    AND user.uidNumber = 0;
DROP TABLE temp_table


-- Generate gidNumber's
CREATE TEMPORARY TABLE temp_table (group_id int, gidNumber int auto_increment PRIMARY KEY);
-- Minimum gidNumber = 1000
INSERT INTO temp_table (group_id, gidNumber)
  VALUES (0, 1000-1);
-- Import existing gidNumber's
INSERT INTO temp_table (group_id, gidNumber)
  SELECT group_id, gidNumber FROM groups WHERE gidNumber > 0;
-- Assign new uidNumber's
INSERT INTO temp_table (group_id)
  SELECT group_id FROM groups WHERE gidNumber = 0;

-- Update gidNumber's
UPDATE groups, temp_table
  SET groups.gidNumber = temp_table.gidNumber
  WHERE groups.group_id = temp_table.group_id
    AND groups.gidNumber = 0;
DROP TABLE temp_table
