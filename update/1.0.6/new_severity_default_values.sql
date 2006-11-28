# See <https://gna.org/bugs/?func=detailitem&item_id=2123>

## bugs 

UPDATE bugs_field_value SET value='1 - Wish',
 description='Issue which is mainly a matter of taste', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='1' LIMIT 1 ;
UPDATE bugs_field_value SET value='2 - Minor',
 description='Issue which doesn\'t affect the object\'s usefulness, and is presumably trivial to handle', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='3' LIMIT 1 ;
UPDATE bugs_field_value SET value='3 - Normal',
 description='', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='5' LIMIT 1 ;
UPDATE bugs_field_value SET value='4 - Important',
 description='Issue which has a major effect on the usability of the object, without rendering it completely unusable to everyone', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='7' LIMIT 1 ;
UPDATE bugs_field_value SET value='5 - Blocker',
 description='Issue which makes the object in question unusable or mostly so, or causes data loss', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='8' LIMIT 1 ;
UPDATE bugs_field_value SET value='6 - Security',
 description='Issue which introduces a security breach', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='9' LIMIT 1 ;

UPDATE bugs_field_value SET value='1,5',
 description='', status='H',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='2' LIMIT 1 ;
UPDATE bugs_field_value SET value='2,5',
 description='', status='H',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='4' LIMIT 1 ;
UPDATE bugs_field_value SET value='3,5',
 description='', status='H',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='6' LIMIT 1 ;

## task

UPDATE task_field_value SET value='1 - Wish',
 description='Issue which is mainly a matter of taste', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='1' LIMIT 1 ;
UPDATE task_field_value SET value='2 - Minor',
 description='Issue which doesn\'t affect the object\'s usefulness, and is presumably trivial to handle', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='3' LIMIT 1 ;
UPDATE task_field_value SET value='3 - Normal',
 description='', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='5' LIMIT 1 ;
UPDATE task_field_value SET value='4 - Important',
 description='Issue which has a major effect on the usability of the object, without rendering it completely unusable to everyone', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='7' LIMIT 1 ;
UPDATE task_field_value SET value='5 - Blocker',
 description='Issue which makes the object in question unusable or mostly so, or causes data loss', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='8' LIMIT 1 ;
UPDATE task_field_value SET value='6 - Security',
 description='Issue which introduces a security breach', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='9' LIMIT 1 ;

UPDATE task_field_value SET value='1,5',
 description='', status='H',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='2' LIMIT 1 ;
UPDATE task_field_value SET value='2,5',
 description='', status='H',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='4' LIMIT 1 ;
UPDATE task_field_value SET value='3,5',
 description='', status='H',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='6' LIMIT 1 ;

## support


UPDATE support_field_value SET value='1 - Wish',
 description='Issue which is mainly a matter of taste', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='1' LIMIT 1 ;
UPDATE support_field_value SET value='2 - Minor',
 description='Issue which doesn\'t affect the object\'s usefulness, and is presumably trivial to handle', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='3' LIMIT 1 ;
UPDATE support_field_value SET value='3 - Normal',
 description='', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='5' LIMIT 1 ;
UPDATE support_field_value SET value='4 - Important',
 description='Issue which has a major effect on the usability of the object, without rendering it completely unusable to everyone', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='7' LIMIT 1 ;
UPDATE support_field_value SET value='5 - Blocker',
 description='Issue which makes the object in question unusable or mostly so, or causes data loss', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='8' LIMIT 1 ;
UPDATE support_field_value SET value='6 - Security',
 description='Issue which introduces a security breach',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='9' LIMIT 1 ;

UPDATE support_field_value SET value='1,5',
 description='', status='H',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='2' LIMIT 1 ;
UPDATE support_field_value SET value='2,5',
 description='', status='H',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='4' LIMIT 1 ;
UPDATE support_field_value SET value='3,5',
 description='', status='H',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='6' LIMIT 1 ;

## patch


UPDATE patch_field_value SET value='1 - Wish',
 description='Issue which is mainly a matter of taste', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='1' LIMIT 1 ;
UPDATE patch_field_value SET value='2 - Minor',
 description='Issue which doesn\'t affect the object\'s usefulness, and is presumably trivial to handle', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='3' LIMIT 1 ;
UPDATE patch_field_value SET value='3 - Normal',
 description='', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='5' LIMIT 1 ;
UPDATE patch_field_value SET value='4 - Important',
 description='Issue which has a major effect on the usability of the object, without rendering it completely unusable to everyone', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='7' LIMIT 1 ;
UPDATE patch_field_value SET value='5 - Blocker',
 description='Issue which makes the object in question unusable or mostly so, or causes data loss', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='8' LIMIT 1 ;
UPDATE patch_field_value SET value='6 - Security',
 description='Issue which introduces a security breach', status='A',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='9' LIMIT 1 ;

UPDATE patch_field_value SET value='1,5',
 description='', status='H',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='2' LIMIT 1 ;
UPDATE patch_field_value SET value='2,5',
 description='', status='H',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='4' LIMIT 1 ;
UPDATE patch_field_value SET value='3,5',
 description='', status='H',
 email_ad=NULL WHERE bug_field_id='102' AND group_id='100' AND value_id='6' LIMIT 1 ;

# EOF