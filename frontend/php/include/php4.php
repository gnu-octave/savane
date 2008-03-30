<?php
# Backward compatibility functions for PHP4
# Copyright (C) 2007  Sylvain Beucler
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

// Appears in PHP5
if (substr(PHP_VERSION, 0, 1) < 5)
{
  function debug_print_backtrace()
  {
    $bt = debug_backtrace();
    array_shift($bt); // remove this very function
    utils_debug_print_mybacktrace($bt);
  }
  
  function memory_get_peak_usage() {
    // Needs PHP5
    return 0;
  }
}
