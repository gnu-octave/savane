<?php
require('include/init.php');

$HTML->header(array('title'=>_("Contact Us")));
utils_get_content("contact");
$HTML->footer(array());
