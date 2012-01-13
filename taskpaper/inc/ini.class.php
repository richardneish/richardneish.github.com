<?php

/**
 * Simple file to access config data from a standard INI file
 * @param load => parse the INI file into an array
 * @param save => update the INI file based on changed items only
 * @param item => get/set the specified item
 * @param changed => true if item values have changed
 *
 * @author sbent
 */

class Ini {
    private $_ini_file = '';
    private $_ini_items = array();
    private $_changed_keys = array();
    private $_changed_values = array();

    function __construct($filename) {
        $this->_ini_file = $filename;
        $this->load();
    }
    function load() {
        $this->_ini_items = parse_ini_file($this->_ini_file);
    }
    function item($key, $value = NULL) {
        if (!empty($value) && array_key_exists($key, $this->_ini_items)) {
            $this->_ini_items[$key] = $value;
            $this->_changed_keys[] = '/(' . $key . '=).+\n/';
            $this->_changed_values[] = "$1" . $value . "\n";
        } else {
            return $this->_ini_items[$key];
        }
    }
    function save() {
        if ($this->changed()) {
            $ini_text = file_get_contents($this->_ini_file);
            // replace only changed lines
            $ini_text = preg_replace(array_values($this->_changed_keys), array_values($this->_changed_values), $ini_text);
            file_put_contents($this->_ini_file, $ini_text);
            // reset all changes
            unset($this->_changed_items);
        }
    }
    function changed() {
        if (count($this->_changed_keys) > 0) {
            return true;
        } else {
            return false;
        }
    }
}
?>
