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
if ( !defined("XOOPS_MAINFILE_INCLUDED") ) 
	require_once('../../mainfile.php');
	
function check_right($server, $group, $action)
{
	global $xoopsDB, $xoopsModule, $xoopsUser;

	$gperm_handler =& xoops_gethandler('groupperm');
	$groups = is_object($xoopsUser) ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
	$modid = $xoopsModule->getVar('mid');

	switch ($action){
	case "server":
		return $gperm_handler->checkRight('xpnews_server_view',$server,$groups, $modid);
		break;
	default:
		$newsgroup_handler = xoops_getmodulehandler('newsgroups', 'xpnews');
		$criteria = new Criteria('server_id', $server);
		$criteria = new Criteria('newsgroup', $group);	
		$newsgroup = $newsgroup_handler->getObjects($criteria);
		return $gperm_handler->checkRight($server.'_'.$action,$newsgroup[0]->getVar('id'),$groups, $modid);
	}
}

# Read and check the configuration (config.inc.php)
require_once('readcfg.inc.php');
require_once('version.inc.php');
require_once('html.inc.php');
require_once('class/nnrpclass.php');

if( isset($_SESSION['rem_url_base']) && $_SESSION['rem_url_base'] != $CFG['url_base'] ) {
	$_SESSION['rem_url_base'] = $CFG['url_base'];
	unset($_SESSION['rem_category']);
	unset($_SESSION['auth_time']);
	unset($_SESSION['auth_expire_time']);
	unset($_SESSION['auth_ticket']);
}

# Global variables

$uri = isset($_SERVER['REQUEST_URI']) ?
	$_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME'] . (( isset($_SERVER['QUERY_STRING']) ) ? '?' . $_SERVER['QUERY_STRING'] : '');

$self             = $_SERVER['PHP_SELF'];
$ip_from          = $_SERVER['REMOTE_ADDR'];
$self_base        = basename( $self );

$auto_slash       = get_magic_quotes_gpc();

$referal          = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

$show_mode        = 0;

if( $CFG['filter_ansi_color'] )
	$show_mode |= FILTER_ANSI;

$nnrp = new pnews_nnrp( $CFG['debug_level'], $CFG['cache_dir'], $CFG['thread_enable'], $CFG['thread_db_format'] );

#if( $referal == '' )
#	$referal = 'index.php';

$global_readonly = $CFG['global_readonly'];

$mail_add_header  = "X-Mailer: $pnews_name $pnews_version (CDSHEEN)\n"; 
$mail_add_header .= "X-Source: $ip_from";

# Limits definition

$lineppg          = $CFG['articles_per_page'];

$subject_limit    = 60;	# Chars Limit for Subject
$nick_limit       = 16;	# Chars Limit for Nickname
$id_limit         = 18;	# Chars Limit for ID ( E-Mail before @ )
$org_limit        = 24;	# Chars Limit for Organization

$textcol          = 66;

##############################################################################
# Read the newsgroups definition

$newsgroup_handler = xoops_getmodulehandler('servers', 'xpnews');
$server_lst = $newsgroup_handler->getObjects(null, true);

if( !$server_lst )
	show_error( "Can not load " . $CFG['group_list'] . " . Copy examples/newsgroups.lst as a template.");

$group_default_charset = $xoopsModuleConfig['default_charset'];

$private_categorys = array();

global $xoopsTpl;

foreach ($server_lst as $key => $server)
{
//	$key = $server->server_id();
	$news_category[$key]  = $server->name();
	if (strlen($server->username())==0&&strlen($server->password())==0)
		$news_authinfo[$key] = 'none';
	else
		$news_authinfo[$key] = $server->username().",".$server->password();
		
	$news_charset[$key]   = $server->charset();
	$news_server[$key]    = $server->server();
	$news_nntps[$key]     = false;
	$news_authperm[$key]  = false;
	$news_readonly[$key]  = false;
	$news_hidden[$key]    = false;
	$news_server_id[$key] = $server->server_id();

	$options = split( ',', $server->option() );
	
	if( in_array( 'default', $options ) || $category_num < 1)
	{
		$default_category = $key;
		$category_num = $key;
		$group_default_server = $server->server();
		$group_default_charset = $server->charset();
	}
	
	if( in_array( 'private', $options ) ) {
		$news_authperm[$key] = true;
		$private_categorys[] = $key;
	}
	if( in_array( 'nntps', $options ) || in_array( 'snews', $options ) ) {
		$news_nntps[$key] = true;
		if( !version_check( '4.3.0' ) )
			show_error( 'PHP 4.3.0 or greater is required for NNTPS support' );
		if( !function_exists( 'openssl_get_publickey' ) )
			show_error( 'OpenSSL is required for NNTPS support' );
	}

	if( in_array( 'readonly', $options ) )
		$news_readonly[$key] = true;
		
	if( in_array( 'hidden', $options ) ) {
		$news_hidden[$key] = true;
	}
	
	if( isset($news_groups[$key]) )
		$news_groups[$key] .= $server->group();
	else
		$news_groups[$key] = $server->group();
	
}

$category_num++;

if( isset($_SESSION['save_postvar']) && $_SESSION['save_postvar'] ) {
	$_POST = $_SESSION['POSTVAR'];
	$_SESSION['POSTVAR'] = array();
	$_SESSION['save_postvar'] = false;
}

##############################################################################
# Determine the $curr_category

$server = isset($_GET['server']) ? $_GET['server'] : '';
$group  = get_group();

if( $server == '*' && $group_default_server != '' )
	$server = $group_default_server;


if( isset( $_GET['c'] ) )
	$curr_category = $_GET['c'];
elseif( isset( $_GET['category'] ) )
	$curr_category = intval($_GET['category']);
elseif( isset( $_GET['catalog'] ) )
	$curr_category = intval($_GET['catalog']);
elseif( isset($_SESSION['rem_category']) )
	$curr_category = $_SESSION['rem_category'];
else
	$curr_category = $default_category;


##############################################################################
# Language conversion Phase 1

$config_convert   = get_conversion( $CFG['charset']['config'], $curr_charset );
$grouplst_convert = get_conversion( $CFG['charset']['grouplst'], $curr_charset );
$database_convert = get_conversion( $CFG['charset']['database'], $curr_charset );

if( $config_convert['to'] ) {
	$CFG['auth_organization'] = $config_convert['to']( $CFG['auth_organization'] );
	$CFG['organization']      = $config_convert['to']( $CFG['organization'] );
	$CFG['title']             = $config_convert['to']( $CFG['title'] );
	$CFG['banner']            = $config_convert['to']( $CFG['banner'] );
	$CFG['post_signature']    = $config_convert['to']( $CFG['post_signature'] );
	$CFG['auth_http_realm']   = $config_convert['to']( $CFG['auth_http_realm'] );
}

$title = $CFG['title'];
if( $grouplst_convert['to'] ) {
	for( $i = 0 ; $i < $category_num ; $i++ )
		$news_category[$i] = $grouplst_convert['to']( $news_category[$i] );
}

if( $CFG['referrer_enforcement'] && !isset($_SESSION['urlbase_access']) ) {
	if( $self_base == 'index.php' )
		$_SESSION['urlbase_access'] = 'yes';
	else {
		header('Location: ' . $CFG['url_base'] );
		exit;
	}
}

##############################################################################
# Authentication

$auth_success     = false;

if( $CFG['auth_type'] != 'open' ) {

	if( isset($_GET['logout']) ) {
		session_destroy();
		if( $CFG['auth_prompt'] == 'http' ) {
			http_logout();
		}
		// CAS
		elseif( $CFG['auth_prompt'] == 'cas' ) {
			phpCAS::logout();
			exit;
		}
		elseif( $CFG['auth_prompt'] == 'other' ) {
			auth_logout();
#			exit;
		}
		else {
			header("Location: $referal");
			exit;
		}
	}

	$need_auth = false;

	if( $CFG['auth_type'] == 'optional' ) {


		if( isset($_GET['login']) )
			$need_auth = true;
		elseif( $self_base == 'indexing.php' || $self_base == 'read.php' || $self_base == 'download.php' ) {
			if( $server == '' || $group == '' || $verify_category == -1 ) {
				html_head( $title, 'index.php' );
				html_tail();
				exit;
			}
			foreach( $private_categorys as $pc )
				if( $server == $news_server[$pc] && match_group( $group, $news_groups[$pc] ) )
					$need_auth = true;
		}
		elseif( need_postperm( $self_base ) || $news_authperm[$curr_category] )
			$need_auth = true;
	}
	else {
		if( $self_base == 'indexing.php' || $self_base == 'read.php' || $self_base == 'download.php' ) {
			if( $server == '' || $group == '' || $verify_category == -1 ) {
				html_head( $title, 'index.php' );
				html_tail();
				exit;
			}
		}
		$need_auth = true;
	}
	$is_expire = false;

	if( $CFG['auth_expire_time'] > 0 && isset( $_SESSION['auth_time']) && $_SESSION['auth_time'] + $CFG['auth_expire_time'] < time() ) {
		$is_expire = true;
		unset($_SESSION['auth_ticket']);
	}

	if( isset( $_SESSION['auth_name'], $_SESSION['auth_ticket'] )
			&& $_SESSION['auth_ticket'] == md5( $_SESSION['auth_name'] . session_id() . $_SESSION['auth_time'] ) ) {
#		echo "<!-- authentication ticket is acknowledged -->\n";
		$auth_success = true;
	}
	elseif( $need_auth ) {
		if( isset($CFG['https_login']) && $CFG['https_login'] && ( !isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on' ) ) {
			$_SESSION['save_postvar'] = true;
			$_SESSION['POSTVAR'] = $_POST;
			header( 'Location: https://' . $_SERVER['HTTP_HOST'] . $uri );
			exit;
		}
		else {
			switch($CFG['auth_prompt']) {
			case 'form':
				html_head( $title . ' - ' . $pnews_msg['Authentication'] );
				form_login_dialog( $is_expire );
				html_foot();
				html_tail();
				exit;
				break;
			case 'http':
				if( !$is_expire && isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) ) {
					if( !in_array( $_SERVER['PHP_AUTH_USER'], $CFG['auth_deny_users']) && ($info = check_user_password( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] )) == null )
						http_login_auth();
					$now = time();
					$_SESSION['auth_time'] = $now;
					$_SESSION['auth_name'] = $_SERVER['PHP_AUTH_USER'];
					$_SESSION['auth_with'] = 'http';
					$_SESSION['auth_info'] = $info;
					$_SESSION['auth_ticket'] = md5( $_SERVER['PHP_AUTH_USER'] . session_id() . $now );
					$auth_success = true;
				}
				else {
					unset($_SESSION['auth_ticket']);
					http_login_auth();
				}
				break;
			/* CAS - by Pascal Aubry */
			case 'cas':
				/* Check CAS authentication */
				$_SESSION['auth_referal'] = $referal;
				phpCAS::authenticateIfNeeded();
				/* Force expiration when ticket has expired */
				if( $is_expire ) {
					unset($_SESSION['auth_ticket'],$_SESSION['auth_time'],$_SESSION['auth_name'],$_SESSION['auth_with'],$_SESSION['auth_info']);
				        phpCAS::forceAuthentication();
				}
				$now = time();
				$_SESSION['auth_time'] = $now;
				$_SESSION['auth_name'] = phpCAS::getUser();
				$_SESSION['auth_with'] = 'cas';
				$_SESSION['auth_info']['%u'] = phpCAS::getUser();
				$_SESSION['auth_ticket'] = md5( phpCAS::getUser() . session_id() . $now );
				$auth_success = true;
				break;
			case 'other':
				$_SESSION['auth_referal'] = $referal;
				if( !auth_already_login() ) {
					auth_show_login_page( 'index.php' );
					exit;
				}
				$_SESSION['auth_time'] = time();
				$_SESSION['auth_name'] = auth_get_name();
				$_SESSION['auth_with'] = 'other';
				$_SESSION['auth_info']['%u'] = auth_get_name();
				$_SESSION['auth_info']['%e'] = auth_get_email();
				$_SESSION['auth_ticket'] = md5( auth_get_name() . session_id() . $now );
				break;
			}
		}
	}
}

if( $CFG['auth_prompt'] == 'other' && auth_already_login() ) {
	$auth_success = true;
}

if( isset($_SESSION['%u']) )
	$_SESSION['%name'] = $_SESSION['%u'];

if( isset($_SESSION['%e']) )
	$_SESSION['%email'] = $_SESSION['%e'];

$info = isset($_SESSION['auth_info']) ? $_SESSION['auth_info'] : '';
if( is_array($info) && $database_convert['to'] )
	foreach( $info as $var => $value )
		$_SESSION['auth_info'][$var] = $database_convert['to']($value);

$auth_user  = vars_convert( $CFG['auth_user_fullname'] );

if( isset($info['%e']) )
	$auth_email = $info['%e'];
else
	$auth_email = vars_convert( $CFG['auth_user_email'] );

# After authentication, $curr_category is permitted to access
$_SESSION['rem_category'] = $curr_category ;

##############################################################################
# Language conversion - Dependent on $curr_category after authentication

$article_convert  = get_conversion( $news_charset[$curr_category], $curr_charset );

#echo "<!-- curr: $curr_charset ({$article_convert['to']}) -->\n";

#if( $article_convert['to'] ) {
#	$read_claim = '// Article auto-converted from ' . $article_convert['source'] . ' to ' . $article_convert['result'];
#	$post_claim = '// Article original posted with ' . $article_convert['result'] . ' encoding';
#}

#############################################################################
# Write access log 

# $CFG['log_level']
#	0 - no log
#	1 - log only post/reply/xpost/forward/delete actions.
#	2 - log all actions for authenticated users.
#	3 - log all actions for all users.

if( $CFG['log'] && $CFG['log_level'] > 0
	&& !( $CFG['log_level'] == 1 && !need_postperm($self_base) )
	&& !( $CFG['log_level'] == 2 && !$auth_success ) ) {

	$fp = @fopen( $CFG['log'], 'a' );
	if( $fp ) {
		$log_uri = $uri;
#		$log_uri = substr( strrchr( $uri, '/' ), 1 );
		if( $log_uri == '/' )
			$log_uri = 'index.php';

		if( $auth_success )
			fwrite( $fp, sprintf( "%s %s(%s) from %s - %s\n", strftime("%Y/%m/%d %H:%M:%S"), $auth_user, $auth_email, $ip_from, $log_uri ) );
		else
			fwrite( $fp, sprintf( "%s guest from %s - %s\n", strftime("%Y/%m/%d %H:%M:%S"), $ip_from, $log_uri ) );
	}
}

#############################################################################
# Functions

function form_login_dialog( $is_expire ) {
	global $CFG, $referal, $uri;
	global $pnews_msg;

	if( isset($_GET['login']) )
		$target = $referal;
	else
		$target = $uri;

	if( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' )
		$target = str_replace( 'http://', 'https://', $target );

	$_SESSION['current_session_id'] = session_id();
	$_SESSION['save_postvar'] = true;
	$_SESSION['POSTVAR'] = $_POST;

?>
<!-- Authentication with FORM style - - - - - - - - - - - - - -->
<center>
<font size=3><b><?php echo $CFG['title']; ?></b></font><p>
<table border=1 cellspacing=0 cellpadding=10 width=80%>
<!--
<tr><td align=center bgcolor=#FFEEA0>
</td></tr>
-->
<tr><td align=center bgcolor=#DDFFDD>
<?php
if( $is_expire )
	echo "<font size=2 color=black>$pnews_msg[AuthExpired]</font>";
else
	echo "<font size=2 color=black>$pnews_msg[NeedLogin]</font>";
echo "</td></tr><tr><td align=center bgcolor=#C0C0FF>";
echo "<font size=2>";
printf( $pnews_msg['UseYourAccountAt'], $CFG['auth_organization'] );

echo <<<EOF
</font><p>
<form name=login action="form-login.php" method="post">
<table>
<tr>
 <td class=field>$pnews_msg[LoginName]</td>
 <td class=value>
EOF;

if( $CFG['auth_method'] == 'mail' && $CFG['domain_select'] ) {
	echo "<input class=login2 name=loginName size=15>\n";
	echo "<select name=domain class=login>\n";
	foreach( $CFG['pop3_mapping'] as $d => $s ) {
		echo "<option value=\"$d\">$d\n";
	}
	echo "</select>";
}
else
	echo "<input class=login name=loginName size=25>\n";

echo <<<EOF
</td>
</tr>
<tr>
 <td class=field>$pnews_msg[PassWord]</td>
 <td class=value><input class=login name=passWord type=password size=25></td>
</tr>
<tr>
 <td>&nbsp;<input name=target type=hidden value="$target"></td>
 <td align=left><input class=normal type=submit value="$pnews_msg[Login]"></td>
</tr>

EOF;

if( $CFG['auth_registration_info'] != '' ) {
	echo "<tr><td colspan=2 align=center class=text><i>";
	echo '<br />' . $CFG['auth_registration_info'];
	echo "</i></td></tr>\n";
}
?>
</table>
</form>
<script type="text/javascript">
	document.login.loginName.focus();
</script>
</td>
</tr>
<?php
	if( !$is_expire && $referal != '' ) {
		echo "<tr>\n<td bgcolor=#EEFFEE align=center>\n";
		echo "<a href=\"$referal\">$pnews_msg[GoBack]</a>";
		echo "</td>\n</tr>\n";
	}
?>
</table>
</center>
<!-- Authentication with FORM style - - - - - - - - - - - - - -->
<?php

}

function http_login_error() {
	global $CFG;
	global $pnews_msg;
	session_destroy();
	html_head( $CFG['title'] );
?>
<center><table border=1 cellspacing=0 cellpadding=10>
<tr><td align=center bgcolor=#EEDDA0>
<font size=2><?php echo $CFG['title']; ?></font>
</td></tr>
<tr><td align=center bgcolor=#C0A0FF height=100>
<font size=2>
<?php echo $pnews_msg['PasswordRetryFail']; ?>
</font><p>
<form><input class=normal type=button value="<?php echo $pnews_msg['ReLogin']; ?>" onClick='reload();'></form>
</td>
</tr>
<?php
if( $CFG['auth_registration_info'] != '' ) {
	echo "<tr><td align=center bgcolor=#DDFFDD><font color=black size=2><i>";
	echo $CFG['auth_registration_info'];
	echo "</i></font></td></tr>\n";
}
?>
</table>
</center>
<?php
	html_foot();
	html_tail();
	exit;
}

function http_login_auth() {
	global $CFG;
	header( "WWW-Authenticate: Basic realm=\"{$CFG['auth_http_realm']}\"");
	header( "HTTP/1.0 401 Unauthorized");
	http_login_error();
}

function http_logout() {
	global $title, $pnews_msg;

	html_head( "$title - $pnews_msg[Logout]" );
	echo "<font size=2><b>$title - $pnews_msg[Logout]</b></font><hr />";
	echo "<font size=3 color=black>$pnews_msg[HTTPlogoutInfo]</font><p>\n";
	echo "<form><input type=button class=normal value=\"$pnews_msg[CloseWindow]\" onClick='close_window();'></form>\n";
	html_foot();
	html_tail();
	exit;
}

function session_error( $server, $group ) {
	global $pnews_msg;
	html_head($pnews_msg['SessionError']);
	echo "<font size=2>\n";
	printf( $pnews_msg['SessionErrorReason'] . "<p>\n", "$server/$group" );
	printf( $pnews_msg['SessionErrorReturn'], "<a href=index.php>$pnews_msg[GroupList]</a>" );
	echo "</font>";
	html_tail();
	exit;
}

function readonly_error( $server, $group ) {
	global $pnews_msg;

	html_head($pnews_msg['NoPostPermission']);
	echo <<<ERR
<center>
<table border=1 cellspacing=0 cellpadding=10>
<tr><td align=center bgcolor=#A0FF30>
    <font size=3>$pnews_msg[NoPostPermission]</font>
    </td></tr>
<tr><td height=80 bgcolor=#A0EEFF align=center>
    <table>
    <tr><td align=right>$pnews_msg[NewsServer]:</td><td>$server</td></tr>
    <tr><td align=right>$pnews_msg[Group]:</td><td>$group</td></tr>
    </table>
    </td></tr>
</table>
<form>
<input class=normal type=button value="$pnews_msg[CloseWindow]" onClick='close_window();'>
</form>
</center>

ERR;
	html_foot();
	html_tail();
	exit;
}

function vars_convert( $instr ) {
	$info = isset($_SESSION['auth_info']) ? $_SESSION['auth_info'] : '';
	if( is_array($info) )
		foreach( $info as $var => $value )
			$instr = str_replace( $var, $value, $instr );
	return($instr);
}

function need_postperm( $script_name ) {
	$post_action = array( 'post.php', 'reply.php', 'forward.php', 'xpost.php', 'delete.php' );
	if( in_array( $script_name, $post_action ) )
		return(true);
	else
		return(false);
}

function verifying( $server, $group ) {

	global $news_groups, $news_server, $category_num;

	$server_handler = xoops_getmodulehandler('servers', 'xpnews');

	if( $server == '' || $group == '' )
		return(-1);

	if( !preg_match( '/^[\w\d.\-_+]+$/', $group ) )
		return(-1);

	$criteria = new CriteriaCompo();
	$criteria->add(new Criteria('server', $server));
	foreach ($server_handler->getObjects($criteria) as $key => $server)
	{
		if (match_group( $group, $news_groups[$key] ))
			return $key;
	}

	return(-1);
}

function check_group( $server, $group ) {
	if( ($c = verifying( $server, $group )) == -1 )
		session_error( $server, $group );
	return($c);
}

function match_group( $group, $pattern ) {

	if( $pattern == '*' ) {
		return(true);
		break;
	}

	$groups = split( ',', $pattern );

	foreach ( $groups as $reg ) {
		$reg = trim($reg);
		$reg = str_replace( '.', '\\.', $reg );
		$reg = str_replace( '*', '.*', $reg );
		$reg = str_replace( '+', '\\+', $reg );
//		echo "<!-- $reg vs $group -->\n";
		if( @eregi( "^$reg\$", $group ) )
			return(true);
	}
#	echo $_SERVER['QUERY_STRING'];
#	exit;
	return(false);
}

function nnrp_authenticate() {
	global  $CFG;
	global	$nnrp;
	global	$curr_category;
	global	$news_authinfo;

	$nnrp->mode_reader();
	if( $curr_category < 0 ) {
		return(true);
	}

	$authinfo = $news_authinfo[$curr_category];

	if( $authinfo == 'none' || $authinfo == '' ) {
		return(true);
	}

	list( $user, $pass ) = explode( ',', $authinfo );

	if( $CFG['auth_prompt'] == 'http' ) {
		$user = str_replace( '%http_user', $_SERVER['PHP_AUTH_USER'], $user);
		$pass = str_replace( '%http_pw',   $_SERVER['PHP_AUTH_PW'],   $pass);
	}

	if( $nnrp->auth( $user, $pass ) ) {
		$nnrp->mode_reader();
		return(true);
	}
	else
		return(false);
}

function kill_myself() {
	echo <<<END
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<script type="text/javascript">
        window.close();
</script>
</head>
</html>
END;
	exit;
}

function connect_error( $server ) {
	global $pnews_msg;
	html_head('ERROR');
	echo <<<ERR
<br />
<br />
<font size=3>$pnews_msg[ConnectServerError] ($server)</font>
<br />
ERR;
	html_foot();
	html_tail();
	exit;
}

function uuencode_file( $filename, $source, $mode = '644' ) {

	global	$nnrp;

# http://pages.prodigy.net/michael_santovec/decode.htm

	if( !file_exists( $source ) )
		return;

	$fp = fopen( $source, 'r' );
	if( ! $fp )
		return;

	$size = filesize( $source );

#	fwrite( $hld, "$size\n");

	$nnrp->post_write( "\nbegin $mode $filename  \n" );

	$ilen = $llen = 0;
	$text = '';
	while( $stuff = fread( $fp, 3 ) ) {
		$byte[0] = (ord($stuff[0]) >> 2) & 0x3F;
		$byte[1] = (((ord($stuff[0]) & 0x03 ) << 4) | ((ord($stuff[1]) >> 4) & 0x0F)) & 0x3F;
		$byte[2] = (((ord($stuff[1]) & 0x0F ) << 2) | ((ord($stuff[2]) >> 6) & 0x03)) & 0x3F;
		$byte[3] = ord($stuff[2]) & 0x3F;
		$ilen += strlen($stuff);
		for ($j = 0; $j < 4; $j++) {

			if( $byte[$j] == 0 )
				$text .= '`';
			else
				$text .= chr($byte[$j] + 32);
			$llen++;
			if( $llen == 60 ) {
#				echo chr(77) . $text . "  <br />\n";
				$nnrp->post_write( chr(77) . $text . "\n" );
				$ilen = $llen = 0;
				$text = '';
			}
		}
	}
	fclose($fp);

	if( $llen > 0 )
		$nnrp->post_write( chr($ilen+32) .  $text . "\n`\nend\n" );
	else
		$nnrp->post_write( "`\nend\n" );
/*
	if( $llen == 0 )
		fwrite( $hld, " \nend\n" );
	elseif( $ilen == 14 )
		fwrite( $hld, '..' .  $text . "  \n` \nend\n" );
	else
		fwrite( $hld, chr($ilen+32) .  $text . "  \n` \nend\n" );
*/
}

function hide_mail( $email ) {
	list( $id, $domain ) = explode( '@', $email );
	$id = str_replace( '\\', '\\\\', $id );
	$id = str_replace( '"', '\\"', $id );
	$domain = str_replace( '\\', '\\\\', $domain );
	$domain = str_replace( '"', '\\"', $domain );
	$hmail = '"' . $id . '" + "&#64;" + "' . str_replace( '.', '&#46;', $domain ) . '"';
	return <<<EMAIL
<script type="text/javascript">document.write( $hmail );</script>
EMAIL;
}

function hide_mail_link( $email, $linktext = '' ) {
	if( strchr( $email, '@' ) )
		list( $id, $domain ) = explode( '@', $email );
	else {
		$id = $email;
		$domain = '';
	}
	$id = str_replace( '\\', '\\\\', $id );
	$id = str_replace( '"', '\\"', $id );
	$domain = str_replace( '\\', '\\\\', $domain );
	$domain = str_replace( '"', '\\"', $domain );
	$hmail = '"' . $id . '" + "&#64;" + "' . str_replace( '.', '&#46;', $domain ) . '"';
	if( $linktext == '' ) {
		return <<<EMAIL
<script type="text/javascript">document.write( '<a href="mailto:' + $hmail + '">' + $hmail + '</a>' );</script>
EMAIL;
	}
	else {
		$linktext = str_replace( '\\', '\\\\', $linktext );
		$linktext = str_replace( '"', '\\"', $linktext );
#		$linktext = htmlspecialchars( $linktext );
		return <<<EMAIL
<script type="text/javascript">document.write( '<a href="mailto:' + $hmail + '">' + "$linktext" + '</a>' );</script>
EMAIL;
	}
}

function get_group() {
	$qstr = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
	$vars = explode( '&', $qstr );
	foreach( $vars as $var ) {
		if( preg_match( '/^group=(.+)$/', $var, $match ) )
			return(rawurldecode($match[1]));
	}
	return('');
}

function mkdirs( $path, $mode = 0755 ) {
	if( is_dir($path) )
		return true;
	$ppath = dirname($path);
        if( !@mkdirs($ppath, $mode) )
        	return false;
	return @mkdir($path, $mode);
}

?>
