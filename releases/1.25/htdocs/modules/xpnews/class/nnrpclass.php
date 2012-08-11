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

define('SHOW_HYPER_LINK',  1 );
define('SHOW_SIGNATURE',   2 );
define('SPACE_ASIS',       4 );
define('SHOW_NULL_LINE',   8 );
define('SHOW_HEADER',     16 );
define('FILTER_ANSI',     32 );
define('IMAGE_INLINE',    64 );
define('HIDE_EMAIL',     128 );

$php_news_agent = "PHP News Reader $pnews_version (CDSHEEN)";

class pnews_nnrp {

	var 	$nhd;
	var	$nnrp_debug_level = 0;
	var	$cache_dir;
	var	$thread_enable;
	var	$db_handler;
	var	$nnrp_last_command;
	var	$nnrp_last_result;
	var	$curr_server;
	var	$curr_group;

	function pnews_nnrp( $dbl = 0, $c = false, $t = false, $d = 'db4' ) {
		$nhd = null;
		$this->nnrp_debug_level = $dbl;
		$this->cache_dir = $c;
		$this->thread_enable = $t;
		$this->db_handler = $d;
	}

	function connected() {
		return( $this->nhd );
	}

	function open( $nnrp_server, $ssl_enable = false ) {
		if( $ssl_enable )
			return( $this->open_nntps( $nnrp_server ) );
		else
			return( $this->open_nntp( $nnrp_server ) );
	}

	function set_flag( $c = false, $t = false, $d = 'db4' ) {
		$this->cache_dir = $c;
		$this->thread_enable = $t;
		$this->db_handler = $d;
	}

	function set_debug_level( $level ) {
		$this->nnrp_debug_level = $level;
	}

	function open_nntp( $nnrp_server ) {
		if( strstr( $nnrp_server, ':' ) )
			list( $nnrp_server, $port ) = split( ':', $nnrp_server );
		else
			$port = 119;
		$this->curr_server = $nnrp_server;
		$this->nhd = null;
		if( $this->nnrp_debug_level ) {
			$this->nhd = fsockopen( $nnrp_server, $port, $errno, $errstr, 5 );
			if( ! $this->nhd )
				echo "ERROR: $errstr ($errno)<br />\n";
		}
		else
			$this->nhd = @fsockopen( $nnrp_server, $port, $errno, $errstr, 5 );
		if( ! $this->nhd )
			return(null);
		list( $code, $msg ) = $this->get_status();
		if( $code[0] != '2' && $code[0] != '3' )
			return(null);
#		$this->send_command( 'MODE READER' );
#		list( $code, $msg ) = $this->get_status();
#		if( $code[0] != '2' )
#			return(null);
		return( $this->nhd );
	}

	function open_nntps ( $nnrp_server ) {
		if( strstr( $nnrp_server, ':' ) )
			list( $nnrp_server, $port ) = split( ':', $nnrp_server );
		else
			$port = 563;
		$this->curr_server = $nnrp_server;
		$this->nhd = null;
		if( $this->nnrp_debug_level ) {
			$this->nhd = fsockopen( "ssl://$nnrp_server", $port, $errno, $errstr, 5 );
			if( ! $this->nhd )
				echo "ERROR: $errstr ($errno)<br />\n";
		}
		else
			$this->nhd = @fsockopen( "ssl://$nnrp_server", $port, $errno, $errstr, 5 );
		if( ! $this->nhd )
			return(null);
		list( $code, $msg ) = $this->get_status();
		if( $code[0] != '2' && $code[0] != '3' )
			return(null);
#		$this->send_command( 'MODE READER' );
#		list( $code, $msg ) = $this->get_status();
#		if( $code[0] != '2' )
#			return(null);
		return( $this->nhd );
	}

	function mode_reader() {
		$this->send_command( 'MODE READER' );
		list( $code, $msg ) = $this->get_status();
		return( $code[0] == '2' );
	}

	function help() {
		$this->send_command( 'HELP' );
		while( $buf = fgets( $this->nhd, 4096 ) ) {
			echo "$buf<br />";
			$buf = chop($buf);
			if( $buf == '.' )
				break;
		}
	}

	function auth( $username, $password ) {
		$this->send_command( "AUTHINFO USER $username" );
		list( $code, $msg ) = $this->get_status();
		if( $code[0] != '3' )
			return(false);
		$this->send_command( "AUTHINFO PASS $password" );
		list( $code, $msg ) = $this->get_status();
		if( $code[0] != '2' )
			return(false);
		return(true);
	}

	function list_group( $filter = '*', $func = null ) {
		if( $filter == '*' )
			$group_show = array( '' );
		else
			$group_show = explode( ',', $filter );
		$active = null;
		foreach( $group_show as $group ) {
			$group = trim($group);
			if( $group[0] == '!' ) {
				$group = substr( $group, 1 );

				foreach( $active as $key => $value) {
					if( eregi( "^$group" , $key ) )
						unset( $active[$key] );
				}
				continue;
			}
			if( strchr( $group, '*' ) && preg_match( '/\.((_-\w\*)+)$/', $group, $match ) ) {
				$re_match = $match[1];
				$re_group = str_replace( $re_match, '*', $group );
				$re_filter = str_replace( '*', '[^\.]*', $re_match );
			}
			else
				$re_match = '*';
			if( $re_match == '*' )
				$this->send_command( "LIST ACTIVE $group");
			else
				$this->send_command( "LIST ACTIVE $re_group" );
			list( $code, $msg ) = $this->get_status();
			if( $code[0] != '2' )
				break;
			while( $buf = fgets( $this->nhd, 4096 ) ) {
				$buf = chop($buf);
				if( $buf == '.' )
					break;
				$entry = split( ' ', $buf );
				if( $re_match != '*' && !preg_match( "/\.$re_filter\$/i", $entry[0] ) )
					continue;
				$active[$entry[0]] = array( (int)$entry[1], (int)$entry[2] );
			}
			$this->send_command( "LIST newsgroups $group");
			list( $code, $msg ) = $this->get_status();
			if( $code[0] != '2' )
				continue;
			while( $buf = fgets( $this->nhd, 4096 ) ) {
				$buf = chop( $buf );
				if( $buf == '.' )
					break;
				preg_match( '/^(\S+)\s+(.+)$/', $buf, $match );
				if( isset($match[1]) && isset($active[$match[1]]) ) {
					if( $func )
						array_push( $active[$match[1]], $func($match[2]) );
					else
						array_push( $active[$match[1]], $match[2] );
				}
			}
		}
		return( $active );
	}

	function group( $group ) {
		$this->curr_group = $group;
		$this->send_command( "GROUP $group");
		list( $code, $msg ) = $this->get_status();
		list( $count, $lowmark, $highmark ) = split( ' ', $msg );
		return( array( $code, $count, $lowmark, $highmark ) );
	}

	function xover( $from, $to=null ) {
		$ovfmt = array( 'Subject:' => 1,
				'From:' => 2,
				'Date:' => 3,
				'Message-ID:' => 4,
				'References:' => 5,
				'Bytes:' => 6,
				'Lines:' => 7,
				'Xref:full' => 8 );
		$this->send_command( 'LIST OVERVIEW.FMT' );
		list( $code, $msg ) = $this->get_status();
		if( $code[0] == '2' ) {
			$n = 1;
			while( $buf = fgets( $this->nhd, 4096 ) ) {
				$buf = trim( $buf );
				if( $buf == "." )
					break;
				$ovfmt[$buf] = $n++;
			}
		}
		if( $to == null )
			$this->send_command( "XOVER $from" );
		else
			$this->send_command( "XOVER $from-$to" );
		list( $code, $msg ) = $this->get_status();
		if( $code[0] != '2' )
			return(null);
		$ov = array();
		while( $buf = fgets( $this->nhd, 4096 ) ) {
			$buf = chop( $buf );
			if( $buf == "." )
				break;
			$xover = split( "\t", $buf );
			$n = $xover[0];
			$ov[$n] = array( $this->decode_subject($xover[$ovfmt['Subject:']]),
						'',
						strtotime( $xover[$ovfmt['Date:']] ),
						'',
						$xover[$ovfmt['Message-ID:']] );
			if( preg_match( '/^<([^@]+)@([\w-_.]+)>$/', $xover[$ovfmt['From:']], $from ) ) {
				$ov[$n][1] = $from[1];
				$ov[$n][3] = $from[0];
			}
			elseif( preg_match( '/^([^@]+)@([\w-_.]+)$/', $xover[$ovfmt['From:']], $from ) ) {
				$ov[$n][1] = $from[1];
				$ov[$n][3] = $from[0];
			}
			elseif( preg_match( '/^(.+)? <(.+)>$/', $xover[$ovfmt['From:']], $from ) ) {
				$from[1] = $this->strip_quotes( $from[1] );
				$ov[$n][1] = $this->decode_subject($from[1]);
				$ov[$n][3] = $from[2];
			}
			elseif( preg_match( '/^(([^@]+)@([\w-_.]+))\s*\((.+)?\)$/', $xover[$ovfmt['From:']], $from ) ) {
				$from[4] = isset($from[4]) ? $this->strip_quotes( $from[4] ) : '';
				$ov[$n][1] = $this->decode_subject($from[4]);
				$ov[$n][3] = $from[1];
			}
			$ref_index = isset($ovfmt['References']) ? $ovfmt['References'] : 5;
			$refs = isset($xover[$ref_index]) ? trim($xover[$ref_index]) : '';
			if( $refs == '' )
				$ov[$n][5] = array();
			else
				$ov[$n][5] = preg_split( '/\s+/', $refs );
			$n++;
		}
		return( $ov );
	}

	function article_list( $lowmark, $highmark ) {
		$new_art = $lowmark;
		$artlist = array();
		if( $this->cache_dir ) {
			$gdir = $this->cache_dir . '/' . $this->curr_server . '/' . str_replace( '.', '/', $this->curr_group );
			if( !mkdirs($gdir) && $this->nnrp_debug_level )
				echo "ERROR: Can not create directory '$gdir'<br />\n";
			$cache_file = $gdir . '/artnum.idx';
			$fp = @fopen( $cache_file, 'rb');
			if( $fp ) {
				$cache_max = -1;
				$artlist = @unserialize( fread( $fp, filesize($cache_file)) );
				fclose($fp);
				if( $artlist ) {
#					echo "<!-- Cache size: " . count($artlist) . " -->\n";
					foreach( $artlist as $idx => $artnum ) {
						if( $artnum < $lowmark || $artnum > $highmark )
							unset($artlist[$idx]);
						else {
							if( $artnum > $cache_max )
								$cache_max = $artnum;
						}
					}
				}
				if( $cache_max > 0 )
					$new_art = $cache_max + 1;
			}
		}
		if( $this->thread_enable ) {
			$file_thread  = $gdir . '/thread.db';
			// require PHP 4.3+ (for 'd' mode flag)
			if( file_exists( $file_thread ) )
				$db_thread = dba_open( $file_thread, 'wd', $this->db_handler );
			else
				$db_thread = dba_open( $file_thread, 'nd', $this->db_handler );
			if( !$db_thread )
				$this->thread_enable = false;
		}
		$field_subject = 1;
		if( $new_art <= $highmark ) {
			if( $new_art == $highmark )
				$this->send_command( "XOVER $new_art" );
			else
				$this->send_command( "XOVER $new_art-$highmark" );
			list( $code, $msg ) = $this->get_status();
			echo "\n<!-- XOVER $new_art-$highmark   STATUS: $code -->\n";
			if( $code[0] != '2' )
				return($artlist);
			while( $buf = fgets( $this->nhd, 4096 ) ) {
#				echo "$buf<br />";
				$buf = chop( $buf );
				if( $buf == '.' )
					break;
				$artinfo = split( "\t", $buf );
				$artnum = intval($artinfo[0]);
				if( $this->thread_enable ) {
					$subject = preg_replace( '/^((RE|FW):\s*)+/i', '', trim($this->decode_subject($artinfo[$field_subject])));
					if( $subject == '' ) $subject = ' ';
					$thread_data = @dba_fetch( $subject, $db_thread );
					if( $thread_data === false ) {
						dba_insert( $subject, $artnum, $db_thread );
					}
					else {
						$thread_list = explode( '+', $thread_data );
#						print "$thread_data<br />\n";
#						print_r($thread_list);
						if( !in_array( $artnum, $thread_list ) )
							$thread_list[] = $artnum;
						$final_list = array();
						foreach( $thread_list as $an ) {
							if( $an >= $lowmark && $an <= $highmark )
								$final_list[] = $an;
						}
						dba_replace( $subject, implode('+',$final_list), $db_thread );
					}
				}
				$artlist[] = $artnum;
			}
		}
		if( $this->cache_dir ) {
			$artlist = array_values( $artlist );
#			$artlist = sort( $artlist );
			$fp = @fopen( $cache_file, 'w' );
			if( $fp ) {
				if( flock( $fp, LOCK_EX|LOCK_NB ) ) {
					@fputs( $fp, serialize( $artlist ) );
					@flock( $fp, LOCK_UN );
				}
				@fclose($fp);
			}
		}
		if( $this->thread_enable ) {
			if( $db_thread )
				@dba_close($db_thread);
		}
		return( $artlist );
	}

	function stat( $artnum ) {
		$this->send_command( "STAT $artnum" );
		list( $code, $msg ) = $this->get_status();
		return( $code[0] == '2' );
	}

	function next( $artnum ) {
		$this->send_command( "STAT $artnum" );
		list( $code, $msg ) = $this->get_status();
		if( $code[0] != '2' )
			return(-1);
		$this->send_command( 'NEXT' );
		list( $code, $msg ) = $this->get_status();
		if( $code[0] != '2' )
			return(-1);
		$ret = split( ' ', $msg );
		return( $ret[0] );
	}

	function prev( $artnum ) {
		$this->send_command( "STAT $artnum" );
		list( $code, $msg ) = $this->get_status();
		if( $code[0] != '2' )
			return(-1);
		$this->send_command( 'LAST' );
		list( $code, $msg ) = $this->get_status();
		if( $code[0] != '2' )
			return(-1);
		$ret = split( ' ', $msg );
		return( $ret[0] );
	}

	function show( $artnum, $artinfo, $mode, $prepend = '', $postpend = '', $trans_func = null, $download_url = '' ) {

		$this->send_command( "ARTICLE $artnum" );

		list( $code, $msg ) = $this->get_status();
		if( $code[0] != '2' )
			return(null);
					
		while( $buf = fgets( $this->nhd, 4096 ) ) {
			$buf = chop($buf);
			if( $buf == '.' || $buf == '' )
				break;
			$buf = htmlspecialchars( $buf, ENT_NOQUOTES );
			if( $hide_email && !strstr( $buf, $artinfo['msgid'] ) )
				$buf = preg_replace( '/(\A|\s|[:;*+&"<{\/\(\[\'])([\w-_.]+)@([\w-_.]+)/e', '"$1".hide_mail("$2@$3")' , $buf );
			# convert charset if required
			if( $trans_func )
				$buf = $trans_func( $buf );
			$header[] = $prepend . $buf . $postpend;
		}
		$header[] = $prepend . $postpend;

		$uu = array();
		$uuencode_skip = false;
		$i = 0;
		while( $buf = fgets( $this->nhd, 4096 ) ) {
			$buf = chop($buf);
			if( $buf == '.' )
				break;
			if( $uuencode_skip ) {
				if( strtolower($buf) == 'end' )
					$uuencode_skip = false;
			}
			elseif( ereg( '^\.', $buf ) )
				$body[$i++] = substr( $buf, 1 );
			elseif( preg_match( '/^begin\s+(\d+)\s+(.+)\s*$/i', $buf, $match ) ) {
				$uuencode_skip = true;
				$body[$i] = $match[2];
				$uu[] = $i++;
			}
			else
				$body[$i++] = $buf;
		}
		if( $artinfo['encoding'] == 'base64' )
			$body = preg_split( '/[\r\n]+/', base64_decode( implode( '', $body ) ) );

		return (array("header" => $header, "body" => $body));
	}

	function get_attachment( $artnum, $type, $filename ) {

		if( $this->cache_dir ) {
			$gdir = $this->cache_dir . '/' . $this->curr_server . '/' . str_replace( '.', '/', $this->curr_group );
			if( !mkdirs($gdir) && $this->nnrp_debug_level )
				echo "ERROR: Can not create directory '$gdir'<br />\n";
			$cache_file = $gdir . "/attach-$artnum-$filename";
			if( file_exists( $cache_file ) && ( $fsize=filesize($cache_file) ) > 0 ) {
				return( array( $cache_file, $fsize ) );
			}
			$fp = @fopen( $cache_file, 'wb');
		}

		$this->send_command( "BODY $artnum" );
		list( $code, $msg ) = $this->get_status();
		if( $code[0] != '2' )
			return(null);
#		$filename = trim($filename);
		$filename = preg_quote($filename);
		$binary = '';
		$fsize = 0;
		if( $type == 'uuencode' ) {
			$pass = 0;
			while( $buf = fgets( $this->nhd, 4096 ) ) {
				$tbuf = trim( $buf );
				if( $tbuf == '.' )
					break;
				if( $buf[0] == '.' )
					$buf = substr( $buf, 1 ); # by ogekuri
				if( $pass == 2 ) {      # skip the rest
					continue;
				}
				elseif( $pass == 1 ) {
					if( $tbuf == 'end' ) {
						$pass = 2;
					}
					else {
						$i = (ord($buf[0]) - 32) & 077;
						if( $i <= 0 )
							continue;
						for( $p = 1 ; $i > 0 ; $p += 4, $i -= 3 ) {
							if( $i >= 3 ) {
								$byte[0] = (ord($buf[$p]) - 32) & 077;
								$byte[1] = (ord($buf[$p+1]) - 32) & 077;
								$byte[2] = (ord($buf[$p+2]) - 32) & 077;
								$byte[3] = (ord($buf[$p+3]) - 32) & 077;

								$tmp = chr(($byte[0] << 2 | $byte[1] >> 4) & 0xff);
								$tmp.= chr(($byte[1] << 4 | $byte[2] >> 2) & 0xff);
								$tmp.= chr(($byte[2] << 6 | $byte[3] ) & 0xff);
							}
							else {
								$byte[0] = (ord($buf[$p]) - 32) & 077;
								$byte[1] = (ord($buf[$p+1]) - 32) & 077;
								$tmp = chr(($byte[0] << 2 | $byte[1] >> 4) & 0xff);
								if( $i > 1 ) {
									$byte[2] = (ord($buf[$p+2]) - 32) & 077;
									$tmp .= chr(($byte[1] << 4 | $byte[2] >> 2) & 0xff);
								}
							}
							$fsize += strlen($tmp);
							if( $this->cache_dir )
								@fwrite( $fp, $tmp );
							else
								$binary.= $tmp;
						}
					}
				}
				elseif( preg_match( '/^begin\s(\d+)\s+'.$filename.'$/i', $tbuf, $match ) ) {
					$pass = 1;
				}
			}
		}
		if( $this->cache_dir ) {
			fclose($fp);
			return( array( $cache_file, $fsize ) );
		}
		else
			return( array( $binary, $fsize ) );
	}


	function head( $artnum, $def_charset = 'utf-8', $time_format = '%Y/%m/%d %H:%M:%S' ) {
		$this->send_command( "HEAD $artnum" );
		list( $code, $msg ) = $this->get_status();
		if( $code[0] != '2' )
			return(null);
		$n = 0 ;
		$nowline = fgets( $this->nhd, 4096 );
		$nowline = chop($nowline);
		while( $nowline && $nowline != '.' ) {
			$nextline = fgets( $this->nhd, 4096 );
			$nextline = chop($nextline);
			while( preg_match( '/^\s/', $nextline ) ) {
				$nowline .= ' ' . trim($nextline);
				$nextline = fgets( $this->nhd, 4096 );
				$nextline = chop($nextline);
			}
			preg_match( '/^([^:]+): (.+)$/', $nowline, $match );
			$headers[strtolower($match[1])] = $match[2];
			$nowline = $nextline;
		}
		return( $this->get_mime_info( $headers, $def_charset, $time_format ) );
	}

	function post_init( $name, $email, $subject, $newsgroups, $organization, $ref = null, $real_email, $art_charset ) {
		global $php_news_agent;
		$client = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '' ;
		if( $client == '' ) {
			$proxy = '';
			$client = $_SERVER['REMOTE_ADDR'];
		}
		else
			$proxy = $_SERVER['REMOTE_ADDR'];
		$this->send_command( 'POST' );
		list( $code, $msg ) = $this->get_status();
		fwrite( $this->nhd, "From: $name <$email>\r\n" );
		fwrite( $this->nhd, "Newsgroups: $newsgroups\r\n" );
		fwrite( $this->nhd, "Subject: $subject\r\n" );
		fwrite( $this->nhd, "Organization: $organization\r\n" );
		fwrite( $this->nhd, "X-User-Real-E-Mail: $real_email\r\n" );
		fwrite( $this->nhd, "User-Agent: $php_news_agent\r\n" );

		fwrite( $this->nhd, "Mime-Version: 1.0\r\n" );
		fwrite( $this->nhd, sprintf("Content-Type: text/plain; charset=\"%s\"\r\n", $art_charset ) );
		fwrite( $this->nhd, "Content-Transfer-Encoding: 8bit\r\n" );

#		fwrite( $this->nhd, "X-User-Agent-URL: http://pnews.sourceforge.net/\r\n" );
		fwrite( $this->nhd, "X-HTTP-Posting-Host: $client\r\n" );
		if( $proxy != '' )
			fwrite( $this->nhd, "X-HTTP-Proxy-Server: $proxy\r\n" );
		if( $ref )
			fwrite( $this->nhd, "References: $ref\r\n" );
		fwrite( $this->nhd, "\r\n" );
	}

	function post_writeln( $buf ) {
		if( $this->nnrp_debug_level == 2 )
			echo "C: [$buf]<br />\n";
		if( $buf[0] == '.' )
			fwrite( $this->nhd, ".$buf\r\n" );
		else
			fwrite( $this->nhd, $buf . "\r\n" );
	}

	function post_write( $buf ) {
		$tok = strtok( $buf, "\n" );
		while ($tok) {
			$tok = rtrim($tok);
			$this->post_writeln( $tok );
			$tok = strtok ("\n");
		}
	}

	function post_end() {
		fwrite( $this->nhd, ".\r\n");
		list( $code, $msg ) = $this->get_status();
	}

	function cancel( $name, $email, $msgid, $newsgroup, $subject = null ) {
		$this->send_command( 'POST' );
		list( $code, $msg ) = $this->get_status();
		fwrite( $this->nhd, "From: $name <$email>\r\n" );
		fwrite( $this->nhd, "Newsgroups: $newsgroup\r\n" );
		fwrite( $this->nhd, "Subject: cmsg cancel $msgid\r\n" );
		fwrite( $this->nhd, "Control: cancel $msgid\r\n" );
		fwrite( $this->nhd, "\r\n" );
		if( $subject )
			fwrite( $this->nhd, "'$subject' deleted from $newsgroups by $email\r\n");
			fwrite( $this->nhd, ".\r\n");
		list( $code, $msg ) = $this->get_status();
	}

	function close() {
		if( $this->nhd )
			fclose($this->nhd);
	}

	function get_thread( $group, $subject ) {

		list( $code, $count, $lowmark, $highmark ) = $this->group( $group );
#		echo "$count, $lowmark, $highmark";
		$thlist = array();
		$thread_db = $this->cache_dir . '/' . $this->curr_server . '/' . str_replace( '.', '/', $group ) . '/thread.db';
		if( !file_exists( $thread_db ) ) {
			return($thlist);
		}
		$db = dba_open( $thread_db, 'r', $this->db_handler );
		if( $db ) {
			$subject = preg_replace( '/^((RE|FW):\s*)+/i', '', trim($this->decode_subject($subject)));
			if( ( $thread = dba_fetch( $subject, $db ) ) !== FALSE ) {
#				echo "$thread ($lowmark-$highmark)";
				$thlist_x = explode( '+', $thread );
				foreach( $thlist_x as $t ) {
					if( $t >= $lowmark && $t <= $highmark )
						$thlist[] = $t;
				}
				sort($thlist);
			}
			dba_close($db);
		}
		return($thlist);
	}

	function send_command( $cmd ) {
		$this->nnrp_last_command = $cmd;
		@fwrite( $this->nhd, "$cmd\r\n");
		if( strstr( $cmd, 'AUTHINFO' ) )
			$cmd = "AUTHINFO  <user>  <passwd>";
		if( $this->nnrp_debug_level == 2 )
			echo "C: [$cmd]<br />\n";
		elseif( $this->nnrp_debug_level == 1 )
			echo "<!-- C: [$cmd] -->\n";
	}

	function get_status() {
		$responds = @fgets( $this->nhd, 1024 );
		$responds = chop($responds);
		if( $this->nnrp_debug_level == 2 )
			echo "S: [$responds]<br />\n";
		elseif( $this->nnrp_debug_level == 1 )
			echo "<!-- S: [$responds] -->\n";
		$this->nnrp_last_result = $responds;
		if( preg_match( '/^(\d+)\s*(.+)$/', $responds, $match ) )
			return( array($match[1], $match[2]) );
		else
			return( array( '400','' ) );
	}

	function decode_subject( $instr ) {
		$enstr = $instr;
		while( preg_match( '/^([^?]+)?=\?[^?]+\?(B|Q)\?([^?]+)=?=?\?=(.+)?$/i', $enstr, $match ) ) {
			if( $match[2] == 'b' || $match[2] == 'B' )
				$enstr = $match[1] . base64_decode( $match[3] ) . (isset($match[4])?$match[4]:'');
			else
				$enstr = $match[1] . quoted_printable_decode( $match[3] );
		}
		return( $enstr );
	}

	function strip_quotes( $str ) {
		if( preg_match( '/^"(.+)"$/', $str, $quotes ) )
			return( $quotes[1] );
		else
			return( $str );
	}

	function get_mime_info( $headers, $def_charset, $time_format ) {
		$artinfo['charset'] = $def_charset;
		$artinfo['type'] = $artinfo['subtype'] = '';
		if( isset($headers['content-type']) ) {
			$ctype = preg_split( '/[;\s]+/', strtolower($headers['content-type']) );
			if( is_array( $ctype ) ) {
				list( $type, $subtype ) = split( '/', $ctype[0]);
				$artinfo['type'] = $type;
				$artinfo['subtype'] = $subtype;
				array_shift( $ctype );
				foreach( $ctype as $c_param ) {
					if( preg_match( '/^(.+)\s*=\s*(.+)$/', $c_param, $match ) ) {
						$match[1] = strtolower($match[1]);
						$match[2] = $this->strip_quotes( $match[2] );
						if( $match[1] == 'charset' )
							$artinfo['charset'] = strtolower($match[2]);
						elseif( $match[1] == 'boundary' )
							$artinfo['boundary'] = $match[2];
					}
				}
			}
		}

		if( isset($headers['content-transfer-encoding']) )
			$artinfo['encoding'] = $headers['content-transfer-encoding'];
		else
			$artinfo['encoding'] = '7bit';

		if( isset($headers['date']) )
			$artinfo['date'] = strftime( $time_format, strtotime($headers['date']) );
		else
			$artinfo['date'] = '';

		$artinfo['msgid'] = $headers['message-id'];
		$artinfo['name'] = $artinfo['mail'] = '';
		if( isset($headers['from']) ) {
			if( preg_match( '/^<([^@]+)@([\w-_.]+)>$/', $headers['from'], $from ) ) {
				$artinfo['name'] = $from[1];
				$artinfo['mail'] = $from[0];
			}
			elseif( preg_match( '/^([^@]+)@([\w-_.]+)$/', $headers['from'], $from ) ) {
				$artinfo['name'] = $from[1];
				$artinfo['mail'] = $from[0];
			}
			elseif( preg_match( '/^(.+)? <(.+)>$/', $headers['from'], $from ) ) {
				$from[1] = $this->strip_quotes( $from[1] );
				$artinfo['name'] = $this->decode_subject($from[1]);
				$artinfo['mail'] = $from[2];
			}
			elseif( preg_match( '/^(([^@]+)@([\w-_.]+))\s*\((.+)?\)$/', $headers['from'], $from ) ) {
				$from[4] = isset( $from[4] ) ? $this->strip_quotes( $from[4] ) : '' ;
				$artinfo['name'] = $this->decode_subject($from[4]);
				$artinfo['mail'] = $from[1];
			}
		}

		$artinfo['org'] = isset($headers['organization']) ? $this->decode_subject($headers['organization']) : '';

		$artinfo['subject'] = $this->decode_subject($headers['subject']);

		if( isset($headers['references']) )
			$artinfo['ref'] = preg_split( '/\s+/', trim($headers['references']) );
		else
			$artinfo['ref'] = array();

		return($artinfo);
	}
}

// Copyright (C) 2001-2007 - All rights reserved
// Shen Cheng-Da (cdsheen at users.sourceforge.net)

?>
