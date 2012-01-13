<?php
  // Quick sanity check to make sure we are not getting stale or bogus info
  if ($_SESSION['links'][$index]->url != $url)
  {
    showFatalError("Bookmark garbled!  Please <a href=\"?action=restart\">" .
                   "restart.</a>");
  }
  $_SESSION['links'][$index]->index=$newIndex;
  $_SESSION['links'][$index]->category=$category;
  $_SESSION['links'][$index]->description=$description;
  $_SESSION['links'][$index]->url=$newUrl;
  $_SESSION['links'][$index]->createdTimestamp=$createdTimestamp;
  $_SESSION['links'][$index]->lastVisitedTimestamp=$lastVisitedTimestamp;
  $_SESSION['links'][$index]->visitCount=$visitCount;
  writeLinkFile($bookmarkFile, $_SESSION['links']);
  refreshLinks($SESSION, $bookmarkFile);
  include('templates/list.html');
?>
