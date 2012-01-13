<?php
  $newLink = @new CLinkClass();
  $newLink->category=$category;
  $newLink->description=$description;
  $newLink->url=$newUrl;
  $newLink->createdTimestamp=time();
  $newLink->lastVisitedTimestamp=0;
  $newLink->visitCount=0;
  array_push($_SESSION['links'], $newLink);
  writeLinkFile($bookmarkFile, $_SESSION['links']);
  refreshLinks($SESSION, $bookmarkFile);
  include('templates/list.html');
?>
