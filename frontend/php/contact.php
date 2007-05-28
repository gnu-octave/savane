<?php
require('include/init.php');
register_globals_off();
#input_is_safe();
#mysql_is_safe();

$HTML->header(array('title'=>_("Contact Us")));
utils_get_content("contact");
$HTML->footer(array());
