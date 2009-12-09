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

/* Read and check the configuration (config.inc.php) */

$valid_charsets = array( 'big5', 'gb', 'gb2312', 'utf-8', 'ascii', 'iso-8859-1', 'iso-8859-2', 'iso-8859-15' );
$valid_language = array( 'zh-tw', 'zh-cn', 'unicode', 'en', 'fr', 'fi', 'de', 'it', 'sk' );

if( !file_exists(XOOPS_ROOT_PATH.'/modules/xpnews/config.inc.php') )
	show_error( "You should edit your 'config.inc.php'. Copy examples/config.inc.php as a template.");

$cfg_timestamp = filemtime(XOOPS_ROOT_PATH.'/modules/xpnews/config.inc.php');
if( isset( $_SESSION['cfg_cache'], $_SESSION['cfg_cache_time'] ) && $cfg_timestamp == $_SESSION['cfg_cache_time'] ) {
	# cache hit - restore $CFG from cache
	# echo 'cache hit, restote $CFG from cache';
	$CFG = $_SESSION['cfg_cache'];
	if( $CFG['auth_type'] != 'open' ) {

		switch( $CFG['auth_method'] ) {
		case 'pop3':
			include('auth/pop3.inc.php');
			break;
		case 'pop3s':
			include('auth/pop3s.inc.php');
			break;
		case 'mail':
			include('auth/mail.inc.php');
			break;
		case 'ldap':
			include('auth/ldap.inc.php');
			break;
		case 'ftp':
			include('auth/ftp.inc.php');
			break;
		case 'ftps':
			include('auth/ftps.inc.php');
			break;
		case 'mysql':
			include('auth/mysql.inc.php');
			break;
		case 'pgsql':
			include('auth/pgsql.inc.php');
			break;
		case 'nntp':
			include('auth/nntp.inc.php');
			break;
		case 'nntps':
			include('auth/nntps.inc.php');
			break;
		case 'user':
			if( file_exists($CFG['auth_user_module']) )
				include( $CFG['auth_user_module'] );
			else
			  	show_error( $CFG['auth_user_module'] . ' can not be included');
			break;
		case 'phpbb':
			include('auth/phpbb.inc.php');
			break;
		}

		if( $CFG['auth_prompt'] == 'cas' ) {

			@include_once('CAS/CAS.php');

			$cas_server = $CFG['auth_cas_server'];
			if( strstr( $cas_server, ':' ) ) {
				list( $cas_server, $cas_port ) = split( ':', $cas_server );
				$cas_port = intval($cas_port);
			}
			else
				show_error('The port on which the CAS server is running was not specified. $CFG[\'auth_cas_server\'] should look like \'hostname:port\'.');

			if( !isset($CFG['auth_cas_debug']) )
				$CFG['auth_cas_debug'] = false;

			phpCAS::setDebug($CFG['auth_cas_debug']);

			phpCAS::client( CAS_VERSION_2_0, $cas_server, $cas_port, $CFG['auth_cas_base_uri']);
		}

	}
	return;
}

$valid_auth_type   = array( 'required', 'optional', 'open' );
$valid_auth_prompt = array( 'http', 'form', 'cas', 'other' );
$valid_auth_method = array( 'ldap', 'pop3', 'pop3s', 'mail', 'ftp', 'ftps', 'mysql', 'pgsql', 'nntp', 'nntps', 'cas', 'user', 'phpbb' );
$valid_method_for_other = array( 'phpbb' );

require_once(XOOPS_ROOT_PATH.'/modules/xpnews/config.inc.php');

// CAS: when using the CAS mechanism
// $CFG['auth_prompt'] and $CFG['auth_method'] should be both set to 'cas'

if( !in_array( $CFG['auth_type'], $valid_auth_type ) ) {
	config_error( '$CFG["auth_type"]' );
}

if( $CFG['auth_type'] != 'open' ) {

	if( !isset($CFG['auth_prompt']) )
		$CFG['auth_prompt'] = 'form';
	elseif( !in_array( $CFG['auth_prompt'], $valid_auth_prompt ) )
		config_error( '$CFG["auth_prompt"]' );

	if( $CFG['auth_prompt'] == 'http' && !isset($CFG['auth_http_realm']) )
		config_error( '$CFG["auth_http_realm"]' );

	if( $CFG['auth_prompt'] == 'other' && !in_array( $CFG['auth_method'], $valid_method_for_other ) )
		show_error( '$CFG["auth_method"] is invalid if $CFG["auth_prompt"] = "other"' );

	if( !isset($CFG['auth_method']) || !in_array( $CFG['auth_method'], $valid_auth_method ) )
		config_error( '$CFG["auth_method"]' );

	if( $CFG['auth_method'] != 'mail' && !isset( $CFG['auth_user_email'] ) )
		config_error( '$CFG["auth_user_email"]' );

	// CAS
	if( $CFG['auth_prompt'] == 'cas' && $CFG['auth_method'] != 'cas' )
		show_error( 'when using CAS, $CFG["auth_prompt"] and $CFG["auth_method"] should be both set to "cas"' );

	switch( $CFG['auth_method'] ) {
	case 'pop3':
		if( !isset( $CFG['pop3_server'] ) )
			config_error( '$CFG["pop3_server"]' );
		if( !isset( $CFG['pop3_user_modify'] ) )
			$CFG['pop3_user_modify'] = '%u';
		if( file_exists('auth/pop3.inc.php') )
			include('auth/pop3.inc.php');
		else
			show_error( 'POP3 authentication module missed' );
		if( !function_exists( 'check_user_password' ) )
			show_error( 'POP3 authentication module is invalid' );
		break;
	case 'pop3s':
		if( !isset( $CFG['pop3s_server'] ) )
			config_error( '$CFG["pop3s_server"]' );
		if( file_exists('auth/pop3s.inc.php') )
			include('auth/pop3s.inc.php');
		else
			show_error( 'POP3S authentication module missed' );
		if( !function_exists( 'check_user_password' ) )
			show_error( 'POP3S authentication module is invalid' );
		if( !version_check( '4.3.0' ) )
			show_error( 'PHP 4.3.0 or greater is required for POP3 over SSL support' );
		if( !function_exists( 'openssl_get_publickey' ) )
			show_error( 'OpenSSL is required for POP3 over SSL support' );
		break;
	case 'mail':
		if( !isset( $CFG['pop3_mapping'] ) )
			config_error( '$CFG["pop3_mapping"]' );
		if( file_exists('auth/mail.inc.php') )
			include('auth/mail.inc.php');
		else
			show_error( 'Mail authentication module missed' );
		if( !function_exists( 'check_user_password' ) )
			show_error( 'Mail authentication module is invalid' );
		break;
	case 'ldap':
		if( !function_exists( 'ldap_connect' ) )
			show_error( 'Your PHP does support LDAP' );
		if( !isset( $CFG['ldap_server'], $CFG['ldap_dn'], $CFG['ldap_bind_rdn'], $CFG['ldap_bind_pwd'] ) )
			config_error( '$CFG["ldap_*"]' );
		if( !isset( $CFG['ldap_filter'] ) )
			$CFG['ldap_filter'] = '(cn=%u)';
		if( file_exists('auth/ldap.inc.php') )
			include('auth/ldap.inc.php');
		else
			show_error( 'LDAP authentication module missed' );
		if( !function_exists( 'check_user_password' ) )
			show_error( 'LDAP authentication module is invalid' );
		break;
	case 'ftp':
		if( !isset( $CFG['ftp_server'] ) )
			config_error( '$CFG["ftp_server"]' );
		if( !isset( $CFG['ftp_deny'] ) )
			$CFG['ftp_deny'] = array( 'anonymous', 'guest', 'ftp' );
		if( file_exists('auth/ftp.inc.php') )
			include('auth/ftp.inc.php');
		else
			show_error( 'FTP authentication module missed' );
		if( !function_exists( 'check_user_password' ) )
			show_error( 'FTP authentication module is invalid' );
		break;
	case 'ftps':
		if( !isset( $CFG['ftps_server'] ) )
			config_error( '$CFG["ftps_server"]' );
		if( !isset( $CFG['ftps_deny'] ) )
			$CFG['ftps_deny'] = array( 'anonymous', 'guest', 'ftp' );
		if( file_exists('auth/ftps.inc.php') )
			include('auth/ftps.inc.php');
		else
			show_error( 'FTPS authentication module missed' );
		if( !function_exists( 'check_user_password' ) )
			show_error( 'FTPS authentication module is invalid' );
		if( !version_check( '4.3.0' ) )
			show_error( 'PHP 4.3.0 or greater is required for FTP over SSL support' );
		if( !function_exists( 'openssl_get_publickey' ) )
			show_error( 'OpenSSL is required for FTP over SSL support' );
		break;
	case 'mysql':
		if( !function_exists( 'mysql_connect' ) )
			show_error( 'Your PHP does support MySQL' );
		if( file_exists('auth/mysql.inc.php') )
			include('auth/mysql.inc.php');
		else
			show_error( 'MySQL authentication module missed' );
		if( !function_exists( 'check_user_password' ) )
			show_error( 'MySQL authentication module is invalid' );
		check_db_settings();
		break;
	case 'pgsql':
		if( !function_exists( 'pg_connect' ) )
			show_error( 'Your PHP does support PostgreSQL' );
		if( file_exists('auth/pgsql.inc.php') )
			include('auth/pgsql.inc.php');
		else
			show_error( 'PostgreSQL authentication module missed' );
		if( !function_exists( 'check_user_password' ) )
			show_error( 'PostgreSQL authentication module is invalid' );
		check_db_settings();
		break;
	case 'nntp':
		if( !isset( $CFG['auth_nntp_server'] ) )
			config_error( '$CFG["auth_nntp_server"]' );
		if( file_exists('auth/nntp.inc.php') )
			include('auth/nntp.inc.php');
		else
			show_error( 'NNTP authentication module missed' );
		if( !function_exists( 'check_user_password' ) )
			show_error( 'NNTP authentication module is invalid' );
		break;
	case 'nntps':
		if( !isset( $CFG['auth_nntps_server'] ) )
			config_error( '$CFG["auth_nntps_server"]' );
		if( file_exists('auth/nntps.inc.php') )
			include('auth/nntps.inc.php');
		else
			show_error( 'NNTPS authentication module missed' );
		if( !function_exists( 'check_user_password' ) )
			show_error( 'NNTPS authentication module is invalid' );
		if( !version_check( '4.3.0' ) )
			show_error( 'PHP 4.3.0 or greater is required for NNTPS support' );
		if( !function_exists( 'openssl_get_publickey' ) )
			show_error( 'OpenSSL is required for NNTPS support' );
		break;
	case 'cas':
		if( !isset( $CFG['auth_cas_server'] ) )
			config_error( '$CFG["auth_cas_server"]' );
		if( !isset( $CFG['auth_cas_base_uri'] ) )
			config_error( '$CFG["auth_cas_base_uri"]' );
		break;
	case 'user':
		if( file_exists( $CFG['auth_user_module'] ) )
			include( $CFG['auth_user_module'] );
		else
			show_error( 'User-defined authentication module missed' );

		if( !function_exists( 'check_user_password' ) )
			show_error( 'User-defined authentication module is invalid' );
		break;
	case 'phpbb':
		if( $CFG['auth_prompt'] != 'other' )
			show_error( '$CFG["auth_prompt"] should be "other" to use phpbb authentication module' );
		if( file_exists('auth/phpbb.inc.php') )
			include('auth/phpbb.inc.php');
		else
			show_error( 'PHPBB authentication module missed' );
		if( !function_exists( 'auth_already_login' ) )
			show_error( 'PHPBB authentication module is invalid' );
		if( !isset( $CFG['auth_phpbb_url_base'] ) )
			config_error( '$CFG["auth_phpbb_url_base"]' );
		if( !isset( $CFG['auth_phpbb_path'] ) )
			config_error( '$CFG["auth_phpbb_path"]' );
		break;
	}

	if( !isset($CFG['auth_organization']) )
		config_error( '$CFG["auth_organization"]' );

	if( !isset($CFG['auth_expire_time']) )
		$CFG['auth_expire_time'] = 3600;

}

if( !isset( $CFG['html_header'] ) || !file_exists( $CFG['html_header'] ) )
	$CFG['html_header'] = false;

if( !isset( $CFG['html_footer'] ) || !file_exists( $CFG['html_footer'] ) )
	$CFG['html_footer'] = false;

if( !isset( $CFG['banner'] ) )
	$CFG['banner'] = false;

if( !isset( $CFG['title'] ) )
	$CFG['title'] = 'Webnews';

if( !isset( $CFG['show_latest_top'] ) ) {
	if( isset($CFG['show_newest_top']) )
		$CFG['show_latest_top'] = $CFG['show_newest_top'];
	else
		$CFG['show_latest_top'] = true;
}

#if( !isset( $CFG['show_article_popup'] ) )
#	$CFG['show_article_popup'] = false;

if( !isset( $CFG['filter_ansi_color'] ) )
	$CFG['filter_ansi_color'] = true;

if( !isset( $CFG['group_sorting'] ) )
	$CFG['group_sorting'] = false;

if( !isset( $CFG['show_group_description'] ) )
	$CFG['show_group_description'] = true;

if( !isset( $CFG['organization'] ) )
	$CFG['organization'] = 'News Server';

if( !isset( $CFG['auth_registration_info'] ) )
	$CFG['auth_registration_info'] = '';

if( !isset( $CFG['domain_select'] ) )
	$CFG['domain_select'] = true;

if( !isset( $CFG['show_article_header'] ) )
	$CFG['show_article_header'] = true;

if( !isset( $CFG['global_readonly'] ) ) {
	if( isset( $CFG['post_restriction'] ) )
		$CFG['global_readonly'] = $CFG['post_restriction'];
	else
		$CFG['global_readonly'] = false;
}

if( !isset( $CFG['auth_deny_users'] ) || !is_array($CFG['auth_deny_users']) )
	$CFG['auth_deny_users'] = array();

if( !isset( $CFG['confirm_post'] ) )
	$CFG['confirm_post'] = false;

if( !isset( $CFG['confirm_forward'] ) )
	$CFG['confirm_forward'] = false;

if( !isset( $CFG['post_signature'] ) )
	$CFG['post_signature'] = '';

if( !isset( $CFG['meta_description'] ) )
	$CFG['meta_description'] = 'PHP News Reader';

if( !isset( $CFG['meta_keywords'] ) )
	$CFG['meta_keywords'] = 'news,pnews,webnews,nntp,usenet,netnews';

if( !isset( $CFG['auth_user_fullname'] ) )
	$CFG['auth_user_fullname'] = '%u';

$checks = array( 'config', 'grouplst', 'database' );

if( !isset($CFG['interface_language']) || !in_array( $CFG['interface_language'], $valid_language ) )
	$CFG['default_language'] = 'en';
else
	$CFG['default_language'] = $CFG['interface_language'];

/*
$default_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

if( isset($CFG['interface_language']) && $CFG['interface_language'] != 'auto' ) {
	if( in_array( $CFG['interface_language'], $valid_language ) )
		$default_language = $CFG['interface_language'];
}

if( !in_array( $default_language, $valid_language ) )
	$default_language = 'en';
*/

#$CFG['charset']['interface'] = $lang_coding[$default_language];

foreach( $checks as $section ) {
	if( !isset( $CFG['charset'][$section] ) )
		$CFG['charset'][$section] = 'utf-8';
	elseif( !in_array( $CFG['charset'][$section], $valid_charsets ) )
		config_error( '$CFG["charset"]["' . $section . '"]' );
}

#$default_charset = $CFG['charset']['interface'];

if( !isset( $CFG['cache_dir'] ) )
	$CFG['cache_dir'] = false;
elseif( !is_dir( $CFG['cache_dir'] ) ) {
	if( !mkdir( $CFG['cache_dir'] ) || !is_writeable( $CFG['cache_dir'] ) )
		show_error( '$CFG["cache_dir"] can not be created' );
}
elseif( !is_writeable( $CFG['cache_dir'] ) )
	show_error( '$CFG["cache_dir"] is not a writable directory' );

if( !isset($CFG['thread_enable'] ) )
	$CFG['thread_enable'] = false;

if( !isset( $CFG['thread_db_format'] ) )
	$CFG['thread_db_format'] = '';

if( $CFG['thread_enable'] ) {
	if( ! $CFG['cache_dir'] )
		show_error( '$CFG["cache_dir"] should be assigned for thread support' );
	if( !function_exists( 'dba_open' ) )
		show_error( 'DBA extension should be enabled for thread support' );
	if( $CFG['thread_db_format'] == '' ) {
		if( version_check( '4.3.2' ) )
			$CFG['thread_db_format'] = 'db4';
		else
			$CFG['thread_db_format'] = 'db3';
	}
	if( version_check( '4.3.0' ) ) {
		if( !in_array( $CFG['thread_db_format'], dba_handlers() ) )
			show_error('$CFG["thread_db_format"] handler is not supported by your DBA extension');
	}
	else {
		$valid_handler = array( 'dbm', 'ndbm', 'gdbm', 'db2', 'db3' );
		if( !in_array( $CFG['thread_db_format'], $valid_handler ) )
			show_error('$CFG["thread_db_format"] handler may not be supported by your DBA extension');
	}
}

if( !isset( $CFG['url_rewrite'] ) ) 
	$CFG['url_rewrite'] = false;

if( !isset( $CFG['email_editing'] ) ) 
	$CFG['email_editing'] = true;

if( !isset( $CFG['hide_email'] ) )
	$CFG['hide_email'] = true;

if( !isset( $CFG['articles_per_page'] ) ) 
	$CFG['articles_per_page'] = 20;

if( !isset($CFG['url_base']) )
	config_error( '$CFG["url_base"]' );

if( !preg_match( '/^https?:\/\//', $CFG['url_base'] ) )
	show_error( '$CFG["url_base"] should begin with http:// or https://' );


if( !isset( $CFG['magic_tag'] ) )
	$CFG['magic_tag'] = false;

if( !isset($CFG['referrer_enforcement']) )
	$CFG['referrer_enforcement'] = false;

if( !isset($CFG['article_numbering_reverse']) )
	$CFG['article_numbering_reverse'] = false;

if( !isset($CFG['advertise_group_list']) )
	$CFG['advertise_group_list'] = false;

if( !isset($CFG['advertise_banner']) )
	$CFG['advertise_banner'] = false;

if( !isset($CFG['advertise_article']) )
	$CFG['advertise_article'] = false;

if( !isset($CFG['time_format']) )
	$CFG['time_format'] = '%Y/%m/%d %H:%M';
#	$CFG['time_format'] = '%Y/%m/%d %H:%M:%S';

if( !isset($CFG['style_sheet']) || !file_exists( 'css/' . $CFG['style_sheet'] ))
	$CFG['style_sheet'] = 'standard.css';

if( !isset($CFG['image_inline']) )
	$CFG['image_inline'] = true;

if( !isset($CFG['allow_attach_file']) )
	$CFG['allow_attach_file'] = 2;

if( !isset($CFG['language_switch']) )
	$CFG['language_switch'] = true;

if( !isset($CFG['log']) )
	$CFG['log'] = false;

if( !isset($CFG['log_level']) )
	$CFG['log_level'] = 3;

if( !isset($CFG['debug_level']) )
	$CFG['debug_level'] = 0;

if( $CFG['log'] && !is_writable($CFG['log']) )
	show_error( '$CFG["log"]: You does not have write permission on ' . $CFG['log'] );

if( !isset($CFG["links"]) )
	$CFG["links"] = null;

/* un-documented settings */
if( !isset($CFG['author_link']) )
	$CFG['author_link'] = true;

if( $CFG["auth_prompt"] == 'cas' ) {

	/* The following codes were contributed by Pascal Aubry */

	/* Import phpCAS library */
	@include_once('CAS/CAS.php');

	/* Check that phpCAS was correctly installed */
	if( !class_exists( 'phpCAS' ) )
		show_error( 'phpCAS was not found. Please install <a href="http://esup-phpcas.sourceforge.net" target="_blank">phpCAS</a> in '.dirname(__FILE__).'/auth or anywhere in the <a href="http://www.php.net/manual/en/configuration.directives.php#ini.include-path" target="_blank">PHP include path</a>.' );

	$cas_server = $CFG['auth_cas_server'];
	if( strstr( $cas_server, ':' ) ) {
		list( $cas_server, $cas_port ) = split( ':', $cas_server );
		$cas_port = intval($cas_port);
	}
	else
		show_error('The port on which the CAS server is running was not specified. $CFG[\'auth_cas_server\'] should look like \'hostname:port\'.');

	if( !isset($CFG['auth_cas_debug']) )
		$CFG['auth_cas_debug'] = false;

	/* Set phpCAS debug mode if needed */
	phpCAS::setDebug($CFG['auth_cas_debug']);

	/* Initialize phpCAS */
	phpCAS::client( CAS_VERSION_2_0, $cas_server, $cas_port, $CFG['auth_cas_base_uri']);
}

/* cache the configuration setting */

$_SESSION['cfg_cache'] = $CFG;
$_SESSION['cfg_cache_time'] = $cfg_timestamp;

return;

/* ------------------------------------------------------------------------ */

function check_db_settings() {
	global $CFG;

	if( !isset( $CFG['db_name'] ) )
		config_error( '$CFG["db_name"]' );
	if( !isset( $CFG['db_username'] ) )
		config_error( '$CFG["db_username"]' );
	if( !isset( $CFG['db_password'] ) )
		config_error( '$CFG["db_password"]' );
	if( !isset( $CFG['db_table'] ) )
		config_error( '$CFG["db_table"]' );
	if( !isset( $CFG['db_field_username'] ) )
		config_error( '$CFG["db_field_username"]' );
	if( !isset( $CFG['db_field_password'] ) )
		config_error( '$CFG["db_field_password"]' );
	if( !isset($CFG['db_password_crypt']) )
		$CFG['db_password_crypt'] = false;
	if( $CFG['db_password_crypt'] && !function_exists( $CFG['db_password_crypt'] ) )
		config_error( '$CFG["db_password_crypt"]' );
}

function config_error( $error_var ) {
	show_error( "<font color=red size=3><i><b>ERROR:</b></i></font> The <b>$error_var</b> setting is incorrect in your config.inc.php !");
}

function show_error( $err_string ) {

	echo <<<EOE
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Configuration Error</title>
<style>
body { color: white ; background: black; font-family: Georgia }
hr { height: 1px ; color: blue }
a { color: magenta }
a:visited { color: magenta }
a:hover { color: red }
</style>
</head>
<body>
<font size=4>PHP News Reader - Configuration Error</font><hr />
<br /><font size=3>$err_string</font>\n<br /><p>
<font color=cyan>For more information, please read the <a href=doc/guide.php>Installation Guide</a> for details.</font>
<br /><br /><br />
<hr />
</body>
</html>
EOE;
	exit;
}

function version_check($check) {
	$minver = explode( '.', $check );
	$curver = explode( '.', phpversion() );
	if( $curver[0]*10000+$curver[1]*100+$curver[2]
			>= $minver[0]*10000+$minver[1]*100+$minver[2] )
		return true;
	else
		return false;
}

?>
