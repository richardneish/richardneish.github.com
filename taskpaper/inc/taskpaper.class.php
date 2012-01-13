<?php
require_once(APP_PATH.'inc/common.php');

/* The Taskpaper Model
 * @class Taskpapers = the app, all taskpapers, and methods to use them
 * @class Taskpaper = a Taskpaper, corresponds to a text file
 * @class Task = an individual task (stored as string in array)
 * @class TaskSearch => a search for tasks
 */
class Taskpapers {
    private $_available_files = array();    // all available taskpapers in app directory
    private $_active_file = '';
    private $_archive_file = '';
    private $_active_index = '';
    private $_active_taskpaper = null;
    private $_folder = '';

    function __construct($active_file, $archive_file, $taskpaper_folder,
                         $cached_tasks = '', $cached_time = 0) {
        // make sure that the folder name is a suitable default, as this can
        // be changed by user
        $this->_folder = $taskpaper_folder;
        if (empty($taskpaper_folder)) {
            $this->_folder = './';
        } elseif (substr($taskpaper_folder, -1, 1) != '/') {
            $this->_folder .= '/';
        }
        $this->_active_file = $active_file;
        $this->_active_taskpaper = new Taskpaper($this, $this->active_fullpath(), $cached_tasks, $cached_time);
        $this->_archive_file = $archive_file;
        $files = glob($this->_folder . "*" . EXT);
        $this->_available_files = str_replace(array(EXT, $this->_folder), '', $files);
        natcasesort($this->_available_files);
        $this->_active_index = array_search($this->active_file(), $this->_available_files);
    }
    function active_fullpath() {
        return $this->_folder . $this->_active_file . EXT;
    }
    function active_file() {
        return $this->_active_file;
    }
    function active_index() {
        return $this->_active_index;
    }    
    function set_active_taskpaper($list_index) {
        $file_name = $this->_available_files[$list_index];
        $this->_active_file = $file_name;
        $this->_active_taskpaper = new Taskpaper($this, $this->active_fullpath());
        $this->_active_index = $list_index;
        return $file_name;
    }
    function archive_file() {
        return $this->_archive_file;
    }
    function archive_fullpath() {
        return $this->_folder . $this->_archive_file . EXT;
    }
    function available_files() {
        // return list of all taskpaper's in apps folder
        return $this->_available_files;
    }
    function active_taskpaper() {
        return $this->_active_taskpaper;
    }
}

/* model of all tasks in one taskpaper
 * add, remove, edit and search functions
 */
class Taskpaper {
    private $_parent;
    private $_plain_tasks = '';
    private $_tasks = array();
    private $_projects = array();
    private $_projects_index = array();
    private $_task_project = array();
    private $_tags = array();
    private $_file_path = '';
    private $_last_modified;
    private $_is_archive = false;
	
    /* @param $parent = reference to parent Taskpapers instance
     * @param $file_name = file source of task list
     * @param $cached_tasks = plain tasks from current SESSION
     * @param $cached_time = last modified date of cached task list
     *
    */ 
    function __construct(Taskpapers &$parent, $file_path = '', $cached_tasks = '', $cached_time = 0) {
        $this->_parent = &$parent;
        // FIXME: no error checking for missing file!
        $this->_file_path = $file_path;
        $this->_last_modified = filemtime($file_path);
        // tasks could be passed from the current session cache if available
        // TODO: save the full set of lists, to speed things up (i.e. a better cache)
        if(empty($cached_tasks) || $this->_last_modified > $cached_time) {
            $this->_plain_tasks = file_get_contents($this->_file_path);
        } else {
            $this->_plain_tasks = $cached_tasks;
            $this->_last_modified = $cached_time;
        }
        $this->_build_task_lists($this->_plain_tasks);
    }

    /* save the tasks as plain text
     * if necessary, recreate the plain text from the task lists
     * also set modified time of file
     * @param $edited_tasks
     */
    function update($edited_tasks = '') {
        if (empty($edited_tasks)) {
            // cached tasks were edited only
            $edited_tasks = implode("\n", $this->_tasks);
        }
        $this->_build_task_lists($edited_tasks);
        file_put_contents($this->_file_path, $edited_tasks);
        $this->_last_modified = filemtime($this->_file_path);
        return true;
    }

    /* create array lists of Tasks, Projects, Tags, and Task->Project
     * @param $plain_tasks => a user edited plain text list of tasks in taskpaper syntax
     */
    private function _build_task_lists($plain_tasks) {
        global $config;
        global $lang;
        $cur_project = false;
        $cur_project_idx = false;
        // clear existing task data
        $this->_tasks = array();
        $this->_projects = array();
        $this->_task_project = array();
        $this->_tags = array();
        $this->_plain_tasks = $plain_tasks;
        $all_lines = explode("\n", $plain_tasks);
        // first project is for orphaned tasks...
        $this->_projects[0] = $lang['projectless'];
        $project_idx = 1;
        $cur_project_idx = 0;
        $task_idx = 0;
        foreach ($all_lines as $line) {
            // build all the cached lists: tasks, tags, projects
            // plus: project_index => index locations of project header lines
            // plus: task_project => which project this task belongs to
            $line = trim($line);
            // Project header
            if (preg_match($config['heading_rgx'], $line) > 0) {
                $this->_tasks[$task_idx] = $line;
                $cur_project = $line;
                $this->_projects[$project_idx] = $cur_project;
                $this->_projects_index[$task_idx] = $project_idx;
                $cur_project_idx = $project_idx++;
                $task_idx++;
            // Tasks and Notes
            } elseif (preg_match($config['task_rgx'], $line) > 0 || preg_match($config['note_rgx'], $line) > 0 ||
                      preg_match($config['subhead_rgx'], $line) > 0) {
                // collect the task and its project
                $this->_tasks[$task_idx] = $line;
                $this->_task_project[$task_idx] = $cur_project_idx;
                $task_idx++;
            // Blank line between projects
            } elseif (empty($line)) {
                $this->_tasks[$task_idx] = '';
                $cur_project = '';
                $cur_project_idx = 0;
                $task_idx++;
            }
        }
        // get a list of all unique tags
        preg_match_all($config['tag_rgx'], $plain_tasks, $out, PREG_PATTERN_ORDER);
        $this->_tags = array_unique($out[0]);
        natcasesort($this->_tags);
    }

    function plain_tasks() {
        // return all tasks as a text string, ready for editing
        return $this->_plain_tasks;
    }
    
    function all_tasks() {
        // return all tasks, ready for use in a view template
        if(empty($this->_tasks)) {
            return array('No Tasks');
        } else {
            return $this->_tasks;
        }
    }

    function all_tags() {
        //return a list of all tags
        if(empty($this->_tags)) {
            return array('No Tags');
        } else {
            return $this->_tags;
        }
    }

    function all_projects() {
        //return a list of all project names
        if(empty($this->_projects)) {
            return array('No Projects');
        } else {
            return $this->_projects;
        }
    }

    function task($task_id) {
        // returns the specified task item as a ref to the array item
        return new Task($this, $this->_tasks[$task_id], $this->_in_project($task_id));
    }

    private function _in_project($task_id, $no_symbol = false) {
        $project = $this->_projects[$this->_task_project[$task_id]];
        if ($no_symbol === true) {
            $project = substr($project, 0, -1);
        }
        return $project;
    }

    /* adds a new task to the top of this taskpaper
     */
    function add_task($new_task, $project_index = 0) {
        $edited_task = $this->_expand_interval_tags($new_task);
        $max = count($this->_projects) - 1;
        if ($project_index == 0) {
            // insert at top of list (new tasks)
            $edited_tasks = $edited_task . "\n" . $this->_plain_tasks;
            $this->update($edited_tasks);
        } elseif ($project_index == $max) {
            // insert at end of list
            $edited_tasks = $this->_plain_tasks . "\n" . $edited_task;
            $this->update($edited_tasks);
        } elseif ($project_index < $max) {
            // insert into middle
            $insert_here = array_search($project_index + 1, $this->_projects_index) - 1;
            $new_lines = array($edited_task, $this->_tasks[$insert_here]);
            array_splice($this->_tasks, $insert_here, 1, $new_lines);
            $this->update();
        }
    }

    private function _expand_interval_tags($plain_task) {
        global $config;
        global $lang;
        // find any tags
        preg_match_all($config['tag_rgx'], $plain_task, $matches, PREG_SET_ORDER);
        // do they match a time period?
        foreach ($matches as $match) {
            $orig_tag = $match[0];
            $date = $this->_get_interval_as_date($match[1]);
            if ($date !== false) {
                $plain_task = preg_replace('/' . $orig_tag . '/', "@" . date($config['date_format'], $date[1]), $plain_task);
            }
        }
        return $plain_task;
    }

    function del_task($task_id, $has_note = false) {
        unset($this->_tasks[$task_id]);
        if ($has_note === true) {
            unset($this->_tasks[$task_id + 1]);
        }
        $this->update();
    }

    function archive_task($task_id) {
        // move this task to the archive taskpaper
        global $config;
        $note = '';
        $task = $this->_tasks[$task_id];
        $has_note = $this->_has_note($task_id);
        if ($has_note === true) {
            $note = "\n" . $this->_tasks[$task_id + 1];
        }
        $task = "\n" . $task
                . $config['note_prefix']
                . " | " . $this->_parent->active_file()
                . " | ". $this->_in_project($task_id, true)
                . " | " . date("d-M-Y H'i") . " |"
                . $note;
        $file = fopen($this->_parent->archive_fullpath(), "a");
        fwrite($file, $task);
        fclose($file);
        // delete the original now!
        $this->del_task($task_id, $has_note);
	}
        
    private function _has_note($task_id) {
        global $config;
        $note_id = ($task_id <= count($this->_tasks)) ? $task_id + 1 : false;
        if ($note_id !== false) {
            return (substr($this->_tasks[$note_id], 0, 3) == $config['note_prefix']) ? true : false;
        } else {
            return false;
        }
    }

    function unstar_tasks() {
        global $config;
        $this->_plain_tasks = preg_replace('/[' . $config['star_tag'] . ']' . "\n" . '/', "\n", $this->_plain_tasks);
        $this->_plain_tasks = preg_replace('/[' . $config['star_tag'] . ']$/', "", $this->_plain_tasks);
        $this->update($this->_plain_tasks);
    }

	function by_tag($tag) {
        // return a list of tasks filtered by a specific tag
        $find = $this->find();
        $find->filter_by_word($tag);
        return $find->search_result($tag);
	}

	function by_project($project) {
        // returns a specific project
        $project_key = array_search($project, $this->_projects);
        if ($project_key !== false) {
            // get a list of tasks (key only) in this project
            $task_keys = array_keys($this->_task_project, $project_key);
            // now finally extract the right task lines
            $task_keys = array_flip($task_keys);
            $items = array_intersect_key($this->_tasks, $task_keys);
            return $items;
        } else {
            return array();
        }
	}

	function by_tagdue($tagdue) {
        // return tasks by specific tag due dates
        global $config;
        $has_date = preg_match($config['tag_date_rgx'], $tagdue, $matches);
        if ($has_date == 1) {
            $duedate = $matches[0];
            return $this->find()->filter_by($duedate);
        } else {
            return array();
        }
    }

    function by_command($cmd) {
        // returns tasks filtered by a specific command (index or word)
        $find = $this->find();
        $find->filter_by_command($cmd, true);
        return $find->search_result($cmd, true);
    }

    /* return a list of tasks (and optionally projects) filtered by given expression, date or time period
     * assumes a space means 'AND', '-' before a word means exclude (Google style)
     * by default command/time periods begin with '='
     * returns empty array if nothing found
     */
    function find() {
        return new TaskSearch($this, $this->_tasks, $this->_projects_index);
    }

    function is_archive($value) {
        // is this taskpaper the archive?
        // get
        if (empty($value)) {
            return $this->_is_archive;
        // set
        } else {
            $this->_is_archive = $value;
        }
    }

    /* returns any time period expression as a real date to end by
     * used by find() and also when adding time period tags
     */
    function _get_interval_as_date($interval) {
        global $config, $lang;
        preg_match($config['interval_rgx'], $interval, $matches);
        $count = $matches[1];
        $period = $matches[2];
        $count = ($count == null) ? 1 : $count;
        //$index = array_search($period, $lang['interval_names']);
        $matches = preg_grep('/^' . $period . '/', $lang['interval_names']);
        if(!empty($matches)) {
            // note: only first matching time period is used
            $index = key($matches);
            $period = $lang['interval_names'][$index];
            // replace with correct date
            $start_date = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
            switch ($index) {
            case 0: //today
                $end_date = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
                break;
            case 1: //tomorrow
                $end_date = mktime(0, 0, 0, date("m"), date("d") + 1, date("Y"));
                break;
            case 2: //day
                $end_date = mktime(0, 0, 0, date("m"), date("d") + $count, date("Y"));
                break;
            case 3: //week
                $end_date = mktime(0, 0, 0, date("m"), date("d") + (7 * $count), date("Y"));
                break;
            case 4: //month
                $end_date = mktime(0, 0, 0, date("m") + $count, date("d") - 1, date("Y"));
                break;
            case 5: //year
                $end_date = mktime(0, 0, 0, date("m"), date("d"), date("Y") + $count);
                break;
            default:
                return false;;
            }
            $parsed_interval = ($index > 1) ? $config['cmd_prefix'] . $count . $period : $period;
            return array($start_date, $end_date, $parsed_interval);
        } else {
            return false;
        }
    }
}


class Task {
    // refers to a specific task item
    private $_parent;
    private $_task; // task item (reference)
    private $_note;
    private $_project;

	function __construct(Taskpaper &$parent, &$task, $project) {
        //remember we are working with the original task here, not a copy!
        $this->_parent =& $parent;
        $this->_task =& $task;
        $this->_project = $project;
	}

	function toggle_done() {
        // set or unset task as "Done" usually X at begining see config.php
        global $config;
        $done = $config['done_tag'];
        if (substr($this->_task, 0, 1) == $done) {
            $this->_task = substr($this->_task, 1);
        } else {
            $this->_task = $done . $this->_task;
        }
        // update the taskpaper text file also, but not the cache
        $this->_parent->update();
	}

    function toggle_star() {
        // set or unset the star/highlight
        global $config;
        $star = $config['star_tag'];
        if (substr($this->_task, -1, 1) == $star) {
            $this->_task = substr($this->_task, 0, -1);
        } else {
            $this->_task = $this->_task . $star;
        }
        // update the taskpaper text file also, but not the cache
        $this->_parent->update();
	}

    /* to allow inplace updating of tasks
     *
     */
    function value($value = '') {
        // get
        if (empty($value)) {
            return $this->_task;
            // set
        } else {
            $this->_task = $value;
            $this->_parent->update();
        }
    }
    
    function note($value) {
        // get
        if (empty($value)) {
            return $this->_note;
            // set
        } else {
            $this->_note = $value;
        }
    }

    function project() {
        return $this->_project;
    }
}


class TaskSearch {
    private $_parent = Taskpaper;
    private $_tasks = array();
    private $_projects_index = array();
    private $_task_count = 0;

    function __construct(Taskpaper &$parent, $tasks, &$projects_index) {
        $this->_parent = $parent;
        $this->_tasks = $tasks;
        $this->_projects_index = $projects_index;
    }

    function filter_by($expression) {
        // split search expression into tokens (quoted phrases are kept together)
        $tokens = $this->_get_phrase_tokens($expression);
        // filter tasks by each token in sequence
        $token_max = count($tokens) - 1;
        foreach($tokens as $key => &$token) {
            // is it a command? (default begins with = )
            $cmd = $this->_parse_command($token);
            // if last token is a date, then allow grouping
            $in_groups = ($key == $token_max) ? true : false;
            if ($cmd !== false) {
                // if command, is it a time period? (fixed list of words)
                $dates = $this->_parse_interval($cmd);
                if($dates !== false) {
                    list($start_date, $end_date, $parsed_period) = $dates;
                    $this->filter_by_date($start_date, $end_date, $in_groups);
                    $can_ignore = true;
                    $token = $parsed_period;
                    continue;
                } else {
                    $token = $this->filter_by_command($cmd, $in_groups);
                    $can_ignore = true;
                    continue;
                }
            }
            // is it a date or dates? (dates are in " " or have .. between)
            $dates = $this->_parse_date($token);
            if ($dates !== false) {
                list($start_date, $end_date, $operator) = $dates;
                $this->filter_by_date($start_date, $end_date, $operator, $in_groups);
                $can_ignore = true;
                continue;
            }
            // is it a word ( -word => NOT this word )?
            $word = $this->_parse_word($token);
            if ($word !== false) {
                list($text, $exclude) = $word;
                $this->filter_by_word($text, $exclude);
            }
        }
        $ignore_projects = (count($tokens) == 1 && $can_ignore) ? true : false;
        $expression = implode($tokens, ' ');
        return $this->search_result($expression, $ignore_projects);
    }

    /* splits a search expression into words and quoted phrases
     * using space as the basic delimiter, and quoted phrases stay together
     * as a token
     */
    private function _get_phrase_tokens($expression) {
        // quoted phrases are replaced by a marker token {n} and later replaced in token array
        $phrases = preg_match_all('/\"(.+?)\"/', $expression, $matches, PREG_PATTERN_ORDER);
        if($phrases !== false) {
            for($i = 0; $i < $phrases; $i++) {
                $expression = preg_replace('/' . $matches[0][$i] . '/', '{' . $i . '}', $expression);
            }
        }
        $tokens = explode(' ' , $expression);
        // replace and phrases using {n} marker token
        $tokens = preg_replace('/\{(\d)\}/e', '$matches[1]["$1"]', $tokens);
        return $tokens;
    }

    function filter_by_word($word, $exclude = false, $wildcard = '.*') {
        $word = "|" . $wildcard . $word . $wildcard . "|i";
        if ($exclude === true) {
            $this->_tasks = preg_grep($word, $this->_tasks, PREG_GREP_INVERT);
        } else {
            $this->_tasks = preg_grep($word, $this->_tasks);
        }
    }

    function filter_by_date($start_date = 0, $end_date = 0, $operator = '', $in_groups = false) {
        global $config;
        if (!is_int($start_date)) $start_date = strtotime(strtolower(trim($start_date)));
        if (!is_int($end_date)) $end_date = strtotime(strtolower(trim($end_date)));
        if ($start_date !== false) {
            if ($end_date === false || empty($end_date)) {
                switch (trim($operator)) {
                case '>':
                    $end_date = 0;
                    break;
                case '<':
                    $end_date = $start_date;
                    $start_date = 0;
                    break;
                default:
                    $end_date = $start_date;
                }
            }
        } else {
            $this->_tasks = array();
            return;
        }
        $due_tasks = array();
        $due_dates = array();
        $dated_tasks = preg_grep($config['tagdue_rgx'], $this->_tasks);
        if (!empty($dated_tasks)) {
            $show_all_dates = ($start_date == 0 && $end_date == 0) ? true : false;
            foreach ($dated_tasks as $key => $task) {
                $valid_date = preg_match($config['tag_date_rgx'], $task, $matches);
                if ($valid_date > 0) {
                    $date_only = strtotime($matches[1]);
                    if($show_all_dates) {
                        $due_tasks[$key] = $task;
                        $due_dates[$key] = $date_only;
                    } elseif ($start_date == 0) {
                        if ($date_only <= $end_date) {
                            $due_tasks[$key] = $task;
                            $due_dates[$key] = $date_only;
                        }
                    } elseif ($end_date == 0) {
                        if ($date_only >= $start_date) {
                            $due_tasks[$key] = $task;
                            $due_dates[$key] = $date_only;
                        }
                    } else {
                        if (($date_only >= $start_date) & ($date_only <= $end_date)) {
                            $due_tasks[$key] = $task;
                            $due_dates[$key] = $date_only;
                        }
                    }
                }
            }
        }
        // sort tasks by date and month
        if (!empty($due_tasks)) {
            $keys = array_keys($due_tasks);
            array_multisort($due_dates, $due_tasks, $keys);
            $due_tasks = array_combine($keys, $due_tasks);
            $this->_task_count = count($due_tasks);
            $prev_month = '';
            $this->_tasks = array();
            foreach ($due_tasks as $key => $due_task) {
                if ($in_groups) {
                    $month = date("M-Y", current($due_dates));
                    if ($prev_month != $month) {
                        $this->_tasks[$month] = $month;
                        $prev_month = $month;
                    }
                }
                $this->_tasks[$key] = $due_task;
                next($due_dates);
            }
        } else {
            $this->_tasks = array();
        }
    }

    function filter_by_command($cmd, $in_groups = false) {
        global $config, $lang;
        if(is_int($cmd) && ($cmd < count($lang['command_names']))) {
            $index = $cmd;
        } else {
            // allow for paartial matches also, use only first match
            $matches = preg_grep('/^' . $cmd . '/', $lang['command_names']);
            $index = (!empty($matches)) ? key($matches) : false;
        }
        if($index !== false && $index < 4) {
            switch($index) {
            case 0; // due (i.e. all dated items)
                $this->filter_by_date(0, 0, '', $in_groups);
                break;
            case 1: // overdue/late items
                $this->filter_by_date(0, time(), '', $in_groups);
                break;
            case 2: // starred items
                $this->filter_by_word($config['find_star_rgx'], false, '');
                break;
            case 3: // done/complete items
                $this->filter_by_word($config['find_done_rgx'], false, '');
                break;              
            }
            return $config['cmd_prefix'] . $lang['command_names'][$index];
        }
        $this->_tasks = array();
        return false;
    }

    function search_result($title, $ignore_projects = false) {
        if ($ignore_projects === false) {
            $tasks = array_diff_key($this->_tasks, $this->_projects_index);
            $projects = array_intersect_key($this->_tasks, $this->_projects_index);
            $tasks = ($tasks == null) ? array() : $tasks;
            $projects = ($projects == null) ? array() : $projects;
            $project_count = count($projects);
            $task_count = count($tasks);
        } else {
            $tasks = $this->_tasks;
            $projects = array();
            $project_count = 0;
            $task_count = ($this->_task_count > 0) ? $this->_task_count : count($this->_tasks);
        }
        return new SearchResult($projects, $tasks, $project_count, $task_count, $title);
    }

    private function _parse_command($token) {
        global $config;
        $is_cmd = preg_match($config['find_cmd_rgx'], $token, $matches);
        return ($is_cmd == 1) ? $matches[1] : false;
    }

    private function _parse_interval($token) {
        $dates = $this->_parent->_get_interval_as_date($token);
        return ($dates !== false) ? $dates : false;
    }

    private function _parse_date($token) {
        global $config;
        $has_dates = preg_match($config['date_between_rgx'], $token, $matches);
        if ($has_dates == 1) {
            $start_date = $matches[1];
            $end_date = $matches[4];
            return array($start_date, $end_date, '');
        }
        $has_dates = preg_match($config['date_single_rgx'], $token, $matches);
        if ($has_dates == 1) {
            $start_date = $matches[2];
            $operator = $matches[1];
            return array($start_date, 0, $operator);
        }
        return false;
    }
    
    /* returns $token, and $exclude (true if $token should be excluded)
     */
    private function _parse_word($token) {
        $exclude = (substr($token, 0, 1) == '-') ? true : false;
        if ($exclude === false) {
            return array($token, $exclude);
        } else {
            return array(substr($token, 1), $exclude);
        }
    }
}

/* stores the result of a search
 * allows access to various lists:
 * i.e. Projects found, tasks found, project count, task count, expression used
 */
class SearchResult {
    private $_projects, $_tasks, $_project_count, $_task_count, $_expression;

    function __construct($projects, $tasks, $project_count, $task_count, $expression) {
        $this->_projects = $projects;
        $this->_tasks = $tasks;
        $this->_projects_count = $projects_count;
        $this->_task_count = $task_count;
        $this->_expression = $expression;
    }
    function projects() {
        return $this->_projects;
    }
    function tasks() {
        return $this->_tasks;
    }
    function project_count() {
        return $this->_project_count;
    }
    function task_count() {
        return $this->_task_count;
    }
    function name() {
        return $this->_expression;
    }
}

abstract class TokenFilter {
    private $_token;
    private $_hits;
    protected static $matched_token = '';
    protected static $matched_tokens = array();
    protected static $regex = ''; //needs to be static to be used by the match function
    
    function __construct($token) {
        $this->_token = $token;
    }
    // true if this token matches (matching order is important!)
    static function match($token) {
    }
    static function _find_match($token = '', $pattern = '') {
        global $config;
        if (pattern != '') {
            self::$regex = $pattern;
        } else {
            $pattern = self::$pattern;
        }
        $found_match = false;
        if ($token == '' && !empty(self::$matched_tokens)) {
            $found_match = true;
        } elseif ($token != '') {
            $matched = preg_match($pattern, $token, $matches);
            if ($matched !== false) {
                self::$matched_tokens = $matches;
                $found_match = true;
            }
        }
        return $found_match;
    }
    // return the tasks filtered by this token only
    function filter_by(array $tasks) {
    }
    // return the token as understood by parser (handy for partial  matches)
    static function matched_token() {
        return self::$matched_token;
    }
    // returns the different parts of the token (prefix, word, operator, etc...)
    static function current_matches(array $value = array()) {
        if (empty($value)) {
            return self::$current_tokens;
        } else {
            self::$matched_tokens = $value;
        }
    }
    function hits() {
        return $this->_hits;
    }
}

abstract class DateTokenFilter extends TokenFilter {
    private $_group_dates;
    function group_dates($value = false) {
        // get
        if (empty($value)) {
            return $this->_group_dates;
            // set
        } else {
            $this->_group_dates = (bool) $value;
        }
    }
    protected function _filter_by_date(array $tasks, $start_date = 0, $end_date = 0, $operator = '') {
        global $config;
        if (!is_int($start_date)) $start_date = strtotime(strtolower(trim($start_date)));
        if (!is_int($end_date)) $end_date = strtotime(strtolower(trim($end_date)));
        if ($start_date !== false) {
            if ($end_date === false || empty($end_date)) {
                switch (trim($operator)) {
                case '>':
                    $end_date = 0;
                    break;
                case '<':
                    $end_date = $start_date;
                    $start_date = 0;
                    break;
                default:
                    $end_date = $start_date;
                }
            }
            $due_tasks = array();
            $due_dates = array();
            $dated_tasks = preg_grep($config['tagdue_rgx'], $tasks);
            if (!empty($dated_tasks)) {
                $show_all_dates = ($start_date == 0 && $end_date == 0) ? true : false;
                foreach ($dated_tasks as $key => $task) {
                    $valid_date = preg_match($config['tag_date_rgx'], $task, $matches);
                    if ($valid_date > 0) {
                        $task_date = strtotime($matches[1]);
                        if($show_all_dates) {
                            $add_task = true;
                        } elseif ($start_date == 0 && $task_date <= $end_date) {
                            $add_task = true;
                        } elseif ($end_date == 0 && $task_date >= $start_date) {
                            $add_task = true;
                        } elseif (($task_date >= $start_date) && ($task_date <= $end_date)) {
                            $add_task = true;
                        }
                        if ($add_task === true) {
                            $due_tasks[$key] = $task;
                            $due_dates[$key] = $task_date;
                        }
                    }
                }
            }
            // sort tasks by date and month
            if (!empty($due_tasks)) {
                $keys = array_keys($due_tasks);
                array_multisort($due_dates, $due_tasks, $keys);
                $due_tasks = array_combine($keys, $due_tasks);
                $this->_hits = count($due_tasks);
                $prev_month = '';
                $tasks = array();
                foreach ($due_tasks as $key => $due_task) {
                    if ($this->_group_dates() === true) {
                        $month = date("M-Y", current($due_dates));
                        if ($prev_month != $month) {
                            $tasks[$month] = $config['proj_suffix'] . $month;
                            $prev_month = $month;
                        }
                    }
                    $tasks[$key] = $due_task;
                    next($due_dates);
                }
                return $tasks;
            }
        }
        return array();
    }
    /* returns any time period expression as a real date to end by
     * used by find() and also when adding time period tags
     */

}

class WordFilter extends TokenFilter {
    static function match($token) {
        global $config;
        return parent::_find_match($token, $config['word_tok_rgx']);
    }
    function filter_by(array $tasks, $token = '') {
        if (parent::_find_match($token) === true) {
            $word = self::$matched_tokens[2];
            $exclude = self::$matched_tokens[1];
            $this->_matched_token = $word;
            $word = "|" . $wildcard . $word . $wildcard . "|i";
            if ($exclude == '-') {
                return preg_grep(substr($word, 1), $tasks, PREG_GREP_INVERT);
            } else {
                return preg_grep($word, $tasks);
            }
        } else {
            return $tasks;
        }
    }
}

class CommandFilter extends TokenFilter {
    static function match($token) {
        global $config;
        return parent::_find_match($token, $config['cmd_tok_rgx']);
    }
    function filter_by(array $tasks, $token = '') {
        global $lang;
        if (parent::_find_match($token) === true) {
            $prefix = self::$matched_tokens[0];
            $cmd = self::$matched_tokens[1];
            // allow for partial matches also, use only first match
            $matches = preg_grep('/^' . $cmd . '/', $lang['command_names']);
            $index = (!empty($matches)) ? key($matches) : false;
            return $this->filter_by_index($tasks, $index);
        } else {
            return $tasks;
        }
    }
    function filter_by_index(array $tasks, $index) {
        global $config, $lang;
        if(is_int($index) && ($index < count($lang['command_names']))) {
            switch($index) {
            case 0; // due (i.e. all dated items)
                $this->filter_by_date($tasks, 0, 0, '');
                break;
            case 1: // overdue/late items
                $this->filter_by_date($tasks, 0, time(), '');
                break;
            case 2: // starred items
                $this->filter_by_word($tasks, $config['find_star_rgx'], false, '');
                break;
            case 3: // done/complete items
                $this->filter_by_word($tasks, $config['find_done_rgx'], false, '');
                break;
            }
            $this->_matched_token = $config['cmd_prefix'] . $lang['command_names'][$index];
        }
        return $tasks;
    }
}

class DateFilter extends DateTokenFilter {
    static function match($token) {
        global $config;
        return parent::_find_match($token, $config['date_tok_rgx']);
    }
    function filter_by(array $tasks, $token = '') {
        if (parent::_find_match($token) === true) {
            $operator = self::$matched_tokens[1];
            $date = self::$matched_tokens[2];
            $this->_matched_token = $token;
            return $this->filter_by_date($tasks, $date, 0, $operator, $this->group_dates());
        } else {
            return $tasks;
        }
    }
}

class RangeFilter extends DateTokenFilter {
    static function match($token) {
        global $config;
        return parent::_find_match($token, $config['range_tok_rgx']);
    }
    function filter_by(array $tasks, $token = '') {
        if (parent::_find_match($token) === true) {
            $start_date = self::$matched_tokens[1];
            $end_date = self::$matched_tokens[2];
            $this->_matched_token = $token;
            return $this->filter_by_date($tasks, $start_date, $end_date, '', $this->group_dates());
        } else {
            return $tasks;
        }
    }
}

class IntervalFilter extends DateTokenFilter {
    static function match($token) {
        global $config;
        $maybe = parent::_find_match($token, $config['interval_tok_rgx']);
        $matched = false;
        if ($maybe === true) {
            $matches = preg_grep('/^' . $interval . '/', $lang['interval_names']);
            $interval = (!empty($matches)) ? $matches[0] : false;
            if ($interval !== false) {
                self::$matched_tokens[2] = $interval;
                self::$matched_tokens[3] = key($matches);
                $matched = true;
            }
        }
        if ($matched === false) {
            self::$matched_tokens = array();
        }
        return $matched;
    }
    function filter_by(array $tasks, $token = '') {
        if (parent::_find_match($token) === true) {
            $count = self::$matched_tokens[1];
            $interval = self::$matched_tokens[2];
            $index = self::$matched_tokens[3];
            self::$matched_token = $config['cmd_prefix'] . $count . $interval;
            list($startdate, $end_date) = self::interval_as_date($index, $count);
            return $this->_filter_by_date($tasks, $start_date, $end_date, '', $this->group_dates());
        } else {
            return $tasks;
        }
    }
    static function interval_as_date($interval_index, $count = 1) {
        global $config, $lang;
        // replace with correct date
        $start_date = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        switch ($index) {
        case 0: //today
            $end_date = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
            break;
        case 1: //tomorrow
            $end_date = mktime(0, 0, 0, date("m"), date("d") + 1, date("Y"));
            break;
        case 2: //day
            $end_date = mktime(0, 0, 0, date("m"), date("d") + $count, date("Y"));
            break;
        case 3: //week
            $end_date = mktime(0, 0, 0, date("m"), date("d") + (7 * $count), date("Y"));
            break;
        case 4: //month
            $end_date = mktime(0, 0, 0, date("m") + $count, date("d") - 1, date("Y"));
            break;
        case 5: //year
            $end_date = mktime(0, 0, 0, date("m"), date("d"), date("Y") + $count);
            break;
        default:
            return false;;
        }
        return array($start_date, $end_date);
    }
}
?>