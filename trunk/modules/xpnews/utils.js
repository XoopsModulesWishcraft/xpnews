/* PHP News Reader - Copyright(C) 2001-2007         */
/* Shen Cheng-Da (cdsheen at users.sourceforge.net) */

/* Javascript Functions, included by html.inc.php   */

function reload() {
	history.go(0);
}
function nothing() {
	return(true);
}
function goto_url( url ) {
	window.location = url;
}
function close_window() {
	window.close();
}
function mailto( email ) {
	var openwin = window.open( "mailto:" . email );
}
function read_article( base, server, group, artnum ) {
	var winstyle = "status=no,menubar=no,scrollbars=yes,height=350,width=550";
	var openwin = window.open( base + "read.php?server=" + server + "&group=" + group + "&artnum=" + artnum, "read" + artnum , winstyle );
	openwin.focus();
}
function post_article( base, server, group ) {
	var winstyle = "status=no,menubar=no,scrollbars=yes,height=400,width=600";
	var openwin = window.open( base + "post.php?server=" + server + "&group=" + group , "post", winstyle );
	openwin.focus();
}
function delete_article( base, server, group, artnum ) {
	var winstyle = "status=no,menubar=no,scrollbars=yes,height=400,width=600";
	var openwin = window.open( base + "delete.php?server=" + server + "&group=" + group + "&artnum=" + artnum, "delete" + artnum , winstyle );
	openwin.focus();
}
function reply_article( base, server, group, artnum, quote ) {
	var winstyle = "status=no,menubar=no,scrollbars=yes,height=400,width=600";
	var quote_text = "&quote=" + quote ;
	var openwin = window.open( base + "reply.php?server=" + server + "&group=" + group + "&artnum=" + artnum + quote_text, "reply" + artnum , winstyle );
	openwin.focus();
}
function xpost_article( base, server, group, artnum ) {
	var winstyle = "status=no,menubar=no,scrollbars=yes,height=370,width=600";
	var openwin = window.open( base + "xpost.php?server=" + server + "&group=" + group + "&artnum=" + artnum, "xpost" + artnum , winstyle );
	openwin.focus();
}
function forward_article( base, server, group, artnum ) {
	var winstyle = "status=no,menubar=no,scrollbars=yes,height=370,width=600";
	var openwin = window.open( base + "forward.php?server=" + server + "&group=" + group + "&artnum=" + artnum, "forward" + artnum , winstyle );
	openwin.focus();
}
function setCookie(sName, sValue, sPath) {
	document.cookie = sName + "=" + escape(sValue) + "; path=" + sPath;
}
function change_language( lang, path, from ) {
	window.location = "chg-lang.php?language=" + lang + "&path=" + path + "&from=" + escape(from);
}
function change_language_base( base, lang, path, from ) {
	window.location = base + "chg-lang.php?language=" + lang + "&path=" + path + "&from=" + escape(from);
}
function myfavor( url, title ) {

	if( window.sidebar && window.sidebar.addPanel ) {
		// Gecko (Netscape 6)
		window.sidebar.addPanel( title, url, '' );
	}
	else if( window.external ) {
		// IE
		window.external.AddFavorite( url, title );
	}
	else if( document.layers ) {
		// NS4
		window.alert( 'Please click OK then press Ctrl+D to create a bookmark' );
	}
	else {
		// Other browsers
		window.alert( 'Please use your browsers\' bookmarking facility to create a bookmark' );
	}
/*
	if ( navigator.appName != "Netscape" ) {
		window.external.AddFavorite( url, title );
	}
*/
}
