-- Some old database may have status = a/h/p instead of A/H/P
UPDATE bugs_field_value SET status=UCASE(status);
