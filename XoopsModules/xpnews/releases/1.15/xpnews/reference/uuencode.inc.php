<?phpphp

/* $Id: uuencode.inc.php 308 2003-10-29 17:21:19Z cdsheen $ */
/*
 * uuedcoder by Scott Price (prices@dflytech.com) Copyright (C) 2000, 2001
 * Dragonfly Technologies, Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 * 
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc., 59
 * Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/*
 * Taken from http://foldoc.doc.ic.ac.uk/foldoc/foldoc.cgi?uuencode
 * 
 * uuencode
 * 
 * Unix program for encoding binary data as ASCII. Uuencode was originally used
 * with uucp to transfer binary files over serial lines which did not
 * preserve the top bit of characters but is now used for sending binary
 * files by e-mail and posting to Usenet newsgroups etc. The program uudecode
 * reverses the effect of uuencode, recreating the original binary file
 * exactly.
 * 
 * Uuencoded data starts with a line of the form
 * 
 * begin <mode> <file>
 * 
 * 
 * where <mode> is the files read/write/execute permissions as three octal
 * digits and <file> is the name to be used when recreating the binary data.
 * 
 * Uuencode repeatedly takes in a group of three bytes, adding trailing zeros if
 * there are less than three bytes left. These 24 bits are split into four
 * groups of six which are treated as numbers between 0 and 63. Decimal 32 is
 * added to each number and they are ouput as ASCII characters which will lie
 * in the range 32 (space) to 32+63 = 95 (underscore). Each group of sixty
 * ouptut characters (corresponding to 45 input bytes) is output as a
 * separate line preceded by an 'M' (ASCII code 77 = 32+45). At the end of
 * the input, if there are N output characters left after the last group of
 * sixty and N>0 then they will be preceded by the character whose code is
 * 32+N. Finally, a line containing just a single space is output, followed
 * by one containing just "end".
 * 
 * Sometimes each data line has an extra dummy character added to avoid problems
 * which mailers that strip trailing spaces. These characters are ignored by
 * uudecode.
 * 
 * Despite using this limited range of characters, there are still some problems
 * encountered when uuencoded data passes through certain old computers. The
 * worst offenders are computers using non-ASCII character sets such as
 * EBCDIC.
 * 
 */

define("LINELEN", 45);

function check_for_uuencode($stuff, &$type, &$filename, &$mode) {

	$uu = explode("\n", $stuff);
	$rows = count($uu);
	for ($row = 0; $row < $rows; $row++) {
		$work = explode(" ", trim($uu[$row]));
		if ((trim(strtolower($work[0])) == "begin") && (count($work) == 3)) {
			$filename = $work[2];
			$mode = $work[1];
			$type = "text/uuencode";
			break;
		}
	}

	// Okay, now decode the file until we get to a 'end'.
	for ($row; $row < $rows; $row++) {
		if (strtolower(trim($uu[$row])) == "end") {
			unset($uu[$row]);
			break;
		}
		unset($uu[$row]);
	}
	$stuff = implode("\n", $uu);

	return ($stuff);
}


function uudecode($stuff, &$filename, &$mode, $direct = FALSE) {

	$uu = explode("\n", $stuff);
	$rows = count($uu);
	$binary = "";
	// Find the beginning of the uuencoded section
	for ($row = 0; $row < $rows; $row++) {
		$uu[$row] = trim($uu[$row]);
		$work = explode(" ", $uu[$row]);
		if (trim(strtolower($work[0])) == "begin") {
			$filename = trim($work[2]);
			$mode = trim($work[1]);
			break;
		}
	}
	$length = 0;
	// Okay, now decode the file until we get to a 'end'.
	for ($row++; $row < $rows; $row++) {
		$uu[$row] = ltrim($uu[$row]);
		if (strtolower(trim($uu[$row])) == "end")
			break;
		if ($uu[$row] != "") {
			$sets = ord($uu[$row]);
			$sets -= 32;
			$sets &= 63;
			$sets /= 3;
			for ($set = 0; $set < $sets; $set++) {
				for ($i = 0; $i < 4; $i++) {
					$byte[$i] = ord(substr($uu[$row], (($set * 4) + 1 + $i), 1));
					$byte[$i] = (($byte[$i] - 32) & 63);
				}

				$tbinary = chr(($byte[0] << 2 | $byte[1] >> 4) & 0xff);
				$tbinary.= chr(($byte[1] << 4 | $byte[2] >> 2) & 0xff);
				$tbinary.= chr(($byte[2] << 6 | $byte[3] >> 0) & 0xff);
				if ($direct == FALSE) {
					$binary.= $tbinary;
					$length += 3;
				} else {
					print $tbinary;
				}
			}
		}
	}
	return ($binary);
}


function uuencode($stuff, $length, $filename, $mode, $raw = FALSE) {
	print $length."\n";
	if (!$raw) {
		$output = "begin ".trim($mode)." ".trim($filename)." ";
	}
	for ($i = 0; $i < $length; $i += 3) {
		$byte[0] = (ord($stuff[$i]) >> 2) & 0x3F;
		$byte[1] = (((ord($stuff[$i]) << 4) & 0x60) | ((ord($stuff[$i]) >> 4) & 0x17)) & 0x3F;
		$byte[2] = (((ord($stuff[$i + 1]) << 2) & 0x74) | ((ord($stuff[$i + 2]) >> 6) & 0x03)) & 0x3F;
		$byte[3] = ((ord($stuff[$i + 2]) & 0x77) & 0x3F);

		for ($j = 0; $j < 4; $j++) {
			$text.= chr($byte[$j] + 32);
		}
	}
	for ($i = 0; $i < strlen($text); $i++) {
		if (($i % LINELEN) == 0) {
			if ((strlen($text) - $i) < LINELEN) {
				$output.= "  \n".chr((strlen($text) - $i) + 32);
			} else {
				$output.= "  \n".chr(LINELEN + 32);
			}
		}
		$output.= $text[$i];
	}
	$output.= "\n` ";

	if (!$raw) {
		$output.= "\nend\n\n";
	}
	return $output;
}

?>
