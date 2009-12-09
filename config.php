<?php
/*
 * Project:	YARRSTE: Yet Another Really Rather Simple Templating Engine
 * File:	config.php, configuration file
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

// Global configs
// Paths
$YARRSTE_tplpath = "templates"; // Path to template dir
$YARRSTE_textpath = "content"; // Path to content text files

// Site stuff
$YARRSTE_sitename = "Template Tester"; // Your site name, appears in the template tag {SITENAME}
$YARRSTE_titlesep = "-"; // The character string you want to seperate site name from page name : | - > are all good ones
$YARRSTE_deftpl = 'default'; // Default site template, should be in the form path/file i.e. 'foo/bar'
$YARRSTE_defpage = 'default'; // Default page if you don't ask for one, say like the homepage, should be in the form path/file i.e. 'foo/bar'
//$YARRSTE_baseurl = "www.yourdomainhere.com/sitedir"; // Uncomment to override auto detection of site url, appears in the template tag {SITEURL}
$YARRSTE_debug = 'True'; // Set this to 'True' if you want some useful info outputted, will output to the {DEBUG} tag if possible, or before </body> if no tag is present
$YARRSTE_caching = 'False'; // Set this to 'True' if you want to use YARRSTE's basic caching system.
//$YARRSTE_c_dir = './cache/'; // Uncomment and set to override the default cache directory
//$YARRSTE_c_age = 600; // Uncomment and set to override the default cache age (in seconds)
//$YARRSTE_c_ext = 'cache';// Uncomment and set to override the default cache file extension

/*
* Auth Keys
*
* Change this to a unique phrase
* You can generate these using 
* https://www.grc.com/passwords.htm
* but beware of ' characters on the "63 random printable ASCII characters:" field
* or by picking a value from
* https://api.wordpress.org/secret-key/1.1/
* WordPress.org secret-key service
*
*/

// Admin magic_key
// define('MAGIC_KEY', 'insert key string here');

/*
*
* <-- You should not need to edit past this point -->
*
*/

// Base URL
if (!isset ($YARRSTE_baseurl)) {
	$YARRSTE_baseurl = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/';
	$YARRSTE_baseurl = preg_replace('/admin\/$/','',$YARRSTE_baseurl);
}
if (!preg_match('/^http:\/\//', $YARRSTE_baseurl)) {
	$YARRSTE_baseurl = preg_replace('/^/','http://',$YARRSTE_baseurl);
}
if (!preg_match('/\/$/', $YARRSTE_baseurl)) {
	$YARRSTE_baseurl = preg_replace('/$/','/',$YARRSTE_baseurl);
} elseif (preg_match('/\/\/$/', $YARRSTE_baseurl)) {
	$YARRSTE_baseurl = preg_replace('/\/\/$/','/',$YARRSTE_baseurl);
}

?>
