<?php
/* 
 * common functions used by all templates
 */

/* used by various templates for task markup
 * 
 */
function mark_up_task($plain_task, $task_id) {
    global $config;
    $tasks = htmlspecialchars($plain_task, ENT_QUOTES);
    // deal with tasks only first
    $checked = '';
    if (preg_match($config['task_rgx'], $plain_task) > 0) {
        $is_done = (substr($plain_task, 0, 1) == $config['done_tag']) ? 1 : 0;
        $is_starred = (substr($plain_task, -1, 1) == $config['star_tag']) ? 1 : 0;
        $task_only = substr($plain_task, 2 + $is_done);
        $task_only = ($is_starred == 1) ? substr($task_only, 0, -1) : $task_only;
        if ($is_starred == 1) {
            $task_only = '<span class="starred">' . $task_only . '</span>';
        }
        if ($is_done == 1) {
            $task_only = '<strike>' . $task_only . '</strike>';
            $checked = ' checked="checked"';
        }
        $markup = '<li>' .
                  '<input type="checkbox" class="check-done" value="' . $task_id . '"' . $checked . ' title="Mark this task as complete">' .
                  " " . $task_only .
                  '<span class="task-buttons">' .       
                  '<input type="image" class="star-button" src="icons/star.png" name="' . $task_id . '" title="Turn highlight on/off">' .
                  '<input type="image" class="archive-button" src="icons/archive.png" name="'. $task_id .'" title="Archive this task">' .
                  '<input type="image" class="delete-button" src="icons/delete.png" name="'. $task_id .'" title="Delete this task">' .
                  '</span>' .
                  '</li>';
    } else {
        $markup = $plain_task;
    }
    // basic lines: headings, notes, and tags only
    $search = array($config['subhead_rgx'],
                    $config['heading_rgx'],        
                    $config['note_rgx'],
                    $config['tagdue_rgx'],
                    $config['tag_rgx'],
                    );
    $replace = array('<h4>\0</h4>',
                     '<h3 title="Click to view this project only">\1</h3>',
                     '<span class="note">\1</span>',
                     '<span class="tagdue">\1</span>',
                     '<span class="tag">\1</span>',
                     );
    $markup = preg_replace($search, $replace, $markup);
    return $markup;
}
?>
