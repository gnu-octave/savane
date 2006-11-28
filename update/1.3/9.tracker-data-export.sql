-- export_id | artifact | unix_group_name | user_name | sql | status | date | 
--  frequency_day | frequency_hour
--
-- artifact = tracker
--
-- status = P  (pending, to be performed)
--        = D  (done, will be removed by the user with the interface)
--
-- date   = unix timestamp. Whenever the current unix timestamp is bigger than
--          the one in the field 'date', then we need to generate the output.
--          It will first be set by the frontend, accordingly to what the
--          user fill (asap = timestamp of today)
--
-- frequency = when these are set, each time a job is performed, the status
--          does not go to D, it stays to P, but the backend update the 
--          when timestamp accordingly
--          _day: values from 1 (monday) to 7 (sunday)
--          -hour: values from 0 (midnight) to 23
--          
--
-- The user selects:
--
--   bla bla  [ ] as soon as possible
--
--            [x] |today | at | NN hour |
-- 
--            [ ] | next  | | monday  | at | NN hour |
--                | every | | tuesday |
--                          | ...     |
--
-- [ ]     =  checkbox
-- | bla | =  select box

CREATE TABLE trackers_export (
 export_id INT( 11 ) NOT NULL AUTO_INCREMENT ,
 task_id INT( 11 ) NOT NULL ,
 artifact VARCHAR( 16 ) NOT NULL ,
 unix_group_name VARCHAR( 255 ) NOT NULL ,
 user_name VARCHAR( 255 ) NOT NULL ,
 sql TEXT NOT NULL ,
 status CHAR(1) NOT NULL ,
 date INT(11) NOT NULL ,
 frequency_day INT( 2 ) NULL ,
 frequency_hour INT( 2 ) NULL ,
 PRIMARY KEY ( export_id ) 
) TYPE = MYISAM ;

