<!--
The Project list template
-->
<h1><?php echo $project_header; ?></h1>
<ul>
    <li><?php echo $project_list[0]; ?></li>
</ul>
<ol>
<?php 
foreach ($project_list as $key => $project) {
    if($key > 0) {
        echo '<li>' . $project . '</li>';
    } else {
        
    }
}
?>
</ol>