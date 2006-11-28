# Necessary to list GPG registered keys 
ALTER TABLE groups ADD registered_gpg_keys TEXT AFTER rand_hash;

# keyring 
CREATE TABLE groups_gpg_keyrings (
 id INT( 11 ) NOT NULL AUTO_INCREMENT ,
 unix_group_name VARCHAR( 30 ) NOT NULL ,
 keyring LONGBLOB,
 UNIQUE (
 id 
)
) TYPE = MYISAM ;