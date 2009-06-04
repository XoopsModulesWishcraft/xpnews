<?php

include('utils.inc.php');

$mimetype = $_GET['mimetype'];


	if( isset($mimetype) ) {
		header( 'Content-Type: ' . $mimetype );
		header( "Content-Disposition: attachment; filename=\"".$_GET['filename']."\"" );
	}
	else {
		header( 'Content-Type: application/octet-stream' );
		header( "Content-Disposition: attachment; filename=\"".$_GET['filename']."\"" );
	}

	if (strlen($part['header']['Content-Type']['name'])>0)
	$filename = $_GET['category'].'_'.$_GET['group'].$_GET['artnum'].'_'.$_GET['filename'];
else
	$filename = $_GET['category'].'_'.$_GET['group'].$_GET['artnum'].'_'.$_GET['filename'];

	if (file_exists($xoopsModuleConfig['decode_path'].'/'.$filename))
		readfile( $xoopsModuleConfig['decode_path'].'/'.$filename );

exit;

?>
