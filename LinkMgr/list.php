<?php
  if (isset($category))
  {
    if (isset($PHPSESSID)) {
      $url = "index.php?PHPSESSID=$PHPSESSID";
    }
    else
    {
      $url = 'index.php';
    }
    header("location: $url#$category\r\n");
  }
  else
  {
    include 'templates/list.html';
  }
?>
