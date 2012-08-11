<?php

include ('../../mainfile.php');

define('on',  true,  true );
define('off', false, true );


global $xoopsModuleConfig;

$CFG["auth_type"] = "open";

$CFG["db_variable"] = array( "%e" => "user_email" );

$CFG['url_base'] = XOOPS_URL.'/modules/xpnews/';

$CFG["title"] = $xoopsModuleConfig['title'];

$CFG["html_header"] = XOOPS_ROOT_PATH."/header.php";

$CFG["cache_dir"] = XOOPS_VAR_PATH."/xoops_cache";

$CFG["html_footer"] = XOOPS_ROOT_PATH."/footer.php";

$CFG["group_list"] = $xoopsModuleConfig['group_list']; //XOOPS_ROOT_PATH."/uploads/newsgroups.lst";

$CFG["article_numbering_reverse"] = $xoopsModuleConfig['article_numbering_reverse'];

$CFG['image_inline'] = $xoopsModuleConfig['image_inline'];

$CFG["allow_attach_file"] = $xoopsModuleConfig['allow_attach_file'];

$CFG['articles_per_page'] = $xoopsModuleConfig['articles_per_page'];

$CFG["organization"] = $xoopsModuleConfig['organization'];

$CFG["post_signature"] = $xoopsModuleConfig['post_signature'];


?>
