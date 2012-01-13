<!--
The Tag list template
    @param $tag_header
    @param $tag_list
-->
<h1><?php echo $tag_header; ?></h1>
<li><span class="done-tag"><?php echo $done_tag; ?></span></li>
<li><span class="star-tag"><?php echo $star_tag; ?></span></li>
<li><span class="due-tag"><?php echo $due_tag; ?></span></li>
<?php
foreach($tag_list as $tag) {
    echo '<li><span class="tag">' . $tag . '</span></li>';
}
?>

