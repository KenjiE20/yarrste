<?php
/*
 * Project:	YARRSTE: Yet Another Really Rather Simple Templating Engine
 * File:	admin.php, administration base file
 * Version:	svn-trunk
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

// <- Pre-admin tasks ->

// Allow included files to run
define('IN_YARRSTE', true);
// Bring in the globals
require ('../config.php');
require ('../include/parse.php');
require ('../include/cache.php');
require ('./admin_funcs.php');

// Setups
$YARRSTE_toedit = '';
$YARRSTE_preview = '';
$YARRSTE_admin_action = '';

// Get do action
if (isset ($_GET['do'])) {
	$YARRSTE_admin_do = $_GET['do'];
	// Page edit
	if ($YARRSTE_admin_do == 'edit') {
		$YARRSTE_toedit = $_GET['file'];
		// Before anything else, Magic key safety check
		// If it's not there we're not doing anything anyway
		// if it is there, it needs to be right
		if (isset ($_REQUEST['magickey'])) {
			if ($_REQUEST['magickey'] == MAGIC_KEY) {
				// Saving an edited file
				if (isset($_POST['textarea'])) {
					YARRSTE_edit_file ($YARRSTE_toedit);
				}
			}
		}
	// Creating a new file
	} elseif ($YARRSTE_admin_do == 'newfile') {
		YARRSTE_newfile();
		$YARRSTE_admin_do = 'base';
	// Are we being asked for a page preview
	} elseif ($YARRSTE_admin_do == 'preview') {
		$YARRSTE_preview = $_GET['file'];
	// Clearcache call
	} elseif ($YARRSTE_admin_do == 'clearcache') {
		clear_cache();
		sleep(1);
		$YARRSTE_admin_do = 'viewcache';
	// Clear single cache call
	} elseif ($YARRSTE_admin_do == 'clear_single_cache') {
		$YARRSTE_clr_single = $_GET['file'];
		clear_single_cache($YARRSTE_clr_single);
		$YARRSTE_admin_do = 'viewcache';
	} elseif ($YARRSTE_admin_action == 'test') {
//		file
	}
} else {
	$YARRSTE_admin_do = 'base';
}

// Headers
echo "<head>\n<link rel=\"stylesheet\" href=\"admin.css\" type=\"text/css\" />\n</head>\n";
if (!$YARRSTE_admin_action == '') {
	echo "<div class=\"adminaction\">$YARRSTE_admin_action</div>\n";
}
echo "<div class=\"wrapper\">\n";
echo "<div class=\"adminmenu\">
<a href=\"".basename($_SERVER['SCRIPT_NAME'])."\">Main</a><br />
<a href=\"".basename($_SERVER['SCRIPT_NAME'])."?do=test\">Test func</a><br />
<a href=\"".basename($_SERVER['SCRIPT_NAME'])."?do=viewcache\">Cache</a><br />
<a href=\"".dirname($_SERVER['SCRIPT_NAME'])."/..\">Back to Site</a>\n</div>\n";
echo "<div class=\"adminmain\">\n";

// Prompt if don't know what to edit
if ($YARRSTE_admin_do == 'base') {
	// Get hidden toggle
	if (isset($_GET['hidden'])) {
		$YARRSTE_admin_show_hidden = $_GET['hidden'];
	} else {
		$YARRSTE_admin_show_hidden = 0;
	}
	// Scan templates
	echo "<!-- BEGIN Templates -->\n";
	echo "Available Template files:<br />\n<div class=\"listdir\">\n";
	$YARRSTE_tplhandle = opendir('../'.$YARRSTE_tplpath);
	list_dir($YARRSTE_tplhandle, '../'.$YARRSTE_tplpath, 'tpl', $YARRSTE_admin_show_hidden);
	// Scan content
	echo "<!-- END Templates -->\n<!-- BEGIN Content -->\n";
	echo "</div>\nAvailable Content files:<br />\n<div class=\"listdir\">\n";
	$YARRSTE_texthandle = opendir('../'.$YARRSTE_textpath);
	list_dir($YARRSTE_texthandle, '../'.$YARRSTE_textpath, 'text', $YARRSTE_admin_show_hidden);
	echo "</div>\n";
	echo "<!-- END Content -->\n";

// Check for preview
} elseif ($YARRSTE_admin_do == 'preview') {
	preview_page ($YARRSTE_preview);
} elseif ($YARRSTE_admin_do == 'viewcache') {
	view_cache ();
// Have an edit file, load up the edit stuff
} elseif ($YARRSTE_admin_do == 'edit') {
	//Check file's extention to determine what we're editing
	$YARRSTE_extension = end(explode('.', $YARRSTE_toedit));
	// Check if we've been told to plain text edit
	if (isset($_GET['adv'])) {
		if ($_GET['adv'] == '1') {
			$YARRSTE_extension = 'adv';
		}
	}

	// Include content or load template
	if ($YARRSTE_extension == 'php') {
		// Bring in the file (not used atm)
//		include ($YARRSTE_toedit);
		$YARRSTE_phpfile = file_get_contents($YARRSTE_toedit);
		// Set $YARRSTE_type & highlighter
		$YARRSTE_type = 'content';
		$codehi = 'php';
		// Confirm active file
		echo "Currently editing content file: ".$YARRSTE_toedit."<br /><br />\n";
	} elseif ($YARRSTE_extension == 'tpl') {
		// Bring in the file
		$YARRSTE_rawfile = file_get_contents($YARRSTE_toedit);
		// Set $YARRSTE_type & highlighter
		$YARRSTE_type = 'raw';
		$codehi = 'html';
		// Confirm active file
		echo "Currently editing template file: ".$YARRSTE_toedit."<br /><br />\n";
	} elseif ($YARRSTE_extension == 'css') {
		// Bring in the file
		$YARRSTE_rawfile = file_get_contents($YARRSTE_toedit);
		// Set $YARRSTE_type & highlighter
		$YARRSTE_type = 'raw';
		// Confirm active file
		echo "Currently editing style sheet file: ".$YARRSTE_toedit."<br /><br />\n";
	} elseif ($YARRSTE_extension == 'adv') {
		// Bring in the file
		$YARRSTE_rawfile = file_get_contents($YARRSTE_toedit);
		// Set $YARRSTE_type
		$YARRSTE_type = 'raw';
		// Confirm active file
		echo "Currently editing file: ".$YARRSTE_toedit."<br /><br />\n";
	} else {
		echo "Unknown filetype";
		break;
	}

?>
<!--<script src="../include/codepress/codepress.js" type="text/javascript"></script>-->
<form action="<?php echo basename($_SERVER['SCRIPT_NAME']); ?>?do=edit&file=<?php echo $YARRSTE_toedit; ?>" method="post">
<input type="hidden" name="magickey" value="<?php echo MAGIC_KEY; ?>" />
<textarea name="textarea" rows=20 cols="100%"><?php
if ($YARRSTE_type == 'content') {
	echo $YARRSTE_phpfile;
} elseif ($YARRSTE_type == 'raw') {
	echo $YARRSTE_rawfile;
}
?></textarea><br />
<input type="submit" name="submit" value="Save"><br />
</form>

<?php
}
global $YARRSTE_version;
echo "\n</div>\n<div style=\"clear:both;\"></div>\n<div style=\"font-size:small;color:grey;\">YARRSTE version: ".$YARRSTE_version."</div>\n</div>\n";

/*	// And set up edit forms
	if ($YARRSTE_type == 'content') {
?>
To edit in plain text mode (same as templates) <a href="<?php echo basename($_SERVER['SCRIPT_NAME']); ?>?edit=<?php echo $YARRSTE_toedit; ?>&adv=1">Click Here</a><br /><br />

<form action="<?php echo basename($_SERVER['SCRIPT_NAME']); ?>?edit=<?php echo $YARRSTE_toedit; ?>" method="post">
<input type="text" name="newparse"></textarea>
<input type="hidden" name="magickey" value="<?php echo MAGIC_KEY; ?>" />
<input type="submit" name="submitadd" value="Add">
</form>

<form action="<?php echo basename($_SERVER['SCRIPT_NAME']); ?>?edit=<?php echo $YARRSTE_toedit; ?>" method="post">
<select size="1" name="vardropdown">
<?php
// Get all variables from file and list them
echo "<option";
if ((!isset ($_POST["vardropdown"])) || $_POST["vardropdown"] == "-1") { echo " selected=\"selected\"";}
echo " value=\"-1\"></option>\n";
$YARRSTE_count = count($toparse);
for ($i = 0; $i < $YARRSTE_count; $i++) {
	echo "<option";
	if (isset ($_POST["vardropdown"])) {
		if ($_POST["vardropdown"] == $i) { echo " selected=\"selected\"";}
	}
	echo " value=\"" . $i . "\">" . $toparse[$i] . "</option>\n";
}
// Make textbox and load selected var if given
?></select>
<input type="submit" name="submitld" value="Load">
</form>

<form action="<?php echo basename($_SERVER['SCRIPT_NAME']); ?>?edit=<?php echo $YARRSTE_toedit; ?>" method="post">
<textarea name="textarea" rows=10 cols=80><?php if (isset ($_POST["vardropdown"])) { echo ${$toparse[$_POST["vardropdown"]]}; } ?></textarea>
</form>
<br />

<?php
	} elseif ($YARRSTE_type == 'tpl') {
*/

/*			// Adding a parse item
			if(isset($_POST['newparse'])) {
				// Get a file into an array.
				$YARRSTE_parseedit = file($YARRSTE_toedit);
				
				// Loop through the array
				foreach ($YARRSTE_parseedit as $line_num => $line) {
					// Find the $toparse var
					if (!(preg_match('/^\/\//', $line)) && !(preg_match('/^#/', $line))) {
						if (eregi('\$toparse', $line)) {
							// Figure out what the array has already
							$pieces = explode("=", $line);
							$YARRSTE_parsearr = trim($pieces[1]);
							// Array has a value
							if (eregi('\(\s*\"', $YARRSTE_parsearr)) {
								echo "found a value\n";
								// Array has multiple values
								if (eregi('\"\s*,\s*\"', $YARRSTE_parsearr)) {
//									if (preg_match('/\);\r|$/',$line)) {echo 'match';}
//									$line = str_replace('/\);$|\r/',', "'.$_POST['newparse'].'");',$line);
								}
							}
							echo htmlspecialchars($line)."\n";
//							if ($YARRSTE_template == '') {unset ($YARRSTE_template);}
						}
					}
				}
			}
*/


?>
