<!--

-->
<?php include('tpl/tasktools.tpl.php'); ?>
<h1><?php echo $project_header; ?></h1>
<?php
foreach($project_tasks as $key => $task) {
    echo mark_up_task($task, $key);
}
?>

