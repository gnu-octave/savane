<?php
#input_is_safe();
#mysql_is_safe();

header('Location: ' . preg_replace(',/sr,', '/support', $_SERVER['PHP_SELF'])
       . '?' . $_SERVER['QUERY_STRING']);
