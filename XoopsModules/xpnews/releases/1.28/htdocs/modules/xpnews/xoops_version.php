<?php
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP MakePayment System                            //
//                    Copyright (c) 2007 chronolabs.org.au                  //
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
error_reporting(E_ALL);
$modversion['name'] = 'xPNEWS';
$modversion['version'] = 1.28;
$modversion['description'] = 'Sending & Recieving NNTP Posts for Xoops';
$modversion['author'] = "Simon Roberts";
$modversion['credits'] = "www.chronolabs.org.au";
$modversion['help'] = "simon@chronolabs.coop";
$modversion['license'] = "GPL see LICENSE";
$modversion['official'] = 1;
$modversion['image'] = "images/xpnews_slogo.png";
$modversion['dirname'] = "xpnews";
$modversion['developer_lead'] = "Simon Roberts [wishcraft]";
$modversion['developer_contributor'] = "Just Me";
$modversion['developer_website_url'] = "http://www.chronolabs.org.au";
$modversion['developer_website_name'] = "Chronolabs International";
$modversion['developer_email'] = "simon@chronolabs.coop";

$modversion['sqlfile']['mysql'] = "sql/xpnews.sql";
// Tables created by sql file (without prefix!)
$modversion['tables'][0] = "xpnews_servers";
$modversion['tables'][1] = "xpnews_newsgroups";

// Admin things
$modversion['hasAdmin'] = 1;
$modversion['adminindex'] = "admin/index.php";
$modversion['adminmenu'] = "admin/menu.php";


// Templates
$modversion['templates'][1]['file'] = 'xpnews_index.html';
$modversion['templates'][1]['description'] = 'Main Index Page';
$modversion['templates'][2]['file'] = 'xpnews_indexing.html';
$modversion['templates'][2]['description'] = 'Indexing of Posts';
$modversion['templates'][3]['file'] = 'xpnews_read.html';
$modversion['templates'][3]['description'] = 'Reading Message';

// Menu
$modversion['hasMain'] = 1;

$modversion['config'][1]['name'] = 'title';
$modversion['config'][1]['title'] = 'XNP_TITLE';
$modversion['config'][1]['description'] = 'XNP_TITLE_DESC';
$modversion['config'][1]['formtype'] = 'textbox';
$modversion['config'][1]['valuetype'] = 'text';
$modversion['config'][1]['default'] = 'News Reader';

$modversion['config'][2]['name'] = 'organisation';
$modversion['config'][2]['title'] = 'XNP_ORG';
$modversion['config'][2]['description'] = 'XNP_ORG_DESC';
$modversion['config'][2]['formtype'] = 'textbox';
$modversion['config'][2]['valuetype'] = 'text';
$modversion['config'][2]['default'] = 'News Reader';

$modversion['config'][3]['name'] = 'signature';
$modversion['config'][3]['title'] = 'XNP_SIGNATURE';
$modversion['config'][3]['description'] = 'XNP_SIGNATURE_DESC';
$modversion['config'][3]['formtype'] = 'textarea';
$modversion['config'][3]['valuetype'] = 'text';
$modversion['config'][3]['default'] = 'News Reader';

$modversion['config'][4]['name'] = 'group_list';
$modversion['config'][4]['title'] = 'XNP_GROUPLIST';
$modversion['config'][4]['description'] = 'XNP_GROUPLIST_DESC';
$modversion['config'][4]['formtype'] = 'textbox';
$modversion['config'][4]['valuetype'] = 'text';
$modversion['config'][4]['default'] = XOOPS_ROOT_PATH."/uploads/newsgroups".rand(100,999999).".lst";

$modversion['config'][5]['name'] = 'thread_enable';
$modversion['config'][5]['title'] = 'XNP_THREAD';
$modversion['config'][5]['description'] = 'XNP_THREAD_DESC';
$modversion['config'][5]['formtype'] = 'yesno';
$modversion['config'][5]['valuetype'] = 'int';
$modversion['config'][5]['default'] = '1';

$modversion['config'][6]['name'] = 'article_numbering_reverse';
$modversion['config'][6]['title'] = 'XNP_NUMBERREVERSE';
$modversion['config'][6]['description'] = 'XNP_NUMBERREVERSE_DESC';
$modversion['config'][6]['formtype'] = 'yesno';
$modversion['config'][6]['valuetype'] = 'int';
$modversion['config'][6]['default'] = '1';

$modversion['config'][7]['name'] = 'image_inline';
$modversion['config'][7]['title'] = 'XNP_IMAGESINLINE';
$modversion['config'][7]['description'] = 'XNP_IMAGESINLINE_DESC';
$modversion['config'][7]['formtype'] = 'yesno';
$modversion['config'][7]['valuetype'] = 'int';
$modversion['config'][7]['default'] = '1';

$modversion['config'][8]['name'] = 'allow_attach_file';
$modversion['config'][8]['title'] = 'XNP_ATTACHED_FILES';
$modversion['config'][8]['description'] = 'XNP_ATTACHED_FILESDESC';
$modversion['config'][8]['formtype'] = 'select';
$modversion['config'][8]['valuetype'] = 'int';
$modversion['config'][8]['default'] = '2';
$modversion['config'][8]['options']	= array('0'=>'None', '1'=>'1 file', '2'=>'2 files', '3'=>'3 files', '4'=>'4 files', '5'=>'5 files', '6'=>'6 files');

$modversion['config'][9]['name'] = 'articles_per_page';
$modversion['config'][9]['title'] = 'XNP_PERPAGE';
$modversion['config'][9]['description'] = 'XNP_PERPAGE_DESC';
$modversion['config'][9]['formtype'] = 'select';
$modversion['config'][9]['valuetype'] = 'int';
$modversion['config'][9]['default'] = '20';
$modversion['config'][9]['options']	= array('10 articles'=>'10', '20 articles'=>'20', '30 articles'=>'30', '40 articles'=>'40', '50 articles'=>'50', '60 articles'=>'60', '100 articles'=>'100');

$modversion['config'][10]['name'] = 'decode_path';
$modversion['config'][10]['title'] = 'XNP_DECODEPATH';
$modversion['config'][10]['description'] = 'XNP_DECODEPATH_DESC';
$modversion['config'][10]['formtype'] = 'textbox';
$modversion['config'][10]['valuetype'] = 'text';
$modversion['config'][10]['default'] = XOOPS_ROOT_PATH."/uploads/xpnews";

$modversion['config'][11]['name'] = 'decode_path_access';
$modversion['config'][11]['title'] = 'XNP_DECODEPATHACCESS';
$modversion['config'][11]['description'] = 'XNP_DECODEPATHACCESS_DESC';
$modversion['config'][11]['formtype'] = 'textbox';
$modversion['config'][11]['valuetype'] = 'text';
$modversion['config'][11]['default'] = "/uploads/xpnews";

$modversion['config'][12]['name'] = 'cache_index';
$modversion['config'][12]['title'] = 'XNP_CACHEINDEX';
$modversion['config'][12]['description'] = 'XNP_CACHEINDEX_DESC';
$modversion['config'][12]['formtype'] = 'select';
$modversion['config'][12]['valuetype'] = 'int';
$modversion['config'][12]['default'] = '5';
$modversion['config'][12]['options']	= array('5 minutes'=>'5', '10 minutes'=>'10', '20 minutes'=>'20', '40 minutes'=>'40', '1 Hour'=>'60', '12 Hours'=>'720', '24 Hours'=>'1440');

$modversion['config'][13]['name'] = 'cache_index_last';
$modversion['config'][13]['title'] = 'XNP_CACHEINDEXLAST';
$modversion['config'][13]['description'] = 'XNP_CACHEINDEXLAST_DESC';
$modversion['config'][13]['formtype'] = 'date';
$modversion['config'][13]['valuetype'] = 'int';
$modversion['config'][13]['default'] = time();

$modversion['config'][14]['name'] = 'htaccess';
$modversion['config'][14]['title'] = 'XNP_HTACCESS';
$modversion['config'][14]['description'] = 'XNP_HTACCESS_DESC';
$modversion['config'][14]['formtype'] = 'yesno';
$modversion['config'][14]['valuetype'] = 'int';
$modversion['config'][14]['default'] = 0;

$modversion['config'][15]['name'] = 'seo_filetype';
$modversion['config'][15]['title'] = 'XNP_SEOFILETYPE';
$modversion['config'][15]['description'] = 'XNP_SEOFILETYPE_DESC';
$modversion['config'][15]['formtype'] = 'textbox';
$modversion['config'][15]['valuetype'] = 'text';
$modversion['config'][15]['default'] = "html";

$modversion['config'][16]['name'] = 'days_cache';
$modversion['config'][16]['title'] = 'XNP_CACHEDAYS';
$modversion['config'][16]['description'] = 'XNP_CACHEDAYS_DESC';
$modversion['config'][16]['formtype'] = 'select';
$modversion['config'][16]['valuetype'] = 'int';
$modversion['config'][16]['default'] = '500';
$modversion['config'][16]['options']	= array('50 days'=>'50', '100 Days'=>'100', '200 Days'=>'200', '400 Days'=>'400', '500 Days'=>'500', '1000 Days'=>'1000');

$modversion['config'][17]['name'] = 'hours_cache';
$modversion['config'][17]['title'] = 'XNP_CACHEHOURS';
$modversion['config'][17]['description'] = 'XNP_CACHEHOURS_DESC';
$modversion['config'][17]['formtype'] = 'select';
$modversion['config'][17]['valuetype'] = 'int';
$modversion['config'][17]['default'] = '500';
$modversion['config'][17]['options']	= array('30 minutes'=>'30', '1 Hour'=>'60', '2 Hour'=>'120', '6 Hours'=>'360', '12 Hours'=>'720', '24 Hours'=>'1440');


?>
