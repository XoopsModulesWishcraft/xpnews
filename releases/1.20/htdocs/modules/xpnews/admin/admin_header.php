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
include_once "../../../mainfile.php";
require_once( XOOPS_ROOT_PATH.'/include/cp_header.php' ) ;
include_once( XOOPS_ROOT_PATH."/include/functions.php" ) ;

if ( $xoopsUser ) {
	$xoopsModule = XoopsModule::getByDirname('xpnews');
	if ( !$xoopsUser->isAdmin($xoopsModule->mid()) ) {
		redirect_header(XOOPS_URL."/", 3, 'Not Permitted Access');
		exit();
		}
	} else {
		redirect_header(XOOPS_URL."/", 3, 'Not Permitted Access');
		exit();
	}

include_once( XOOPS_ROOT_PATH."/modules/xpnews/include/functions.php" ) ;

?>