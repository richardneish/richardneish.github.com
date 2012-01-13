<?PHP

/* 
 *	LinkMgr
 *	-----------
 *	$Id: globals.inc,v 1.2 2003/08/29 00:16:26 richardn Exp $
 *	Usage:		Global Variables and Constants
 *	Description:	This script is to define global variables and constants to use in all files
 *	Author:		Richard Neish <richardn@richardneish.org>
 *	Borrowed from W-AGORA by Stefan Schreyjak <Stefan.Schreyjak@cyperon.de>
 */

if (!defined('_GLOBALS')) {

	define('_GLOBALS', 1);

	$ext		= 'php';
	$main_page	= "index.$ext";
        $bookmarkFile   = 'links.html';

# set $debug= 1 for debugging mode
# --------------------------------
	$debug = 0;

# initialize directories
# ------------------------------
	$bin_dir	= '.';
	$inc_dir	= 'include';
	$cfg_dir	= 'conf';
	$tmpl_dir	= 'templates';
	$user_dir	= 'user';
	$icon_dir	= 'icons';
	$img_dir	= 'images';
	$lang_dir	= 'locales';

# set the current time & formated date 
# -------------------------------------
	$now = time();					// system local time
	$today = mktime (0, 0, 0, date ('m'), date ('d'), date ('Y') ); // Today's (00h00) date
	$bn_tz_offset = isset($bn_tz_offset) ? $bn_tz_offset : 0;	// Default Time zone (overwritten in config file)
	$bn_date_format = isset($bn_date_format) ? $bn_date_format : 'M-Y-d H:i';	// default date format (overwritten in config file)

# set modes for creating directories and files
# --------------------------------------------
	define('_DIRMODE', 0777);
	umask (0);

# Set general directives for PHP engine behaviour
# -----------------------------------------------
	error_reporting (E_ERROR | E_WARNING | E_PARSE);
	php_track_vars;

} // defined
?>
