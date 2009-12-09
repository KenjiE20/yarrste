<?php
/*
 * Project:	YARRSTE: Yet Another Really Rather Simple Templating Engine
 * File:	parse.php, template parsing file
 * Version:	0.0.39
 * License:	GPL
 *
 * Copyright (c) 2009 by KenjiE20 <longbow@longbowslair.co.uk>
 *
 */

if ( !defined('IN_YARRSTE') )
{
	die("Hacking attempt");
	exit;
}

// Bring in the globals
global $YARRSTE_tplpath;
global $YARRSTE_textpath;
global $YARRSTE_sitename;
global $YARRSTE_titlesep;
global $YARRSTE_baseurl;
global $YARRSTE_debug;
global $YARRSTE_time_start;
// YARRSTE version
$YARRSTE_version = '0.0.39';
$GLOBALS['credinline'] = '';
$GLOBALS['debuginline'] = '';
$GLOBALS['geninline'] = '';
$YARRSTE_genline = '<meta name="generator" content="YARRSTE '.$YARRSTE_version.'" />';

function timer_time()
{
    global $YARRSTE_time_start;
    return microtime(TRUE) - $YARRSTE_time_start;
}

// Conditional replace for certain tags
// Special replace for title
function settitle ($line, $title) {
	global $YARRSTE_sitename;
	global $YARRSTE_titlesep;
	if ($title == '') {
		$replace = $YARRSTE_sitename;
	} else {
		$replace = $YARRSTE_sitename.' '.$YARRSTE_titlesep.' '.$title;
	}
	$line = str_replace("{TITLE}", $replace, $line);
	return $line;
	
}
// Special replace for sitename
function setsitename ($line) {
	global $YARRSTE_sitename;
	$line = str_replace("{SITENAME}", $YARRSTE_sitename, $line);
	return $line;

}
// Special replace for siteurl
function setsiteurl ($line) {
	global $YARRSTE_baseurl;
	$line = str_replace("{SITEURL}", $YARRSTE_baseurl, $line);
	return $line;

}
// Site generator
function setgenerator ($line) {
	global $YARRSTE_genline;
	$line = $YARRSTE_genline."\n".$line;
	$GLOBALS['geninline'] = '1';
	return $line;
}
// Site credits
function setcredits ($line, $inline) {
	global $YARRSTE_sitename;
	global $YARRSTE_version;
	global $YARRSTE_baseurl;
	if ($inline) {
		$replace = 'Copyright &copy; '.date('Y').' <a href="'.$YARRSTE_baseurl.'">'.$YARRSTE_sitename.'</a> -- Powered by <a href="http://yarrste.longbowslair.co.uk" target="_blank">YARRSTE</a> version: '.$YARRSTE_version;
		$line = str_replace("{YARRSTE_CREDIT}", $replace, $line);
		$GLOBALS['credinline'] = '1';
	} else {
		$replace = "<p style=\"font-size:small;color:#888888;text-align:center;\">Powered by <a style=\"color:#888888;\" href=\"http://yarrste.longbowslair.co.uk\" target=\"_blank\">YARRSTE</a> version: ".$YARRSTE_version."</p>\n</body>";
		$line = str_replace("</body>", $replace, $line);
	}
	return $line;
}

// Output some useful stuff for debugging
function outputdebug ($line, $inline) {
	global $YARRSTE_debug;
	global $YARRSTE_version;
	if ($YARRSTE_debug) {
		$YARRSTE_gentime = number_format(timer_time(), 2);
		$info = "Debug Info:<br />\nPage: http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."<br />\nRequest via: ".$_SERVER['SERVER_PROTOCOL']." | Referer: ".$_SERVER['HTTP_REFERER']."<br />\nUser Agent: ".$_SERVER['HTTP_USER_AGENT']."<br />\nYARRSTE: ".$YARRSTE_version." | Generation Time: ".$YARRSTE_gentime." seconds.";
		if ($inline) {
			$line = str_replace("{DEBUG}", $info, $line);
			$GLOBALS['debuginline'] = '1';
		} else {
			$info = $info."\n</body>";
			$line = str_replace("</body>", $info, $line);
		}
	} else {
		$line = str_replace("{DEBUG}", '', $line);
	}
	return $line;
}

// Main parser
function YARRSTE_parse ($template, $page) {
	global $YARRSTE_debug;
	$return = '';
	// Bring in the content
	include ($page);
	// Get a file into an array.
	$lines = file($template);
	// If have things to parse
	if (count($toparse)) {
//DEBUG		print_r ($lines);
		// Initial regexp build
		$check = '/';
		foreach ($toparse as $item) {
			$check .= '\{'.strtoupper($item).'\}|';
		}
		$check .= '\{TITLE\}|\{SITENAME\}|\{SITEURL\}|\{YARRSTE_CREDIT\}|\{DEBUG\}/';
		// Initial var search
		$parselines = preg_grep ($check, $lines);
		// So long as the var search has vars keep parsing
		while ($parselines) {
//DEBUG			print_r ($parselines);
			// Only need to parse those line found by the search
			foreach ($parselines as $linen => $line) {
				// Check for special replacers
				// Check for title
				if (eregi('\{title\}', $line)) { $line = settitle($line, $title); }
				// Check for sitename
				if (eregi('\{sitename\}', $line)) { $line = setsitename($line); }
				// Check for siteurl
				if (eregi('\{siteurl\}', $line)) { $line = setsiteurl($line); }
				// Check for YARRSTE credits
				if (eregi('\{yarrste_credit\}', $line)) { $line = setcredits($line, '1'); }
				// Check for debug credits
				if (eregi('\{debug\}', $line)) { $line = outputdebug($line, '1'); }
				// Loop through the array in $page to find/replace
				foreach ($toparse as $item) {
					$replace = ${$item};
					$find = "{".strtoupper($item)."}";
					$line = str_replace($find, $replace, $line);
				}
				// Send parsed info back to the full page array
				$lines[$linen] = $line;
			}
			// Re-search full array for vars
			$parselines = preg_grep ($check, $lines);
		}
		
//DEBUG		print_r ($lines);

		// New search for post-parse extras
		$check = '/<\/body>|<\/head>|<meta name="generator"/';
		$parselines = preg_grep ($check, $lines);
//DEBUG		print_r ($parselines);

		foreach ($parselines as $linen => $line) {
			// If the template is stingy :P stick credits / debug just before </body>
			if ((!$GLOBALS['credinline']) && eregi('</body>', $line)) { $line = setcredits($line, '0'); }
			if ($YARRSTE_debug && (!$GLOBALS['debuginline']) && eregi('</body>', $line)) { $line = outputdebug($line, '0'); }
			// Place generator in header
			if (eregi('<meta name="generator"', $line)) { $GLOBALS['geninline'] = '1'; }
			if ((!$GLOBALS['geninline']) && eregi('</head>', $line)) { $line = setgenerator($line); }

			// Send parsed info back to the full page array
			$lines[$linen] = $line;
		}

//DEBUG		print_r ($lines);

		// Build return string
		foreach ($lines as $line) {
			$return .= $line;
		}
	}
	return $return;
}

?>
