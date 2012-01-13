<?php
require_once(APP_PATH.'inc/init.php');

/* unique session entry point:
 * updates a session item if a value if iincluded
 * gets a session data item if only name is provided,
 * returns empty string of it does not exist
 */
function cached_item($name, $value = '') {
    if (empty($value)) {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        } else {
            return '';
        }
    } else {
        $_SESSION[$name] = $value;
    }
}

function get_errors(){
    global $self, $file, $auto_writable;
    if(!file_exists($self)){
        $error = "Can't find ajax file. You may need to set its location in config.ini";
    } else if(!file_exists('./'.$file)){
        $error = "Can't find your taskpaper document. You may need to set its location in config.ini";
    } else if(is_writable('./'.$file)){
        if($auto_writable && copy($file, $file.".tmp")){
            unlink($file);
            copy($file.".tmp", $file);
            unlink($file.".tmp");
            chmod($file, 0777);
        } else {
            $error = "Your taskpaper document is not writable so you will be unable to save changes";
        }
    }
    if(isset($error))
         return '<div class="error"><img src="error.png"> '.$error.'</div>';
    return '';
}

/* Simple debug logging function
 * The debug file will be created if not existing, open if not open
 * @param $message => whatever you want to write to the debug log
 * Note: accepts multiple arguments, will be written out using commas between
 */
function dbg($message) {
    static $log_file;
    global $config;
    if(DEBUG === true) {
        if(!$log_file) {
            $file_path = APP_PATH.$config['debug_file'];
            $log_file = fopen($file_path, 'a') or exit("Cannot open Debug Log: ".$file_path);
        }
        $args = func_get_args();
        if(!empty($args)) {
            foreach ($args as &$arg) {
                ob_start();                    // Start output buffering
                var_export($arg);                // Human readable version of variables and arrays
                $arg = ob_get_contents();      // Get the contents of the buffer
                ob_end_clean();                // End buffering and discard
            }
            $message = implode("\n\t", $args);
            // TODO: add date, and maybe function name if possible
            fwrite($log_file, '['.date('Y-M-d  H:i:s').']'."\n\t".$message."\n");
        }
    }
}
?>