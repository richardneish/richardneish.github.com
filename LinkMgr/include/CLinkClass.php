<?php
  class CLinkClass
  {
    var $url;
    var $description;
    var $category;
    var $createdTimestamp;
    var $lastVisitedTimestamp;
    var $visitCount;
    var $index;

    function CLinkClass($url, $description, $category, $createdTimestamp,
                        $lastVisitedTimestamp, $visitCount, $index)
    {
      DEBUG("in CLinkClass constructor (
  $url,
  $description,
  $category,
  $createdTimestamp,
  $lastVisitedTimestamp,
  $visitCount,
  $index
)\n");
      $this->url = $url ? $url : '';
      $this->description = $description ? $description : 'New link';
      $this->category = $category ? $category : 'Unfiled';
      $this->createdTimestamp = $createdTimestamp ? $createdTimestamp: time();
      $this->lastVisitedTimestamp =
             $lastVisitedTimestamp ? $lastVisitedTimestamp : 0;
      $this->visitCount = $visitCount ? $visitCount : 0;
      $this->index = $index ? $index : 0;
    }
 }
?>
