# This script takes care of removal of old field values.
# It must be run on Savane installation made before savane release 1.0.0
# If it's your case, please read first 
#   <https://gna.org/task/index.php?func=detailitem&item_id=777>
# to fully understand what is implies.

DELETE FROM bugs_field_value WHERE bug_fv_id='104' AND group_id='100' LIMIT 1;
DELETE FROM bugs_field_value WHERE bug_fv_id='105' AND group_id='100' LIMIT 1;
DELETE FROM bugs_field_value WHERE bug_fv_id='106' AND group_id='100' LIMIT 1;
DELETE FROM bugs_field_value WHERE bug_fv_id='107' AND group_id='100' LIMIT 1;
DELETE FROM bugs_field_value WHERE bug_fv_id='108' AND group_id='100' LIMIT 1;
DELETE FROM bugs_field_value WHERE bug_fv_id='109' AND group_id='100' LIMIT 1;
DELETE FROM bugs_field_value WHERE bug_fv_id='110' AND group_id='100' LIMIT 1;
