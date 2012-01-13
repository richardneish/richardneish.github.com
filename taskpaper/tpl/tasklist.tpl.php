<!--
The main Task list template
-->
<?php include('tpl/tasktools.tpl.php'); ?>
<h1> <?php echo $task_header; ?> </h1>
<?php
foreach($task_list as $key => $task) {
    echo mark_up_task($task, $key);
}
?>