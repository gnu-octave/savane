<?php
# <one line to give a brief idea of what this does.>
# 
#  Copyright 2005 (c) Tobias Toedter <t.toedter--gmx.net>
#
# This file is part of Savane.
# 
# Savane is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
# 
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
# 
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.


require_once 'PHPUnit.php';

# Collect all test files in the current directory
if ($handle = opendir("./"))
  {
    while (false !== ($file = readdir($handle)))
      {
        if (substr($file, 0, 5) == "test_")
	  {
	    # Create a hash with the filename as the key
	    # and the test class (without test_ and .php)
	    # as the value
	    $tests[$file] = substr($file, 5, -4);
	  }
      }
    closedir($handle);
  }
else
  {
    print "Could not open current directory.\n";
  }


$stats_tests = 0;
$stats_failures = 0;
$stats_errors = 0;

# Run each test file separately
foreach ($tests as $filename => $testclass)
  {
    require_once $filename;

    $suite  = new PHPUnit_TestSuite($testclass);
    $result = PHPUnit::run($suite);

    print $result->toString();
    if ($result->wasSuccessful())
      {
        printf("  -> OK (%s tests)\n", $result->runCount());
      }
    else
      {
        printf("  -> FAILED (%s tests, %s failures, %s errors)\n", $result->runCount(),
          $result->failureCount(), $result->errorCount());
      }

    $stats_tests += $result->runCount();
    $stats_failures += $result->failureCount();
    $stats_errors += $result->errorCount();
  }

print "\nDone.\n";
printf("%s tests, %s failures, %s errors\n", $stats_tests,
  $stats_failures, $stats_errors);
?>
