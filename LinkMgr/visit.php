<?php
  $index = $REQUEST['index'];
  $url = $REQUEST['url'];

  // Quick sanity check to make sure we are not getting stale or bogus info
  if ($_SESSION['links'][$index]->url != $url)
  {
    showFatalError("Bookmark garbled!  Please <a href=\"?action=restart\">" .
                   "restart.</a>");
  }
  $_SESSION['links'][$index]->lastVisitedTimestamp=time();
  $_SESSION['links'][$index]->visitCount++;
  writeLinkFile($bookmarkFile, $_SESSION['links']);
  refreshLinks($bookmarkFile);
  header("Location: {$_SESSION['links'][$index]->url}\r\n");
?>
