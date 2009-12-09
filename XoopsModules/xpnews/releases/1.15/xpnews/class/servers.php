<?php
if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}
/**
 * Class for Servers
 * @author Simon Roberts (simon@chronolabs.org.au)
 * @copyright copyright (c) 2000-2009 XOOPS.org
 * @package kernel
 */
class XpnewsServers extends XoopsObject
{

    function XpnewsServers($id = null)
    {
   		$this->initVar('server_id', XOBJ_DTYPE_INT, null, false);
		$this->initVar('name', XOBJ_DTYPE_TXTBOX, null, false, 128);
		$this->initVar('server', XOBJ_DTYPE_TXTBOX, null, false, 255);
	    $this->initVar('group', XOBJ_DTYPE_TXTBOX, null, false);
        $this->initVar('option', XOBJ_DTYPE_OTHER, null, true);
        $this->initVar('auth_username', XOBJ_DTYPE_TXTBOX, null, true);
        $this->initVar('auth_password', XOBJ_DTYPE_TXTBOX, null, true);				
		$this->initVar('charset', XOBJ_DTYPE_TXTBOX, null, true);
    }

    function server_id()
    {
        return $this->getVar("server_id");
    }
	
    function name($format="S")
    {
        return $this->getVar("name", $format);
    }
	
    function server($format="S")
    {
        return $this->getVar("server", $format);
    }
	
	function group($format="S")
    {
        return $this->getVar("group", $format);
    }
	
	function option($format="S")
    {
        return $this->getVar("option", $format);
    }

	function username($format="S")
    {
        return $this->getVar("auth_username", $format);
    }
	
	function password($format="S")
    {
        return $this->getVar("auth_password", $format);
    }
	
	function charset($format="S")
    {
        return $this->getVar("charset", $format);
    }
	
}


/**
* XOOPS Servers handler class.
* This class is responsible for providing data access mechanisms to the data source
* of XOOPS user class objects.
*
* @author  Simon Roberts <simon@chronolabs.org.au>
* @package kernel
*/
class XpnewsServersHandler extends XoopsPersistableObjectHandler
{
    function __construct(&$db) 
    {
        parent::__construct($db, "xpnews_servers", 'XpnewsServers', "server_id", "name");
    }
	
	function delete_id($server_id)
	{
		global $xoopsDB;
		$sql = "DELETE FROM ".$xoopsDB->prefix('xpnews_servers')." WHERE server_id = $server_id";
		$resa = $xoopsDB->queryF($sql);
		$sql = "DELETE FROM ".$xoopsDB->prefix('xpnews_newsgroups')." WHERE server_id = $server_id";
		$resb = $xoopsDB->queryF($sql);
		if ($resa==true&&$resb==true)
			return true;
		else
			return false;
	}
	
}
?>