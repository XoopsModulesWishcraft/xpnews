<?php


	function editserver_form($server_id)
	{
	
		$newsgroup_handler = xoops_getmodulehandler('servers', 'xpnews');
		if (!empty($server_id))
			$server = $newsgroup_handler->get($server_id);
		else
			$server = $newsgroup_handler->create();
		if (!empty($server_id))			
			$form = new XoopsThemeForm("Edit Server ".$server->getVar('name'), "servers", $_SERVER['PHP_SELF'] ."");
		else
			$form = new XoopsThemeForm("New Server", "servers", $_SERVER['PHP_SELF'] ."");
		$form->setExtra( "enctype='multipart/form-data'" ) ;
		
		$form->addElement(new XoopsFormText("Name", "name", 45, 128, $server->getVar('name')));
		$form->addElement(new XoopsFormText("Server", "server", 45, 255, $server->getVar('server')));
		$form->addElement(new XoopsFormTextArea("Group", "group", $server->getVar('group'), 6, 45));
		$form->addElement(new XoopsFormTextArea("Options", "option", $server->getVar('option'), 6, 45));
		$form->addElement(new XoopsFormText("Username", "auth_username", 45, 255, $server->getVar('auth_username')));
		$form->addElement(new XoopsFormPassword("Password", "auth_password", 45, 255, $server->getVar('auth_password')));
		$form->addElement(new XoopsFormText("Character Set", "charset", 45, 255, $server->getVar('charset')));				
		$form->addElement(new XoopsFormHidden("op", "save"));
		$form->addElement(new XoopsFormHidden("server_id", $server_id));		
		$form->addElement(new XoopsFormButton('', 'send', _SEND, 'submit'));					
		$form->display();			
	}
?>