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
include('class/mimedecode.php');

$xoopsOption['template_main'] = 'xpnews_read.html';
global $xoopsTpl;
# -------------------------------------------------------------------

$artnum = intval($_GET['artnum']);

#if( isset( $_GET['orig'] ) )
#	$newwin = ($_GET['orig']==0);
#else
#	$newwin = true;

if( $server == $group_default_server )
	$reserver = '';
else
	$recategory=$curr_category;

if( ! ( $nnrp->open( $news_server[$curr_category], $news_nntps[$curr_category] ) && nnrp_authenticate() ) )
	echo 'error connecting to server '.$news_server[$curr_category];

list( $code, $count, $lowmark, $highmark ) = $nnrp->group( $group );

$nextnum = $nnrp->prev( $artnum );
$lastnum = $nnrp->next( $artnum );


$prefix = "read.php?category=$curr_category&group=$group&artnum=";
$threadurl = "read.php?category=$curr_category&group=$group&show_all=1&artnum=$artnum";
$idxurl = "indexing.php?category=$curr_category&group=$group&cursor=$artnum";
$headerurl = $prefix . $artnum . "&header";


$nexturl = ($nextnum>0) ? $prefix . $nextnum : '';
$lasturl = ($lastnum>0) ? $prefix . $lastnum : '';

#list( $from, $email, $subject, $date, $msgid, $org )

$thread_all = ( isset($_GET['show_all']) && $_GET['show_all'] == 1 );

$show_mode |= SHOW_HYPER_LINK|SHOW_SIGNATURE|SHOW_NULL_LINE;
$show_mode |= IMAGE_INLINE;
if( isset($_GET['header']) && $CFG['show_article_header'] )
$show_mode |= SHOW_HEADER;

$dlbase = str_replace( 'https://', 'http://', $urlbase );

$artinfo = $nnrp->head( $artnum, $news_charset[$curr_category], $CFG['time_format'] );

$xoopsTpl->assign('header',$artinfo);

if( !$artinfo ) {
	header( "Location: indexing.php?category=$curr_category&group=$group" );
	exit;
}

$artconv = get_conversion( $artinfo['charset'], $curr_charset );

if( $artconv['to'] ) {
	$from  = $artconv['to']( $artinfo['name'] );
	$email = $artconv['to']( $artinfo['mail'] );
	$subject = $artconv['to']( $artinfo['subject'] );
	$org = $artconv['to']( $artinfo['org'] );
}
else {
	$from  = $artinfo['name'];
	$email = $artinfo['mail'];
	$subject = $artinfo['subject'];
	$org = $artinfo['org'];
}
$date = $artinfo['date'];

if( strlen( $org ) > $org_limit )
	$org = substr( $org, 0, $org_limit ) . ' ..';

if( $thread_all )
	$subject = preg_replace( '/^((RE|FW):\s*)+/i', '', $subject );

if( $CFG['thread_enable'] ) {
	$thlist = $nnrp->get_thread( $group, $artinfo['subject'] );
	if( count($thlist) < 2 )
		$thread_all = false;
}
html_head( "$subject ($group)" );

$subject = htmlspecialchars( $subject );

if( $CFG['html_header'] && file_exists($CFG['html_header']) ) {
	if( preg_match( '/\.php$/', $CFG['html_header'] ) )
		include( $CFG['html_header'] );
	else
		readfile( $CFG['html_header'] );
}

$xoopsTpl->assign('newsgroup',"<a href=indexing.php?category=$curr_category&group=$group>$group</a>");

if( $lasturl != '' )
	$xoopsTpl->assign('first_link',$lasturl);

if( $nexturl != '' )
	$xoopsTpl->assign('next_link',$nexturl);


if( $thread_all ) {
	foreach( $thlist as $an ) {
		$artinfo = $nnrp->head( $an, $news_charset[$curr_category], $CFG['time_format'] );

		if( !$artinfo )
			continue;

		$artconv = get_conversion( $artinfo['charset'], $curr_charset );

		if( $artconv['to'] ) {
			$from  = $artconv['to']( $artinfo['name'] );
			$email = $artconv['to']( $artinfo['mail'] );
		}
		else {
			$from  = $artinfo['name'];
			$email = $artinfo['mail'];
		}

		$date = $artinfo['date'];

		$hmail = $CFG['hide_email'] ? hide_mail_link( $email ) : "<a href=\"mailto:$email\">$email</a>";

		$xoopsTpl->assign('subtitle', "<p>$from ($hmail)</p>");
		$message = $nnrp->show( $an, $at, $show_mode, '', " <br />\n", $artconv['to'],
			"$dlbase/download.php?category=$curr_category&group=$group&artnum=$an&type=uuencode&filename=%s" );
	}
}
else {
	$hmail = "<a href=\"mailto:$email\">$email</a>";

	$message = $nnrp->show( $artnum, $artinfo, $show_mode, '', " <br />\n", $artconv['to'],
		"$dlbase/download.php?category=$curr_category&group=$group&artnum=$artnum&type=uuencode&filename=%s" );
}

$md = new mime_decode($message);
$xoopsTpl->assign('message', $md->getBody());
$xoopsTpl->assign('attachments', $md->getAttachments());
$xoopsTpl->assign('from', $md->from());
$xoopsTpl->assign('to', $md->to());
$xoopsTpl->assign('subject', $md->subject());
$xoopsTpl->assign('time', $md->date());
if( !isset($_GET['header']) && $CFG['show_article_header'] && !$thread_all ) {
	$xoopsTpl->assign('show_header',$headerurl);
	
} else {
	$xoopsTpl->assign('showing_header', true);
	$xoopsTpl->assign('headers', $md->getHeader());	
}
/*if( $CFG['thread_enable'] ) {
	if( !$thread_all )
		echo "<hr />\n";
	if( count($thlist) > 1 ) {
		echo "<table border=0 cellpadding=0 cellspacing=0><tr>\n";
		if( $thread_all )
		  echo "<td class=thread_current>#</td>";
		else
		  echo "<td class=thread onClick='window.location=\"$threadurl\"' onMouseover='this.className=\"thread_hover\"' onMouseout='this.className=\"thread\"'>#</td>";
		$i = 0;
		foreach( $thlist as $art ) {
			$i++;
			if( $i > 1 && ($i+1) % 30 == 1 )
				echo "</tr>\n<tr>";
			if( $art == $artnum && !$thread_all )
				echo "<td class=thread_current>$i</td>";
			else
				echo "<td class=thread onClick='window.location=\"$prefix$art\"' onMouseover='this.className=\"thread_hover\"' onMouseout='this.className=\"thread\"'>$i</td>";
		}
		echo "</tr></table>";
	}
}*/

$nnrp->close();

if( $nexturl != '' )
	$xoopsTpl->assign('next_link', $nexturl);

if( $lasturl != '' )
	$xoopsTpl->assign('previous_link', $lasturl);

$xoopsTpl->assign('article_list', $idxurl);

if (check_right($curr_category, $group, 'reply'))
	$xoopsTpl->assign('reply_link', reply_article( $curr_category, $group, $artnum, Reply, false ));
if (check_right($curr_category, $group, 'crosspost'))
	$xoopsTpl->assign('crosspost_link', xpost_article( $curr_category, $group, $artnum, CrossPost));

$host = $_SERVER['HTTP_HOST'];
$xoopsTpl->assign('favorite_link', "javascript:myfavor('http://$host$uri', '$subject')");

if( $CFG['html_footer'] && file_exists($CFG['html_footer']) ) {
	if( preg_match( '/\.php$/', $CFG['html_footer'] ) )
		include( $CFG['html_footer'] );
	else
		readfile( $CFG['html_footer'] );
}

?>
