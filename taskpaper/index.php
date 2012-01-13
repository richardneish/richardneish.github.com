<?php

session_start();

require_once('inc/init.php');
require_once(APP_PATH.'inc/template.class.php');
global $taskpapers;

$active_taskpaper = $taskpapers->active_taskpaper();

// load the initial view
$main_view = new Template('main');
$main_view->set('title', $config['title'],
                'ajax_file', $config['ajax_file'],
                'start_page', $config['start_page']);

$filetabs_view = new Template('filetabs');
$filetabs_view->set('file_names', $taskpapers->available_files(),
                    'active_index', $taskpapers->active_index());
$main_view->set('filetabs_view', $filetabs_view);

$project_view = new Template('projectlist');
$project_view->set('project_list', $active_taskpaper->all_projects(),
                   'project_header', $lang['project_header']);
$main_view->set('project_view', $project_view);

$tag_view = new Template('taglist');
$tag_view->set('tag_list', $active_taskpaper->all_tags(),
               'done_tag', $lang['done_tag'],
               'star_tag', $lang['star_tag'],
               'due_tag', $lang['due_tag'],
               'tag_header', $lang['tag_header']);
$main_view->set('tag_view', $tag_view);

$task_view = new Template('tasklist');
$task_view->set('task_list', $active_taskpaper->all_tasks(),
                'task_header', $lang['task_header']);
$main_view->set('task_view', $task_view);

$main_view->show();
?>
