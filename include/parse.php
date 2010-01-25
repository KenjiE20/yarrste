<?php
/*
 * Project:	YARRSTE: Yet Another Really Rather Simple Templating Engine
 * File:	parse.php, template parsing file
 * Version:	svn-trunk
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
global $YARRSTE_caching;
// YARRSTE version
$YARRSTE_version = 'svn-trunk';
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
	if (eregi('\<title\>.*\{title\}.*\<\/title\>', $line)) {
		if ($title == '') {
			$replace = $YARRSTE_sitename;
		} else {
			$replace = $YARRSTE_sitename.' '.$YARRSTE_titlesep.' '.$title;
		}
	} else {
		$replace = $title;
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
		$info = "Debug Info:<br />\nPage: http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."<br />\nRequest via: ".$_SERVER['SERVER_PROTOCOL']." | Referer: ";
		if (isset($_SERVER['HTTP_REFERER'])) {
			$info .= $_SERVER['HTTP_REFERER'];
		}
		$info .= "<br />\nUser Agent: ".$_SERVER['HTTP_USER_AGENT']."<br />\nYARRSTE: ".$YARRSTE_version." | Generation Time: ".$YARRSTE_gentime." seconds.";
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
//DEBUG	print_r ($lines);

	// Build Logic regexp
	$logiccheck = '/';
	if (count($toparse)) {
		foreach ($toparse as $item) {
			$logiccheck .= '\{IF\s'.strtoupper($item).'(?:\s:|)\}|\{FOREACH\s'.strtoupper($item).'\s:\}|';
		}
	}
	$logiccheck .= '\{ELSE\s:\}|\{ENDIF\s;\}|\{ENDFOREACH\s;\}/';
	$logiclines = preg_grep ($logiccheck, $lines);

	while ($logiclines) {
//DEBUG		print_r ($logiclines);

		// IF : ELSE : ENDIF ; Tags
		if ($ifelselines = preg_grep ('/\{IF .* :\}|\{ELSE :\}|\{ENDIF ;}/', $logiclines)) {
			// Find deepest if/else/endif
			while (preg_match ('/\{IF .* :\}/', current($ifelselines))) {
				next($ifelselines);
			}
			// Keep line numbers for later
			prev($ifelselines);
			$start = key($ifelselines);
			next($ifelselines);
			$else = key($ifelselines);
			next($ifelselines);
			$end = key($ifelselines);
			// If else isn't an ELSE, it's an ENDIF, make end, else, and throw out else.
			if (!preg_match('/\{ELSE :\}/', $lines[$else])) {$end = $else; unset($else);}
			// Recheck match and catch var
			if (preg_match ('/\{IF (.*) :\}/', $ifelselines[$start], $match)) {
				// Save var name as lower case
				$var = strtolower($match[1]);
				// Check for var being set and not blank
				if (isset(${$var}) && ${$var} != '') {
					// Remove the IF
					$lines[$start] = preg_replace('/\{IF .* :\}/', '', $lines[$start]);
					// If we are an IF-ELSE-END
					if (isset($else)) {
						$line = $else;
						// Remove else, and following code (hope you didn't use just one line :)
						$lines[$else] = preg_replace('/\{ELSE :\}.*$/', '', $lines[$else]);
						$line++;
						// Unset anything between ELSE and END
						while ($line < $end) {
							unset($lines[$line]);
							$line++;
						}
						// Clear out anything up to and including ENDIF
						$lines[$end] = preg_replace('/^.*\{ENDIF ;\}/', '', $lines[$end]);
					} else {
						// Not and IF-ELSE, just an IF-END
						// Remove ENDIF
						$lines[$end] = preg_replace('/\{ENDIF ;\}/', '', $lines[$end]);
					}
				// Var isn't set, or is blank
				} else {
					$line = $start;
					// Remove IF, and following code (hope you didn't use just one line :)
					$lines[$line] = preg_replace('/\{IF .* :\}.*$/', '', $lines[$line]);
					$line++;
					// If we are an IF-ELSE-END
					if (isset($else)) {
						// Unset anything between IF and ELSE
						while ($line < $else) {
							unset($lines[$line]);
							$line++;
						}
						// Clear up to and including ELSE
						$lines[$else] = preg_replace('/^.*\{ELSE :\}/', '', $lines[$else]);
						// Remove ENDIF
						$lines[$end] = preg_replace('/\{ENDIF ;\}/', '', $lines[$end]);
					} else {
						// NOT IF-ELSE, just IF-END
						// Unset anything between IF and ENDIF
						while ($line < $end) {
							unset($lines[$line]);
							$line++;
						}
						// Clear up to and including ENDIF
						$lines[$end] = preg_replace('/^.*\{ENDIF ;\}/', '', $lines[$end]);
					}
				}
				// If the IF/ELSE/ENDIF lines are empty (or just whitespace) remove them
				if (preg_match ('/^\s*$/', $lines[$start])) { unset ($lines[$start]); }
				if (isset($else)) { if (preg_match ('/^\s*$/', $lines[$else])) { unset ($lines[$else]); }}
				if (preg_match ('/^\s*$/', $lines[$end])) { unset ($lines[$end]); }
			}
			// Run check again
			$logiclines = preg_grep ($logiccheck, $lines);
			// Immediately go back and run again
			continue;
		}
		// FOREACH tag
		if ($forlines = preg_grep ('/\{FOREACH .* :\}|\{ENDFOREACH ;}/', $logiclines)) {
			// Find deepest for loop
			while (preg_match ('/\{FOREACH .* :\}/', current($forlines))) {
				next($forlines);
			}
			// Store start and end for later
			prev($forlines);
			$start = key($forlines);
			next($forlines);
			$end = key($forlines);
			// Recheck match and store var name
			if (preg_match ('/\{FOREACH (.*) :\}(.*)$/', $forlines[$start], $match)) {
				// Save var name as lowercase
				$var = strtolower($match[1]);
				// If var isset and not blank and an array
				if (isset(${$var}) && ${$var} != '' && is_array(${$var})) {
					$array = ${$var};
					// Remove FOREACH
					$lines[$start] = preg_replace('/\{FOREACH .* :\}.*$/', '', $lines[$start]);
					// Build string out of html between foreach
					$eachout = '';
					// Only put what followed FOREACH if it's actually text
                                        if (isset($match[2]) && $match[2] != "\r" ) { $eachout .= $match[2]; }
					$line = $start+1;
					while ($line < $end) {
						$eachout .= $lines[$line];
						// Remove each line as we add it
						unset ($lines[$line]);
						$line++;
					}
					// Remove END and catch any text here
					preg_match ('/^(.*)\{ENDFOREACH ;\}/', $lines[$end], $endmatch);
					// Put last bit of text on
					$eachout .= $endmatch[1];
					// Build out string
					$out = '';
					// Test if array is nested
					if (is_array($array[0])) { $nest = 1; } else { $nest = 0; }
					// Build out with string for each key in array
					foreach ($array as $item) {
						// Check array is consistantly an array
						if ($nest && is_array($item)) {
							$nestout = $eachout;
							while (preg_match ('/\{FOREACH (.*)\}/', $nestout, $match)) {
								$nestout = preg_replace('/\{FOREACH .*\}/', $item[$match[1]], $nestout, 1);
							}
							$out .= $nestout;
						} elseif ($nest && !is_array($item)) {
							// Entire array's should have nested, mismatched values will break stuff
							$YARRSTE_caching = 0;
							$str = "Parsing error; This array contains non-array elements, where arrays where expected.<br />\nThe entire array \"".$var."\" should contain the same elements.\n\n<pre>\n{FOREACH ".strtoupper($var)." :}";
							if (isset($match[2]) && $match[2] == "\r" ) { $str .= $match[2]; }
							$str .= htmlspecialchars($eachout)."{ENDFOREACH ;}\n</pre>";
							return $str;
						// Not nested, handle as list
						} elseif (!$nest && !is_array($item)) {
							if (preg_match ('/\{FOREACH\}/', $eachout)) {
								// Fill in data
								$out .= preg_replace('/\{FOREACH\}/', $item, $eachout);
							}
						} else {
							// Entire array's should have nested, mismatched values will break stuff
							$YARRSTE_caching = 0;
							$str = "Parsing error; This array contains non-array elements, where arrays where expected.<br />\nThe entire array \"".$var."\" should contain the same elements.\n\n<pre>\n{FOREACH ".strtoupper($var)." :}";
							if (isset($match[2]) && $match[2] == "\r" ) { $str .= $match[2]; }
							$str .= htmlspecialchars($eachout)."{ENDFOREACH ;}\n</pre>";
							return $str;
						}
					}
					// Remove END tag
					$lines[$end] = preg_replace('/^.*\{ENDFOREACH ;\}/', '', $lines[$end]);
					// If newline was all that followed the FOREACH tag, then insertion needs to backup a bit.
					// If start line is empty, replace it with string
					if (preg_match ('/^\s*$/', $lines[$start])) {
						$lines[$start] = $out;
					// Otherwise append it
					} else {
						$lines[$start] .= $out;
					}
				// var not valid
				} else {
					$line = $start;
					// Remove FOREACH, and following code (hope you didn't use just one line :)
					$lines[$line] = preg_replace('/\{FOREACH .* :\}.*$/', '', $lines[$line]);
					$line++;
					// Unset anything between start and end
					while ($line < $end) {
						unset($lines[$line]);
						$line++;
					}
					// Remove END tag
					$lines[$end] = preg_replace('/^.*\{ENDFOREACH ;\}/', '', $lines[$end]);
				}
				// If the FOREACH/ENDFOREACH lines are empty (or just whitespace) remove them
				if (preg_match ('/^\s*$/', $lines[$start])) { unset ($lines[$start]); }
				$line = $start;
				while ($line < $end) { if (preg_match ('/^\s*$/', $lines[$start])) { unset ($lines[$start]); } $line++; }
				if (preg_match ('/^\s*$/', $lines[$end])) { unset ($lines[$end]); }
			}
			// Run check again
			$logiclines = preg_grep ($logiccheck, $lines);
			// Immediately go back and run again
			continue;
		}
		// End FOREACH

		// Basic IF tag
		if ($iflines = preg_grep ('/\{IF .*[^\s:]\}/', $logiclines)) {
			foreach ($iflines as $linen => $line) {
				if (preg_match ('/\{IF (.*[^\s:])\}/', $line, $match)) {
					$var = strtolower($match[1]);
					if (isset(${$var}) && ${$var} != '') {
						// Variable is set and has something in it
						$line = preg_replace('/\{IF .*[^\s:]\}/', '{'.$match[1].'}', $line);
						$lines[$linen] = $line;
					} else {
						// Variable unset or is an empty string
						$line = preg_replace('/\{IF .*[^\s:]\}/', '', $line);
						$lines[$linen] = $line;
					}
				}
			}
		}
		// End Basic IF

	$logiclines = preg_grep ($logiccheck, $lines);
	}
	// END WHILE

	// Initial regexp build
	$check = '/';
	// If have things to parse
	if (count($toparse)) {
		foreach ($toparse as $item) {
			$check .= '\{'.strtoupper($item).'\}|';
		}
	}
	$check .= '\{TITLE\}|\{SITENAME\}|\{SITEURL\}|\{YARRSTE_CREDIT\}|\{DEBUG\}/';
	// Initial var search
	$parselines = preg_grep ($check, $lines);

	// So long as the var search has vars keep parsing
	while ($parselines) {
//DEBUG		print_r ($parselines);
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
				
			// If have things to parse
			if (count($toparse)) {
				// Loop through the array in $page to find/replace
				foreach ($toparse as $item) {
					// Check we don't have tags that should be logical
					if (isset(${$item}) && !is_array(${$item})) {
						// Replace tag with content
						$replace = ${$item};
						$find = "/\{".strtoupper($item)."\}/";
						$line = preg_replace($find, $replace, $line);
					// Raise an error if so
					} else {
						$find = "/\{".strtoupper($item)."\}/";
						if (preg_match ($find, $line, $match)) {
							$YARRSTE_caching = 0;
							return "Parsing error; ".$match[0]." should be set and not an array. If you have arrays, or transient variables, use the {IF} and {FOREACH} tags.\n\n<pre>".htmlspecialchars($lines[$linen-1].$lines[$linen].$lines[$linen+1])."</pre>";
						}
					}
				}
			}
			
			// Send parsed info back to the full page array
			$lines[$linen] = $line;
		}
		// Re-search full array for vars
		$parselines = preg_grep ($check, $lines);
	}

//DEBUG	print_r ($lines);

	// New search for post-parse extras
	$check = '/<\/body>|<\/head>|<meta name="generator"/';
	$parselines = preg_grep ($check, $lines);
//DEBUG	print_r ($parselines);

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


//DEBUG	print_r ($lines);
	// Build return string
	foreach ($lines as $line) {
		$return .= $line;
	}
	return $return;
}

?>
