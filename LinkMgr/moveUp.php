<?php
  if ($index > 0)
  {
    $tmp = $_SESSION['links'][$index];
    $_SESSION['links'][$index] = $_SESSION['links'][$index - 1];
    $_SESSION['links'][$index - 1] = $tmp;
  }
  include 'templates/list.html';
?>
