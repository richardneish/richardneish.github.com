<?php
  require 'CLinkClass.php';

  // Debugging function must be commented out in production code
  /*
  function DEBUG($msg)
  {
    echo("DEBUG: " . $msg);
  }
  */
  function DEBUG($msg)
  {
  }

  // Dump an error message and exit
  function showFatalError($msg)
  {
    echo $msg;
    exit;
  }

  /**
   * actionAllowed
   *   Confirm that a user-supplied action is allowed
   */
  function actionAllowed($action) {
    if ($action == 'list' ||
        $action == 'edit' ||
        $action == 'visit') {
      return true;
    }
    return false;
  }

  /**
   * parseLinkFile
   *   Extract a list of CLinkClass objects from a HTML bookmark file
   */
  function parseLinkFile($linkFileName)
  {
    $links = array();
    $index = 0;
    if (!($linkFile = file_get_contents($linkFileName)))
    {
      showFatalError("Cannot open link file '$linkFileName'");
    }
    $openBracePos = strpos($linkFile, '<');
    $closeBracePos = strpos($linkFile, '>', $openBracePos);
    $done = ($closeBracePos == 0 || $linkFile{$openBracePos} != '<');
    while (!$done)
    {
      if ($linkFile{$openBracePos+1} == '/')
      {
        $closeTag = strtolower(substr($linkFile, $openBracePos+2,
                                      $closeBracePos-$openBracePos-2));
        DEBUG("closing tag '$closeTag' detected.\n");
	if ($closeTag == $openTag)
	{
	  $tagElementEnd = $openBracePos;
	  $tagElement = substr($linkFile, $tagElementStart,
	                       $tagElementEnd-$tagElementStart);
          DEBUG("tag element = '$tagElement'\n");
        }
        switch($closeTag)
        {
          case "h2":
            $category = trim($tagElement);
            break;
          case "a":
            $description = trim($tagElement);
            break;
          case "li":
            DEBUG("creating new link ($url, $description, $category)\n");
            array_push($links,
                       new CLinkClass($url, $description, $category,
                                      $createdTimestamp, $lastVisitedTimestamp,
                                      $visitCount, $index));
            $index++;
            break;
        }
      }
      else
      {
        $openTag = substr($linkFile, $openBracePos+1,
                          $closeBracePos-$openBracePos-1);
	$tagElementStart=$closeBracePos+1;
	preg_match_all('/\s(\S*)\s*=\s*"([^"]*)"/', $openTag, $matches,
	               PREG_SET_ORDER);
      // 	array_shift($matches);
	foreach ($matches as $match)
	{
	  $attributes[strtolower($match[1])] = $match[2];
	  DEBUG("Set attrib[$match[1]] = '$match[2]'\n");
	}
        $spacePos = strpos($openTag, ' ');
        if ($spacePos > 0)
	{
	  $openTag = substr($openTag, 0, $spacePos);
	}
        $openTag = strtolower($openTag);
        DEBUG("opening tag '$openTag' detected\n");
        switch ($openTag)
	{
	  case 'a':
            $url = $attributes['href'];
            $createdTimestamp = $attributes['createdtimestamp'];
            $lastVisitedTimestamp = $attributes['lastvisitedtimestamp'];
            $visitCount = $attributes['visitcount'];
	    break;
	}
	    
      }
      if (!($openBracePos = strpos($linkFile, '<', $closeBracePos)))
      {
        DEBUG("done (no more open braces found).\n");
        $done = TRUE;
      }
      if (!($closeBracePos = strpos($linkFile, '>', $openBracePos)))
      {
        DEBUG("done (no more close braces found).\n");
        $done = TRUE;
      }
    }
    return $links;
  }
  
   /**
   * writeLinkFile
   *   Create a HTML bookmark file from an array of CLinkClass objects
   */
  function writeLinkFile($linkFileName, $links)
  {
    $oldCategory = '';
    $linkFile = <<<EOD
<html>
  <body>
    <title>Bookmarks</title>
    <h1>Bookmarks</h1>

EOD;

    foreach ($links as $link)
    {
      if ($link->category != $oldCategory)
      {
        if ($oldCategory != '')
        {
          $linkFile .= <<<EOD
    </ul>
    <hr align="center" width="50%">

EOD;
        }
        $linkFile .= <<<EOD
    <h2>$link->category</h2>
    <ul>

EOD;
        $oldCategory = $link->category;
      }
      $linkFile .= <<<EOD
      <li>
        <a href="$link->url"
           createdTimestamp="$link->createdTimestamp"
           lastVisitedTimestamp="$link->lastVisitedTimestamp"
           visitCount="$link->visitCount">
          $link->description
        </a>
      </li>

EOD;
    }
    $now = date('Y-m-d H:i');
    $linkFile .= <<<EOD
    </ul>
    <hr>
    <address>Generated by LinkMgr on $now</address>
  </body>
</html>

EOD;

    file_put_contents($linkFileName, $linkFile);
  }

  // Returns a list of unique categories from a list of CLinkClass objects
  // The order of the first occurence of categories is preserved
  function getCategories($links)
  {
    $categories = array();
    foreach ($links as $link)
    {
      $result = array_search($link->category, $categories);
      if ($result === NULL || $result === FALSE)
      {
        array_push($categories, $link->category);
      }
    }
    return $categories;
  }
  
  function cmpUrl($a, $b)
  {
    if ($a->url == $b->url)
    {
      if ($a->index == $b->index)
      {
        return 0;
      }
      return ($a->index > $b->index ? +1 : -1);
    }
    return ($a->url > $b->url ? +1 : -1);
  }

  function sortLinksByUrl($links)
  {
    usort($links, "cmpUrl");
    return $links;
  }

  function cmpDescription($a, $b)
  {
    if ($a->description == $b->description)
    {
      if ($a->index == $b->index)
      {
        return 0;
      }
      return ($a->index > $b->index ? +1 : -1);
    }
    return ($a->description > $b->description ? +1 : -1);
  }

  function sortLinksByDescription($links)
  {
    usort($links, "cmpDescription");
    return $links;
  }

  function cmpCategory($a, $b)
  {
    if ($a->category == $b->category)
    {
      if ($a->index == $b->index)
      {
        return 0;
      }
      return ($a->index > $b->index ? +1 : -1);
    }
    return ($a->category > $b->category ? +1 : -1);
  }

  function sortLinksByCategory($links)
  {
    usort($links, "cmpCategory");
    return $links;
  }

  function cmpCreatedTimestamp($a, $b)
  {
    if ($a->createdTimestamp == $b->createdTimestamp)
    {
      if ($a->index == $b->index)
      {
        return 0;
      }
      return ($a->index > $b->index ? +1 : -1);
    }
    return ($a->createdTimestamp > $b->createdTimestamp ? +1 : -1);
  }

  function sortLinksByCreatedTimestamp($links)
  {
    usort($links, "cmpcreatedTimestamp");
    return $links;
  }

  function cmpLastVisitedTimestamp($a, $b)
  {
    if ($a->lastVisitedTimestamp == $b->lastVisitedTimestamp)
    {
      if ($a->index == $b->index)
      {
        return 0;
      }
      return ($a->index > $b->index ? +1 : -1);
    }
    return ($a->lastVisitedTimestamp > $b->lastVisitedTimestamp ? +1 : -1);
  }

  function sortLinksByLastVisitedTimestamp($links)
  {
    usort($links, "cmpLastVistedTimestamp");
    return $links;
  }

  function cmpVisitCount($a, $b)
  {
    if ($a->visitCount == $b->visitCount)
    {
      if ($a->index == $b->index)
      {
        return 0;
      }
      return ($a->index > $b->index ? +1 : -1);
    }
    return ($a->visitCount > $b->visitCount ? +1 : -1);
  }

  function sortLinksByVisitCount($links)
  {
    usort($links, "cmpVisitCount");
    return $links;
  }

  function refreshLinks($bookmarkFile)
  {
    if (!isset($_SESSION['links']))
    {
      $_SESSION['links'] = parseLinkFile($bookmarkFile);
      $_SESSION['links_ctime'] = filectime($bookmarkFile);
    }
    if ($_SESSION['categories_ctime'] < $_SESSION['links_ctime'])
    {
      $_SESSION['categories'] = getCategories($_SESSION['links']);
      $_SESSION['categories_ctime'] = $_SESSION['links_ctime'];
    }
  }
?>
