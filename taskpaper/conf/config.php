<?php
/* 
 * default application config files
 * 
 */
define('CMD_DUE', 0);
define('CMD_LATE', 1);
define('CMD_STAR', 2);
define('CMD_DONE', 3);

final class CmdTypeEnum {
    const due = 0;
    const late = 1;
    const star = 2;
    const done = 3;
}

final class LineTypeEnum {
    const project = 0;
    const task = 1;
    const note = 2;
    const heading = 3;
    const blank = 4;
}

$config['ajax_file']        = './ajax_response.php';
$config['start_page']       = 'startpage:true';
$config['debug_file']       = 'logs/debug.txt';        // relative to App Base Path
$config['title']            = 'Taskpaper Web';

$config['proj_suffix']      = ':';
$config['task_prefix']      = '- ';
$config['note_prefix']      = '...';
$config['done_tag']         = 'X';
$config['star_tag']         = '*';
$config['cmd_prefix']       = '=';
$config['date_sep']         = '..';
$config['date_format']      = 'j-M-Y';

// all regexes used to identify the various parts of a taskpaper;
// this allows the user to adapt the style to his own preference
$config['heading_rgx']      = '/(.+:)$/';
$config['subhead_rgx']        = '/^(\w|\s)+$/';
$config['task_rgx']         = '/(' . $config['done_tag'] . '*- .+)/';
$config['note_rgx']         = '/\.\.\.([^<]+)/';
$config['tag_rgx']          = '/(@(?!\d\S+\d)\w+)/';
$config['tagdue_rgx']       = '/(@(\d\S+\d))/';
$tag_date                   = '[1-3]*[0-9]-(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)-\d{2,4}';
$config['tag_date_rgx']     = '/(' . $tag_date . ')(\.\.' . $tag_date . ')?/i';
$config['in_proj_rgx']      = '|\/(\d{1,2})$|';

// regex to find both tag type (used in TaskSearch)
$config['find_done_rgx']    = '^' . $config['done_tag'];
$config['find_star_rgx']    = '[' . $config['star_tag'] . ']$';

// old parser tokens
$date_rgx                   = '(\d{1,2}[-.,\/](\d{1,2}|\w{3})[-.,\/]\d{2,4})';
$config['date_single_rgx']  = '/(>|<)?' . $date_rgx . '/';
$config['date_between_rgx'] = '/'. $date_rgx . '( | to |..)' . $date_rgx . '/';
$config['interval_rgx']     = '/(\d{0,2})(\w+)/';
$config['find_cmd_rgx']     = '/=(\w+)/';
$config['find_words_rgx']   = '/(\-?.+|\".+\")/';

// new parser tokens
$config['cmd_tok_rgx']      = '/=(\w+)/';
$config['word_tok_rgx']     = '/(\-)?(\w+)/';
$config['date_tok_rgx']     = '/(>|<)?(' . $date_rgx . ')/';
$config['range_tok_rgx']    = '/('. $date_rgx . ')\.\.(' . $date_rgx . ')/';
$config['interval_tok_rgx'] = '/=(\d{0,2})(\w+)/';

// language options
$lang['interval_names']     = array('today', 'tomorrow', 'day', 'week', 'month', 'year');
$lang['command_names']      = array('due', 'late', 'star', 'done');
$lang['projectless']        = 'No project:';
$lang['search_results']     = 'Search results: ';
$lang['done_tag']           = 'done';
$lang['star_tag']           = 'highlighted';
$lang['due_tag']            = 'dated';
$lang['task_header']        = 'Tasks';
$lang['tag_header']         = 'Tags';
$lang['project_header']     = 'Projects';
?>
