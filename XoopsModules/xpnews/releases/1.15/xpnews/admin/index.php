<?php
//  ------------------------------------------------------------------------ //
//                    Chronolabs xpNEWS - NNTP News Module                   //
//                    Copyright (c) 2007 chronolabs.org.au                   //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
// Author: Simon Roberts (AKA WISHCRAFT)                                     //
// Site: http://www.chronolabs.org.au                                        //
// Project: The Chrononaut Project                                           //
// ------------------------------------------------------------------------- //

include_once "admin_header.php";
include XOOPS_ROOT_PATH."/modules/xpnews/include/forms.php";
include XOOPS_ROOT_PATH . '/class/xoopsformloader.php';

error_reporting(E_ALL);
global $xoopsDB;

if (isset($_GET)) {
    foreach ($_GET as $k => $v) {
      ${$k} = $v;
    }
  }

  if (isset($_POST)) {
    foreach ($_POST as $k => $v) {
      ${$k} = $v;
    }
  }

switch($op){
	case "pollserver":

		require_once('../utils.inc.php');
		
		error_reporting(E_ALL);
		
		$newsgroup_handler = xoops_getmodulehandler('newsgroups', 'xpnews');
		$server_handler = xoops_getmodulehandler('servers', 'xpnews');

		$nnrp->open( $news_server[$server], $news_nntps[$server] );
		set_time_limit(60*2);

		$active = $nnrp->list_group( $news_groups[$server], $article_convert['to'] );

		$config_handler = xoops_gethandler('config');
		
		while ( list ($group, $value) = each ($active) ) {
		
			if( !isset($value[2]) || $value[2] == '' )
				$value[2] = '&nbsp;';
			elseif( strlen( $value[2] ) > 50 )
				$value[2] = htmlspecialchars(substr( $value[2], 0, 150 )) . ' ..';
		
			$num = $value[0] - $value[1] + 1;
			if( $num < 0 ) $num = 0;
		
			$criteria = new CriteriaCompo();
			$criteria->add(new Criteria('newsgroup', $group));
			$criteria->add(new Criteria('server_id', $news_server_id[$curr_category]));
			if ($newsgroup_handler->getCount($criteria)==0)
				$newsgroup[0] = $newsgroup_handler->create();
			else
				$newsgroup = $newsgroup_handler->getObjects($criteria);
				
				$newsgroup[0]->setVar("posts", $num);
				$newsgroup[0]->setVar("newsgroup", $group);
				$newsgroup[0]->setVar("server_id", $news_server_id[$curr_category]);
				$newsgroup[0]->setVar("description", $value[2]);
				
			$newsgroup_handler->insert($newsgroup[0]);
			$class = ($class=="even")?"odd":"even";	
			$groups[] = array("class" => $class, "num" => $i, "posts" => $num, "newsgroup" => $glink, "description" => $value[2]);
		
		}
		
		$criteria = new CriteriaCompo();
		$criteria->add(new Criteria('conf_name', 'cache_index_last'));
		$conf = $config_handler->getConfigs($criteria);
		$conf[0]->setVar('conf_value', time());
		$config_handler->insertConfig($conf[0]);

		redirect_header("index.php", 2 , "Poll Completed");
				
	case "delete":
		$newsgroup_handler = xoops_getmodulehandler('servers', 'xpnews');
		if ($newsgroup_handler->delete_id($server))
			redirect_header("index.php", 2 , "Deleting Server a Success");
		else
			redirect_header("index.php", 2 , "Deleting Server Unsuccessful");		
		break;
	case "save":
		$newsgroup_handler = xoops_getmodulehandler('servers', 'xpnews');
		if (!empty($server_id))
			$pserver = $newsgroup_handler->get($server_id);
		else
			$pserver = $newsgroup_handler->create();
			
		$pserver->setVar("name", $name);
		$pserver->setVar("server", $server);
		$pserver->setVar("group", $group);
		$pserver->setVar("option", $option);
		$pserver->setVar("auth_username", $auth_username);
		$pserver->setVar("auth_password", $auth_password);
		$pserver->setVar("charset", $charset);
		
		$inresult = $newsgroup_handler->insert($pserver);

		if ($inresult)
			redirect_header("index.php?op=pollserver&server=".$pserver->getVar('server_id'), 2 , "Editing/Creating Server a Success");
		else
			redirect_header("index.php", 2 , "Editing/Creating Server Unsuccessful");		
			
		break;
	case "newserver":
		xoops_cp_header();
		adminMenu(1);
		editserver_form();	
		break;
	case "editserver":
		xoops_cp_header();
		adminMenu(1);
		editserver_form($server);	
		break;
	default:
		xoops_cp_header();
		adminMenu(1);
		$newsgroup_handler = xoops_getmodulehandler('servers', 'xpnews');
		$server_lst = $newsgroup_handler->getObjects(null, true);
		?><p><a href="index.php?op=newserver">Create New Server</a><br/></p><?php
		
		$form = new XoopsThemeForm("Server List", "servers", $_SERVER['PHP_SELF'] ."");
		$form->setExtra( "enctype='multipart/form-data'" ) ;

		foreach($server_lst as $server)
		{
			$form->addElement(new XoopsFormLabel("<strong>".$server->getVar('name')."</strong> (".$server->getVar('server').")", "<a href='index.php?op=editserver&server=".$server->getVar('server_id')."'>Edit</a>&nbsp;<a href='index.php?op=pollserver&server=".$server->getVar('server_id')."'>Poll</a>&nbsp;<a href='index.php?op=delete&server=".$server->getVar('server_id')."'>Delete</a>"));
		}

		$form->display();
}
		echo "&nbsp;";
footer_adminMenu();
echo chronolabs_inline(false);
xoops_cp_footer();


?>
