<?php

session_start();

require_once('inc/init.php');
require_once(APP_PATH.'inc/template.class.php');

$active_taskpaper = $taskpapers->active_taskpaper();
RespondToEvents();

//**************************************************

function changefile_response($params) {
    global $taskpapers;
    global $active_taskpaper;
    global $ini;
    $fileindex = $params['value'];
    $filename = $taskpapers->set_active_taskpaper($fileindex);
    $ini->item('active_taskpaper', $filename);
    $ini->save();
    $active_taskpaper = $taskpapers->active_taskpaper();
    startpage_response();
}

function saveclick_response($params) {
    global $active_taskpaper;
	$updated_tasks = $params['value'];
    if(!empty($updated_tasks)) {
        $active_taskpaper->update($updated_tasks);
        startpage_response();
    }
}

function editclick_response($params) {
    global $active_taskpaper;
    echo $active_taskpaper->plain_tasks();
}

function findtext_response($params) {
    global $active_taskpaper;
    global $config;
    global $lang;
	$find_text = $params['value'];
	if (!empty($find_text)) {
        // various command options first
        $add_task = strpos($find_text, $config['task_prefix']);
        if($add_task !== false && $add_task == 0) {
            // where to add the task?
            $in_project = 0;
            list($prev_state, $prev_value) = explode("|", $params['previous']);
            $has_project = preg_match($config['in_proj_rgx'], $find_text, $matches);
            if($has_project !== false && $has_project != 0) {
                $in_project = $matches[1];
                $find_text = substr($find_text, 0, - strlen($matches[0]));
                $active_taskpaper->add_task($find_text, $in_project);
                startpage_response(true);
            } elseif($prev_state == 'projectclick') {
                $in_project = array_search($prev_value, $active_taskpaper->all_projects());
                $active_taskpaper->add_task($find_text, $in_project);
                _refresh_view($params['previous']);
            } else {
                $active_taskpaper->add_task($find_text);
                startpage_response(true);
            }
        } else {
            $result = $active_taskpaper->find()->filter_by($find_text);
            _display_results($result, $lang['search_results'] . $result->name());
        }
    } else {
        startpage_response();
    }
}

function finddone_response() {
    global $active_taskpaper;
    global $lang;
    $result = $active_taskpaper->by_command(CMD_DONE);
    _display_results($result, $lang['done_tag']);
}

function findstar_response() {
    global $active_taskpaper;
    global $lang;
    $result = $active_taskpaper->by_command(CMD_STAR);
    _display_results($result, $lang['star_tag']);
}

function finddue_response() {
    global $active_taskpaper;
    global $lang, $config;
    $result = $active_taskpaper->by_command(CMD_DUE);
    _display_results($result, $lang['due_tag'] . ' (today is ' . date($config['date_format'], time()) . ')');
}

function tagclick_response($params) {
    global $active_taskpaper;
    $tag = $params['value'];
    $result = $active_taskpaper->by_tag($tag);
    _display_results($result);
}

function tagdueclick_response($params) {
    global $active_taskpaper;
    $tagdue = $params['value'];
    $result = $active_taskpaper->by_tagdue($tagdue);
    _display_results($result);
}

function projectclick_response($params) {
    global $active_taskpaper;
    $project = $params['value'];
    $tasks = $active_taskpaper->by_project($project);
    $results_view = new Template('project');
    $results_view->set('project_header', $project,
                       'project_tasks', $tasks);
    $results_view->show();
}

function doneclick_response($params) {
    global $active_taskpaper;
    $task = $active_taskpaper->task($params['value']);
    $task->toggle_done();
    _refresh_view($params['current']);
}

function starclick_response($params) {
    global $active_taskpaper;
    $task = $active_taskpaper->task($params['value']);
    $task->toggle_star();
    _refresh_view($params['current']);
}

function staroffclick_response($params) {
    global $active_taskpaper;
    $active_taskpaper->unstar_tasks();
    _refresh_view($params['current']);
}

function archiveclick_response($params) {
    global $active_taskpaper;
    $active_taskpaper->archive_task($params['value']);
    _refresh_view($params['current']);
}

function deleteclick_response($params) {
    global $active_taskpaper;
    $active_taskpaper->del_task($params['value']);
    _refresh_view($params['current']);
}

function startpage_response($params = null) {
    global $active_taskpaper;
    global $lang;
    $task_view = new Template('tasklist');
    $task_view->set('task_list', $active_taskpaper->all_tasks(),
                    'task_header', $lang['task_header']);
    $task_view->show();
    if($params === true) {
        echo "||true";
    }
}

/* return the updated project and tag lists
 */
function update_sidebars_response(){
    global $active_taskpaper;
    global $lang;
    $project_view = new Template('projectlist');
    $project_view->set('project_list', $active_taskpaper->all_projects(),
                       'project_header', 'Projects');
    $tag_view = new Template('taglist');
    $tag_view->set('tag_list', $active_taskpaper->all_tags(),
                   'done_tag', $lang['done_tag'],
                   'star_tag', $lang['star_tag'],
                   'due_tag', $lang['due_tag'],
                   'tag_header', 'Tags');
    $lists = $project_view->fetch() . "||" . $tag_view->fetch();
    echo $lists;
}


function _refresh_view($current_state) {
    // update to previous state
    list($event, $value) = explode('|', $current_state);
    $event .= '_response';
    $params['value'] = $value;
    if (function_exists($event)) {
        call_user_func($event, $params);
    }
}

function _display_results(SearchResult $result, $result_header = '') {
    $proj_count = $result->project_count();
    $proj_plural = ($proj_count == 1) ? '' : 's';
    $task_count = $result->task_count();
    $task_plural = ($task_count == 1) ? '' : 's';
    $project_results = new Template('results');
    $result_header = ($result_header == '') ? $result->name() : $result_header;
    $project_results->set('result_header', $result_header,
                          'project_header', $proj_count . ' Project' . $proj_plural,
                          'project_results', $result->projects(),
                          'task_header', $task_count . ' Task' . $task_plural,
                          'task_results', $result->tasks());
    $project_results->show();
}

//*********************************************************************

function RespondToEvents() {
	// assumes that each POST or GET request will
	// include a 'event' field, identifying the event-raising function in js
	// this is used to call the correct PHP function based on a simple naming convention
	if (isset($_POST['event'])) {
		$event = $_POST['event'] . '_response';
		if (function_exists($event)) {
			call_user_func($event, $_POST);
		}
	} elseif (!empty($_GET['event'])) {
		$event = $_GET['event'] . '_response';
		if (function_exists($event)) {
			call_user_func($event, $_GET);
		}		
	}
}
?>