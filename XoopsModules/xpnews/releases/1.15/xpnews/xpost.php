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

$title .= " - ".CrossPost;

# -------------------------------------------------------------------

$server     = $_POST['server'];
$content    = $_POST['content'];
$postgroup  = $_POST['postgroup'];
$artnum     = intval($_GET['artnum']);

if( $content != '' && $postgroup != '' ) {

	$group  = $_POST['group'];

	if (!check_right($server, $group, 'post'))
	{
		html_delay_close( 100 );
		exit;
	}


	$nickname   = $_POST['nickname'];
	$content    = $_POST['content'];
	$subject    = $_POST['subject'];
	$refid      = $_POST['refid'];

	$email = $_POST['email'];

	if( $auto_slash ) {
		$subject  = stripslashes( $_POST['subject'] );
		$content  = stripslashes( $_POST['content'] );
		$nickname = stripslashes( $_POST['nickname'] );
	}
	else {
		$subject  = $_POST['subject'];
		$content  = $_POST['content'];
		$nickname = $_POST['nickname'];
	}

	$nickname = rtrim($nickname);
	$subject = rtrim($subject);

	$artconv = get_conversion( $_POST['charset'], $curr_charset );

	if( ! ( $nnrp->open( $news_server[$server], $news_nntps[$server] ) && nnrp_authenticate() ) )
		echo 'error connecting to server '.$news_server[$server];

	if( $artconv['back'] ) {
		$nnrp->post_init( $artconv['back']($nickname), $email, $artconv['back']($subject), $postgroup, $artconv['back']($CFG['organization']), null, $auth_email, $_POST['charset'] );
		$nnrp->post_write( $article_convert['back']($content) );
	}
	else {
		$nnrp->post_init( $nickname, $email, $subject, $postgroup, $CFG['organization'], null, $auth_email, $_POST['charset'] );
		$nnrp->post_write( $content );
	}

	$nnrp->post_end();
	$nnrp->close();

	html_head( "$postgroup - $subject" );

	$time = strftime($CFG['time_format']);
	$subject = htmlspecialchars( $subject );
	echo <<<EOT
<table width=100%>
 <tr><td class=status>Article Posted</td>
 <td class=field><input class=normal type=button onClick="close_window();" value="Close Window"></td></tr>
</table>
<hr />
<table>
 <tr><td class=field>Author: </td><td class=value>$nickname ($email)</td></tr>
 <tr><td class=field>Time: </td><td class=value>$time</td></tr>
 <tr><td class=field>Subject: </td><td class=value>$subject</td></tr>
 <tr><td class=field>Group: </td><td class=value>$postgroup</td></tr>
</table>
<hr />

EOT;
	echo '<pre class=content>' . htmlspecialchars($content, ENT_NOQUOTES ) . "</pre>\n";
	echo "<hr />\n";

	html_delay_close( 2000 );
	html_tail();
}
elseif( $artnum != '' ) {

	$server = $_GET['server'];
	$group  = $_GET['group'];


	if (!check_right($server, $group, 'post'))
	{
		html_delay_close( 100 );
		exit;
	}

	global $xoopsUser;
	if (is_object($xoopsUser))
	{
		$auth_user = $xoopsUser->getVar('uname');
		$auth_email = $xoopsUser->getVar('email');
	}
	
	if( ! ( $nnrp->open( $news_server[$server], $news_nntps[$server] ) && nnrp_authenticate() ) )
		echo 'error connecting to server '.$news_server[$curr_category];

	list( $code, $count, $lowmark, $highmark ) = $nnrp->group( $group );

	$artinfo = $nnrp->head( $artnum, $news_charset[$curr_category], $CFG['time_format'] );
	if( !$artinfo )
		kill_myself();

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
	$msgid = $artinfo['msgid'];

#	if( !preg_match( '/^Re: /i', $subject ) )
#		$subject = 'Re: ' . $subject ;

	html_head( "$group - $subject" );
?>
<script type="text/javascript">
	function really() {
		if( document.post.content.value == "" ) {
			window.close();
			return(true);
		}
		if( confirm('<?php echo addslashes($pnews_msg['ReallyQuit']); ?>') ) {
			window.close();
			return(true);
		}
		return(false);
	}
	function verify() {
		if( document.post.nickname.value == "" ) {
			alert('<?php echo addslashes($pnews_msg['PleaseEnterName']); ?>');
			document.post.nickname.focus();
			return(false);
		}
		if( document.post.email.value == "" || ! /^[_.\d\w-]+@([\d\w][\d\w-]+\.)+[\w]{2,3}$/.test(document.post.email.value) ) {
			alert('<?php echo addslashes($pnews_msg['PleaseEnterEmail']); ?>');
			document.post.email.focus();
			return(false);
		}
		if( document.post.postgroup.value == "" ) {
			alert('<?php echo addslashes($pnews_msg['PleaseEnterGroup']); ?>');
			document.post.postgroup.focus();
			return(false);
		}
		if( document.post.subject.value == "" ) {
			alert('<?php echo addslashes($pnews_msg['PleaseEnterSubject']); ?>');
			document.post.subject.focus();
			return(false);
		}
		if( document.post.content.value == "" ) {
			alert('<?php echo addslashes($pnews_msg['PleaseEnterContent']); ?>');
			document.post.content.focus();
			return(false);
		}
<?php
		if( $CFG['confirm_post'] ) {
			echo <<<CONFIRM
		if( !confirm( '$CFG[confirm_post]' ) ) {
			return(false);
		}

CONFIRM;
		}
?>
		document.post.submit();
		return(true);
	}
</script>
<?php
	$mail_disable = $CFG['email_editing'] ? '' : ' disabled';

	echo "<form name=post action=\"$self\" method=post>\n";
	echo "<center><table cellspacing=0 cellpadding=0 width=100%>\n";
	echo "<tr><td class=field width=12%>".Name.":</td><td><input class=input name=nickname size=20 value=\"$auth_user \"></td></tr>\n";
	echo "<tr><td class=field>".Email.":</td><td><input class=input name=email size=40 value=\"$auth_email\" $mail_disable></td></tr>\n";
	echo "<tr><td class=field>".Group.":</td><td><input class=input name=postgroup size=40 value=\"\"></td></tr>\n";
	echo "<tr><td class=field>".Subject.":</td><td><input class=input name=subject value=\"" . htmlspecialchars($subject, ENT_QUOTES ) . " \" size=60></td></tr>\n";

	echo "<tr><td class=field>\n";
	echo "<input name=authormail value=\"$email\" type=hidden>\n";
	echo "<input name=server value=\"$server\" type=hidden>\n";
	echo "<input name=group value=\"$group\" type=hidden>\n";
#	echo "<input name=refid type=hidden value=\"" . htmlspecialchars($msgid, ENT_NOQUOTES ) . "\">\n";
	echo "<input name=charset value=\"" . $artinfo['charset'] . "\" type=hidden>";

	echo Content.":</td><td align=right>";
	echo " <input class=normal type=button value='".FormConfirmPost."' onClick='verify()' tabindex=2>\n";
	echo " <input class=normal type=button value='".FormCancelPost."' onClick='really()' tabindex=3></td></tr>\n";
	echo "<tr><td colspan=2>";
	echo "<textarea name=content class=content wrap=hard tabindex=1 cols=66 rows=12>";

	printf("\n".CrossPostAuthor."\n", "$from ($email)" );
	printf("".PostStatus."\n\n", $date, $group );
#	echo "-------------------------------------------------------------\n";

	$show_mode |= SHOW_SIGNATURE|SHOW_NULL_LINE;
	$message = $nnrp->show( $artnum, $artinfo, $show_mode, '&gt; ', "\n", $artconv['to'] );
	include('class/mimedecode.php');
	$md = new mime_decode($message);
	echo $md->getTextBody();
	$nnrp->close();

	echo "\n</textarea>\n";
	echo "</td></tr></table></center>\n";
	echo "</form>\n";
	html_focus( 'post', 'content' );
	html_tail();
}


?>
