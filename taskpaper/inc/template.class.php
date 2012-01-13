<?php
/**
 * A very simple templating class
 * @author © Copyright 2003 Brian Lozier
 */
require('./tpl/common.tpl.php');

class Template {
    var $vars; // Holds all the template variables

    /**
     * Constructor
     * @param $file string the file name you want to load
     */
    function __construct($tpl_name = null) {
        $this->file = $this->_get_path($tpl_name);
    }

    /**
     * Set a template variable; could also be another Template instance
     * Function accepts multiple arguments, i.e many name=>value pairs
     */
    function set($name, $value) {
        $args = func_get_args();
        $cnt = count($args);
        // we check for odd number of args => missing values...
        if ($cnt % 2 == 0 && $cnt >= 2 ) {
            for ($i=0; $i < $cnt; $i += 2) {
                $name = $args[$i];
                $value = $args[$i + 1];
                $this->vars[$name] = ($value instanceof Template) ? $value->fetch() : $value;
            }
        }      
    }

    /**
     * Open, parse, and return the template file.
     *
     * @param $file string the template file name
     */
    function fetch($tpl_name = null) {
        if(!$tpl_name) {
            $file = $this->file;
        } else {
            $file = $this->_get_path($tpl_name);
        }
        extract($this->vars);          // Extract the vars to local namespace
        ob_start();                    // Start output buffering
        include($file);                // Include the file
        $contents = ob_get_contents(); // Get the contents of the buffer
        ob_end_clean();                // End buffering and discard
        return $contents;              // Return the contents
    }

     /**
      * show this template file.
      *
      */
    function show() {
        echo $this->fetch();
    }

    /**
     * All templates are assumed to be in /tpl, and
     * follow the convention [name].tpl.php
     * This could be in config.php, but why bother, I have no intention
     * of changing it..
     */
    private function _get_path($name) {
        return './tpl/'.$name.'.tpl.php';
    }
}
?>
