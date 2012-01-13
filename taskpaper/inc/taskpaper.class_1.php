<?php
require_once(APP_PATH.'inc/common.php');

/** The Taskpaper Model
 * @class Taskpapers = the app, all taskpapers, and methods to use them
 * @class Taskpaper = a Taskpaper, corresponds to a text file
 * @class Task = an individual task (stored as string in array)
 * @class TaskSearch => a search for tasks
 **/
class Taskpapers {
    private $_available_files = array();    // all available taskpapers in app directory
    private $_active_file = '';
    private $_archive_file = '';
    private $_active_taskpaper = Taskpaper;
    private $_folder = '';

    function __construct($active_file, $archive_file, $taskpaper_folder) {
        // make sure that the folder name is a suitable default, as this can
        // be changed by user
        $this->_folder = $taskpaper_folder;
        if (empty($taskpaper_folder)) {
            $this->_folder = './';
        } elseif (substr($taskpaper_folder, -1, 1) != '/') {
            $this->_folder .= '/';
        }
        $this->_active_file = $active_file;
        $this->_active_taskpaper = new Taskpaper($this, $this->active_fullpath());
        $this->_archive_file = $archive_file;
        $files = glob($this->_folder . "*" . EXT);
        $this->_available_files = str_replace(array(EXT, $this->_folder), '', $files);
        natcasesort($this->_available_files);
    }
    function active($name = '') {
        if ($name == '') {
            return $this->_active_taskpaper;
        } else {
            if (array_search($name, $this->_available_files) !== false) {
            $this->_active_file = $name;
            $this->_active_taskpaper = new Taskpaper($this, $this->active_fullpath());
            return $this->_active_taskpaper;
            }
        }
    }
    function active_file() {
        return $this->_active_file;
    }
    function active_fullpath() {
        return $this->_folder . $this->_active_file . EXT;
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
}


/* model of all tasks in one taskpaper
 * add, remove, edit and search functions
 */
class Taskpaper {
    public $data = TaskpaperData;
    public $tasks = Tasks;
    private $_parent = Taskpapers;
    private $_file_path = '';
    private $_is_archive = false;
	
   /** @param $parent = reference to parent Taskpapers instance
     * @param $file_name = file source of task list
     * @param $cached_tasks = plain tasks from current SESSION
     * @param $cached_time = last modified date of cached task list
     *
     */
    function __construct(Taskpapers &$parent, $file_name) {
        $this->_parent = &$parent;
        $this->data = new TaskpaperData($file_name);
        $this->data->update();
        $this->tasks = new Tasks($this->data);
    }
    function tasks() {
        return new Tasks($this->data);
    }
    function plain_tasks() {
        return $this->data->plain_tasks;
    }
    function tags() {
        //return a list of all tags
        if(empty($this->data->tags)) {
            return array('No Tags');
        } else {
            return $this->data->tags;
        }
    }
    function projects() {
        //return a list of all project names
        if(empty($this->data->projects)) {
            return array('No Projects');
        } else {
            return $this->data->projects;
        }
    }
    /* return a list of tasks (and optionally projects) filtered by given expression, date or time period
     * assumes a space means 'AND', '-' before a word means exclude (Google style)
     * by default command/time periods begin with '='
     * returns empty array if nothing found
     */
    function filter() {
        return new TaskSearchFilter($this->data);
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
}


class Tasks {
    private $_data = TaskpaperData;
    function __construct(TaskpaperData $data) {
        $this->_data = $data;
    }
    function add($new_task, $into_project = 0) {
        $edited_task = $this->_expand_interval_tags($new_task);
        $max = count($this->_data->projects) - 1;
        if ($into_project == 0) {
            // insert at top of list (new tasks)
            $edited_tasks = $edited_task . "\n" . $this->_plain_tasks;
            $this->save_edits($edited_tasks);
        } elseif ($into_project == $max) {
            // insert at end of list
            $edited_tasks = $this->_plain_tasks . "\n" . $edited_task;
            $this->save_edits($edited_tasks);
        } elseif ($into_project < $max) {
            // insert into middle
            $insert_here = array_search($into_project + 1, $this->_projects_index) - 1;
            $new_lines = array($edited_task, $this->_tasks[$insert_here]);
            array_splice($this->_data->tasks, $insert_here, 1, $new_lines);
            $this->update();
        }
    }
    function delete($key) {
        unset($this->_data->tasks[$key]);
        // TODO: check if task nas a note automatically
        if ($has_note === true) {
            unset($this->_tasks[$key + 1]);
        }
        $this->update();
    }
    function item($key) {
        // returns the specified task item as a ref to the array item
        return new Task($this, $this->_data->tasks[$key], $this->_in_project($key));
    }
    function archive($key) {
        // move this task to the archive taskpaper
        global $config;
        $note = '';
        $task = $this->_data->tasks[$key];
        $has_note = $this->_has_note($key);
        if ($has_note === true) {
            $note = "\n" . $this->_tasks[$key + 1];
        }
        $task = "\n" . $task
                . $config['note_prefix']
                . " | " . $this->_parent->active_file()
                . " | ". $this->_in_project($key, true)
                . " | " . date("d-M-Y H'i") . " |"
                . $note;
        $file = fopen($this->_parent->archive_fullpath(), "a");
        fwrite($file, $task);
        fclose($file);
        // delete the original now!
        $this->delete($key);

    }
    function all() {
        // return all tasks, ready for use in a view template
        if(empty($this->_data->tasks)) {
            return array('No Tasks');
        } else {
            return $this->_data->tasks;
        }
    }
    function no_star() {
        global $config;
        $this->_plain_tasks = preg_replace('/[' . $config['star_tag'] . ']' . "\n" . '/', "\n", $this->_plain_tasks);
        $this->_plain_tasks = preg_replace('/[' . $config['star_tag'] . ']$/', "", $this->_plain_tasks);
        $this->update($this->_plain_tasks);
    }
    function done($value) {

    }
    function plain_tasks() {
        // return all tasks as a text string, ready for editing
        return $this->_data->plain_tasks;
    }
    private function _in_project($key, $no_suffix = false) {
        $project = $this->_data->projects[$this->_data->task_project[$key]];
        if ($no_suffix === true) {
            $project = substr($project, 0, -1);
        }
        return $project;
    }
    private function _expand_interval_tags($plain_task) {
        //TODO: get_interval function needs updating
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
}


class Task {
    // refers to a specific task item
    private $_data;
    private $_task; // task item (reference)
    private $_note;

    function __construct(TaskpaperData &$data, $key) {
        //remember we are working with the original task here, not a copy!
        $this->_data = &$data;
        $this->_task = &$this->_data->tasks[$key];
    }
    /**set or unset task as "Done" usually X at begining see config.php
     * @global array $config
     * @param boolean $value
     * @return boolean 
     */
    function done($value = null) {
        global $config;
        $done = $config['done_tag'];
        if ($value === false) {
            $this->_task = substr($this->_task, 1);
        } elseif ($value === true) {
            $this->_task = $done . $this->_task;
        } elseif ($value == null) {
            return (substr($this->_task, 1) == $done) ? true : false;
        }
        // update the taskpaper text file also, but not the cache
        $this->_data->update();
    }

    function star($value = null) {
        // set or unset the star/highlight
        global $config;
        $star = $config['star_tag'];
        if ($value === false) {
            $this->_task = substr($this->_task, 0, -1);
        } elseif ($value === true) {
            $this->_task = $this->_task . $star;
        } elseif ($value == null) {
            return (substr($this->_task, 0, -1) == $star) ? true : false;
        }
        // update the taskpaper text file also, but not the cache
        $this->_data->update();
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
            $this->_data->update();
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
}


/* local cache of all taskpaper data
 * includes functions to build and refresh the data
 */
class TaskpaperData {
    public $plain_tasks = '';
    public $tasks = array();
    public $tags = array();
    public $projects = array();
    public $project_index = array();
    public $task_project = array();
    public $line_types = array();
    public $modified_time = 0;
    private $_cache_name = '';
    private $_file_name = '';

    function __construct($file_name) {
        $this->_cache_name = $cache_name;
        $this->_file_name = $file_name;
    }
    /* update from cache to/from taskpaper file or session (newest only)
     * both are from disk, however the session cache is pre-built
     * session may be replaced by memory cache later
     * NOTE: no check if file exists yet
     */
    function update() {
        $session_time = $_SESSION['modified_time'];
        $file_time = filemtime($file_name);
        if ($session_time == $file_time) {
            $this->_from_session();
        } else {
            $this->plain_tasks = file_get_contents($this->_file_path);
            $this->_build_task_lists($this->plain_tasks);
            $this->_to_session($file_time);
        }
    }
    private function _to_session($time = 0) {
        $_SESSION['tasks'] = $this->tasks;
        $_SESSION['tags'] = $this->tags;
        $_SESSION['projects'] = $this->projects;
        $_SESSION['project_index'] = $this->project_index;
        $_SESSION['task_project'] = $this->task_project;
        $_SESSION['modified_time'] = ($time > 0) ? $time : time();
    }
    private function _from_session() {
        $this->tasks = $_SESSION['tasks'];
        $this->tags = $_SESSION['tags'];
        $this->projects = $_SESSION['projects'];
        $this->project_index = $_SESSION['project_index'];
        $this->task_project = $_SESSION['task_project'];
    }
    /* save user edits (or internal edits if blank) to session and file
     *
     */
    function save_changes($edited_tasks = '') {
        if (!empty($edited_tasks)) {
            // cached tasks were edited only
            $edited_tasks = implode("\n", $this->tasks);
        }
        $this->_build_task_lists($edited_tasks);
        file_put_contents($this->_file_name, $edited_tasks);
        $this->modified_time = filemtime($this->_file_name);
        $this->_to_session($this->modified_time);
    }
    /* build all the cached lists: tasks, tags, projects
     * plus: project_index => index locations of project header lines
     * plus: task_project => which project this task belongs to
     */
    private function _build_task_lists($plain_tasks) {
        global $config;
        global $lang;
        $cur_project = false;
        $cur_project_idx = false;
        // clear existing task data
        $this->tasks = array();
        $this->projects = array();
        $this->task_project = array();
        $this->tags = array();
        $this->plain_tasks = $plain_tasks;
        $all_lines = explode("\n", $plain_tasks);
        // first project is for orphaned tasks...
        $this->projects[0] = $lang['projectless'];
        $project_idx = 1;
        $cur_project_idx = 0;
        $line_idx = 0;
        foreach ($all_lines as $line) {
            $line = trim($line);
            if (preg_match($config['project_rgx'], $line) > 0) {
                $this->tasks[$line_idx] = $line;
                $cur_project = $line;
                $this->projects[$project_idx] = $cur_project;
                $this->projects_index[$line_idx] = $project_idx;
                $this->line_types[$line_idx] = LineTypeEnum::project;
                $cur_project_idx = $project_idx++;
                $line_idx++;
            } elseif (preg_match($config['task_rgx'], $line) > 0) {
                // collect the task and its project
                $this->tasks[$line_idx] = $line;
                $this->task_project[$line_idx] = $cur_project_idx;
                $this->line_types[$line_idx] = LineTypeEnum::task;
                $line_idx++;
            } elseif (preg_match($config['note_rgx'], $line) > 0) {
                $this->tasks[$line_idx] = $line;
                $this->task_project[$line_idx] = $cur_project_idx;
                $this->line_types[$line_idx] = LineTypeEnum::note;
                $line_idx++;
            } elseif (preg_match($config['heading_rgx'], $line) > 0) {
                $this->tasks[$line_idx] = $line;
                $this->task_project[$line_idx] = $cur_project_idx;
                $this->line_types[$line_idx] = LineTypeEnum::subhead;
                $line_idx++;
            // Blank line between projects
            } elseif (empty($line)) {
                $this->tasks[$line_idx] = '';
                $this->line_type[$line_idx] = LineTypeEnum::blank;
                $cur_project = '';
                $cur_project_idx = 0;
                $line_idx++;
            }
        }
        // get a list of all unique tags
        preg_match_all($config['tag_rgx'], $plain_tasks, $out, PREG_PATTERN_ORDER);
        $this->tags = array_unique($out[0]);
        natcasesort($this->_tags);
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
                        $this->_tasks[$month] = $config['proj_suffix'] . $month;
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


class TaskSearchFilter {
    private $_data = TaskpaperData;

    function __construct(TaskpaperData $data) {
        $this->_data = $data;
    }
    static function tokenise($expression) {
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
    function by_expression($expression) {
        // build a queue of expression tokens
        $tokens = self::tokenise($expression);
        $matched_tokens = array();
        foreach ($tokens as $key => $token) {
            // try to match token type (order matters)
            if (IntervalFilter::match($token) === true) {
                $matched_tokens[] = new IntervalFilter();
            } elseif (CommandFilter::match($token) === true) {
                $matched_token[] = new CommandFilter();
            } elseif (DateFilter::match($token) === true) {
                $matched_tokens[] = new DateFilter();
            } elseif (RangeFilter::match($token) === true) {
                $matched_tokens[] = new RangeFilter();
            } elseif (WordFilter::match($token) === true) {
                $matched_tokens[] = new WordFilter();
            }
        }
        $last_token = $matched_tokens[$key];
        if ($last_token instanceof DateTokenFilter) {
            $last_token->group_dates(true);
        }
        $tasks = $this->_data->tasks;
        $token_filter = new TokenFilter;
        foreach ($matched_tokens as $token_filter) {
            $token_filter->filter_by($tasks);
        }
        // TODO: sort and group?
        return $tasks;
    }
    function by_project($project) {
        // returns a specific project
        $project_key = array_search($project, $this->_data->projects);
        if ($project_key !== false) {
            // get a list of tasks (key only) in this project
            $task_keys = array_keys($this->_data->task_project, $project_key);
            // now finally extract the right task lines
            $task_keys = array_flip($task_keys);
            $tasks = array_intersect_key($this->_data->tasks, $task_keys);
            return $tasks;
        } else {
            return array();
        }
    }
    function by_tag($tag) {
        // return tasks by specific tag (could be a date tag also)
        global $config;
        $has_date = preg_match($config['tag_date_rgx'], $tag, $matches);
        if ($has_date == 1) {
            $date = $matches[0];
            $date_filter = new DateFilter;
            return $date_filter->filter_by($this->_data->tasks, $date);
        } else {
            $word_filter = new WordFilter;
            return $word_filter->filter_by($this->_data->tasks, $tag);
        }
    }
    function by_command($command) {
        $cmd_filter = new CommandFilter();
        return $cmd_filter->filter_by($tasks, $command);
    }
    function sort_results(&$tasks, $title, $task_count = 0, $ignore_projects = false) {
        if ($ignore_projects === false) {
            $tasks = array_diff_key($this->_tasks, $this->_data->projects_index);
            $projects = array_intersect_key($this->_data->tasks, $this->_data->projects_index);
            $tasks = ($tasks == null) ? array() : $tasks;
            $task_count = count($tasks);
            $projects = ($projects == null) ? array() : $projects;
            $project_count = count($projects);
        } else {
            $tasks = $this->_data->tasks;
            $task_count = ($task_count > 0) ? $task_count : count($tasks);
            $projects = array();
            $project_count = 0;
        }
        return new SearchResult($projects, $tasks, $project_count, $task_count, $title);
    }
}


/* stores the result of a search
 * allows access to various lists:
 * i.e. Projects found, tasks found, project count, task count, expression used
 */
class SearchResult {
    private $projects, $tasks, $project_count, $task_count, $name;

    function __construct($projects, $tasks, $project_count, $task_count, $name) {
        $this->projects = $projects;
        $this->tasks = $tasks;
        $this->project_count = $project_count;
        $this->task_count = $task_count;
        $this->name = $name;
    }
}

class TokenFilter {
    private $_token;
    private $_hits;
    protected static $matched_token = '';
    protected static $matched_tokens = array();
    protected static $matched = false;
    
    function __construct($token) {
        $this->_token = $token;
    }
    // true if this token matches (matching order is important!)
    static function match($token) {
    }
    static function _find_match($token = '', $pattern = '') {
        global $config;
        $matched = false;
        if (empty($token) && !empty(self::$matched_tokens)) {
            $matched = true;
        } elseif ($token != '') {
            $matched = preg_match($pattern, $token, $matches);
            if ($matched !== false) {
                self::$matched_tokens = $matches;
                $matched = true;
            }
        }
        return $matched;
    }
    static function matched() {
        return self::$matched;
    }
    /** return the tasks filtered by this token only
     * remember to fill in _hits!
     * @param array $tasks *
     */
    function filter_by(array $tasks) {
    }
    // return the token as understood by parser (handy for partial  matches)
    static function matched_token() {
        return self::$matched_token;
    }
    // returns the different parts of the token (prefix, word, operator, etc...)
    static function current_matches() {
        if (empty($value)) {
            return self::$current_tokens;
        }
    }
    function hits() {
        return $this->_hits;
    }
}

class DateTokenFilter extends TokenFilter {
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
                // Specific task count needed due to inserted group-by-month headers
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
    static $word = '';
    static $exclude = '';
    
    static function match($token) {
        global $config;
        self::$matched = false;
        if (parent::_find_match($token, $config['word_tok_rgx']) === true) {
            self::$matched_token = self::$matched_tokens[0];
            self::$word = self::$matched_tokens[2];
            self::$exclude = self::$matched_tokens[1];
            self::$matched = true;
        }
        return self::$matched;
    }
    function filter_by(array $tasks, $token = '') {
        if (!empty($token)) {
            self::match($token);
        }
        if (self::matched() === true) {
            $word = "|" . $wildcard . self::$word . $wildcard . "|i";
            if (self::$exclude == '-') {
                $tasks =  preg_grep($word, $tasks, PREG_GREP_INVERT);
            } else {
                $tasks = preg_grep($word, $tasks);
            }
            $this->_hits = count($tasks);
        }
        return $tasks;
    }
}

class CommandFilter extends TokenFilter {
    static $cmd;
    static $index;
    
    static function match($token = '') {
        global $config, $lang;
        self::$matched = false;
        if (parent::_find_match($token, $config['cmd_tok_rgx']) === true) {
            $prefix = self::$matched_tokens[0];
            $cmd = self::$matched_tokens[1];
            // allow for partial matches also, use only first match
            $matches = preg_grep('/^' . $cmd . '/', $lang['command_names']);
            $index = (!empty($matches)) ? key($matches) : false;
            if ($index !== false) {
                self::$cmd = $lang['command_names'][$index];
                self::$index = $index;
                self::$matched_token = $prefix . self::$cmd;
                self::$matched = true;
            }
        }
        return self::$matched;
    }
    function filter_by(array $tasks, $token = '') {
        if (!empty($token)) {
            self::match($token);
        }
        if (self::matched() === true) {
            return $this->filter_by_index($tasks, self::$index);
        } else {
            return $tasks;
        }
    }
    function filter_by_index(array $tasks, $index) {
        global $config;
        switch($index) {
        case 0; // due (i.e. all dated items)
            $date_filter = new DateFilter();
            $tasks = $date_filter->filter_by($tasks, 0);
            break;
        case 1: // overdue/late items
            $date_filter = new RangeFilter();
            $tasks = $date_filter->filter_by($tasks, '0..' . date($config['date_format']));
            break;
        case 2: // starred items
            $word_filter = new WordFilter();
            $tasks =$word_filter->filter_by($tasks, $config['find_star_rgx']);
            break;
        case 3: // done/complete items
            $word_filter = new WordFilter();
            $tasks = $word_filter->filter_by($tasks, $config['find_done_rgx']);
            break;
        }
        $this->_hits = count($tasks);
        return $tasks;
    }
}

class DateFilter extends DateTokenFilter {
    static $operator = '';
    static $date = '';
    
    static function match($token) {
        global $config;
        self::$matched = false;
        if (parent::_find_match($token, $config['date_tok_rgx']) === true) {
            self::$operator = self::$matched_tokens[1];
            self::$date = self::$matched_tokens[2];
            self::$matched_token = self::$matched_tokens[0];
            self::$matched = true;
        }
        return self::$matched;
    }
    function filter_by(array $tasks, $token = '') {
        if (!empty($token)) {
            self::match($token);
        }
        if (self::matched() === true) {
            return $this->filter_by_date($tasks, $date, 0, $operator, $this->group_dates());
        } else {
            return $tasks;
        }
    }
}

class RangeFilter extends DateTokenFilter {
    static $start_date = 0;
    static $end_date = 0;
    
    static function match($token = '') {
        global $config;
        self::$matched = false;
        if (parent::_find_match($token, $config['range_tok_rgx']) === true) {
            self::$start_date = self::$matched_tokens[1];
            self::$end_date = self::$matched_tokens[2];
            self::$matched_token = self::$matched_tokens[0];
            self::$matched = true;
        }
        return self::$matched;
    }
    function filter_by(array $tasks, $token = '') {
        if (!empty($token)) {
            self::match($token);
        }
        if (self::matched() === true) {
            return $this->filter_by_date($tasks, self::$start_date, self::$end_date, '', $this->group_dates());
        } else {
            return $tasks;
        }
    }
}

class IntervalFilter extends DateTokenFilter {
    static $interval = '';
    static $count = 0;
    static $index = false;
    
    function __construct() {
        parent::__construct($token);
    }
    static function match($token) {
        global $config, $lang;
        self::$matched = false;
        if (parent::_find_match($token, $config['interval_tok_rgx']) === true) {
            $matches = preg_grep('/^' . $interval . '/', $lang['interval_names']);
            $interval = (!empty($matches)) ? $matches[0] : false;
            if ($interval !== false) {
                $count = self::$matched_tokens[1];
                self::$count = ($count > 1) ? $count : 1;
                self::$interval = $interval;
                self::$index = key($matches);
                self::$matched_token = $config['cmd_prefix'] . $count . $interval;
                self::$matched = true;
            }
        }
        return self::$matched;
    }
    function filter_by(array $tasks, $token = '') {
        if (!empty($token)) {
            self::match($token);
        }
        if (self::matched() === true) {
            list($startdate, $end_date) = self::_convert_to_date($index, $count);
            return $this->_filter_by_date($tasks, $start_date, $end_date, '', $this->group_dates());
        } else {
            return $tasks;
        }
    }
    static function interval_as_date($interval = '') {
        if (self::match($interval) === true) {
            return $this->_convert_to_date($index, $count);
        }
    }
    private function _convert_to_date($index, $count = 1) {
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