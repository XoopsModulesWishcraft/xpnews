<?php
if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}
/**
 * Class for Newsgroups
 * @author Simon Roberts (simon@chronolabs.org.au)
 * @copyright copyright (c) 2000-2009 XOOPS.org
 * @package kernel
 */
class XpnewsNewsgroups extends XoopsObject
{

    function XpnewsNewsgroups($id = null)
    {
   		$this->initVar('id', XOBJ_DTYPE_INT, null, false);
		$this->initVar('server_id', XOBJ_DTYPE_INT, null, false);
	    $this->initVar('posts', XOBJ_DTYPE_INT, null, false);
        $this->initVar('newsgroup', XOBJ_DTYPE_TXTBOX, null, true, 255);
        $this->initVar('description', XOBJ_DTYPE_TXTBOX, null, true);
    }

    function id()
    {
        return $this->getVar("id");
    }
	
    function posts($format="S")
    {
        return $this->getVar("posts", $format);
    }
	
	function newsgroup($format="S")
    {
        return $this->getVar("newsgroup", $format);
    }

}


/**
* XOOPS Newsgroups handler class.
* This class is responsible for providing data access mechanisms to the data source
* of XOOPS user class objects.
*
* @author  Simon Roberts <simon@chronolabs.org.au>
* @package kernel
*/
class XpnewsNewsgroupsHandler extends XoopsPersistableObjectHandler
{
    function __construct(&$db) 
    {
        parent::__construct($db, "xpnews_newsgroups", 'XpnewsNewsgroups', "id", "newsgroup");
    }
	
}
?>