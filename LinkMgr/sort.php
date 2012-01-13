<?php
  if (isset($sort))
  {
    switch ($sort)
    {
      case 'url':
        $_SESSION['links'] = sortLinksByUrl($_SESSION['links']);
        break;
      case 'description':
        $_SESSION['links'] = sortLinksByDescription($_SESSION['links']);
        break;
      case 'category':
        $_SESSION['links'] = sortLinksByCategory($_SESSION['links']);
        break;
      case 'createdTimestamp':
        $_SESSION['links'] = sortLinksByCreatedTimstamp($_SESSION['links']);
        break;
      case 'lastVisitedTimestamp':
        $_SESSION['links'] = sortLinksBylastVisitedTimestamp($_SESSION['links']);
        break;
      case 'visitCount':
        $_SESSION['links'] = sortLinksByVisitCount($_SESSION['links']);
        break;
    }
  }
  include 'templates/list.html';
?>
