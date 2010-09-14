<?php
// $Id: groupperms.php,v 1.7 2004/07/26 17:51:25 hthouzard Exp $
// ------------------------------------------------------------------------ //
// XOOPS - PHP Content Management System            				        //
// Copyright (c) 2000 XOOPS.org                           					//
// <http://www.xoops.org/>                             						//
// ------------------------------------------------------------------------ //
// This program is free software; you can redistribute it and/or modify     //
// it under the terms of the GNU General Public License as published by     //
// the Free Software Foundation; either version 2 of the License, or        //
// (at your option) any later version.                                      //
// 																			//
// You may not change or alter any portion of this comment or credits       //
// of supporting developers from this source code or any supporting         //
// source code which is considered copyrighted (c) material of the          //
// original comment or credit authors.                                      //
// 																			//
// This program is distributed in the hope that it will be useful,          //
// but WITHOUT ANY WARRANTY; without even the implied warranty of           //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
// GNU General Public License for more details.                             //
// 																			//
// You should have received a copy of the GNU General Public License        //
// along with this program; if not, write to the Free Software              //
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
// ------------------------------------------------------------------------ //
include_once("admin_header.php");
include_once XOOPS_ROOT_PATH . "/class/xoopslists.php";
include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";
include_once XOOPS_ROOT_PATH . "/class/xoopsform/grouppermform.php";
include_once XOOPS_ROOT_PATH . "/modules/xpnews/admin/mygrouppermform.php";

error_reporting(E_ALL);

$mydirname = basename( dirname( dirname( __FILE__ ) ) );

xoops_cp_header();
adminMenu(2);

global $xoopsDB;

$maxr = 100;
$permtoset= isset($_POST['permtoset']) ? ($_POST['permtoset']) : 1;
$permtoseta = explode('_', $permtoset);
$selected=array('','','','');
$selected[$permtoseta[0]-1]=' selected';
$module_id = $xoopsModule->getVar('mid');

$newsgroup_handler = xoops_getmodulehandler('newsgroups', 'xpnews');
$server_handler = xoops_getmodulehandler('servers', 'xpnews');

$servers = $server_handler->getObjects(null);

$server_view = array(); 
foreach( $servers as $server ) {
	$server_view[] = array("server_id" => $server->getVar('server_id'), "title" => $server->getVar('name'));
}

echo "<form method='post' name='jselperm' action='groupperms.php'><table border=0><tr><td><select name='permtoset' onChange='javascript: document.jselperm.submit()'><option value='1'".$selected[0].">Viewing Server Permissions</option>";
foreach( $server_view as $key => $value ) {
	echo "<option value='". ($yy+2) .'_'.$value['server_id'].'_reply\''.$selected[$yy+1].">".$value['title']." Reply Permissions</option>";
	echo "<option value='". ($yy+3) .'_'.$value['server_id'].'_crosspost\''.$selected[$yy+2].">".$value['title']." Crossposting Permissions</option>";
	echo "<option value='". ($yy+4) .'_'.$value['server_id'].'_newpost\''.$selected[$yy+3].">".$value['title']." New Post Permissions</option>";
	$yy=$yy+3;
}
echo "</select></td><td></tr></table></form>";



$ts = &MyTextSanitizer::getInstance();

switch($permtoset)
{
	case 1:
		
		$title_of_form = "Select the server items that can be viewed";
		$perm_name = "xpnews_server_view";
		$perm_desc = "This is the items of newsgroups that can be viewed";
		$item_list_view = array();
		
		$permform = new XoopsGroupPermForm($title_of_form, $module_id, $perm_name, $perm_desc);
		foreach ($server_view as $itemlists) 
			$permform->addItem($itemlists['server_id'], $ts->displayTarea($itemlists['title']));
		
		break;

	default:
	
		$criteria = new Criteria('server_id', $permtoseta[1]);
		$newsgroups = $newsgroup_handler->getObjects($criteria);
		
		$title_of_form = "Select the server items that can be ".$permtoseta[2];
		$perm_name = $permtoseta[1].'_'.$permtoseta[2];
		$perm_desc = "This is the items of newsgroups that can be ".$permtoseta[2];
		$item_list_view = array();
		
		$permform = new XoopsGroupPermForm($title_of_form, $module_id, $perm_name, $perm_desc);
		foreach ($newsgroups as $newsgroup) 
			$permform->addItem($newsgroup->getVar('id'), $ts->displayTarea($newsgroup->getVar('newsgroup')));
		
		break;

}

echo $permform->render();
echo "<br /><br /><br /><br />\n";
unset ($permform);
footer_adminMenu();
xoops_cp_footer();
?>