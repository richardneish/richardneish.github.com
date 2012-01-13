<!--
The find results list template
-->
<?php include('tpl/tasktools.tpl.php'); ?>
<h1><?php echo $result_header; ?></h1>
<?php
if(!empty($project_results)) {
    echo '<h2>' . $project_header .'</h2>';
    foreach($project_results as $project) {
        echo '<h3>' . $project . '</h3>';
    }
    echo '<br />';
}
?>
<h2><?php echo $task_header; ?></h2>
<?php
if(!empty($task_results)) {
    foreach($task_results as $key => $task) {
        $markup = mark_up_task($task, $key);
        echo $markup;
    }
}
?>
<br>