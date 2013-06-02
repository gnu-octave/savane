<?php
# Common, reuseable HTML code
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                          Paul Pogonyshev <pogonyshev--gmx.net>
# Copyright (C) 2007, 2008  Sylvain Beucler
# Copyright (C) 2008  Aleix Conchillo Flaque
# Copyright (C) 2013 Ineiev <ineiev--gnu.org>
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


require_once(dirname(__FILE__).'/sane.php');
require_once(dirname(__FILE__).'/markup.php');
require_once(dirname(__FILE__).'/form.php');

# display browsing/display options: should be on top of pages, after the
# specific content/page description.
# The form should end by #options so it the user does not have to scroll down
# too much
function html_show_displayoptions ($content, $form_opening=0, $submit=0)
{
  return html_show_boxoptions(_("Display Criteria"), $content, $form_opening, $submit);
}

function html_show_boxoptions ($legend, $content, $form_opening=0, $submit=0)
{

  $script_hide = <<<EOF
document.getElementById('boxoptionscontent').style.display='none';   document.getElementById('boxoptionslinkhide').style.display='none';   document.getElementById('boxoptionslinkshow').style.display='inline';
EOF;
  $script_show = <<<EOF
document.getElementById('boxoptionscontent').style.display='inline'; document.getElementById('boxoptionslinkhide').style.display='inline'; document.getElementById('boxoptionslinkshow').style.display='none';
EOF;

  $ret = '
<fieldset class="boxoptions">
<a name="options"></a>
<legend>';

  # yeupou, 2006-02: it is not ubercool to use monospace font to show
  # plus or minus button, but all the tests I made with icons so far ended up
  # in quite an ugly thing when the font scale change.
  $ret .= '
  <script type="text/javascript">';
  
  extract(sane_import('request', array('boxoptionwanted')));

  if ($boxoptionwanted != 1)
    {
      $ret .= '
    document.write(\'<span onclick="'.addslashes($script_hide).'" id="boxoptionslinkhide" style="display: none"><span class="minusorplus">(-)</span>'.htmlspecialchars($legend, ENT_QUOTES).'</span>\');
    document.write(\'<span onclick="'.addslashes($script_show).'" id="boxoptionslinkshow" style="display: inline"><span class="minusorplus">(+)</span>'.htmlspecialchars($legend, ENT_QUOTES).'</span>\');';
    }
  else
    {
      $ret .= '
    document.write(\'<span onclick="'.addslashes($script_hide).'" id="boxoptionslinkhide" style="display: inline"><span class="minusorplus">(-)</span>'.htmlspecialchars($legend, ENT_QUOTES).'</span>\');
    document.write(\'<span onclick="'.addslashes($script_show).'" id="boxoptionslinkshow" style="display: none"><span class="minusorplus">(+)</span>'.htmlspecialchars($legend, ENT_QUOTES).'</span>\');';

    }
  $ret .= '
  </script>
  <noscript>
    <span id="boxoptionslinkshow">'.$legend.'</span>
  </noscript>
</legend>';

  if ($boxoptionwanted != 1)
    {
      $ret .= '
<script type="text/javascript">
  document.write(\'<span id="boxoptionscontent" style="display: none">\');
</script>
<noscript>
  <span id="boxoptionscontent">
</noscript>
';
    }
  else
    {
      $ret .= '
  <span id="boxoptionscontent">
';
    }

  if ($form_opening && $submit)
    {
      $ret .= '
'.$form_opening.'
<span class="boxoptionssubmit">'.$submit.'</span>';
    }

  # We add boxoptionwanted to be able to determine if a boxoption was used
  # to update the page, in which case the boxoption must appear deployed
  $ret .= '
<span class="smaller">'.$content.form_input("hidden", "boxoptionwanted", "1").'</span>';

  if ($form_opening && $submit)
    {
      $ret .= '
</form>';
    }

  $ret .= '
</span>
</fieldset>
';

  return $ret;
}

# Function to create a an area in the page that can be hidden or shown 
# in one click with a javascript
# Per policy, this must work with a browser that does not support at all 
# javascript
# This is useful on item pages because we have some info that is not
# essential to be shown (like CC list etc), but still very nice be able to
# access easily
function html_hidsubpart_header ($uniqueid, $title, $deployed=false)
{
  global $is_deployed;

  # Try to find a deployed value that match the unique id
  # If found, override the deployed setting (the deployed setting should be
  # used to set a default behavior, but if in the case we explicitely 
  # use an array to determine what is deployed, this matters more)
  if (is_array($is_deployed) && 
      array_key_exists($uniqueid, $is_deployed))
    {
      $deployed = $is_deployed[$uniqueid];
    }

  $script_hide = <<<EOF
document.getElementById('hidsubpartcontent$uniqueid').style.display='none';   document.getElementById('hidsubpartlinkhide$uniqueid').style.display='none';   document.getElementById('hidsubpartlinkshow$uniqueid').style.display='block';
EOF;
  $script_show = <<<EOF
document.getElementById('hidsubpartcontent$uniqueid').style.display='inline'; document.getElementById('hidsubpartlinkhide$uniqueid').style.display='block'; document.getElementById('hidsubpartlinkshow$uniqueid').style.display='none';
EOF;

  # put the #uniqueid at the begin because
  # several browsers cant cope when
  # there are several times the same
  # anchor in a page
  $ret = '
  <h3><a name="'.$uniqueid.'"></a>
  <script type="text/javascript">';

    if (!$deployed)
    {
      $ret .= '
    document.write(\'<a onclick="'.addslashes($script_hide).'" id="hidsubpartlinkhide'.$uniqueid.'" style="display: none" href="#'.$uniqueid.'"><span class="minusorplus">(-)</span> '.htmlspecialchars($title, ENT_QUOTES).'</a>\');
    document.write(\'<a onclick="'.addslashes($script_show).'" id="hidsubpartlinkshow'.$uniqueid.'" style="display: block" href="#'.$uniqueid.'"><span class="minusorplus">(+)</span> '.htmlspecialchars($title, ENT_QUOTES).'</a>\');';
    }
  else
    {
      $ret .= '
    document.write(\'<a onclick="'.addslashes($script_hide).'" id="hidsubpartlinkhide'.$uniqueid.'" style="display: block" href="#'.$uniqueid.'"><span class="minusorplus">(-)</span> '.htmlspecialchars($title, ENT_QUOTES).'</a>\');
    document.write(\'<a onclick="'.addslashes($script_show).'" id="hidsubpartlinkshow'.$uniqueid.'" style="display: none" href="#'.$uniqueid.'"><span class="minusorplus">(+)</span> '.htmlspecialchars($title, ENT_QUOTES).'</a>\');';

    }
    $ret .= '
  </script>
  <noscript>
    <a id="hidsubpartlinkshow'.$uniqueid.'" href="#'.$uniqueid.'">'.$title.'</a>
  </noscript>
  </h3>';

    if (!$deployed)
      {
	$ret .= '
<script type="text/javascript">
  document.write(\'<span id="hidsubpartcontent'.$uniqueid.'" style="display: none">\');
</script>
<noscript>
  <span id="hidsubpartcontent'.$uniqueid.'">
</noscript>
';
      }
    else
      {
	$ret .= '
<span id="hidsubpartcontent'.$uniqueid.'">
';
      }

    return $ret;
}


function html_hidsubpart_footer ()
{
  return '
</span><!-- closing hidsubpart -->
';

}





function html_splitpage ($how)
{
  if ($how == 'start' || $how == '1')
    {
      return "\n".'<div class="splitright">'."\n";

    }
  elseif ($how == 'middle' || $how == '2')
    {
      return  "\n".'</div><!-- end  splitright -->'."\n".
	'<div class="splitleft">'."\n";

    }
  else
    {
      return "\n".'</div><!-- end  splitleft -->'."\n";
    }
}


function html_nextprev ($search_url, $rows, $rows_returned, $varprefix=false)
{
  global $offset, $max_rows;

  if (!$varprefix)
    { $varprefix = ''; }
  else
    { $varprefix .= '_'; }

  if (($rows_returned > $rows) || ($offset != 0))
    {
      print "\n<br /><h5 class=\"nextprev\">\n";

      if ($offset != 0)
	{
	  print '<a href="'.$search_url.'&amp;'.$varprefix.'offset='.($offset-$rows).'&amp;'.$varprefix.'max_rows='.$max_rows.'#'.$varprefix.'results">';
	  print '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/arrows/previous.png" border="0" alt="'._("Previous Results").'" />'._("Previous Results").'</a>';

	}
      else
	{
	  print '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/arrows/previousgrey.png" border="0" alt="'._("Previous Results").'" /><em>'._("Previous Results").'</em>';
	}

      print "&nbsp; &nbsp; &nbsp;";

      if ($rows_returned > $rows)
	{
	  print '<a href="'.$search_url.'&amp;'.$varprefix.'offset='.($offset+$rows).'&amp;'.$varprefix.'max_rows='.$max_rows.'#'.$varprefix.'results">';
	  print _("Next Results").' <img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/arrows/next.png" border="0" alt="'._("Next Results").'" /></a>';
	}
      else
	{
	  print '<em>'._("Next Results").'</em> <img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/arrows/nextgrey.png" border="0" alt="'._("Next Results").'" />';
	}

      print "</h5>\n";
    }
}

function html_anchor ($content, $name)
{
  if (!$name) { $name = $content; };
  return '<a name="'.$name.'" href="#'.$name.'">'.$content.'</a>';
}

##
# Print out the feedback
function html_feedback($bottom)
{
  global $feedback, $ffeedback;

  # Escape the html special chars, active markup

  // Ugh... Actually this is because feedback may be passed through
  // $_GET[] in some situations, which can lead to XSS if the content
  // is not escaped. We need a proper way to display formatted text to
  // the user - OR, we need to properly replace pages that pass
  // 'feedback' as a GET argument (grep 'feedback=').

  $feedback = markup_basic(htmlspecialchars($feedback));
  $ffeedback = markup_basic(htmlspecialchars($ffeedback));

  # Be quiet when there is no feedback.
  if (!($GLOBALS['ffeedback'] || $GLOBALS['feedback']))
    return;

  $suffix = '';
  if ($bottom)
    $suffix = '_bottom';

  $script_hide = 'onclick="document.getElementById(\'feedback'.$suffix.'\')'.
                 '.style.visibility=\'hidden\'; '.
                 'document.getElementById(\'feedbackback'.$suffix.
                 '\').style.visibility=\'visible\';"';
  $script_show = 'onclick="document.getElementById(\'feedback'.$suffix.'\')'.
                 '.style.visibility=\'visible\'; '.
                 'document.getElementById(\'feedbackback'.$suffix.
                 '\').style.visibility=\'hidden\';"';

  # With MSIE  the feedback will be be 
  # in relative position, so the hiding link will not make sense
  if (is_broken_msie() && empty($_GET['printer']))
    { $script_hide = ''; }
  # Users can choose the same behavior, disallowing the fixed positionning
  # of the feedback (less convenient as the feedback gets easily hidden,
  # requires to scroll to be accessed, but seems prefered by users of 
  # mozilla that slow scrolling down/up when there is such fixed box on the
  # page)
  if (user_get_preference("nonfixed_feedback"))
    { $script_hide = 'style="top: 0; right: 0; bottom: 0; left: 0; position: relative"'; }

  print '<div '.$script_show.
        ' id="feedbackback'.$suffix.'" class="feedbackback">'.
        _("Show feedback again").'</div>';

  # Only success
  if ($GLOBALS['feedback'] && !$GLOBALS['ffeedback'])
    {
        print '<div id="feedback'.$suffix.'" class="feedback" '.
              $script_hide.'><span class="feedbacktitle"><img src="'.
              $GLOBALS['sys_home'].'images/'.SV_THEME.
              '.theme/bool/ok.png" class="feedbackimage" alt="" /> '.
              _("Success:").'</span> '.$GLOBALS['feedback'].'</div>';
    }

  # Only errors
  if ($GLOBALS['ffeedback'] && !$GLOBALS['feedback'])
    {
      print '<div id="feedback'.$suffix.'" class="feedbackerror" '.
            $script_hide.'><span class="feedbackerrortitle"><img src="'.
            $GLOBALS['sys_home'].'images/'.SV_THEME.
            '.theme/bool/wrong.png" class="feedbackimage" alt="" /> '.
            _("Error:").'</span><br/>'.$GLOBALS['ffeedback'].'</div>';
    }

  # Errors and success
  if ($GLOBALS['ffeedback'] && $GLOBALS['feedback'])
    {
      print '<div id="feedback'.$suffix.'" class="feedbackerrorandsuccess" '.
            $script_hide.'><span class="feedbackerrorandsuccesstitle">'.
            '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.
            '.theme/bool/wrong.png" class="feedbackimage" alt="" /> '.
            _("Some Errors:").'</span>'.$GLOBALS['feedback'].' '.
            $GLOBALS['ffeedback'].'</div>';
    }

  # We empty feedback so there will be a bottom feedback only if something
  # changed. It may confuse users, however I would find more confusing to
  # have two lookalike feedback information providing most of the time the
  # same information AND (that is the problem) sometimes more information
  # in the second one.
  $GLOBALS['feedback'] = '';
  $GLOBALS['ffeedback'] = '';
}

function html_feedback_top()
{
  html_feedback(0);
}

function html_feedback_bottom()
{
  html_feedback(1);
}

function html_image ($src,$args,$display=1)
{
  GLOBAL $img_attr;
  $return = ('<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/'.$src.'"');
  reset($args);
  while(list($k,$v) = each($args))
    {
      $return .= ' '.$k.'="'.$v.'"';
    }

  # ## insert a border tag if there isn't one
  if (empty($args['border'])) $return .= (' border="0"');


  # ## if no height AND no width tag, insert em both
  if (empty($args['height']) and empty($args['width']))
    {

     #Check to see if we've already fetched the image data
 if(!$img_attr[$src] && is_file($GLOBALS['sys_www_topdir'].'/images/'.SV_THEME.'.theme/'.$src))
   {
     list($width, $height, $type, $img_attr[$src]) = @getimagesize($GLOBALS['sys_www_topdir'].'/images/'.SV_THEME.'.theme/'.$src);
     
   }
 else
   {
     if (is_file($GLOBALS['sys_www_topdir'].'/images/'.SV_THEME.'.theme/'.$src))
       {
	 list($width, $height, $type, $img_attr[$src])  = @getimagesize($GLOBALS['sys_www_topdir'].'/images/'.SV_THEME.'.theme/'.$src);
       }
   }
 $return .= ' ' . $img_attr[$src];
}

# ## insert alt tag if there isn't one
  if (!$args['alt']) $return .= " alt=\"$src\"";

  $return .= (' />');
  if ($display)
    {
      print $return;
    }
  else
    {
      return $return;
    }
}

function html_build_list_table_top ($title_arr,$links_arr=false,$table=true)
{
  /*
		Takes an array of titles and builds
		The first row of a new table

		Optionally takes a second array of links for the titles
  */
  GLOBAL $HTML;
  $return = '';

  if ($table)
    {
     $return = '
  <table class="box">';
    }

     $return .= '
		<tr>';

  $count=count($title_arr);
  if ($links_arr)
    {
      for ($i=0; $i<$count; $i++)
	{
	  $return .= '
			<th class="boxtitle"><a class="sortbutton" href="'.$links_arr[$i].'">'.$title_arr[$i].'</a></th>';
	}
    }
  else
    {
      for ($i=0; $i<$count; $i++)
	{
	  $return .= '
			<th class="boxtitle">'.$title_arr[$i].'</th>';
	}
    }
  return $return.'</tr>';
}

function html_get_alt_row_color ($i)
{
  GLOBAL $HTML;
  if ($i % 2 == 0)
    {
      return 'boxitemalt';
    }
  else
    {
      return 'boxitem';
    }
}

#deprecated
function utils_get_alt_row_color ($i)
{
  return html_get_alt_row_color ($i);
}



function html_build_select_box_from_array ($vals,$select_name,$checked_val='xzxz',$samevals = 0)
{
  /*
		Takes one array, with the first array being the "id" or value
		and the array being the text you want displayed

		The second parameter is the name you want assigned to this form element

		The third parameter is optional. Pass the value of the item that should be checked
  */

  $return = '';
  $return .= '
		<select name="'.$select_name.'">';

  $rows=count($vals);

  for ($i=0; $i<$rows; $i++)
    {
      if ($samevals)
	{
	  $return .= "\n\t\t<option value=\"" . $vals[$i] . "\"";
	  if ($vals[$i] == $checked_val)
	    {
	      $return .= ' selected="selected"';
	    }
	}
      else
	{
	  $return .= "\n\t\t<option value=\"" . $i .'"';
	  if ($i == $checked_val)
	    {
	      $return .= ' selected="selected"';
	    }
	}
      $return .= '>'.$vals[$i].'</option>';
    }
  $return .= '
		</select>';

  return $return;
}

function html_build_select_box_from_arrays ($vals,
					    $texts,
					    $select_name,
					    $checked_val='xzxz', #4
					    $show_100=true,
					    $text_100='None', #6
					    $show_any=false,
					    $text_any='Any', #8
					    $show_unknown=false)
{
  /*

  The infamous '100 row' has to do with the
			SQL Table joins done throughout all this code.
		There must be a related row in users, categories, etc, and by default that
			row is 100, so almost every pop-up box has 100 as the default
		Most tables in the database should therefore have a row with an id of 100 in it
			so that joins are successful

		There is now another infamous row called the Any row. It is not
		in any table as opposed to 100. it's just here as a convenience mostly
		when using select boxes in queries (bug, task,...). The 0 value is reserved
		for Any and must not be used in any table.

		Params:

		Takes two arrays, with the first array being the "id" or value
		and the other array being the text you want displayed

		The third parameter is the name you want assigned to this form element

		The fourth parameter is optional. Pass the value of the item that should be checked

		The fifth parameter is an optional boolean - whether or not to show the '100 row'

		The sixth parameter is optional - what to call the '100 row' defaults to none
		The 7th parameter is an optional boolean - whether or not to show the 'Any row'

		The 8th parameter is optional - what to call the 'Any row' defaults to nAny	*/



  $return = '';
  $return .= '
		<select name="'.$select_name.'">';


  # We want the "Default" on item initial post, only at this momement
  if ($show_unknown)
    {
      $return .= "\n<option value=\"!unknown!\">"._("Unknown")."</option>";
    }

  #we don't always want the default any  row shown
  if ($show_any)
    {
      $selected = ( $checked_val == 0 ? 'selected="selected"':'');
      $return .= "\n<option value=\"0\" $selected>$text_any </option>";
    }

  #we don't always want the default 100 row shown
  if ($show_100)
    {
      $selected = ( $checked_val == 100 ? 'selected="selected"':'');
      $return .= "\n<option value=\"100\" $selected>$text_100 </option>";
    }


  $rows=count($vals);
  if (count($texts) != $rows)
    {
      $return .= 'ERROR - uneven row counts';
    }



  for ($i=0; $i<$rows; $i++)
    {
      #  uggh - sorry - don't show the 100 row and Any row
      #  if it was shown above, otherwise do show it
      if ((($vals[$i] != '100') && ($vals[$i] != '0')) ||
	   ($vals[$i] == '100' && !$show_100) ||
	   ($vals[$i] == '0' && !$show_any))
	{
	  $return .= '
				<option value="'.$vals[$i].'"';
	  if ($vals[$i] == $checked_val)
	    {
	      $return .= ' selected="selected"';
	    }
	  $return .= '>'.$texts[$i].'</option>';

       }

    }
  $return .= '
		</select>';
  return $return;
}

function html_build_select_box ($result, $name, $checked_val="xzxz",$show_100=true,$text_100='None',$show_any=false,$text_any='Any',$show_unknown=false)
{
  /*
		Takes a result set, with the first column being the "id" or value
		and the second column being the text you want displayed

		The second parameter is the name you want assigned to this form element

		The third parameter is optional. Pass the value of the item that should be checked

		The fourth parameter is an optional boolean - whether or not to show the '100 row'

		The fifth parameter is optional - what to call the '100 row' defaults to none
  */

  return html_build_select_box_from_arrays (utils_result_column_to_array($result,0),utils_result_column_to_array($result,1),$name,$checked_val,$show_100,$text_100,$show_any,$text_any,$show_unknown);
}

function html_build_multiple_select_box ($result,$name,$checked_array,$size='8',$show_100=true,$text_100='None', $show_any=false,$text_any='Any',$show_value=true)
{
  /*
		Takes a result set, with the first column being the "id" or value
		and the second column being the text you want displayed

		The second parameter is the name you want assigned to this form element

		The third parameter is an array of checked values;

		The fourth parameter is optional. Pass the size of this box

		Fifth to eigth params determine whether to show None and Any

		Ninth param determine whether to show numeric values next to
		the menu label (default true for backward compatibility
  */

  $checked_count=count($checked_array);
  #      print '-- '.$checked_count.' --';
  $return = '
		<SELECT NAME="'.$name.'" MULTIPLE SIZE="'.$size.'">';
  /*
		Put in the Any box
  */
  if ($show_any)
    {
      $return .= '
		<option value="0"';
      for ($j=0; $j<$checked_count; $j++)
	{
	  if ($checked_array[$j] == '0')
	    {
	      $return .= ' selected="selected"';
	    }
	}
      $return .= '>'.$text_any.'</option>';
    }

  /*
		Put in the default NONE box
  */
  if ($show_100)
    {
      $return .= '
		<option value="100"';
      for ($j=0; $j<$checked_count; $j++)
	{
	  if ($checked_array[$j] == '100')
	    {
	      $return .= ' selected="selected"';
	    }
	}
      $return .= '>'.$text_100.'</option>';
    }

  $rows=db_numrows($result);

  for ($i=0; $i<$rows; $i++)
    {
      if (db_result($result,$i,0) != '100')
	{
	  $return .= '
				<option value="'.db_result($result,$i,0).'"';
	  /*
				Determine if it's checked
	  */
	  $val=db_result($result,$i,0);
	  for ($j=0; $j<$checked_count; $j++)
	    {
	      if ($val == $checked_array[$j])
		{
		  $return .= ' selected="selected"';
		}
	    }
	  $return .= '>'. ($show_value?$val.'-':'').
	     substr(db_result($result,$i,1),0,35). '</option>';
	}
    }
  $return .= '
		</SELECT>';
  return $return;
}

function html_select_permission_box ($artifact, $row, $level="member")
{
  # If $row['user_id'] does not exists, we havent got a row but simple value
  # and it means that we are about to modify per group default
  $num = '';
  if ($level == "type")
    {
      $value = $row;
      $default = 0;
    }
  elseif ($level == "group")
    {
      $value = $row;
      $default = _("Group Type Default");
    }
  else
    {
      $num = $row['user_id'];
      $value = $row[$artifact.'_flags'];
      $default = _("Group Default");
    }

  print '
    <td align="center">
      <select name="'.$artifact.'_user_'.$num.'">';
  if ($default)
    {
      print '
        <option value="NULL"'.((!$value)?" selected=\"selected\"":"").'>'.$default.'</option>';
    }
  print '
        <option value="9"'.(($value == 9)?" selected=\"selected\"":"").'>'._("None").'</option>';
  if ($artifact != "news")
    {
      print '
        <option value="1"'.(($value == 1)?" selected=\"selected\"":"").'>'._("Technician").'</option>';
    }

  print '
        <option value="3"'.(($value == 3)?" selected=\"selected\"":"").'>'._("Manager").'</option>';


  if ($artifact != "news")
    {
      print '
        <option value="2"'.(($value == 2)?" selected=\"selected\"":"").'>'._("Techn. & Manager").'</option>';
    }

  print '
      </select>';

  if (!$value && $level == "group")
    {
      $value = group_gettypepermissions($GLOBALS['group_id'], $artifact);
      print '<br />('.
	(($value == 9)?_("None"):"").
	(($value == 1)?_("Technician"):"").
	(($value == 3)?_("Manager"):"").
	(($value == 2)?_("Techn. & Manager"):"").
	')';
    }

  print '
    </td>';
}


function html_select_restriction_box ($artifact, $row, $level="group", $notd=0, $event=1)
{
  # event = 1 : posting items
  # event = 2 : posting comments

  # If $row['user_id'] does not exists, we havent got a row but simple value
  # and it means that we are about to modify per group default
  if ($level == "type")
    {
      $value = $row;
      $default = 0;
    }
  else
    {
      $value = $row;
      if ($event == 2)
	{
	  $default = _("Same as for new items");
	}
      else
	{
	  $default = _("Group Type Default");
	}
    }

  if (!$notd)
    {
      print '
    <td align="center">';
    }

  print '
      <select name="'.$artifact.'_restrict_event'.$event.'">';

  if ($default)
    {
      print '
        <option value="NULL"'.((!$value)?" selected=\"selected\"":"").'>'.$default.'</option>';
    }
  print '
        <option value="5"'.(($value == 5)?" selected=\"selected\"":"").'>'._("Project Member").'</option>';
  print '
        <option value="3"'.(($value == 3)?" selected=\"selected\"":"").'>'._("Logged-in User").'</option>';
  print '
        <option value="2"'.(($value == 2)?" selected=\"selected\"":"").'>'._("Anonymous").'</option>';

  print '
      </select>';

  if (!$value && $level == "group" && $event == 1)
    {
      $value = group_gettyperestrictions($GLOBALS['group_id'], $artifact);
      print '<br />('.
	(($value == 5)?_("Project Member"):"").
	(($value == 3)?_("Logged-in User"):"").
	(($value == 2)?_("Anonymous"):"").
	')';
    }


  if (!$notd)
    {
      print '
    </td>';
    }
}


# This function must now every type of directory that can be built by the
# backend.
# FIXME: in a future, we may create a table of method associating
# method -> perl module -> sub name
function html_select_typedir_box ($input_name, $current_value)
{
  print '<br />&nbsp;&nbsp;
      <select name="'.$input_name.'">
        <option value="basicdirectory"'.(($current_value == "basicdirectory")?" selected":"").'>'._("Basic Directory").'</option>
        <option value="basiccvs"'.(($current_value == "basiccvs")?" selected=\"selected\"":"").'>'._("Basic Cvs Directory").'</option>
        <option value="basicsvn"'.(($current_value == "basicsvn")?" selected=\"selected\"":"").'>'._("Basic Subversion Directory").'</option>
        <option value="basicgit"'.(($current_value == "basicgit")?" selected=\"selected\"":"").'>'._("Basic Git Directory").'</option>
        <option value="basichg"'.(($current_value == "basichg")?" selected=\"selected\"":"").'>'._("Basic Mercurial Directory").'</option>
        <option value="basicbzr"'.(($current_value == "basicbzr")?" selected=\"selected\"":"").'>'._("Basic Bazaar Directory").'</option>
        <option value="cvsattic"'.(($current_value == "cvsattic")?" selected=\"selected\"":"").'>'._("Cvs Attic/Gna").'</option>
        <option value="svnattic"'.(($current_value == "svnattic")?" selected=\"selected\"":"").'>'._("Subversion Attic/Gna").'</option>
        <option value="svnatticwebsite"'.(($current_value == "svnatticwebsite")?" selected=\"selected\"":"").'>'._("Subversion Subdirectory Attic/Gna").'</option>
        <option value="savannah-gnu"'.(($current_value == "savannah-gnu")?" selected=\"selected\"":"").'>'._("Savannah GNU").'</option>
        <option value="savannah-nongnu"'.(($current_value == "savannah-nongnu")?" selected=\"selected\"":"").'>'._("Savannah non-GNU").'</option>
      </select> [BACKEND SPECIFIC]
';

  # put some information
  print '<br /><span class="smaller">Basic directory will make the backend using DownloadMakeArea(), defined in Savannah::Download; <br /> CVS directory will make the backend using CvsMakeArea(), defined in Savannah::Cvs;<br />
(If you need to build directories with another method, the solution is to write a new sub in the appropriate perl module, please send a mail to savane-dev@gna.org to get information about that)</span><br /><br />';

}

# Print an theme select box. This function will add the special rotate
# and random meta-themes. This function will hide disallowed theme.
# That is said that theme are not strictly forbidden, someone can
# forge a form and choose a forbidden theme.
# But it is really not a big deal and it is better to have making to
# many checks in theme_list(), which is frequently ran, unlike this
# one.
function html_select_theme_box ($input_name="user_theme", $current=0)
{

  print '
		<select name="'.$input_name.'">';

  # usual themes
  foreach (theme_list() as $theme)
    {
      print "\n\t\t".'<option value="'.$theme.'"';
      if ($theme == $current)
	{ print ' selected="selected"'; }
      print '>'.$theme;
      if ($theme == $GLOBALS['sys_themedefault'])
	{ print ' ('._("default").')'; }
      print '</option>'."\n";
    }

  # special rotate case
  $theme = "rotate";
  print "\n\t\t".'<option value="'.$theme.'"';
  if ($theme == $current)
    { print ' selected="selected"'; }
  print '> &gt; '.("alphabetically picked everyday").'</option>';
  # special random case
  $theme = "random";
  print "\n\t\t".'<option value="'.$theme.'"';
  if ($theme == $current)
    { print ' selected="selected"'; }
  print '> &gt; '.("randomly picked everyday").'</option>';
  print "		</select>\n";

}

function html_build_priority_select_box ($name='priority', $checked_val='5')
{
  /*
		Return a select box of standard priorities.
		The name of this select box is optional and so is the default checked value
  */
    ?>
     <SELECT NAME="<?php print $name; ?>">
	<option value="1"<?php if ($checked_val=="1")
	  {print " selected=\"selected\"";} ?>>1 - Lowest</option>
				    <option value="2"<?php if ($checked_val=="2")
				      {print " selected=\"selected\"";} ?>>2</option>
								<option value="3"<?php if ($checked_val=="3")
								  {print " selected=\"selected\"";} ?>>3</option>
											    <option value="4"<?php if ($checked_val=="4")
											      {print " selected=\"selected\"";} ?>>4</option>
															<option value="5"<?php if ($checked_val=="5")
															  {print " selected=\"selected\"";} ?>>5 - Medium</option>
																		    <option value="6"<?php if ($checked_val=="6")
																		      {print " selected=\"selected\"";} ?>>6</option>
																						<option value="7"<?php if ($checked_val=="7")
																						  {print " selected=\"selected\"";} ?>>7</option>
																									    <option value="8"<?php if ($checked_val=="8")
																									      {print " selected=\"selected\"";} ?>>8</option>
																													<option value="9"<?php if ($checked_val=="9")
																													  {print " selected=\"selected\"";} ?>>9 - Highest</option>
																																    </SELECT>
																																    <?php

																																    }
function html_buildpriority_select_box ($name='priority', $checked_val='5')
{
  return html_build_priority_select_box($name, $checked_val);
}

function html_build_checkbox ($name, $is_checked=0)
{
  print  '<input type="checkbox" name="'.$name.'" value="1"';
  if ($is_checked)
    { print ' checked="checked"'; }
  print ' />';
}

##
# Catch all header functions
function html_header($params)
{
  global $HTML, $feedback;
  print $HTML->header($params);
  print html_feedback_top($feedback);
}

function html_footer($params)
{
  global $HTML, $feedback;
  print html_feedback_bottom($feedback);
  $HTML->footer($params);
}

##
# aliases of catch all header functions
function site_header($params)
{
  html_header($params);
}

function site_footer($params)
{
  html_footer($params);
}

/*
	Project pages functions
	----------------------------------------------------------------
*/


/*! 	@function site_project_header
	@abstract everything required to handle security and state checks for a project web page
	@param params array() must contain $context and $group
	@result text - prints HTML to the screen directly
*/
function site_project_header($params)
{
  global $group_id;

  #get the project object
  $project=project_get_object($group_id);

  #group doesn't exist
  if ($project->isError())
    {
      exit_error("Invalid Group $group_id","That group does not exist.");
    }

  #group is private
  if (!$project->isPublic())
    {
      #if its a private group, you must be a member of that group
      session_require(array('group'=>$group_id));
    }

  #for dead projects must be member of admin project
  if (!$project->isActive())
    {
      # only sys_group people can view non-active, non-holding groups
      session_require(array('group'=>$GLOBALS['sys_group_id']));
    }

  html_header($params);
}

/*!     @function site_project_footer
	@abstract currently a simple shim that should be on every project page,
		rather than a direct call to site_footer() or theme_footer()
	@param params array() empty
	@result text - prints HTML to the screen directly
*/
function site_project_footer($params)
{
  html_footer($params);
}

/*
	User pages functions
	----------------------------------------------------------------
*/

/*!     @function site_user_header
	@abstract everything required to handle security and
		add navigation for user pages like /my/ and /account/
	@param params array() must contain $user_id
	@result text - prints HTML to the screen directly
*/
function site_user_header($params)
{
  session_require(array('isloggedin'=>'1'));
  html_header($params);
}

/*!     @function site_user_footer
	@abstract currently a simple shim that should be on every user page,
		rather than a direct call to site_footer() or theme_footer()
	@param params array() empty
	@result text - prints HTML to the screen directly
*/
function site_user_footer($params)
{
  html_footer($params);
}

/*
	Administrative pages functions
	----------------------------------------------------------------
*/

function site_admin_header($params)
{
  session_require(array('group'=>'1','admin_flags'=>'A'));
  html_header($params);
}

function site_admin_footer($params)
{
  html_footer($params);
}

function show_group_type_box($name='group_type',$checked_val='xzxz', $show_select_one=false)
{
  $result=db_query("SELECT * FROM group_type");
  return html_build_select_box($result,'group_type',$checked_val,$show_select_one, "> "._("Choose one below"));
}


function html_member_explain_roles ()
{

  print _("Technicians, and only technicians, can be assigned tracker's items. They cannot reassign items, change the status or priority of items.");
  print '<p>';
  print _("Trackers Managers can fully manage the trackers items, including assigning items to technicians, reassign items over trackers and projects, changing priority and status of items - but they cannot configure the trackers.");
  print '<p>';
  print _("Project Admins can manage members, configure the trackers, post jobs, and add mailing-list. They actually also have manager rights on every tracker and are allowed to read private items.");
  print '<p>';
}
