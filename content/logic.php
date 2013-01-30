<?php

// <!-- Setting up -->

// Uncomment to override the default site template for a specific page
$tpl = 'logic';
// Uncomment to override default caching
//$Y_cache = 0;
// List which variables should be used for parsing
// This allows extra PHP code to be ran here, without affecting what the parser does
$toparse = array("title", "t", "f", "list", "nested");

$title = 'Logic Example';
// <!-- Content -->

$t = TRUE;
$f = FALSE;

$list = array("one", "two", "three", "four");

$nested = array(
	array("one", "1"),
	array("two", "2"),
	array("three", "3"),
	array("four", "4")
);

?>