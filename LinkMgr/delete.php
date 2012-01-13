<?php
  if ($token != $_SESSION['token'] ||
      $_SESSION['links'][$index]->url != $url)
  {
    showFatalError("Bookmark garbled!  Please <a href=\"?action=restart\">" .
                   "restart.</a>");
  }
  if ($_SESSION['links_ctime'] < filectime($bookmarkFile))
  {
    showFatalError("Stale bookmark list detected!  " .
                   "Please <a href=\"?action=restart\">restart</a>");
  }
  unset($_SESSION['links'][$index]);
  $_SESSION['links'] = array_values($_SESSION['links']);
  writeLinkFile($bookmarkFile, $_SESSION['links']);
  refreshLinks($SESSION, $bookmarkFile);
  include 'templates/list.html';
?>
