<?php
# <one line to give a brief idea of what this does.>
# 
# Took from Annif <http://gna.org/projects/annif/>
# Copyright 2003 (c) Mathieu Roy <yeupou--at--gnu.org>
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


function calendar_month_name ($month)
{
  switch ($month) 
    {
    case '1': 
      return _("January"); break;
    case '2': 
      return _("February"); break;
    case '3': 
      return _("March"); break;
    case '4': 
      return _("April"); break;
    case '5': 
      return _("May"); break;
    case '6': 
      return _("June"); break;
    case '7': 
      return _("July"); break;
    case '8': 
      return _("August"); break;
    case '9': 
      return _("September"); break;
    case '10': 
      return _("October"); break;
    case '11': 
      return _("November"); break;
    case '12': 
      return _("December"); break;
    } 
}



function calendar_every_weekday_name ($weekday)
{
  # Start monday, not sunday...
  switch ($weekday)
    {
    case '1':
      return _("every Monday"); break;
    case '2':
      return _("every Tuesday"); break;
    case '3':
      return _("every Wednesday"); break;
    case '4':
      return _("every Thursday"); break;
    case '5':
      return _("every Friday"); break;
    case '6':
      return _("every Saturday"); break;
    case '7':
      return _("every Sunday"); break;
    }
}


function calendar_days_count ($month)
{
  if ($month == '2')
    { return '29'; }
  elseif ($month == '4' ||
	  $month == '6' ||
	  $month == '9' ||
	  $month == '11')
    { return '30'; }
  else 
    { return '31'; }
}

function calendar_selectbox ($level, $checked_val='xxaz', $inputname=false)
{
  if (!$inputname)
    { $inputname = $level; }
  
  # initialize array
  $text = array();
  $number = array();

  if ($level == 'day')
    {
      for ($day = 1; $day <= calendar_days_count(1); $day++)
	{
	  $number[] = $day;
	  $text[] = $day; 
	}
    }
  elseif ($level == 'month')
    {
      for ($month = 1; $month <= 12; $month++)
	{ 
	  $number[] = $month;
	  $text[] = calendar_month_name($month); 
	}
    }


  return html_build_select_box_from_arrays($number,
					   $text,
					   $inputname, 
					   $checked_val,
					   0);
}
