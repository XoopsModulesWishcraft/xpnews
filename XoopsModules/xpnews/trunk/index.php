<?php

# PHP News Reader
# Copyright (C) 2001-2007 Shen Cheng-Da
# 
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

require_once('utils.inc.php');

# ---------------------------------------------------------------------
error_reporting(E_ALL);
html_head( $title );

$newsgroup_handler = xoops_getmodulehandler('newsgroups', 'xpnews');
$server_handler = xoops_getmodulehandler('servers', 'xpnews');

$xoopsOption['template_main'] = 'xpnews_index.html';

if( $CFG['html_header'] && file_exists($CFG['html_header']) ) {
	if( preg_match( '/\.php$/', $CFG['html_header'] ) )
		include( $CFG['html_header'] );
	else
		readfile( $CFG['html_header'] );
}

$server_obj = $server_handler->get($curr_category);

$nnrp->open( $news_server[$curr_category], $news_nntps[$curr_category] );

global $xoopsTpl;

foreach ($server_lst as $key => $server)
{
	if (check_right($server->getVar('server_id'), $group, 'server'))
	$xoopsTpl->append("servers", array("link" => "$self?category=" . ($key), "title" => $server->name()));	
}

if( is_array($CFG['links']) )
	foreach( $CFG['links'] as $text => $link ) {
		if( $config_convert['to'] ) {
			$text = $config_convert['to']($text);
			$link = $config_convert['to']($link);
		}
		echo "<tr><td class=menu_link width=100 align=center onMouseover='this.className=\"menu_hover\";' onMouseout='this.className=\"menu_link\";'><a href=\"" . $link . '">' . $text . '</a></td></tr>';
	}



if( ! $nnrp->connected() ) {
	echo "<br /><br /><font size=3>".ConnectServerError." (" . $news_server[$curr_category] . ")</font></td></tr></table>\n";
	include(XOOPS_ROOT_PATH.'/footer.php');
	exit;
}

nnrp_authenticate();
$criteria = new CriteriaCompo();
$criteria->add(new Criteria('server_id', $news_server_id[$curr_category]));

if ((($xoopsModuleConfig['cache_index_last'] + ($xoopsModuleConfig['cache_index']*60))>time())||$newsgroup_handler->getCount($criteria)==0)
{
	set_time_limit(60*2);
	$active = $nnrp->list_group( $news_groups[$curr_category], $article_convert['to'] );
	
	if( $active == null ) {
		echo "<br /><br /><font size=3>".ConnectServerError." &lt;" . $news_server[$curr_category] . "&gt;</font></td></tr></table>\n";
		include(XOOPS_ROOT_PATH.'/footer.php');
		exit;
	}

	$config_handler = xoops_gethandler('config');
	
	while ( list ($group, $value) = each ($active) ) {
	
		$i++;
	
		$glink = "<a href=\"indexing.php?category=$curr_category&group=$group\">$group</a>";
	
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
	
} else {
	$newsgroup = $newsgroup_handler->getObjects($criteria);
	if (!empty($newsgroup))
		foreach($newsgroup as $news)
		{
			$class = ($class=="even")?"odd":"even";	
			$glink = "<a href=\"indexing.php?category=".$server_obj->server_id()."&group=".$news->getVar('newsgroup')."\">".$news->getVar('newsgroup')."</a>";
			$groups[] = array("class" => $class, "num" => $news->getVar('id'), "posts" => $news->getVar('posts'), "newsgroup" => $glink, "description" => $news->getVar('description'));
		}
}
/*
if( $global_readonly ) {
	echo "<font color=red>* $pnews_msg[ReadonlyNotify]</font>\n";
	echo '<p>';
}
*/


if( $CFG['group_sorting'] )
	ksort( $active );

reset( $active );

$i = 0;

$server = $news_server[$curr_category];



$xoopsTpl->assign("news_groups", $groups);

$nnrp->close();

if( $CFG['html_footer'] && file_exists($CFG['html_footer']) ) {
	if( preg_match( '/\.php$/', $CFG['html_footer'] ) )
		include( $CFG['html_footer'] );
	else
		readfile( $CFG['html_footer'] );
}

?>
