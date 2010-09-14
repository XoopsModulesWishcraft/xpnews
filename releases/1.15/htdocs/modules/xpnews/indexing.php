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

include('utils.inc.php');

# ---------------------------------------------------------------------

html_head( $group );

$xoopsOption['template_main'] = 'xpnews_indexing.html';
global $xoopsTpl;
error_reporting(E_ALL);
if( $CFG['html_header'] && file_exists($CFG['html_header']) ) {
	if( preg_match( '/\.php$/', $CFG['html_header'] ) )
		include( $CFG['html_header'] );
	else
		readfile( $CFG['html_header'] );
}

if( ! ( $nnrp->open( $news_server[$curr_category], $news_nntps[$curr_category] ) && nnrp_authenticate() ) )
	echo 'error';

list( $code, $count, $lowmark, $highmark ) = $nnrp->group( $group );

$artlist = $nnrp->article_list( $lowmark, $highmark );

$artsize = count($artlist);



if( $artsize > 0 ) {
	$highmark = $artlist[$artsize-1];
	$lowmark  = $artlist[0];
}

$artsppg = $CFG['articles_per_page'];

$totalpg = ceil($artsize / $artsppg) ;

if( !isset($_GET['cursor']) ) {
	if( isset( $_GET['page'] ) ) {
		$cpg = $_GET['page'];
		$tmpidx = $artsize - ($cpg-1)*$artsppg - 1;
		$cursor = $artlist[$tmpidx];
	}
	else
		$cursor = $highmark;
}
else
	$cursor = $_GET['cursor'];

if( $news_server[$curr_category] == $group_default_server )
	$reserver = '';
else
	$reserver = $news_server[$curr_category];


	$xoopsTpl->assign("newsgroup", $group);
	
	if (!check_right($curr_category, $group, 'server'))
	{
		html_delay_close( 100 );
		exit;
	}
	

$ncount = 0;
$curlist = array();

$xoopsTpl->assign("cursor", $cursor);
$xoopsTpl->assign("lowmark", $lowmark);

$higher = $lower = '';

$i = $cursor;
while( $i >= $lowmark ) {
	$cut_end = array_search( $i, $artlist );
	if( $cut_end !== false ) {
		if( $artsize <= $artsppg ) {
			$cut_from = 0;
			$cut_end = $artsize-1;
		}
		else {
			$cut_from = $cut_end - $artsppg + 1;
			if( $cut_from < 0 )
				$cut_from = 0;
		}
		$ncount = $cut_end - $cut_from + 1;
		for( $j = $cut_end ; $j >= $cut_from ; $j-- )
			$curlist[] = $artlist[$j];
		if( $cut_from > 0 )
			$lower = $artlist[$cut_from-1];
		else
			$lower = $lowmark;
		if( $cut_end + $artsppg + 1 < $artsize )
			$higher = $artlist[$cut_end+$artsppg];
		else
			$higher = $highmark;
		break;
	}
	$i--;
	if( $i < $cursor - 1000 )
		break;
}

if( $ncount > 0 ) {
	$show_from = $curlist[$ncount - 1];
	$show_end  = $curlist[0];
	$xoopsTpl->assign("show_from", $show_from);
	$xoopsTpl->assign("show_end", $show_end);	
	$xover = $nnrp->xover( $show_from, $show_end );

	$ncount = count($xover);
}

if( $ncount == 0 ) {
	$xoopsTpl->assign("noarticles", true);
	$show_from = $show_end = $cursor;
}
else {
	krsort($xover);

	foreach( $xover as $artnum => $ov ) {

		if( !$ov )
			continue;
		if( strlen( $ov[0] ) > $subject_limit )
			$subject = substr( $ov[0], 0, $subject_limit ) . ' ..';
		else
			$subject = $ov[0];

		if( $article_convert['to'] ) {
			$subject = $article_convert['to']( $subject );
			$ov[1] = $article_convert['to']( $ov[1] );
		}

		if( trim($subject) == '' )
			$subject = $pnews_msg['NoSubject'];

		$subject = htmlspecialchars( $subject );

		if( strlen( $ov[1] ) > $nick_limit )
			$nick = substr( $ov[1], 0, $nick_limit ) . ' ..';
		else
			$nick = $ov[1];

		$nick = trim($nick);

		if( $nick == '' ) {
			$id = strtok( $ov[3], '@.' );
			$nick = $id;
		}
		$email = trim($ov[3]);
		$datestr = strftime( $CFG['time_format'], $ov[2] );

		$readlink = read_article( $curr_category, $group, $artnum, $subject, false, 'sub' );
		if( $CFG['article_numbering_reverse'] )
			$artidx = $artnum - $lowmark + 1;
		else
			$artidx = $highmark - $artnum + 1;
		if( $CFG['hide_email'] )
			$hmail = hide_mail_link( $email, "$nick " );
		else
			$hmail = "<a href=\"mailto:$email\">$nick</a>";
		$class = ($class=="even")?"odd":"even";
		$author = (strlen($hmail)!=0)? $hmail: $nick;
		if (trim($author)=='')
			$author = 'unknown';
		$xoopsTpl->append("posts", array("class" => $class, "num" => $artidx, "subject" => $readlink, "author" => $author, "time" =>$datestr));
		
	}

}

if( $totalpg == 1 )
	$page = 1;
else {
	$page = floor( ( $artsize - array_search( $show_from, $artlist ) + 1 ) / $artsppg);
	if( $page == 1 && $show_end < $highmark )
		$page = 2;
	elseif( $page == $totalpg && $show_from > $lowmark )
		$page = $totalpg - 1;
	elseif( $page > $totalpg || $show_from == $lowmark )
		$page = $totalpg;
}


if( $show_end < $highmark ) {
	$xoopsTpl->assign("first_link","$self?category=$curr_category&group=$group");
	$xoopsTpl->assign("previous_link","$self?category=$curr_category&group=$group&cursor=$higher");
}

if( $show_from > $lowmark ) {
	$xoopsTpl->assign("next_link","$self?category=$curr_category&group=$group&cursor=$lower");
	$target = isset($artlist[$artsppg-1])?$artlist[$artsppg-1]:$artlist[$artsize-1];
	$xoopsTpl->assign("last_link","$self?category=$curr_category&group=$group&cursor=$target");
}

$pg_str = "<form style='display: inline;' name=select><select name=pgidx class=pgidx onLoad='initPage(document.select.pgidx)' onChange='changePage(document.select.pgidx)'>%s</select></form>";
for($i=1;$i<=$totalpg;$i++)
	if ($i == $page)
		$pg_option .= "<option value='$i' selected>Page $i of $totalpg</option>";
	else
		$pg_option .= "<option value='$i'>Page $i of $totalpg</option>";

$pg_str = sprintf( $pg_str, $pg_option);
$pageurl = "$self?category=$curr_category&group=$group&page=";


$xoopsTpl->assign('pageurl', $pageurl);
$xoopsTpl->assign('pages', $pg_str );
$xoopsTpl->assign('refresh_link' , 'javascript:reload()');

$xoopsTpl->assign('refresh_link' , 'javascript:reload()');
$xoopsTpl->assign('post_link' , post_article( $curr_category, $group, 'New Post'));
$nnrp->close();

if( $CFG['html_footer'] && file_exists($CFG['html_footer']) ) {
	if( preg_match( '/\.php$/', $CFG['html_footer'] ) )
		include( $CFG['html_footer'] );
	else
		readfile( $CFG['html_footer'] );
}

?>
