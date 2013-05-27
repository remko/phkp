<?php

/*
 * PHKP: A PHP implementation for a HKP keyserver.
 * http://el-tramo.be/software/phkp
 *
 * See the README.md file for installation.
 *
*  Copyright (c) 2006 Remko Tronçon
*  Licensed under the GNU General Public License.
*  See the COPYING file for more information.
 */

// The command to start pgp. Currently only works with GnuPG
$PGP_COMMAND="gpg";

// A dir where the PHP script has write access
$PGP_HOME="../.phkp";

// This file will contain some GnuPG logging information
$PGP_LOG="log";

// The maximum size (in characters) of a submitted key. 
// Set to '0' to disable receiving of keys, and '-1' for no limit.
$MAX_KEYSIZE=102400;


// ----------------------------------------------------------------------------

function send_key($id,$key) {
	header("Content-Type: application/pgp-keys");
	header("Content-Disposition: attachment; filename=$id.asc");
	foreach ($key as $output_line) {
		$output .= $output_line . "\n";
	}
	print $output;
}

function send_index($index) {
	$nb_keys = 0;
	$keys = "";
	
	foreach ($index as $index_line) {
		if (preg_match("/pub\s+(\d+)(\S)\/([0-9A-Fa-f]+)\s+(\d\d\d\d)-(\d\d)-(\d\d)\s*(.*)/",$index_line,$matches)) {
			$keyid = $matches[3];
			if ($matches[2] == "D") {
				$algo = "17";
			}
			else if ($matches[2] == "g") {
				$algo = "1";
			}
			else if ($matches[2] == "R") {
				$algo = "16";
			}
			$keylen = $matches[1];
			$creation_date = strval(mktime(1,0,0,$matches[5],$matches[6],$matches[4]));
			if (preg_match("/expire.: (\d\d\d\d)-(\d\d)-(\d\d)/",$matches[7],$expmatches)) {
				$exp_date = strval(mktime(1,0,0,$expmatches[2],$expmatches[3],$expmatches[1]));
			}
			if (preg_match("/revoked/",$matches[7])) {
				$flags .= "r";
			}
			if (preg_match("/expired/",$matches[7])) {
				$flags .= "e";
			}
			if (preg_match("/disabled/",$matches[7])) {
				$flags .= "d";
			}
			$keys .= "pub:$keyid:$algo:$keylen:$creation_date:$exp_date:$flags\n";
			$nb_keys++;
		}
		else if (preg_match("/uid\s+(.*)/",$index_line,$matches)) {
			$uid = $matches[1];
			$keys .= "uid:$uid:::\n";
		}
		else if ($index_line && !preg_match("/sub/",$index_line)) {
			// Panic
			break;
		}
	}
	header("Content-Type: text/plain");
	print "info:1:$nb_keys\n";
	print "$keys";
}

function pgp_exec($command,&$output) {
	global $PGP_COMMAND, $PGP_HOME, $PGP_LOG;
	//exec("rm -f $PGP_HOME/*");

	$PGP_OPTIONS = 
		"--homedir $PGP_HOME " .
		"--no-random-seed-file --no-options --no-default-keyring " .
		"--preserve-permissions " .
		"--armor";

	$command = "$PGP_COMMAND $PGP_OPTIONS $command";
	//print "$command\n";
	exec("$command 2>>$PGP_HOME/$PGP_LOG",$output,$result);
	return $result;
}


if (ereg("/pks\/lookup\?(.*)",$_SERVER['REQUEST_URI'],$regs)) {
	parse_str($regs[1],$vars);
	if ($vars['op'] == 'get') {
		$id = $vars['search'];
		$pgp_result = pgp_exec("--export $id", $output);
		if (!$pgp_result && count($output) > 0) {
			send_key($id,$output);
		}
		else {
			header("HTTP/1.0 404 Not Found");
		}
	}
	else if ($vars['op'] == 'index') {
		$search = $vars['search'];
		$pgp_result = pgp_exec("--list-public-keys --list-keys $search", $output);
		if (!$pgp_result && count($output) > 0) {
			send_index($output);
		}
		else {
			header("HTTP/1.0 404 Not Found");
		}
	}
	else {
		header("HTTP/1.0 501 Not Implemented");
	}
}
else if (ereg("/pks\/add",$_SERVER['REQUEST_URI'])) {
	if ($MAX_KEYSIZE == -1 || strlen($_POST['keytext']) <= $MAX_KEYSIZE) {
		$tmp = fopen("$PGP_HOME/tmp","w");
		if ($tmp) {
			fwrite($tmp,$_POST['keytext']);
			fclose($tmp);
			$pgp_result = pgp_exec("--import < $PGP_HOME/tmp", $output);
			if ($pgp_result) {
				header("HTTP/1.0 500 Internal Server Error");
			}
			exec("rm -f $PGP_HOME/tmp");
		}
		else {
			header("HTTP/1.0 500 Internal Server Error");
		}
	}
	else {
		header("HTTP/1.0 403 Forbidden");
	}
}
else {
	header("HTTP/1.0 404 Not Found");
}

?>
