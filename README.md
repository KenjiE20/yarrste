YARRSTE - Yet Another Really Rather Simple Templating Engine

YARRSTE is a PHP templating engine I wrote as I could not
find something that was both simple but allowed for a lot
of flexibility.

FEATURES:

* Simple variable replacement
* Basic if/foreach logic templating
* Basic file caching of dynamic pages
* Admin interface for clering cache, file management (bare-bones)

USAGE:

YARRSTE splits a web page into to parts, a HTML template,
and a PHP content file. The filename of these become your page
name to access them, by the URL
http://www.my-site.tld/index.php?page=file
or by POST'ing the 'page' variable.

The HTML template is a standard HTML file, with variables of the
form {VAR} for content. They have the extension .tpl and you can
have multiple of these in the 'templates' directory.

The PHP content files are normal PHP, so you can make it do anything
PHP can, with a few expected variables.

* $toparse (required)
  This is an array of variables to populate the template with. These
  MUST match the {VARS} in the template, and MUST exist as $vars to
  PHP. If you are using logic, their variables should also be listed
  here.

* $title (required)
  This is the page title to go between <title> and anywhere else in
  the page. (Still required to be listed in $toparse).

* $tpl (optional)
  You may add this variable to instruct YARRSTE to use this .tpl file
  instead of the global default defined by config.

* $Y_cache (optional)
  Set this to TRUE or FALSE to override global caching one way or
  the other.

NOTES:

ADMIN;

  The admin pages for YARRSTE, while functional, are particularly barebone.
  The main page, shows a list of content and template pages, and will let
  you open an editor for them. GeSHi is included to allow for some code
  highlighting, though not set up.
  
  There is NO access control for the admin/ directory, and it is recommended
  you set up your own .htaccess to limit user access.

BUILT-IN VARIABLES;

  YARRSTE reserves a few variable names for global things, and its
  own purposes.
  
  * {TITLE} - as mentioned above.
  * {SITENAME} - as set by config.
  * {SITEURL} - either auto detected or set in config.
  * {DEBUG} - for spitting out a few variables and run-time
  * {IF}/{ELSE}/{FOREACH}/{ENDIF}/{ENDFOREACH} - see below
  * {YARRSTE_CREDIT} - YARRSTE's credit line, recommended to use this tag
    in some form (hidden if you so please), or YARRSTE will attempt to
	place it itself discreetly at the end of the <body>.

LOGIC;

  YARRSTE features a simple IF/ELSE and FOREACH logic system, that can
  be added to templates. These are the recognised tags;
  
  * {IF VAR :}
  * {ELSE :}
  * {ENDIF ;}
  * {FOREACH VAR :}
  * {ENDFOREACH ;}
  * {FOREACH VAL}
  * {FOREACH}
  
  Each IF or FOREACH should contain the variable it is checking against.
  It will attempt to match each IF or FOREACH with a corresponding ENDIF
  or ENDFOREACH, to allow for logic nesting.
  
  IF's work on a simple true/false basis. If the VAR resolved to true, then
  anything after the IF, and before the ELSE/ENDIF, in the template, will be
  parsed. If false, then either nothing, or only what is between the ELSE
  and ENDIF, will be parsed.
  
  FOREACH's are somewhat more complex, depending on what is provided to it.
  The VAR for a FOREACH must be an array.
  
  If the array is a flat array, then the system looks for {FOREACH} and
  replaces it with the value in the array.
  
  If the array is a nested one, then the system looks for {FOREACH VAL}.
  It then looks for VAL in one side of the nested array, to replace with
  content from the other.

CACHING;

  YARRSTE's caching works by simply saving the output from a particular page
  for n seconds (default 3600) and delivering that, instead of running
  the full PHP script everytime. Global enabling can be toggled in the config,
  while per page can be defined by the content file. The admin interface allows
  you to view what pages are cached and when it was cached.
  