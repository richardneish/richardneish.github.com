<?php

/** 
 * LinkMgr
 * -------
 * $Id: index.php,v 1.6 2003/09/09 16:38:24 richardn Exp $
 * Usage: main program
 * Author: Richard Neish <richardn@richardneish.com>
 * Borrowed from W-AGORA code by Stefan Schreyjak <Stefan.Schreyjak@cyperon.de>
 */

  require 'include/functions.php';
  require 'globals.inc';

# Setup the session
  ini_set('session.use_cookies', '0');
  ini_set('session.use_trans_sid', '1');
  session_start();
  refreshLinks($bookmarkFile);

# set script to be invoked in the main frame/page
  if (!isset($_REQUEST['action']))
  {
    $action = "list";
  } else {
    $action = $_REQUEST['action'];
  }

  
  if (actionAllowed($action)) {
    include "$action.$ext";
  } else {
    include "accessDenied.php";
  }
?>
