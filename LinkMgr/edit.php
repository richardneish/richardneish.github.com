<?php
  // Quick sanity check to make sure we are not getting stale or bogus info
  if ($_SESSION['links'][$index]->url != $url)
  {
    showFatalError("Bookmark garbled!  Please <a href=\"?action=restart\">" .
                   "restart.</a>");
  }
  $link = $_SESSION['links'][$index];
  include 'templates/edit.html';
?>
