<?php
/*
 * Project:	YARRSTE: Yet Another Really Rather Simple Templating Engine
 * File:	index.php, main site file
 * Version:	0.0.39
 * License:	GPL
 *
 * Copyright (c) 2009 by KenjiE20 <longbow@longbowslair.co.uk>
 *
 */
function timer_start()
{
	global $YARRSTE_time_start;
	$YARRSTE_time_start = microtime(TRUE);
}

timer_start();
# ^^^ Timing stuff ^^^

// Allow included files to run
define('IN_YARRSTE', true);
// Bring in the globals
require ('config.php');
require ('include/cache.php');
// Find full path to self
$YARRSTE_self = dirname(__FILE__);

// Debugging output
if (isset ($_GET['debug'])) {
	$YARRSTE_debug = $_GET['debug'];
} elseif ($YARRSTE_debug == 'True' || $YARRSTE_debug == 'true' || $YARRSTE_debug == '1') {
	$YARRSTE_debug = '1';
} else {
	$YARRSTE_debug = '0';
}

// <!-- Get variables and check they work -->

// Get page for content if given
// Make file value lower-case to avoid case-sensitivity
// And add the extention
if (isset ($_GET['page'])) {
	$YARRSTE_page = $_GET['page'];
	$YARRSTE_page = strtolower($YARRSTE_page);
	$YARRSTE_page = $YARRSTE_self."/".$YARRSTE_textpath."/".$YARRSTE_page.".php";
} else {
	$YARRSTE_page = $YARRSTE_self.'/'.$YARRSTE_textpath.'/'.$YARRSTE_defpage.'.php';  // Default if no name given
}

// Check existance of file
if (!file_exists($YARRSTE_page)) {
	echo "Cannot find a valid content file."; // File given doesn't exist
	exit;
}

// Check if content wants a specific template or caching
// Get a file into an array.
$YARRSTE_textsearch = file($YARRSTE_page);

$tplset = $cacheset = 0;
// Search for pre-parse vars
$check = '/\$tpl|\$Y_cache/';
$searchlines = preg_grep ($check, $YARRSTE_textsearch);
foreach ($searchlines as $line_num => $line) {
	if ($tplset && $cacheset) {
		break;
	}
	if (!(preg_match('/^\/\//', $line)) && !(preg_match('/^#/', $line))) {
		if (eregi('\$tpl', $line)) {
			// Figure out what template is being asked for
			$pieces = explode("=", $line);
			$YARRSTE_template = trim($pieces[1], " '\";\n\r");
			if ($YARRSTE_template == '') {unset ($YARRSTE_template);}
			$tplset = 1;
		} elseif (eregi('\$Y_cache',$line)) {
			// Figure out cache bool
			$pieces = explode ("=", $line);
			$YARRSTE_caching = trim($pieces[1], " '\";\n\r");
			$cacheset = 1;
		}
	}
}
unset ($YARRSTE_textsearch,$searchlines,$check,$tplset,$cacheset);

// Caching
if ($YARRSTE_caching == 'True' || $YARRSTE_caching == 'true' || $YARRSTE_caching == '1') {
	$cache = YARRSTE_cache_check($YARRSTE_page, false);

	if ($cache == 'HIT') {
		$output = YARRSTE_cache_open($YARRSTE_page);
		if ($output) {
			echo $output;
			exit();
		}
	}
}

// Get and override template if given
// Make file value lower-case to avoid case-sensitivity
// And add the extention
if (isset ($_GET['template'])) {
	$YARRSTE_template = $_GET['template'];
	$YARRSTE_template = strtolower($YARRSTE_template);
	$YARRSTE_template = $YARRSTE_self."/".$YARRSTE_tplpath."/".$YARRSTE_template.".tpl";
// Use template set by content file
} elseif (isset ($YARRSTE_template)) {
	$YARRSTE_template = $YARRSTE_self.'/'.$YARRSTE_tplpath.'/'.$YARRSTE_template.'.tpl';
// Default if nothing set
} else {
	$YARRSTE_template = $YARRSTE_self.'/'.$YARRSTE_tplpath.'/'.$YARRSTE_deftpl.'.tpl';
}

// Check existance of template
if (!file_exists($YARRSTE_template)) {
	echo "Cannot find a valid template."; // File given doesn't exist
	exit;
}

// <---- End of variables and checking ---->

// Bring in the parser
include ('include/parse.php');

$output = '';
$output = YARRSTE_parse($YARRSTE_template, $YARRSTE_page);

if ($YARRSTE_caching == 'True' || $YARRSTE_caching == 'true' || $YARRSTE_caching == '1' && $cache != 'IGNORE') {
	YARRSTE_cache_write ($YARRSTE_page, $output);
}
echo $output;

?>
