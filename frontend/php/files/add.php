<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2002-2003 (c) Mathieu Roy <yeupou--at--gnu.org>
#
# The Savane project is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# The Savane project is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


# FIXME: this page wont show up if there is nothing for the filelist
# in the download area. It is problematic for people that want to use
# it without having already files in their download area

require '../include/pre.php';

$project=project_get_object($group_id);

if ($group_id) {

  if (member_check(0,$group_id)) {

    site_project_header(array('title'=>_("Add"),
			      'group'=>$group_id,
			      'context'=>'download'));


    # NOTE: because it was not really needed for the backend and frontend,
    # the download host configuration  per group type has been removed.
    # However, if you really need Download type host that would be group
    # specific and not equal to the TypeHost, write to savannah-dev@gnu.org
    # Will find a way to configure that.

    $files_dir = $project->getTypeDir("download").$project->getUnixName();
    $files_path = $project->getTypeUrl("download").$project->getUnixName();
    $scp_dir = $project->getTypeBaseHost().":".$files_dir;

    if ( $project->getTypeName() == 'GNU' ){
	$scp_dir = "*not available*".$files_dir;
	echo '<h3 class="error">'._("Note for GNU projects").'</h3>';
	echo '<p>'._("Currently, since file uploads to ftp.gnu.org are not supported by Savannah at present (You should get in touch with accounts@gnu.org to get permissions to do them), the form below cannot be used.").'</p>';
	echo '<p>'._("But, file release system should work if you respect the standard tree explained below.").'</p>';
    }


    printf ('<h2>'._("How does file list work on %s?").'</h2>',$GLOBALS['sys_name']);
    echo '<p>'.sprintf(_("On %s, you must use scp, sftp or rsync via SSH to upload your files."),$GLOBALS['sys_name']);
	echo _("We prefer this way of managing files for security matters and also because scp and rsync are designed for this kind of usage, unlike a web browser.");
	echo ' '._("We want the file management system to be usable with a shell.").'</p>';
	echo '<p>'._("The following form won't upload files. It will display a list of commands that you will have to copy and paste in a terminal.").'</p>';
	printf ('<p>'._("If you follow the directory organisation outlined below, the %s %s Filelist for your project%s (available once you have checked the corresponding box in your %s Project Public Info %s) will display the versions in a user friendly way. Otherwise, the visitor of your project will be presented a regular directory listing.").'</p>','<a href="'.$GLOBALS['sys_home'].'files/?group_id='.$group_id.'">',$GLOBALS['sys_name'],'</a>','<a href="'.$GLOBALS['sys_home'].'project/admin/editgroupinfo.php?group_id='.$group_id.'">','</a>');
    echo '<p>'._("The suggested layout is as follows:").'<br />';
    echo '&nbsp;&nbsp;'.$files_dir.' &nbsp;&nbsp;&nbsp;<em>'._("is your dedicated area").'</em><br />';
    echo '&nbsp;&nbsp;&nbsp;&nbsp;'.$files_dir.'/unstable.pkg &nbsp;&nbsp;&nbsp;<em>'._("is the package/branch name, since you can have as many packages/branches as you want.").' '._("It must end with .pkg.").'</em><br />';
    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$files_dir.'/unstable.pkg/1.0.0 &nbsp;&nbsp;&nbsp;<em>'._("is the version name/number for the package/branch we call unstable.").'</em><br />';
    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$files_dir.'/unstable.pkg/1.0.0/my_project-1.0.0.tar.gz &nbsp;&nbsp;&nbsp;<em>'._("is a package that users can download for this version of this package/branch.").'</em></p>';


    echo '<p class="error">'._("Note that the package name should be the name of the .tar.gz file. Or whatever you want, but it must not contain characters as \" \" (whitespace) or \"/\" (slash).").'</p>';

    echo '<p><strong class="error">'._("Help!").'</strong> '.sprintf (_("You may take a look at the %s FAQ about %sHow do I add files in the download area%s, especially the \"Tips\" part.").'</p>',$GLOBALS['sys_name'],'<a href="'.$GLOBALS['sys_home'].'faq/?group_id=11&question=How_do_I_add_files_in_the_download_area.txt">','</a>');


    echo '<h2>'._("Getting a command list to upload the files:").'</h2>';
    echo '<form action="'. $PHP_SELF .'#list" method="post">
<table>';
    echo '<input type="hidden" name="group_id" value="'.$group_id.'" />';
    echo '<tr><td>'._("Package / Branch:").' </td><td><input type="text" name="pkg" value="'.$pkg.'" size="20" maxlength="30" /> ('._("ex: the project name, or stable / unstable").')</td></tr>';
    echo '<tr><td>'._("Version:").' </td><td><input type="text" name="version" value="'.$version.'" size="20" maxlength="30" /> ('._("ex: 1.0.0 or 20020427").')</td></tr>';
    echo '<tr><td>'._("1st File:").' </td><td><input type="text" name="input_file1" size="40" value="'.$input_file1.'" /> ('
	._("this should be the absolute path of the file on your machine").')</td></tr>';
    echo '<tr><td>'._("2nd File:").' </td><td><input type="text" name="input_file2" size="40" value="'.$input_file2.'" /> ('._("this should be the absolute path of the file on your machine").')</td></tr>';
    echo '<tr><td>'._("3rd File:").' </td><td><input type="text" name="input_file3" size="40" value="'.$input_file3.'" /> ('._("this should be the absolute path of the file on your machine").')</td></tr>';
    echo '<tr><td>'._("4th File:").' </td><td><input type="text" name="input_file4" size="40" value="'.$input_file4.'" /> ('._("this should be the absolute path of the file on your machine").')</td></tr>';
    echo '<tr><td>'._("5th File:").' </td><td><input type="text" name="input_file5" size="40" value="'.$input_file5.'" /> ('._("this should be the absolute path of the file on your machine").')</td></tr>';
    echo '<tr><td>'._("6th File:").' </td><td><input type="text" name="input_file6" size="40" value="'.$input_file6.'" /> ('._("this should be the absolute path of the file on your machine").')</td></tr>';
    echo '<tr><td>'._("7th File:").' </td><td><input type="text" name="input_file7" size="40" value="'.$input_file7.'" /> ('._("this should be the absolute path of the file on your machine").')</td></tr>';
    echo '<tr><td>'._("8th File:").' </td><td><input type="text" name="input_file8" size="40" value="'.$input_file8.'" /> ('._("this should be the absolute path of the file on your machine").')</td></tr>';
    echo '<tr><td>'._("9th File:").' </td><td><input type="text" name="input_file9" size="40" value="'.$input_file9.'" /> ('._("this should be the absolute path of the file on your machine").')</td></tr>
</table>';
    echo '<br /><input type="submit" name="submit_command_list" value="'._("Show me the commands list").'"" />&nbsp;&nbsp;&nbsp;&nbsp;';

#echo '<INPUT TYPE="SUBMIT" NAME="submit_last_release" VALUE="Set this version as latest release">';

    echo '<form>';

  function scp_command($input_file,$scp_dir,$pkg,$version){
   if ($input_file!=""){
     echo '<br />&nbsp;&nbsp;&nbsp;# package '.$input_file;
     echo '<br />&nbsp;&nbsp;&nbsp;mkdir -pv /tmp/sv_upload/'.$pkg.'.pkg/'.$version.'/ && cp "'.$input_file.'" /tmp/sv_upload/'.$pkg.'.pkg/'.$version.'/';
     echo '<br />&nbsp;&nbsp;&nbsp;scp -vr /tmp/sv_upload/* '.user_getname().'@'.$scp_dir.'/';
     echo '<br />&nbsp;&nbsp;&nbsp;rm -rf /tmp/sv_upload';
   }
 }

 if ($submit_command_list) {
   echo '<h3>'._("Here is the command list:").'</h3>';
   if($pkg=="") {
	echo '<span class="error">'._("You must specify a Package / Branch name.").'</span>';
   } elseif($version=="") {
        echo '<span class="error">'._("You must specify a Version name.").'</span>';
   } else {
     echo '<a name="list"></a><p>'._("Here is the result of the information you give.").' '._("Basically, you just have to copy and paste those commands in a terminal.").'';
scp_command($input_file1,$scp_dir,$pkg,$version);
scp_command($input_file2,$scp_dir,$pkg,$version);
scp_command($input_file3,$scp_dir,$pkg,$version);
scp_command($input_file4,$scp_dir,$pkg,$version);
scp_command($input_file5,$scp_dir,$pkg,$version);
scp_command($input_file6,$scp_dir,$pkg,$version);
scp_command($input_file7,$scp_dir,$pkg,$version);
scp_command($input_file8,$scp_dir,$pkg,$version);
scp_command($input_file9,$scp_dir,$pkg,$version);
   }
 }

if ($submit_last_release){
  echo '<a name="list"></a>';
  if(file_exists($files_dir."/".$pkg.".pkg/".$version)) {
	$latest_release_is_file = $files_dir."/".$pkg.".pkg/LATEST_RELEASE_IS";
	$fopened = fopen($latest_release_is_file,"w");
	fputs($fopened,$version);
	fclose($fopened);

	echo '<h3 class="error">'._("UPDATED: now the specified version is highlighted").'</h3>';
	printf (_("Note that you can also add a #%s to the HTML links to your filelist, as %s"),$pkg.$version,'<a href="'.$GLOBALS['sys_home'].'files/?group='.$group_name.'#'.$pkg.$version.'">http://'.$GLOBALS['sys_default_domain'].$GLOBALS['sys_home'].'files/?group='.$group_name.'#'.$pkg.$version.'</a>');
	printf ('<p>'._("Alternatively, you can make a link to %s").'</p>', '<a href="'.$GLOBALS['sys_home'].'files/?group='.$group_name.'#'.pkg.'latest">http://'.$GLOBALS['sys_default_domain'].$GLOBALS['sys_home'].'files/?group='.$group_name.'#'.$pkg.'latest</a>');
	echo '<h3>'._("How does it work, how to do this without a web browser?").'</h3>
	'._("It just creates a nice LATEST_RELEASE_IS file in the package dir with the version name as content.").'
	<p>'._("You can do this job exactly by making a similar file and upload it:").'<br />
	<br />&nbsp;&nbsp;&nbsp;echo "'.$version.'" &gt; LATEST_RELEASE_IS
        <br />&nbsp;&nbsp;&nbsp;scp LATEST_RELEASE_IS '.user_getname().'@'.$scp_dir.'/'.$pkg.'.pkg/</p>';

  } else {
	printf ('<h3 class="error">'._("There is no such Package / Branch or Version online for %s"),$project->getUnixName());
	echo '</h3>';
  }

}

site_project_footer(array());

} else {
  exit_error(_("Error"),_("You do not have the required privileges to access this page"));
}

} else {
  exit_no_group();
}

?>
