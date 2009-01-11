-- `privacy` is declared as char(2), but as int(2) in the
-- '.initvalues' files. `cookbook`.`privacy` is always declared as
-- int(2). Let's tidy:
ALTER TABLE bugs    MODIFY  `privacy` int(2) NOT NULL default '1';
ALTER TABLE patch   MODIFY  `privacy` int(2) NOT NULL default '1';
ALTER TABLE support MODIFY  `privacy` int(2) NOT NULL default '1';
ALTER TABLE task    MODIFY  `privacy` int(2) NOT NULL default '1';
