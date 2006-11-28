-- Remove revision tag leftover field (that could not work, because it refers
-- to a non-existant field)

DELETE FROM patch_field WHERE field_name='revision tag';
