<?php

header('Location: ' . preg_replace(',/sr,', '/support', $_SERVER['PHP_SELF'])
       . '?' . $_SERVER['QUERY_STRING']);
