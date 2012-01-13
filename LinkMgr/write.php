<?php
  writeLinkFile($bookmarkFile, $_SESSION['links']);
  refreshLinks($SESSION, $bookmarkFile);
  include('templates/list.html');
?>
