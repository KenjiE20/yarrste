<?php
/*
 * Project:	YARRSTE: Yet Another Really Rather Simple Templating Engine
 * File:	admin_funcs.php, admin functions file
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

// Recursive searching
function list_dir ($dir_handle, $dir, $type, $show_hidden) {
	$row = '0';
	echo "<ol>\n";
	?>
<li class="ydirli<?php echo $row; ?>">
<form action="<?php echo basename($_SERVER['SCRIPT_NAME']); ?>?do=newfile" method="post">
<input type="hidden" name="magickey" value="<?php echo MAGIC_KEY; ?>" />
<input type="hidden" name="dir" value="<?php echo $dir; ?>" />
<input type="hidden" name="type" value="<?php echo $type; ?>" />
Create new file: <input type="text" name="newfile"></textarea>
<input type="submit" name="submit" value="Save">
</form>
<?php if ($type == 'tpl'): ?>
<form action="<?php echo basename($_SERVER['SCRIPT_NAME']); ?>?do=newfile" method="post">
<input type="hidden" name="magickey" value="<?php echo MAGIC_KEY; ?>" />
<input type="hidden" name="dir" value="<?php echo $dir; ?>" />
<input type="hidden" name="type" value="css" />
Create new css file: <input type="text" name="newfile"></textarea>
<input type="submit" name="submit" value="Save">
</form>
<?php endif; ?>
</li>
<?php
	while (false !== ($file = readdir($dir_handle))) {
		$dircheck = $dir.'/'.$file;
		if (is_dir ($dircheck) && $file != "." && $file != "..") {
			if (preg_match('/^\.\w+/', $file)) {
				if (!$show_hidden) {
					continue;
				}
			}
			$handle = @opendir($dircheck);
			echo "<li class=\"dir\">".$file;
			list_dir($handle, $dircheck, $type, $show_hidden);
			echo "</li>\n";
		} elseif ($file != "." && $file != "..") {
			if (preg_match('/^\.\w+/', $file)) {
				if (!$show_hidden) {
					continue;
				}
			}
			if ($row) { $row = '0'; } else { $row = '1'; }
			echo "<li class=\"ydirli".$row."\">";
			if ($type == 'text') { echo "<span class=\"preview\"><a href=\"".basename($_SERVER['SCRIPT_NAME'])."?do=preview&file=".$dir."/".$file."\">Preview Code</a></span>"; }
			echo "<a href=\"".basename($_SERVER['SCRIPT_NAME'])."?do=edit&file=".$dir."/".$file."\">".$file."</a>";
			// Stolen description lines from wordpress just... because
			$data = implode( '', file( $dir.'/'.$file ) );
			if ( preg_match( '|Description:(.*)$|mi', $data, $name )) {
			echo " - Description: ".trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $name[1])); }
			echo "</li>\n";


		}
	}
	echo "</ol>\n";
	closedir($dir_handle);
}
// End Recursive Searching

// Make a new file
function YARRSTE_newfile () {
	global $YARRSTE_admin_action;
	// Magic key safety check
	if (isset ($_REQUEST['magickey'])) {
		if ($_REQUEST['magickey'] == MAGIC_KEY) {
			// Grab and sort items
			$YARRSTE_name = $_POST['newfile'];
			$YARRSTE_dir = $_POST['dir'];
			$YARRSTE_type = $_POST['type'];
			// What are we making
			if ($YARRSTE_type == 'text') { $YARRSTE_type = '.php'; }
			elseif ($YARRSTE_type == 'tpl') {$YARRSTE_type = '.tpl'; }
			elseif ($YARRSTE_type == 'css') {$YARRSTE_type = '.css'; }

			// Check file doesn't already exist
			$YARRSTE_file = $YARRSTE_dir.'/'.$YARRSTE_name.$YARRSTE_type;
			if (file_exists($YARRSTE_file)) {
				$YARRSTE_admin_action = "File already exists: $YARRSTE_file";
			} else {
				$YARRSTE_admin_action = "Creating new file... ";
				if ($YARRSTE_type == '.php') {
					// Build basic content file
					if (new_content($YARRSTE_file)) {
						$YARRSTE_admin_action .= "File ".$YARRSTE_file." created";
					} else {
						$YARRSTE_admin_action .= "Something bad happened";
					}
				} else {
					// Make blank file, no basic build for others
					touch ($YARRSTE_file);
					$YARRSTE_admin_action .= "File ".$YARRSTE_file." created";
				}
			}
		}
	}
}

// Write edits to a file
function YARRSTE_edit_file ($YARRSTE_toedit) {
	global $YARRSTE_admin_action;
	$fp = @fopen($YARRSTE_toedit, 'w');
	if (!$fp) {
		$YARRSTE_admin_action = "Unable to open file for writing: $YARRSTE_toedit";
		fclose($fp);
	} else {
		// Dev marker -- Magic quotes changes in 6
		if (get_magic_quotes_gpc()) {
			$data = stripslashes($_POST['textarea']);
		} else {
			$data = $_POST['textarea'];
		}
		fwrite($fp, $data);
		fclose($fp);
		$YARRSTE_admin_action = "Successfully updated file: $YARRSTE_toedit";
	}
}

// Create new content file layout
function new_content ($file) {
	$newfile = fopen($file, 'x');
	$basetext = "<?php
/*
Description: File description for admin page
*/

// <!-- Setting up -->

// Uncomment to override the default site template for a specific page
//\$tpl = 'template_name';
// Uncomment to override default caching (True / 1 for on, False / 0 for off)
//\$Y_cache = 0;
// This page's title, this is expected, but optional
//\$title = 'Page Title';
// You can add your own set up vars here

// List which variables should be used for parsing
// This allows extra PHP code to be ran here, without affecting what the parser does
\\ e.g. \$toparse = array(\"left\", \"right\", \"content\");
\$toparse = array();

// <!-- Content -->



?>";
	fwrite ($newfile, $basetext);
	fclose ($newfile);
	return 1;

}

// Run parsers and preview page code
function preview_page ($page) {
	global $YARRSTE_tplpath;
	global $YARRSTE_textpath;
	global $YARRSTE_baseurl;
	global $YARRSTE_deftpl;
	global $YARRSTE_debug;
	$page2 = preg_replace('/\.\.\/'.$YARRSTE_textpath.'\/(.*)\.(.*)/','\\1',$page);
	include_once '../include/geshi/geshi.php';

	// Check if content wants a specific template
	// Get a file into an array.
	$YARRSTE_tplsearch = file($page);

	// Loop through the array and look for the $tpl variable
	foreach ($YARRSTE_tplsearch as $line_num => $line) {
		if (!(preg_match('/^\/\//', $line)) && !(preg_match('/^#/', $line))) {
			if (eregi('\$tpl', $line)) {
				// Figure out what template is being asked for
				$pieces = explode("=", $line);
				$YARRSTE_template = trim($pieces[1], " '\";\n\r");
				if ($YARRSTE_template == '') { unset ($YARRSTE_template); }
			}
		}
	}
	unset ($YARRSTE_tplsearch);

	// Get and override template if given
	// Make file value lower-case to avoid case-sensitivity
	// And add the extention
	if (isset ($_GET['template'])) {
		$YARRSTE_template = $_GET['template'];
		$YARRSTE_template = strtolower($YARRSTE_template);
		$YARRSTE_template = '../'.$YARRSTE_tplpath."/".$YARRSTE_template.".tpl";
	// Use template set by content file
	} elseif (isset ($YARRSTE_template)) {
		$YARRSTE_template = '../'.$YARRSTE_tplpath.'/'.$YARRSTE_template.'.tpl';
	// Default if nothing set
	} else {
		$YARRSTE_template = '../'.$YARRSTE_tplpath.'/'.$YARRSTE_deftpl.'.tpl';
	}

	// Debugging output
	if (isset ($_GET['debug'])) {
		$YARRSTE_debug = $_GET['debug'];
	} elseif ($YARRSTE_debug == 'True' || $YARRSTE_debug == 'true' || $YARRSTE_debug == '1') {
		$YARRSTE_debug = '1';
	} else {
		$YARRSTE_debug = '0';
	}

	// Get a file into an array.
	$lines = file($YARRSTE_template);

	$geshi1line = '';
	// Loop through the template array and add line numbers
	foreach ($lines as $line_num => $line) {
		$geshi1line .= $line;
	}

	$geshi2line = '';
	$geshi2line = YARRSTE_parse($YARRSTE_template, $page);

	$geshi1 = new GeSHi($geshi1line, 'html4strict');
	$geshi1->enable_classes();
	$geshi1->set_header_type(GESHI_HEADER_DIV);
	$geshi1->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);

	$geshi2 = new GeSHi($geshi2line, 'html4strict');
	$geshi2->enable_classes();
	$geshi2->set_header_type(GESHI_HEADER_DIV);
	$geshi2->enable_line_numbers(GESHI_NO_LINE_NUMBERS);

	echo '<style type="text/css"><!--';
	echo $geshi1->get_stylesheet();
	echo $geshi2->get_stylesheet();
	echo '--></style>';

	echo "Using template; '".$YARRSTE_template."' and content; '".$page."'<br />\n";
	echo "<a href=\"".$YARRSTE_baseurl."index.php?page=".$page2."\">Show page</a><br /><br />\n";
	echo "<!-- Unparsed -->".$geshi1->parse_code()."<!-- End unparsed -->";
	echo "<br /><br />\n";
	echo "<!-- Parsed -->".$geshi2->parse_code()."<!-- End parsed -->";
}

// Cache cleaning
function clear_cache () {
	global $cachedir;
	global $YARRSTE_admin_action;
	$manifest = $cachedir."manifest";
	foreach (glob($cachedir."*") as $filename) {
//		echo "$filename size " . filesize($filename) . "\n";
		unlink($filename);
	}
	touch ($manifest);
	$YARRSTE_admin_action = "Cache Cleared";
}

// Single Cache cleaning
function clear_single_cache ($file) {
	global $cachedir;
	global $YARRSTE_admin_action;
	$filename = $cachedir.$file;
	$manifest = $cachedir."manifest";
	$manifile = file_get_contents($manifest);
	if (file_exists($filename)) {
		$find = "/^".preg_quote($filename,'/').".*\n/";
		$manifile = preg_replace($find, "", $manifile);
		unlink($filename);
		file_put_contents($manifest, $manifile);
	}
	$YARRSTE_admin_action = "Cache File ".$file." Removed";
}

// Cache list
function view_cache () {
	global $YARRSTE_caching;
	global $cachedir;
	$manifest = $cachedir."manifest";
	if ($YARRSTE_caching != 'True' || $YARRSTE_caching != 'true' || $YARRSTE_caching != '1') {
		if (!file_exists($manifest)) {
			echo "Caching offline, and no exisitng cache files";
			return;
		}
	}
	$manifile = file($manifest);
	echo "Cache files:<br />\n";
	echo "<table class=\"cachefiles\"><tr><th>&nbsp;</th><th width=\"300\">Filename</th><th>Age</th><th>Page</th><th width=\"80\">File Size</th></tr>\n";
	$tr = 0;
	foreach (glob($cachedir."*") as $filename) {
		echo "<tr class=\"d".($tr & 1)."\"><td style=\"text-align: center;\"><a title=\"Delete cache file\" href=\"".basename($_SERVER['SCRIPT_NAME'])."?do=clear_single_cache&file=".basename($filename)."\">x</td><td>".basename($filename)."</td><td>";
		$mtime = filemtime($filename);
		$age = time() - $mtime;
		echo time_duration($age) . "</td><td>";
                $manigrep = preg_grep('/'.preg_quote($filename,'/').'/',$manifile);
		if ($manigrep) {
			$manigrep = array_values($manigrep);
			$out = strstr($manigrep[0],'=');
			$out = ltrim ($out, " =");
			$out = rtrim($out);
			echo basename($out);

                }
		echo "</td><td>";
		echo floor((filesize($filename)/1024)*100)/100 . "Kb</td></tr>\n";
		$tr++;
	}
	echo "</table>\n";
	echo "<a href=\"".basename($_SERVER['SCRIPT_NAME'])."?do=viewcache\">Refresh Cache</a>\n";
	echo "<a href=\"".basename($_SERVER['SCRIPT_NAME'])."?do=clearcache\">Clear Cache</a>\n";
}

// TinyMCE header
function tinymce_head () {
	echo "<!-- TinyMCE -->\n<script type=\"text/javascript\" src=\"../include/tiny_mce/tiny_mce.js\"></script>\n<script type=\"text/javascript\">
tinyMCE.init({
	// General options
	mode : \"textareas\",
	theme : \"advanced\",
	plugins : \"safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template\",

	// Theme options
	theme_advanced_buttons1 : \"save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect\",
	theme_advanced_buttons2 : \"cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor\",
	theme_advanced_buttons3 : \"tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen\",
	theme_advanced_buttons4 : \"insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,pagebreak\",
	theme_advanced_toolbar_location : \"top\",
	theme_advanced_toolbar_align : \"left\",
	theme_advanced_statusbar_location : \"bottom\",
	theme_advanced_resizing : true,

	// Example content CSS (should be your site CSS)
//	content_css : \"css/content.css\",

	// Drop lists for link/image/media/template dialogs
	template_external_list_url : \"lists/template_list.js\",
		external_link_list_url : \"lists/link_list.js\",
	external_image_list_url : \"lists/image_list.js\",
		media_external_list_url : \"lists/media_list.js\"
	});
</script>\n<!-- /TinyMCE -->";
}

/**
 * A function for making time periods readable
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     2.0.0
 * @link        http://aidanlister.com/2004/04/making-time-periods-readable/
 * @param       int     number of seconds elapsed
 * @param       string  which time periods to display
 * @param       bool    whether to show zero time periods
 */
function time_duration($seconds, $use = null, $zeros = false)
{
    // Define time periods
    $periods = array (
        'years'     => 31556926,
        'Months'    => 2629743,
        'weeks'     => 604800,
        'days'      => 86400,
        'hours'     => 3600,
        'minutes'   => 60,
        'seconds'   => 1
        );

    // Break into periods
    $seconds = (float) $seconds;
    foreach ($periods as $period => $value) {
        if ($use && strpos($use, $period[0]) === false) {
            continue;
        }
        $count = floor($seconds / $value);
        if ($count == 0 && !$zeros) {
            continue;
        }
        $segments[strtolower($period)] = $count;
        $seconds = $seconds % $value;
    }

    // Build the string
    foreach ($segments as $key => $value) {
        $segment_name = substr($key, 0, -1);
        $segment = $value . ' ' . $segment_name;
        if ($value != 1) {
            $segment .= 's';
        }
        $array[] = $segment;
    }

    $str = implode(', ', $array);
    return $str;
}

?>
