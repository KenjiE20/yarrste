<?php
/*
 * Project:	YARRSTE: Yet Another Really Rather Simple Templating Engine
 * File:	cache.php, simple caching
 * Version:	0.0.53
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

// Settings
global $YARRSTE_c_dir;
global $YARRSTE_c_age;
global $YARRSTE_c_ext;
global $YARRSTE_textpath;
// Defaults (set these in config.php)
$cachedir = 'cache';
$cachetime = 3600;
$cacheext = 'cache';

if (isset ($YARRSTE_c_dir) && $YARRSTE_c_dir != '') {
	$cachedir = $YARRSTE_c_dir;
}
if (isset ($YARRSTE_c_age) && $YARRSTE_c_age != '') {
	$cachetime = $YARRSTE_c_age;
}
if (isset ($YARRSTE_c_ext) && $YARRSTE_c_ext != '') {
	$cacheext = $YARRSTE_c_ext;
}

$cachedir = dirname(dirname(__FILE__)).'/'.$cachedir;
if (!preg_match('/\/$/', $cachedir)) {
	$cachedir = preg_replace('/$/','/',$cachedir);
} elseif (preg_match('/\/\/$/', $cachedir)) {
	$cachedir = preg_replace('/\/\/$/','/',$cachedir);
}


// Ignore List
$ignore_list = array(
'page=ignore',
'/admin/'
);

// attempt to make the cache directory
if (!file_exists($cachedir)) {
	$status = @mkdir($cachedir, 0755);

	// if make failed
	if (!$status) {
		$error = "Cache couldn't make dir '".$cachedir."'.";
	}
}

function cachesum ($reqpage) {
	global $cachedir;
	global $cacheext;
	$cachefile = $cachedir . md5($reqpage) . '.' . $cacheext; // Cache file to check
	return $cachefile;
}

function YARRSTE_cache_check ($file, $time) {
	if ($time == false) {
		global $cachetime;
	} else {
		$cachetime = $time;
	}
	global $ignore_page;
	global $ignore_list;
	$cachefile = cachesum ($file);

	$ignore_page = false;
	for ($i = 0; $i < count($ignore_list); $i++) {
		$ignore_page = (strpos($file, $ignore_list[$i]) !== false) ? true : $ignore_page;
	}
	if ($ignore_page) {
		return 'IGNORE';
	}

	if (file_exists($cachefile)) {
		// find how long ago the file was added to the cache
		// and whether that is longer then MAX_AGE
		$mtime = filemtime($cachefile);
		$age = time() - $mtime;

		if ($cachetime > $age) {
			// object exists and is current
			return 'HIT';
		} else {
			// object exists but is old
			return 'STALE';
		}
	} else {
		// object does not exist
		return 'MISS';
	}
}

function YARRSTE_cache_write ($file, $data) {
	global $cachedir;
	$cachefile = cachesum ($file);
	$fp = @fopen($cachefile, 'w');
	if (!$fp) {
		echo "Cache unable to open file for writing: $cachefile";
		return 0;
	}
	fwrite($fp, $data);
	fclose($fp);
	$manifest = $cachedir."manifest";
	$manfind = file($manifest);
	if (!(preg_grep('/'.preg_quote($cachefile,'/').'/',$manfind))) {
		$manifp = @fopen($manifest, 'a+');
		$manidata = $cachefile.' = '.$file."\n";
		fwrite($manifp, $manidata);
		fclose($manifp);
	}
	unset ($manfind);
}

function YARRSTE_cache_open ($file) {
	$cachefile = cachesum ($file);
	$fp = @fopen($cachefile, 'r');
	if (!$fp) {
		echo "Failed to open cache file for reading: $cachefile";
		return 0;
	}
	if ($filesize = filesize($cachefile)) {
		$data = fread($fp, filesize($cachefile));
		return $data;
	}
	return 0;
}

?>
