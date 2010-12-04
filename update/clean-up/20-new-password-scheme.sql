-- We need more room for bigger password hashes
ALTER TABLE user MODIFY user_pw VARCHAR(128);
