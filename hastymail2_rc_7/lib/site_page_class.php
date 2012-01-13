<?php

/*  site_page_class.php: Output parts of the page
    Copyright (C) 2002-2009  Hastymail Development group

    This file is part of Hastymail.

    Hastymail is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Hastymail is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Hastymail; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

* $Id:$
*/

class site_page extends fw_page_data {

function site_page() {
    $this->init_base_data();
}
function print_hm_html_head() {
    global $conf;
    global $page_id;
    $url_base = $conf['url_base'];
    $host_name = $conf['host_name'];
    $http_prefix = $conf['http_prefix'];
    $data = ''; 
    if (isset($this->pd['plugin_css'])) {
        foreach ($this->pd['plugin_css'] as $val) {
            $data .= '<link rel="stylesheet" type="text/css" href="'.$val.'" />';
        }
    }
    if ($this->user->full_effects_enabled) {
        $data .= '<script type="text/javascript" src="js/effects_full.js"></script>';
    }
    elseif ($this->user->light_effects_enabled) {
        $data .= '<script type="text/javascript" src="js/effects_light.js"></script>';
    }
    if ($this->user->logged_in && isset($this->pd['settings']['font_size'])) {
        $fs = $this->pd['settings']['font_size'];
        $data .= '<style type="text/css">body, select, option, textarea, input { font-size: '.
        $fs.'%; }</style>';
    }
    else {
        $fs = '100%';
    }
    if ($this->dsp_page == 'login' || ($this->dsp_page == 'main' && !$this->user->logged_in)) {
        $data .= '<script type="text/javascript">
        '.$this->start_cdata().'
        window.onload = function() {
            if (document.getElementById("login")) {
                document.getElementById("user").focus();
            }
        }
        '.$this->end_cdata().'
        </script>';
    }
    elseif ($this->user->ajax_enabled && $this->user->logged_in) {
        $data .= '<script type="text/javascript">';
        $data .= $this->start_cdata();
        $data .= 'var page_title="'.$this->user->page_title.'";';
        if (isset($this->pd['plugin_ajax']) && !empty($this->pd['plugin_ajax'])) {
            foreach ($this->pd['plugin_ajax'] as $vals) {
                $data .= 'function x_ajax_'.$vals['plugin'].'_'.$vals['name'].'(){sajax_do_call("ajax_'.$vals['plugin'].'_'.$vals['name'].
                         '",x_ajax_'.$vals['plugin'].'_'.$vals['name'].'.arguments);} function hm_ajax_'.$vals['plugin'].'_'.$vals['name'].'(';
                for ($i=0;$i<$vals['args'];$i++) {
                    $data .= 'arg_'.$i.', ';
                }
                $data = rtrim($data, ', ');
                $data .= ') { function callback_'.$vals['plugin'].'_'.$vals['name'].'(output) {';
                if ($vals['div_id']) {
                    $data .= 'if (document.getElementById("'.$vals['div_id'].'")) { document.getElementById("'.$vals['div_id'].'").innerHTML = output; }';
                }
                $data .= ' } x_ajax_'.$vals['plugin'].'_'.$vals['name'].'("'.$vals['plugin'].'", ';
                for ($i=0;$i<$vals['args'];$i++) {
                    $data .= 'arg_'.$i.', ';
                }
                $data .= 'callback_'.$vals['plugin'].'_'.$vals['name'].'); };';
            }
        }
        $data .= '
        var update_delay = '.$this->pd['settings']['ajax_update_interval'].';';
        if ($this->pd['settings']['new_page_refresh'] && $this->dsp_page == 'new') {
            $data .= 'var do_new_page_refresh = '.$this->pd['settings']['new_page_refresh'].';';
        }
        else {
            $data .= 'var do_new_page_refresh = 0;';
        }
        if ($this->pd['settings']['dropdown_ajax']) {
            $data .= 'var do_folder_dropdown = \''.$this->user->htmlsafe($this->pd['mailbox']).'\';';
        }
        else {
            $data .= 'var do_folder_dropdown = 0;';
        }
        if ($this->pd['settings']['show_folder_list'] && isset($this->pd['settings']['folder_list_ajax']) && $this->pd['settings']['folder_list_ajax']) {
            $data .= 'var do_folder_list = 1;';
        }
        else {
            $data .= 'var do_folder_list = 0;';
        }
        if ($this->dsp_page == 'new') {
            $data .= 'var new_page_notice = "'.$this->user->str[418].'";';
        }
        if (isset($this->pd['settings']['compose_autosave']) && $this->pd['settings']['compose_autosave'] &&
            $this->dsp_page == 'compose') {
            $data .= 'var c_autosave = '.(5 + $this->pd['settings']['compose_autosave']).';';
        }
        else {
            $data .= 'var c_autosave = 0;';
        }
        $data .= 'var update_notice = "'.$this->user->str[417].'";';
        $data .= 'window.onload = function() { start_timer();';
        if (isset($this->pd['settings']['message_window']) && $this->pd['settings']['message_window'] && $this->parent_refresh && $this->dsp_page == 'message') {
            $data .= 'refresh_parent();';
        }
        if (isset($this->pd['plugin_js_onload'])) {
            foreach ($this->pd['plugin_js_onload'] as $val) {
                $data .= $val;
            }
        }
        $data .= '} '.$this->end_cdata().'</script>';
    }
    if (isset($this->pd['plugin_js'])) {
        foreach ($this->pd['plugin_js'] as $val) {
            $data .= $val;
        }
    }
    if (!$this->user->ajax_enabled && $this->dsp_page == 'new' && $this->pd['settings']['new_page_refresh']) {
        $data .= '<meta HTTP-EQUIV="refresh" content="'.$this->pd['settings']['new_page_refresh'].
                 ';url='.$http_prefix.'://'.$host_name.$url_base.'?page=new&amp;mailbox='.urlencode($this->pd['mailbox']).'" />';
    }
    return $data;
}
function print_clock() {
    global $page_id;
    global $date_formats;
    global $time_formats;
    if (isset($this->pd['settings']['time_format'])) {
        if (isset($time_formats[$this->pd['settings']['time_format']])) {
            $time_format = $this->pd['settings']['time_format'];
        }
        else {
            $time_format = false;
        }
    }
    else {
        $time_format = 'h:i:s A';
    }
    if (isset($this->pd['settings']['date_format']) && isset($date_formats[$this->pd['settings']['date_format']])) {
        $date_format = $this->pd['settings']['date_format'];
    }
    else {
        $date_format = 'm/d/y';
    }
    if ($time_format) {
        return '<div>'.date($date_format).'&#160;'.date($time_format).'</div>';
    }
    else {
        return '<div>'.date($date_format).'</div>';
    }
}
function print_notices($page=false) {
    $data = '';
    if (!empty($this->notices)) {
        foreach ($this->notices as $v) {
            $data .= $v.'<br />';
        }
    }
    return $data;
}
function print_sort_form($name=false, $disabled=false) {
    global $sort_types;
    global $client_sort_types;
    global $page_id;
    $data = '<complex-'.$page_id.'>';
    if (stristr($this->pd['imap_capability'], 'SORT')) {
        $stype = 'server';
        $types = $sort_types;
    }
    else {
        $stype = 'client';
        $types = $client_sort_types;
    }
    if ($name !== false) {
        $data .= '<select ';
        if ($disabled) {
            $data .= 'disabled="disabled" ';
        }
        $data .= 'name="sort_by['.$name.']">';
    }
    else {
        $data .= '<input type="hidden" name="page" value="mailbox" /><input type="hidden" name="mailbox" value="'.
                 $this->user->htmlsafe($this->pd['mailbox']).'" />'.$this->user->str[39].' <select ';
        $data .= 'name="sort_by" ';
        if (isset($this->pd['frozen_folders'][$this->pd['mailbox']])) { $data .= 'class="disabled_sort" disabled="disabled" '; }
        $data .= 'onchange="display_notice(this, \'Resorting Mailbox...\');">';
    }
    foreach ($types as $i => $v) {
        $data .= '<option ';
        if ($i == $this->pd['sort_by']) { $data .= 'selected="selected" '; }
        $data .= 'value="'.$i.'">'.$this->user->str[$v].'</option>';
    }
    $data .= '</select> ';
    if ($name === false) {
        if ($stype == 'server') {
            $data .= '&#160;'.$this->user->str[38].' <select ';
            if (isset($this->pd['frozen_folders'][$this->pd['mailbox']])) { $data .= 'class="disabled_sort" disabled="disabled" '; }
            $data .= 'onchange="display_notice(this, \'Filtering Mailbox...\');" name="filter_by">';
            foreach ($this->pd['sort_filters'] as $i => $v) {
                $data .= '<option ';
                if ($i == $this->pd['filter_by']) {
                    $data .= 'selected="selected" ';
                }
                $data .= 'value="'.$i.'">'.$v.'</option>';
            }
            $data .= '</select>';
        }
        $data .= '<noscript><input type="submit" value="'.$this->user->str[39].'" /></noscript>';
    }
    $data .= '</complex-'.$page_id.'>';
    return $data;
}
function print_message_controls() {
    $data = '<input type="hidden" name="current_mailbox" value="'.$this->user->htmlsafe($this->pd['mailbox']).'" />';
    if (isset($this->pd['settings']['trash_folder']) && $this->pd['settings']['trash_folder'] == $this->pd['mailbox']) {
        $data .= '<input type="submit" onclick="return hm_confirm(\''.$this->user->str['421'].'\');" class="empty_trash_btn" name="empty_trash" value="'.$this->user->str[420].'" />';
    }
    $data .= '<input type="submit" '.
            'class="delete_btn" name="delete_message" onclick="return hm_confirm(\''.$this->user->str[63].'\');" value="'.$this->user->str[59].'" />';
    if ((isset($this->pd['settings']['always_expunge']) && $this->pd['settings']['always_expunge']) ||
         !isset($this->pd['settings']['trash_folder']) || !$this->pd['settings']['trash_folder']) {
        $data .= '<input type="submit" class="undelete_btn" name="undelete_message" value="'.$this->user->str[433].'" /><input type="submit" class="expunge_btn" name="expunge_messages" onclick="return '.
                 'hm_confirm(\''.$this->user->str[422].'\');" value="'.$this->user->str[68].'" />';
    }
    $data .= '<input type="submit" class="read_btn" name="read_message" value="'.$this->user->str[33].'" /><input type="submit" class="unread_btn" name="unread_message" value="'.$this->user->str[34].'" />
              <input type="submit" class="flag_btn" name="flag_message" value="'.$this->user->str[35].'" /><input type="submit" class="unflag_btn" name="unflag_message" value="'.$this->user->str[65].'" />
              <input type="submit" class="move_btn" name="move_message" value="'.$this->user->str[66].'" /><input type="submit" class="copy_btn" name="copy_message" value="'.$this->user->str[67].'" />
              &#160;&#160;'.$this->user->str[55].': &#160;<select name="destination_folder">'.
              $this->print_folder_option_list($this->pd['folders'], false, 0, array($this->pd['current_destination']), true, true).'</select>';
    return $data;
}
function print_message_controls2() {
    $data = '';
    if (isset($this->pd['settings']['trash_folder']) && $this->pd['settings']['trash_folder'] == $this->pd['mailbox']) {
        $data .= '<input type="submit" onclick="return hm_confirm(\''.$this->user->str[421].'\');" class="empty_trash_btn" name="empty_trash" value="'.$this->user->str[420].'" />';
    }
    $data .= '<input type="submit" class="delete_btn" name="delete_message" onclick="'.
             'return hm_confirm(\''.$this->user->str[63].'\');" value="'.$this->user->str[59].'" />';
    if ((isset($this->pd['settings']['always_expunge']) && $this->pd['settings']['always_expunge']) ||
        !isset($this->pd['settings']['trash_folder']) || !$this->pd['settings']['trash_folder']) {
        $data .= '<input type="submit" class="undelete_btn" name="undelete_message" value="'.$this->user->str[433].'" /><input type="submit" class="expunge_btn" name="expunge_messages" onclick="return '.
                 'hm_confirm(\''.$this->user->str[422].'\');" value="'.$this->user->str[68].'" />';
    }
    $data .= '<input type="submit" class="read_btn" name="read_message" value="'.$this->user->str[33].'" /><input type="submit" class="unread_btn" name="unread_message" value="'.$this->user->str[34].'" />
              <input type="submit" class="flag_btn" name="flag_message" value="'.$this->user->str[35].'" /><input type="submit" "unflag_btn" name="unflag_message" value="'.$this->user->str[65].'" />
              <input type="submit" class="move_btn" name="move_message2" value="'.$this->user->str[66].'" /><input type="submit" class="copy_btn" name="copy_message2" value="'.$this->user->str[67].'" />
              &#160;&#160;'.$this->user->str[55].': &#160;<select name="destination_folder2">'.
              $this->print_folder_option_list($this->pd['folders'], false, 0, array($this->pd['current_destination']), true, true).'</select>';
    return $data;
}
function print_folder_option_list($folders, $parent=false, $i=0, $selected=array(), $clean=false, $no_current=false, $selectable_type='selectable', $exclude_list=array()) {
    $data = '';
    global $used;
    global $conf;
    if ($this->pd['settings']['folder_style'] == 1) {
        $pre = str_repeat('&#160;', ($i*5));
    }
    else {
        $pre = '';
    }
    if (!$parent) {
        $used = array();
    }
    foreach ($folders as $vals) {
        $disabled_check = false;
        if (!isset($vals['name'])) {
            continue;
        }
        if (in_array($vals['name'], $used)) {
            continue;
        }
        $used[] = $vals['name'];
        $classes = array();
        if (!$vals['hidden'] && $vals['parent'] == $parent) {
            $data .= '<option ';
            if (in_array($vals['realname'], $selected) && 
                    (!$no_current || $vals['realname'] != $this->pd['mailbox'])) {
                $data .= 'selected="selected" ';
            }
            switch ($selectable_type) {
                case 'selectable':
                    if ($vals['realname'] != 'INBOX' && $vals['noselect']) {
                        $data .= 'disabled="disabled" ';
                        $classes[] = 'disabled_option';
                        $disabled_check = true;
                    }
                    break;
                case 'no_kids':
                    if ($vals['has_kids'] || $vals['realname'] == 'INBOX') {
                        $data .= 'disabled="disabled" ';
                        $classes[] = 'disabled_option';
                        $disabled_check = true;
                    }
                    break;
                case 'noselect':
                    if (!$vals['noselect'] || $vals['realname'] == 'INBOX') {
                        $data .= 'disabled="disabled" ';
                        $classes[] = 'disabled_option';
                        $disabled_check = true;
                    }
                    break;
                case 'all':
                    if ($vals['realname'] == 'INBOX') {
                        $data .= 'disabled="disabled" ';
                        $classes[] = 'disabled_option';
                        $disabled_check = true;
                    }
                    break;
                case 'custom':
                    if (in_array($vals['name'], $exclude_list)) {
                        $data .= 'disabled="disabled" ';
                        $classes[] = 'disabled_option';
                        $disabled_check = true;
                    }
                    break;

            }
            if ($this->pd['mailbox'] == $vals['realname'] && $no_current && $selectable_type == 'selectable') {
                $data .= 'disabled="disabled" ';
                $classes[] = 'disabled_option';
            }
            $cnt = '';
            if (!$clean) {
                if ($this->pd['settings']['folder_detail'] == 1 && in_array($vals['name'], $this->pd['settings']['folder_check'])) {
                    if (isset($vals['status']['unseen'])) {
                        $cnt = ' ('.$vals['status']['unseen'].') ';
                    }
                }
                if ($this->pd['settings']['folder_detail'] == 2 && in_array($vals['name'], $this->pd['settings']['folder_check'])) {
                    if (isset($vals['status']['unseen'])) {
                        $cnt = ' ('.$vals['status']['unseen'];
                    }
                    else {
                        $cnt = '(-';
                    }
                   if (isset($vals['status']['messages'])) {
                        $cnt .= '/'.$vals['status']['messages'].') ';
                    }
                }
            }
            if ($vals['special'] && !$disabled_check) {
                $classes[] = 'special_folder';
            }
            if ($vals['name'] == 'INBOX') {
                $vals['basename'] = 'INBOX';
            }
            if ($vals['name'] == $conf['imap_folder_prefix']) {
                $name = 'INBOX';
            }
            elseif ($this->pd['settings']['folder_style'] == 1) {
                $name = $vals['basename'];
            }
            else {
                $name = $vals['name'];
            }
            if (!empty($classes)) {
                $data .= 'class="'.join(' ', $classes).'" ';
            }
            if ($name == 'INBOX') {
                $data .= 'value="'.$this->user->htmlsafe($vals['name']).'">'.$pre.' '.$this->user->str[436].$cnt.'</option>';
            }
            else {
                $data .= 'value="'.$this->user->htmlsafe($vals['name']).'">'.$pre.' '.$this->user->htmlsafe($name, 0, 0, 1).$cnt.'</option>';
            }
        }
        if (($vals['has_kids'] && strtoupper($vals['name']) != 'INBOX') || $vals['noselect']) {
            $subfolders = array();
            foreach ($this->pd['folders'] as $atts) {
                    if (!isset($atts['basename'])) {
                        continue;
                    }
                    if ($vals['realname'] == $atts['parent']) {
                        $subfolders[$atts['realname']] = $atts;
                    }
            }
            if (!empty($subfolders)) {
                $i++;
                $data .= $this->print_folder_option_list($subfolders, $vals['realname'], $i, $selected, $clean, $no_current, $selectable_type, $exclude_list); 
                $i--;
                $subfolders = array();
            }
        }
    }
    return $data;
}
function print_folder_dropdown($folders) {
    $data = '<form method="get" action=""><input type="hidden" name="page" value="mailbox" /><input type="hidden" id="enable_delete_warning" value="'.
            $this->pd['settings']['enable_delete_warning'].'" /><select onchange="display_notice(this, \'Opening mailbox...\');" name="mailbox">'.$this->print_folder_option_list(
            $folders, false, 0, array($this->pd['mailbox'])).'</select> &#160;<input id="goto_mailbox" type="submit" value="'.$this->user->str[25].'" /></form>';
    if (isset($this->user->use_cookies) && !$this->user->use_cookies) {
        $data .= '<input type="hidden" id="sid" value="'.session_id().'" />';
    }
    return $data;
}
function print_folder_list($folders, $parent=false, $i=0, $inbox=false) {
    $i++;
    global $conf;
    $data = '';
    if ($inbox && $inbox == $parent) {
        $data .= '<div class="inbox_list folder_list"><ul>';
    }
    elseif ($this->pd['settings']['folder_style']  == 1 || !$parent) {
        $data .= '<div class="folder_lists"><ul>';
    }
    $sid = '';
    if (!$this->user->use_cookies && isset($_GET['rs'])) {
        $sid = '&amp;PHPSESSID='.session_id();
    } 
    foreach ($folders as $vals) {
        if (!isset($vals['name'])) {
            continue;
        }
        $hash = md5($vals['name']);
        if ($vals['name'] == $conf['imap_folder_prefix']) {
            $inbox = true;
            if (!isset($_SESSION['folder_state'][$hash])) {
                $_SESSION['folder_state'][$hash] = 1;
            }
        }
        else {
            $inbox = false;
        }
        if (!$vals['hidden'] && $vals['parent'] == $parent) {
            if (isset($this->pd['mailbox']) && ($this->pd['mailbox'] == $vals['name'] ||
                $this->pd['mailbox'] == $vals['realname'])) {
                $data .= '<li class="current_mailbox">';
            }
            else {
                $data .= '<li>';
            }
            if ($this->pd['settings']['folder_style'] == 1) {
                if (($vals['has_kids'] && strtoupper($vals['name']) != 'INBOX') || $vals['noselect']) {
                    if ((isset($_SESSION['folder_state'][$hash]) && $_SESSION['folder_state'][$hash]) ||
                        (isset($this->pd['mailbox']) && substr($this->pd['mailbox'], 0, strlen($vals['name'])) == $vals['name'])) {
                        $state = 1;
                    }
                    else {
                        $state = 0;
                    }
                    if (strstr($this->sticky_url, 'folder_state')) {
                        $url = preg_replace("/folder_state=\d+/", 'folder_state='.$hash.$state, $this->sticky_url);
                    }
                    else {
                        $url = $this->sticky_url.'&amp;folder_state='.$hash.$state;
                    }
                    $data .= '<a href="'.$url.'" class="expand_link" id="folder_link_'.$hash.
                             '" onclick="expand_folder(\'folder_div_'.$hash.'\', \'folder_link_'.$hash.'\'); ';
                    if ($this->user->ajax_enabled) {
                        $data .= 'save_folder_state(\''.$hash.'\');';
                    }
                    $data .= 'return false;">';
                    if ($state) {
                        $data .= '-';
                    }
                    else {
                        $data .= '+';
                    }
                    $data .= '</a> ';
                }
                else {
                    $data .= '<a class="expand_link" style="visibility: hidden;">+</a> ';
                }
            }
            if ($vals['name'] == 'INBOX') {
                $vals['basename'] = 'INBOX';
            }
            if (!$vals['noselect']) {
                $data .= '<a ';
                if ($vals['special']) {
                    $data .= 'class="special_folder" ';
                }
                $data .= 'href="?page=mailbox'.$sid.'&amp;mailbox='.urlencode($vals['name']).'">';
            }
            if ($conf['imap_folder_prefix'] == $vals['name']) {
                $name = 'INBOX';
            }
            elseif ($this->pd['settings']['folder_style'] == 1) {
                $name = $vals['basename'];
            }
            else {
                $name = $vals['name'];
            }
            if ($name == 'INBOX') {
                $name = $this->user->str[436];
            }
            else {
                $name = $this->user->htmlsafe($name, 0, 0, 1);
            }
            $data .= $name;
            if (!$vals['noselect']) {
                $data .= '</a>';
            }
            if ($this->pd['settings']['folder_detail'] == 1) {
                if (isset($vals['status']['unseen']) && in_array($vals['name'], $this->pd['settings']['folder_check'])) {
                    if ($vals['status']['unseen']) {
                        $data .= '  &#160;(<b>'.$vals['status']['unseen'].'</b>) ';
                    }
                    else {
                        $data .= '  &#160;('.$vals['status']['unseen'].') ';
                    }
                }
            }
            if ($this->pd['settings']['folder_detail'] == 2 && isset($vals['status']['messages']) && in_array($vals['name'], $this->pd['settings']['folder_check'])) {
                if (isset($vals['status']['unseen']) && in_array($vals['name'], $this->pd['settings']['folder_check'])) {
                    if ($vals['status']['unseen']) {
                        $data .= '  &#160;(<b>'.$vals['status']['unseen'].'</b>';
                    }
                    else {
                        $data .= '  &#160;('.$vals['status']['unseen'];
                    }
                }
                else {
                    $data .= ' &#160;( - ';
                }
                if (isset($vals['status']['messages']) && in_array($vals['name'], $this->pd['settings']['folder_check'])) {
                    $data .= '/'.$vals['status']['messages'].') ';
                }
            }
            if (($vals['has_kids'] && strtoupper($vals['name']) != 'INBOX') || $vals['noselect']) {
                $subfolders = array();
                foreach ($this->pd['folders'] as $atts) {
                    if (!isset($atts['basename'])) {
                        continue;
                    }
                    if ($vals['realname'] == $atts['parent']) {
                        $subfolders[$atts['realname']] = $atts;
                    }
                }
                if (!empty($subfolders)) {
                    if ($this->pd['settings']['folder_style'] == 1) {
                        $data .= '<div style="';
                        if ((isset($_SESSION['folder_state'][$hash]) && $_SESSION['folder_state'][$hash]) ||
                            (isset($this->pd['mailbox']) && substr($this->pd['mailbox'], 0, strlen($vals['name'])) == $vals['name'])) {
                            $data .= 'display: block;';
                        }
                        else {
                            $data .= 'display: none;';
                        }
                        $data .= '" id="folder_div_'.$hash.'">';
                    }
                    $data .= $this->print_folder_list($subfolders, $vals['realname'], $i, $inbox);
                    if ($this->pd['settings']['folder_style'] == 1) {
                        $data .= '</div>'; 
                    }
                }
            }
            $data .= '</li>';
        }
        $i++;
    }
    if ($this->pd['settings']['folder_style'] == 1 || !$parent) {
        $data .= '</ul></div>';
    }
    return $data;
}
function print_icon() {
    global $conf;
    global $page_id;
    if (isset($conf['site_logo'])) {
        $logo = $conf['site_logo'];
    }
    else {
        $logo = '<span>Hm<span class="super">2</span></span>';
    }
    $data = '<complex-'.$page_id.'>';
    $theme = 'default';
    if (isset($this->pd['settings']['theme'])) {
        $user_theme = $this->pd['settings']['theme'];
        if (isset($conf['site_themes'][$user_theme])) {
            if ($conf['site_themes'][$user_theme]['icons'] && $conf['site_themes'][$user_theme]['icons'] !== 'default') {
                $theme = $user_theme;
            }    
            elseif (!$conf['site_themes'][$user_theme]['icons']) {
                return $data;
            }
        }
    }
    if ($this->user->logged_in) { $data .= '<a href="?page=about&amp;mailbox='.urlencode($this->pd['mailbox']).'">'; }
    if ($this->user->user_agent_class == 'msie') {
        $data .= '<img src="images/spacer.png" style="width: 30px; height: 30px; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src=themes/'.$theme.'/icons/';
    }
    else {
        $data .= '<img src="themes/'.$theme.'/icons/';
    }
    if ($this->user->logged_in) {
        switch ($this->dsp_page) {
            case 'mailbox':
                if (isset($this->pd['settings']['trash_folder']) && $this->pd['mailbox'] == $this->pd['settings']['trash_folder']) {
                    if (!empty($this->pd['header_list'])) {
                        $data .= 'trash';
                    }
                    else {
                        $data .= 'empty_trash';
                    }
                }
                else {
                    if (!empty($this->pd['header_list'])) {
                        $data .= 'mailbox';
                    }
                    else {
                        $data .= 'empty_mailbox';
                    }
                }
                break;
            case 'login':
            case 'logout':
                $data .= 'default';
                break;
            case 'message':
            case 'about':
            case 'folders':
            case 'search':
            case 'compose':
            case 'profile':
            case 'new':
            case 'options':
            case 'contacts':
                $data .= $this->dsp_page;
                break;
            default:
                $data .= 'mailbox';
                break;
        }
    }
    else {
        $data .= 'default';
    }
    if ($this->user->user_agent_class == 'msie') {
        $data .= '.png, sizingMethod=scale);" />';
    }
    else {
        $data .= '.png" alt="-" title="Hastymail 2" />';
    }
    $data .= '&#160;'.$logo;
    if ($this->user->logged_in) { $data .= '</a>'; }
    $data .= '</complex-'.$page_id.'>';
    return $data;
}
function print_imap_server_opts() {
    global $conf;
    $data = '';
    $alt_servers = get_alt_servers($conf);
    if (!empty($alt_servers)) {
        $data .= $this->user->str[273].'<br /><select class="logintext" name="imap_server">';
        $data .= '<option value="0">';
        if (isset($conf['imap_display_name']) && $conf['imap_display_name']) {
            $data .= $conf['imap_display_name'];
        }
        $data .= '</option>';
        foreach ($alt_servers as $i => $vals) {
            if (isset($vals['imap_server'])) {
                $data .= '<option value="'.$i.'">';
                if (isset($vals['imap_display_name'])) {
                    if (isset($vals['imap_display_name']) && $vals['imap_display_name']) {
                        $data .= $vals['imap_display_name'];
                    }
                    else {
                        $data .= $vals['imap_server'];
                    }
                }
                $data .= '</option>';
            }
        }
        $data .= '</select>';
    }
    return $data;
}
function print_mailbox_list($cols=array('checkbox_cell', 'indicators_cell', 'subject_cell', 'from_cell', 'date_cell', 'size_cell'), $onclick=false) {
    $data = $this->print_mailbox_list_rows($cols, $this->pd['header_list'], $onclick, $this->pd['mailbox']);
    $_SESSION['toggle_all'] = false;
    $_SESSION['toggle_uids'] = array();
    $_SESSION['toggle_boxes'] = array();
    return $data;
}
function print_mailbox_list_headers($cols=array('subject_cell', 'from_cell', 'date_cell', 'size_cell')) {
    $data = '';
    $labels = array('subject_cell'  => $this->user->str[13],
                    'from_cell'     => $this->user->str[56],
                    'date_cell'     => $this->user->str[58],
                    'size_cell'     => $this->user->str[57]);
    if (isset($this->pd['settings']['sent_folder']) && $this->dsp_page == 'mailbox' &&
        $this->pd['mailbox'] == $this->pd['settings']['sent_folder']) {
        $labels['from_cell'] = $this->user->str[55];
    }
    foreach ($cols as $v) {
        if (isset($labels[$v])) {
            $data .= '<th>'.$labels[$v].'</th>';
        }
    }
    return $data;
}
function print_mailbox_list_rows($cols, $rows, $onclick, $mailbox, $n=1) {
    global $page_id;
    $data = '';
    $search_res = array();
    $date_format_2 = false;
    if (isset($this->pd['settings']['mailbox_date_format'])) {
        $date_format = $this->pd['settings']['mailbox_date_format'];
        if ($date_format != 'r' && $date_format != 'h') {
            if (isset($this->pd['settings']['mailbox_date_format_2'])) {
                $date_format_2 = $this->pd['settings']['mailbox_date_format_2'];
            }
        }
        elseif ($date_format == 'h') {
            $date_format = false;
        }
    }
    else {
        $date_format = false;
    }
    if (!$this->user->use_cookies && isset($_GET['rs'])) {
        $sid = '&amp;PHPSESSID='.session_id();
    } 
    else {
        $sid = '';
    }
    if ($this->dsp_page != 'search' && isset($this->pd['search_results'][$mailbox])) {
        $search_res = $this->pd['search_results'][$mailbox];
    }
    if (!isset($this->pd['mailbox_page'])) {
        $this->pd['mailbox_page'] = '';
    }
    if (!isset($this->pd['filter_by'])) {
        $this->pd['filter_by'] = '';
    }
    $list_count = count($rows);
    if (isset($_SESSION['toggle_uids'])) {
        $toggle_uids = $_SESSION['toggle_uids'];
    }
    else {
        $toggle_uids = array();
    }
    if (isset($_SESSION['toggle_all'])) {
        $toggle_all = $_SESSION['toggle_all'];
    }
    else {
        $toggle_all = false;
    }
    if (isset($_SESSION['toggle_boxes'])) {
        $toggle_boxes = $_SESSION['toggle_boxes'];
    }
    else {
        $toggle_boxes = array();
    }
    foreach ($rows as $vals) {
        if (isset($this->pd['settings']['hide_deleted_messages']) &&
            $this->pd['settings']['hide_deleted_messages'] &&
            stristr($vals['flags'], 'deleted')) {
            continue;
        }
        $message_url = '?page=message'.$sid.'&amp;uid='.$vals['uid'].'&amp;mailbox_page='.
                       $this->pd['mailbox_page'].'&amp;sort_by='.$this->pd['sort_by'].
                       '&amp;filter_by='.$this->pd['filter_by'].'&amp;mailbox='.
                       urlencode($mailbox);
        if ($onclick) {
            if (isset($this->pd['settings']['message_window']) && $this->pd['settings']['message_window']) {
                $js = 'onclick="open_window(\''.$message_url.'&amp;new_window=1&amp;parent_refresh=1\', 1024, 900, '.$vals['uid'].'); return false;" onmouseover="this.style.cursor=\'pointer\'"';
            }
            else {
                $js = 'onclick="document.location.href=\''.$message_url.'\';" onmouseover="this.style.cursor=\'pointer\'"';
            }
        }
        else {
            $js = false;
            if (isset($this->pd['settings']['message_window']) && $this->pd['settings']['message_window']) {
                $new_window = 'onclick="open_window(\''.$message_url.'&amp;new_window=1&amp;parent_refresh=1\', 1024, 900, '.$vals['uid'].'); return false;"';
            }
            else {
                $new_window = '';
            }
        }
        if (isset($this->pd['settings']['sent_folder']) &&
            $mailbox == $this->pd['settings']['sent_folder']) {
            $from = clean_from($vals['to']);
        }
        else {
            $from = clean_from($vals['from']);
        }
        $xtra_class = '';
        if (!empty($search_res) && in_array($vals['uid'], $search_res)) {
            $xtra_class = 'search_res ';
        }
        if (stristr($vals['flags'], 'seen')) {
            $class_prefix= 'mbx_';
        }
        else {
            $class_prefix= 'mbx_unseen_';
        }
        if ($n == $list_count) {
            $xtra_class .= ' last_row ';
        }
        if (!trim($vals['subject'])) {
            $vals['subject'] = 'no subject';
        }
        $indicators = '&#160;&#160;';
        if (stristr($vals['content-type'], 'multipart')) {
            $indicators .= '<span class="multi_ind">+&#160;</span>';
        }
        if (stristr($vals['flags'], 'answered')) {
            $indicators .= '<span class="reply_ind">r&#160;&#160;</span>';
        }
        if (stristr($vals['flags'], 'flagged')) {
            $indicators .= '<span class="flag_ind">f&#160;&#160;</span>';
            $xtra_class .= ' flagged ';
        }
        if (!empty($search_res) && in_array($vals['uid'], $search_res)) {
            $indicators .= '<span class="search_ind">*&#160;&#160;</span>';
        }
        if ($indicators) {
            $indicators = '<span class="'.$xtra_class.'indicators">'.$indicators.'</span>';
        }
        $subj_post = '';
        $subj_pre = '';
        if (isset($this->pd['thread_data'][$vals['uid']])) {
            $ind = $this->pd['thread_data'][$vals['uid']]['level'] - 1;
            $subj_pre = str_repeat('&#160;', 5*$ind);
            /*if (!$this->pd['thread_data'][$vals['uid']]['parent']) {
                if (isset($this->pd['thread_data'][$vals['uid']]['reply_count'])) {
                    $subj_post .= ' <span class="reply_count">replies: <b>'.$this->pd['thread_data'][$vals['uid']]['reply_count'].'</b></span>';
                }
                else {
                    $subj_post .= ' <span class="reply_count">replies: 0</span>';
                }
            }*/
        }
        if (stristr($vals['flags'], 'deleted')) {
            $subj_post .= '</span>';
            $subj_pre .= '<span class="deleted_message">';
        }
        
        $indicators_cell = '';
        $subject_cell = '';
        $from_cell = '';
        $date_cell = '';
        $size_cell = '';
        $checkbox_cell = '';
        $data .= '<tr>';
        $indicators_cell = '<td '.$js.' class="'.$xtra_class.$class_prefix.'indicators">';
        $indicators_cell .= $indicators.'</td>';
        $checkbox_cell = '<td class="'.$xtra_class.$class_prefix.'checkbox"><complex-'.$page_id.'>';
        if (isset($this->pd['last_message_read'][$mailbox]) &&
            $this->pd['last_message_read'][$mailbox] == $vals['uid']) {
            $checkbox_cell .= '<span class="last_read">&gt;</span>';
        }
        else {
            $checkbox_cell .= '<span class="last_read_hidden">&gt;</span>';
        }
        $checkbox_cell .= '</complex-'.$page_id.'><input type="hidden" id="mailboxes-'.  $vals['uid'].'" name="mailboxes['.$vals['uid'].']" value="'.
                          $this->user->htmlsafe($mailbox, false, false, true).'" /><input type="checkbox" ';
        if ($toggle_all && !in_array($vals['uid'], $toggle_uids) && isset($toggle_boxes[$vals['uid']]) && $toggle_boxes[$vals['uid']] == $mailbox) {
            $checkbox_cell .= 'checked="checked" ';
        }
        elseif (isset($_GET['toggle_folder']) && $_GET['toggle_folder'] == $mailbox) { $checkbox_cell .= 'checked="checked" '; }
        $checkbox_cell .= 'id="message_'.$n.'" name="uids[]" value="'.$vals['uid'].'" /><input type="hidden" name="mailboxes['.$vals['uid'].']" value="'.
                  $this->user->htmlsafe($mailbox, false, false, true).'" /></td>';
        $subject_cell = '<td '.$js.' class="'.$xtra_class.$class_prefix.'subject">'.$subj_pre.'<a '.$new_window.' href="'.$message_url.'">'.
                        $this->user->htmlsafe($vals['subject'], $vals['charset'], true).'</a>'.$subj_post.'</td>';
        $from_cell = '<td '.$js.' class="'.$xtra_class.$class_prefix.'from">'.$this->user->htmlsafe($from, $vals['charset'], true, false, false, false, true).'</td>';
        $date_cell = '<td '.$js.' class="'.$xtra_class.$class_prefix.'date" >'.print_time2($vals['date'], $date_format, $date_format_2).'</td>';
        $size_cell = '<td '.$js.' class="'.$xtra_class.$class_prefix.'size">'.format_size($vals['size']/1024).'</td>';
        foreach ($cols as $v) {
            if (isset($$v)) {
                $data .= $$v;
            }
        }
        $data .= '</tr>';
        $n++;
    }
    return $data;
}
function print_contact_detail($message_view=false) {
    $data = '';
    if (!$message_view) {
        $data .= '<h4>Contact Details</h4>';
    }
    if (isset($this->pd['card_detail']) && !empty($this->pd['card_detail'])) {
        $data .= '<table id="card_details" cellpadding="0" cellspacing="0">';
        foreach ($this->pd['card_detail'] as $vals) {
            if (!trim($vals['value'])) {
                continue;
            }
            $data .= '<tr><th>'.$this->user->htmlsafe(ucfirst(strtolower($vals['name'])));
            if ($vals['group'] == 'N') {
                $data .= ' Name';
            }
            if (isset($vals['properties'][0])) {
                $data .= ' '.$vals['properties'][0];
            } 
            $data .= '</th><td>'.$this->user->htmlsafe($vals['value']).'</td></tr>';
        }
        if (!$message_view) {
            $data .= '<tr><td></td><td><a href="?page=contacts&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;edit_card='.$this->pd['card_id'].'#contactform">Edit</a>
                      / <a href="?page=contacts&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;download_card='.$this->pd['card_id'].'">Export</a></td></tr>';
        }
        else {
            $data .= '<tr><td></td><td><a href="?page=contacts&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;import_card_attachment=1#contact_form">'.$this->user->str[146].'</a></td></tr>';
        }
        $data .= '</table>';
    }
    return $data;
}
}
?>
