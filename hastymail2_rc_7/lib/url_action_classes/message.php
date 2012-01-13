<?php

/*  url_action_class.php: Process $_GET values
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

class fw_user_action_page extends fw_user_action {
function url_action_message($get) {
    global $sort_types;
    global $client_sort_types;
    global $max_read_length;
    global $user;
    global $imap;
    global $include_path;
    global $message_part_types;
    global $conf;
    global $fd;
    $user->page_data['top_link'] = '<a href="'.$user->sticky_url.'#top">'.$user->str[186].'</a>';
    if ($user->logged_in) {
        do_work_hook('message_page_start');
        $user->page_data['show_previous_options'] = 0;
        $user->page_data['show_up_options'] = 0;
        $user->page_data['show_next_options'] = 0;
        $user->page_data['sort_filters'] = array( 'ALL' => 'All messages',
            'UNSEEN' => 'Unread', 'SEEN' => 'Read', 'FLAGGED' => 'Flagged', 'UNFLAGGED' => 'Unflagged',
            'ANSWERED' => 'Answered', 'UNANSWERED' => 'Unanswered', 'DELETED' => 'Deleted', 'UNDELETED' => 'Not Deleted');

        $user->page_data['message_link_class'] ='current_page';
        $mailbox = false;
        if (isset($get['mailbox'])) {
            if (isset($_SESSION['folders'][$get['mailbox']])) {
                $mailbox = $get['mailbox'];
            }
        }
        if (isset($get['find_response']) && isset($get['current_uid']) && isset($get['response_id'])) {
            $uid = false;
            if (isset($_SESSION['user_settings']['sent_folder'])) {
                $select_res = $imap->select_mailbox($_SESSION['user_settings']['sent_folder'], false, false, true);
                if ($select_res) {
                    $search_res = $imap->simple_search('header in-reply-to', false, $get['response_id']);
                    if (isset($search_res[0])) {
                        $get['uid'] = $search_res[0];
                        $uid = $get['uid'];
                        $mailbox = $_SESSION['user_settings']['sent_folder'];
                        $this->errors[] = $user->str[388];
                    }
                }
            }
            if (!$uid) {
                $get['uid'] = $get['current_uid'];
                $this->errors[] = $user->str[389];
            }
        }
        if ($mailbox && isset($get['uid'])) {
            if (isset($_SESSION['frozen_folders'][$mailbox])) {
                $user->page_data['frozen_dsp'] = '<span id="frozen">(Mailbox Frozen)</span>';
            }
            else {
                $user->page_data['frozen_dsp'] = '';
            }
            $id = (int) $get['uid'];
            if ($id) {
                $sort_by = 'ARRIVAL';
                if (isset($get['sort_by'])) {
                    if (stristr($_SESSION['imap_capability'], 'SORT')) {
                        $types = $sort_types;
                    }
                    else {
                        $types = $client_sort_types;
                    }
                    if (isset($types[$get['sort_by']])) {
                        $sort_by = $get['sort_by'];
                    }
                }
                $filter_by = 'ALL';
                if (isset($get['filter_by'])) {
                    if (isset($user->page_data['sort_filters'][$get['filter_by']])) {
                        $filter_by = $get['filter_by'];
                    }
                }
                $user->page_data['filter_by'] = $filter_by;
                $user->page_data['sort_by'] = $sort_by;
                if ($imap->select_mailbox($mailbox, $sort_by, false, true, $filter_by)) {
                    if (isset($_SESSION['last_prev_next_folder'])) {
                        $user->page_data['last_prev_next_folder'] = $_SESSION['last_prev_next_folder'];
                    }
                    else {
                        $user->page_data['last_prev_next_folder'] = false;
                    }
                    $user->page_data['mailbox'] = $mailbox;
                    if ($mailbox == 'INBOX') {
                        $user->page_data['mailbox_dsp'] = $user->str[436];
                    }
                    else {
                        $user->page_data['mailbox_dsp'] = $user->htmlsafe($mailbox, 0, 0, 1);
                    }
                    $user->page_data['url_mailbox'] = urlencode($mailbox);
                    $struct = $imap->get_message_structure($id);
                    $flat_list = $this->get_flat_part_list($struct);
                    $user->page_data['part_nav_list'] = $flat_list;
                    if (isset($get['raw_view']) && $get['raw_view']) {
                        $raw = 1;
                    }
                    else {
                        $raw = 0;
                    }
                    $user->page_data['raw_view'] = $raw;
                    if (isset($get['message_part'])) {
                        $mpart = $get['message_part'];
                    }
                    else {
                        $mpart = 0;
                    }
                    $sort_by = 'ARRIVAL';
                    if (isset($_SESSION['user_settings']['html_first']) && $_SESSION['user_settings']['html_first']) {
                        $message_data = $this->find_message_part($struct, $mpart, 'text', 'html');
                        if (empty($message_data)) {
                            $message_data = $this->find_message_part($struct, $mpart);
                        }
                    }
                    else {
                        $message_data = $this->find_message_part($struct, $mpart, 'text', 'plain');
                        if (empty($message_data)) {
                            $message_data = $this->find_message_part($struct, $mpart);
                        }
                    }
                    $user->page_data['message_struct'] = $struct;
                    $user->page_data['message_uid'] = $id;
                    $user->page_data['message_part'] = $mpart;
                    $count = $_SESSION['uid_cache'][$mailbox]['total'];
                    $count = $_SESSION['uid_cache'][$mailbox]['total'];
                    $page = 1;
                    if (isset($get['mailbox_page'])) {
                        $page = (int) $get['mailbox_page'];
                        if (!$page) {
                            $page = 1;
                        }
                    } 
                    $user->page_data['previous_uid'] = false;
                    $user->page_data['uid_index'] = false;
                    $user->page_data['next_uid'] = false;
                    for ($i=0;$i<$count;$i++) {
                        if ($id == $_SESSION['uid_cache'][$mailbox]['uids'][$i]) {
                            $page = floor($i/$user->page_data['settings']['mailbox_per_page_count']) + 1;
                            $user->page_data['uid_index'] = $i;
                            if (isset($_SESSION['uid_cache'][$mailbox]['uids'][($i + 1)])) {
                                $user->page_data['next_uid'] = $_SESSION['uid_cache'][$mailbox]['uids'][($i + 1)];
                                $user->page_data['next_uid_page'] = floor(($i + 1)/$user->page_data['settings']['mailbox_per_page_count']) + 1;
                            }
                            if (isset($_SESSION['uid_cache'][$mailbox]['uids'][($i - 1)])) {
                                $user->page_data['previous_uid'] = $_SESSION['uid_cache'][$mailbox]['uids'][($i - 1)];
                                $user->page_data['prev_uid_page'] = floor(($i - 1)/$user->page_data['settings']['mailbox_per_page_count']) + 1;
                            }
                            break;
                        }
                    }
                    if (isset($_SESSION['header_cache'][$mailbox][$page][$id])) {
                        $user->page_data['header_flags'] = $_SESSION['header_cache'][$mailbox][$page][$id]['flags'];
                    }
                        $user->page_data['show_small_headers'] = 0;
                        $user->page_data['show_full_headers'] = 0;
                        if (isset($get['full_headers']) && $get['full_headers']) {
                            $user->page_data['show_full_headers'] = 1;
                        }
                        elseif (isset($get['small_headers']) && $get['small_headers']) {
                            $user->page_data['show_small_headers'] = 1;
                        }
                    if (!empty($message_data)) {
                        $user->page_data['message_part'] = $message_data['imap_id'];
                        $type = strtolower($message_data['type'].'/'.$message_data['subtype']);
                        $user->page_data['charset'] = 'us-ascii';
                        if (isset($message_data['charset'])) {
                            $user->page_data['charset'] = strtolower($message_data['charset']);
                        }
                        $user->page_data['raw_message_type'] = $type;
                        if (isset($message_part_types[$type]) || isset($get['download'])) {
                            $user->page_data['message_type'] = false;
                            if (isset($message_part_types[$type])) {
                                $user->page_data['message_type'] = $message_part_types[$type];
                            }
                            if (isset($get['show_image']) && strtolower($message_data['type'] == 'image')) {
                                $data = $imap->get_message_part($id, $message_data['imap_id']);
                                if (isset($message_data['encoding']) && strtolower($message_data['encoding']) == 'base64') {
                                    $data = base64_decode($data);
                                }
                                elseif (isset($message_data['encoding']) && strtolower($message_data['encoding']) == 'quoted-printable') {
                                    $data = quoted_printable_decode($data);
                                }
                                ob_end_clean();
                                if ($data) {
                                    if (isset($get['thumbnail']) && $get['thumbnail'] && function_exists('imagecreatefromstring')) {
                                        $im = @imagecreatefromstring($data);
                                        $width = imagesx($im);
                                        $height = imagesy($im);
                                        $max_width = 80;
                                        $max_height = 60;
                                        if ($width > $max_width) {
                                            $new_width = $max_width;
                                            $new_height = ($new_width*$height)/$width;
                                            if ($new_height > $max_height) {
                                                $new_height = $max_height;
                                                $new_width = ($new_height*$width)/$height;
                                            }
                                        }
                                        elseif ($height > $max_height) {
                                            $new_height = $max_height;
                                            $new_width = ($new_height*$width)/$height;
                                        }
                                        else {
                                            $new_height = $height;
                                            $new_width = $width;
                                        }
                                        if (!$new_height || !$new_width) {
                                            $new_height = 50;
                                            $new_width = 50;
                                        }
                                        $im2 = @imagecreatetruecolor($new_width, $new_height);
                                        imagecolortransparent($im2, imagecolorallocate($im2, 0, 0, 0));
                                        imagealphablending($im2, false);
                                        imagesavealpha($im2, true);
                                        if ($im2 !== false && $im !== false) {
                                            imagecopyresampled($im2, $im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                                            imagepng($im2);
                                        }
                                        $imap->disconnect();
                                        $user->clean_up();
                                        exit;
                                    }
                                    $size = (int) $message_data['size'];
                                    header("Content-Type: ".$type);
                                    header("Content-Length: ".strlen($data));
                                    echo $data;
                                    $imap->disconnect();
                                    $user->clean_up();
                                    exit;
                                }
                            }
                            elseif (isset($get['download'])) {
                                if (isset($message_data['filename']) && $message_data['filename']) {
                                    $name = $message_data['filename'];
                                }
                                elseif (isset($message_data['name']) && $message_data['name']) {
                                    $name = $message_data['name'];
                                }
                                else {
                                    $name = 'message_'.$message_data['imap_id'];
                                }
                                $exten = '';
                                switch ($type) {
                                    case 'text/html':
                                        $exten = '.htm';
                                        break;
                                    case 'image/jpeg':
                                    case 'image/pjpeg':
                                    case 'image/jpg':
                                        $exten = '.jpg';
                                        break;
                                    case 'image/gif':
                                        $exten = '.gif';
                                        break;
                                    case 'image/png':
                                        $exten = '.png';
                                        break;
                                    case 'image/bmp':
                                        $exten = '.bmp';
                                        break;
                                    case 'message/rfc822':
                                        $exten = '.eml';
                                        break;
                                    case 'application/pgp-signature':
                                    case 'message/disposition-notification':
                                    case 'message/delivery-status':
                                    case 'message/rfc822-headers':
                                    case 'text/plain':
                                    case 'text/unkown':
                                        $exten = '.txt';
                                        break;
                                    case 'text/enriched':
                                        $exten = '.rtf';
                                        break;
                                }
                                if (strtolower(substr($name, -4)) != $exten) {
                                    $name .= $exten;
                                }
                                $encoding = false;
                                if (isset($message_data['encoding']) && strtolower($message_data['encoding']) == 'base64') {
                                    $encoding = 'base64_decode';
                                }
                                elseif (isset($message_data['encoding']) && strtolower($message_data['encoding'] == 'quoted-printable')) {
                                    $encoding =  'quoted_decode';
                                }
                                $size = 0;
                                $left_over = '';
                                $read_size = 0;
                                $lit_size = $imap->get_message_part_start($id, $message_data['imap_id']);
                                $size = $lit_size;
                                header("Content-Type: $type");
                                header("Pragma: public");
                                header("Expires: 0");
                                header('Cache-Control: must-revalidate');
                                header('Content-Disposition: attachment; filename="'.$name.'"');
                                ob_end_clean();
                                while ($data = $imap->get_message_part_line()) {
                                    $read_size += strlen($data);
                                    if ($read_size > $lit_size) {
                                        if (substr($data, -3, 1) == ')') {
                                            $data = substr($data, 0, -3);
                                        }
                                    }
                                    if ($encoding == 'base64_decode') {
                                        $data = base64_decode($data);
                                    }
                                    elseif ($encoding == 'quoted_decode') {
                                        $data = $user->user_action->quoted_decode($data);
                                    }
                                    if ($data) {
                                        echo $data;
                                    }
                                    $data = false;
                                }
                                $imap->disconnect();
                                $user->clean_up();
                                exit;
                            }
                            else {
                                if (isset($_SESSION['header_cache'][$mailbox][$page][$id]['flags'])) {
                                    if (!stristr($_SESSION['header_cache'][$mailbox][$page][$id]['flags'], 'seen')) {
                                        if (isset($_SESSION['folders'][$mailbox]['status']['unseen']) && $_SESSION['folders'][$mailbox]['status']['unseen'] > 0) {
                                            $_SESSION['folders'][$mailbox]['status']['unseen'] -= 1;
                                            $user->page_data['folders'] = $_SESSION['folders'];
                                        }
                                    }
                                    if (!stristr($_SESSION['header_cache'][$mailbox][$page][$id]['flags'], 'Seen')) {
                                        $_SESSION['header_cache'][$mailbox][$page][$id]['flags'] .= ' \Seen';
                                        if (isset($_SESSION['total_unread']) && $_SESSION['total_unread'] > 0 &&
                                            isset($_SESSION['user_settings']['folder_check']) && is_array($_SESSION['user_settings']['folder_check']) &&
                                            in_array($mailbox, $_SESSION['user_settings']['folder_check'])) {
                                            $_SESSION['total_unread']--;
                                        }
                                    }
                                }
                                $user->page_data['full_message_headers'] = $imap->get_message_headers($id, false);
                                $user->page_data['message_headers'] = $this->prep_headers($user->page_data['full_message_headers']);
                                if (!$user->page_data['charset']) {
                                    foreach ($user->page_data['message_headers'] as $vals) {
                                        if (strtolower($vals[0]) == 'content-type') {
                                            if (preg_match("/charset=([^ ]+)/", $vals[1], $matches)) {
                                                $user->page_data['charset'] = trim(str_replace(array("'", '"'), '', $matches[1]));
                                            }
                                            break;
                                        }
                                    }
                                        
                                }
                                if (count($user->page_data['part_nav_list']) > 1) {
                                    $parent_id = 0;
                                    foreach ($flat_list as $vals) {
                                        if ($vals[0] == $message_data['imap_id'] && $vals[1]) {
                                            $parent_id = $vals[1];
                                            break;
                                        }
                                    }
                                    if (!$parent_id) {
                                        $parent_id = $message_data['imap_id'];
                                    }
                                    $user->page_data['message_part_headers'] = $this->prep_headers($imap->get_message_headers($id, $parent_id));
                                }
                                if ($raw) {
                                    $user->page_data['message_data'] = $imap->get_message_part($id, false, $raw, $max_read_length);
                                    if (isset($user->page_data['message_part_headers'])) {
                                        unset($user->page_data['message_part_headers']);
                                    }
                                    $user->page_data['part_nav_list'] = array();
                                }
                                elseif ($message_part_types[$type] == 'text' || $message_part_types[$type] == 'html') {
                                    $user->page_data['message_data'] = $imap->get_message_part($id, $message_data['imap_id'], $raw, $max_read_length);
                                    if ($imap->max_read) {
                                        $this->errors[] = $user->str[390];
                                        $imap->max_read = false;
                                    }
                                    if ($message_part_types[$type] == 'html') {
                                        if (isset($get['show_external_images'])) {
                                            if ($get['show_external_images']) {
                                                $user->page_data['show_external_images'] = true;
                                            }
                                            else { 
                                                $user->page_data['show_external_images'] = false;
                                            }
                                        }
                                    }
                                    if (!$raw) {
                                        if (isset($message_data['encoding']) && strtolower($message_data['encoding']) == 'base64') {
                                            $user->page_data['message_data'] = base64_decode($user->page_data['message_data']);
                                        }
                                        elseif (isset($message_data['encoding']) && strtolower($message_data['encoding'] == 'quoted-printable')) {
                                            $user->page_data['message_data'] = $user->user_action->quoted_decode($user->page_data['message_data']);
                                        }
                                    }
                                    if ($message_part_types[$type] == 'html' && isset($get['inline_html']) && $get['inline_html']) {
                                        ob_clean();
                                        if ($user->sub_class_names['url']) {
                                            $class_name = 'site_page_'.$user->sub_class_names['url'];
                                            $pd =& new $class_name();
                                        }
                                        else {
                                            $pd =& new site_page();
                                        }
                                        echo $pd->print_message_iframe_content();
                                        exit;
                                    }
                                    if (strstr($type, 'x-vcard')) {
                                        require_once($include_path.'lib'.$fd.'vcard.php');
                                        $vcard =& new vcard();
                                        $vcard->import_card(explode("\r\n", $user->page_data['message_data']));
                                        if (is_array($vcard->card) && !empty($vcard->card)) {
                                            $user->page_data['card_detail'] = $vcard->card;
                                            $_SESSION['import_card_detail'] = $vcard->card;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if (!isset($user->page_data['full_message_headers'])) {
                        $user->page_data['full_message_headers'] = $imap->get_message_headers($id, false);
                        $user->page_data['message_headers'] = $this->prep_headers($user->page_data['full_message_headers']);
                        $user->page_data['charset'] = false;
                        if (!isset($user->page_data['charset'])) {
                            foreach ($user->page_data['message_headers'] as $vals) {
                                if (strtolower($vals[0]) == 'content-type') {
                                    if (preg_match("/charset=([^ ]+)/", $vals[1], $matches)) {
                                        $user->page_data['charset'] = trim(str_replace(array("'", '"'), '', $matches[1]));
                                    }
                                    break;
                                }
                            }
                        }
                    }
                    $user->page_data['mailbox_page'] = $page;
                    $user->page_data['mailbox_total'] = $_SESSION['uid_cache'][$mailbox]['total'];
                    $user->page_data['page_links'] = build_page_links($page, $_SESSION['uid_cache'][$mailbox]['total'],
                                                     $user->page_data['settings']['mailbox_per_page_count'], '?page=mailbox&amp;sort_by='.$sort_by.
                                                     '&amp;filter_by='.$filter_by.'&amp;mailbox='.urlencode($mailbox), $user->str[88]);
                    $user->dsp_page = 'message';
                    $_SESSION['last_message_read'][$mailbox] = $id;
                    if (isset($get['print_view']) && $get['print_view']) {
                        ob_clean();
                        if ($user->sub_class_names['url']) {
                            $class_name = 'site_page_'.$user->sub_class_names['url'];
                            $pd =& new $class_name();
                        }
                        else {
                            $pd =& new site_page();
                        }
                        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                              <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
                                <head><title>Message Print View</title><base href="'.$pd->pd['base_href'].'" /><title id="title">'.$pd->user->page_title.'</title>
                                <style type="text/css">table {padding:10px;margin-left:-10px;padding-bottom:20px;}
                                table td {padding-left:10px;} table th {text-align:left;font-weight:normal;}
                                pre {white-space: pre-wrap; white-space: -moz-pre-wrap !important; white-space: -pre-wrap; white-space: -o-pre-wrap; word-wrap: break-word;}
                                </style></head>
                            <body style="background: none; background-image: none; background-color: #fff; color: #000; margin: 30px;">'.
                            '<table>'.$pd->print_message_headers().'</table>';
                            if (isset($pd->pd['message_part_headers'])) {
                                '<table>'.$pd->print_part_headers().'</table.';
                            }
                        //$pd->pd['raw_view'] = true;
                        switch ($user->page_data['message_type']) {
                            case 'text':
                            case 'image':
                            case 'html':
                                echo $pd->{'print_message_'.$user->page_data['message_type']}();
                                break;
                            default:
                                echo '<div style="text-align: center; margin-top: 100px;">Unsupported MIME type: '.
                                        $user->htmlsafe($user->page_data['raw_message_type']).'</div>';
                                break;
                        }
                        echo '
                        </body>
                        </html>'; 
                        exit;
                    }
                    if (isset($get['show_up_options'])) {
                        $user->page_data['show_up_options'] = 1;
                    }
                    if (isset($get['show_next_options'])) {
                        $user->page_data['show_next_options'] = 1;
                    }
                    if (isset($get['show_previous_options'])) {
                        $user->page_data['show_previous_options'] = 1;
                    }
                    $new_contacts = array();
                    if (isset($user->page_data['full_message_headers'])) {
                        foreach($user->page_data['full_message_headers'] as $vals) {
                            $i = $vals[0];
                            $v = $vals[1];
                            if (strtolower($i) == 'received') {
                                continue;
                            }
                            if (strstr($v, ' ')) {
                                $bits = explode(' ', trim($v));
                            }
                            else {
                                $bits = array($v);
                            }
                            foreach ($bits as $val) {
                                $val = rtrim(ltrim($val, '<'), '>');
                                if ($this->match_email($val)) {
                                    $new_contacts[] = $val;
                                }
                            }
                        }
                    }
                    $user->page_data['new_contacts'] = $new_contacts;
                    $imap->get_mailbox_unseen($mailbox);
                    if (isset($_SESSION['search_results'])) {
                        $user->page_data['search_results'] = $_SESSION['search_results'];
                    }
                    if (isset($_SESSION['uid_cache'][$mailbox]['thread_data'])) {
                        $user->page_data['thread_data'] = $_SESSION['uid_cache'][$mailbox]['thread_data'];
                    }
                    $user->page_data['folders'] = $_SESSION['folders'];
                    $user->page_title .= ' | Message |';
                }
            }
        }
    }
}
function prep_headers($headers) {
    global $user;
    $short_list = $_SESSION['user_settings']['small_headers'];
    $data = '';
    if ($user->page_data['raw_view']) {
        $res = array();
    }
    elseif ($_SESSION['user_settings']['full_headers_default'] && !$user->page_data['show_small_headers']) {
        $res = $headers;
    }
    elseif ($user->page_data['show_full_headers']) {
        $res = $headers;
    }
    else {
        $res = array();
        foreach ($short_list as $v) {
            $found = false;
            foreach ($headers as $vals) {
                if (strtolower($vals[0]) == $v) {
                    $res[] = array($vals[0], $vals[1]);
                    $found = true;
                    break;
                }
            }
            /*if (!$found) {
                $res[] = array($v, '');
            }*/
        }
    }
    return $res;
}
function get_flat_part_list($struct, $list=array(), $parent=0) {
    global $message_part_types;
    foreach ($struct as $i => $v) {
        if (isset($v['type']) && $v['subtype']) {
            if (isset($message_part_types[strtolower($v['type'].'/'.$v['subtype'])])) {
                $list[] = array($i, $parent);
            }
        }
        if (isset($v['subs']) && is_array($v['subs'])) {
            $list = $this->get_flat_part_list($v['subs'], $list, $i);
        }
    }
    return $list;
}
}

class site_page_message extends site_page {
function print_message_text() {
    $data = '<div id="message_text" style="font-family: '.$this->pd['settings']['font_family'].';">';
    if ($this->pd['raw_view']) {
        $data .= '<div id="raw_message_text"><pre id="msg_pre">'.$this->user->htmlsafe($this->pd['message_data'], false, false, false, false, false, true).'</pre></div>';
    }
    elseif (isset($this->pd['card_detail'])) {
        $data = $this->print_contact_detail(true);
    }
    else {
        $data .= '<pre id="msg_pre">'.prep_text_part($this->pd['message_data'], $this->pd['charset']).'</pre>';
    }
    $data .= '</div>';
    return $data;
}
function print_message_html($clean=false) {
    $override = false;
    if (isset($this->pd['settings']['remote_image']) && $this->pd['settings']['remote_image']) {
        $image_replace = false;
    }
    else {
        $image_replace = true;
    }
    if (isset($this->pd['show_external_images']) && $this->pd['show_external_images']) {
        $image_replace = false;
        $override = true;
    }
    $data = '';
    if (!$clean) {
        $data .= '<div id="message_html"><table cellpadding="0" cellspacing="0" width="100%"><tr><td>';
    }
    $data .= prep_html_part($this->user->htmlclean($this->pd['message_data'], array(), false, $this->pd['charset']),
            $this->pd['message_uid'], $this->pd['mailbox'], $image_replace, $override);
    if (!$clean) {
        $data .= '</td></tr></table></div>';
    }
    return $data;
}
function print_message_image() {
    if (!$this->user->use_cookies) {
        $sess = '&PHPSESSID='.session_id();
    }
    else {
        $sess = '';
    }
    $data = '<div id="message_image"><img src="?page=message&amp;uid='.$this->pd['message_uid'].'&amp;message_part='.
             $this->pd['message_part'].'&amp;show_image=1&amp;mailbox='.$this->pd['mailbox'].$sess.'" /></div>';
    return $data;
}
function print_message_parts_inner($parts, $url_base, $level=0) {
    global $message_part_types;
    global $page_id;
    $data = '';
    if (!is_array($parts) || empty($parts)) {
        return;
    }
    foreach ($parts as $id => $vals) {
        if (isset($vals['type']) && isset($vals['subtype']) && count($vals) > 2) {
            $current = false;
            if (!isset($vals['charset'])) {
                $vals['charset'] = false;
            }
            $pad = str_repeat('&#160;', 7*$level);
            if ($id == $this->pd['message_part']) {
                $current = true;
                $pre = '<complex-'.$page_id.'><div class="current_part">-&gt;</div></complex-'.$page_id.'>
                        <simple-'.$page_id.'>-&gt;</simple-'.$page_id.'>';
            }
            else {
                $pre = '<complex-'.$page_id.'><div class="current_part" style="visibility: hidden;">-&gt;</div></complex-'.$page_id.'>';
            }
            if ($vals['subtype'] != 'rfc822' && $level > 0) {
                $xtra_class = 'inner_part';
            }
            else {
                $xtra_class = '';
            }
            if (isset($message_part_types[strtolower($vals['type'].'/'.$vals['subtype'])])) {
                $data .= '<tr ';
                if ($current) { $data .= 'class="current_part_row" '; }
                $data .= '><td nowrap="nowrap" class="view_cell '.$xtra_class.'">'.$pre.' <a href="'.$url_base.'&amp;message_part='.$id.
                         $this->pd['new_window_arg'].'">'.$this->user->str[85].'</a> &#160;| &#160;<a href="'.$url_base.'&amp;message_part='.$id.
                         '&amp;download=1">'.$this->user->str[86].'</a></td>';
            }
            else {
                $data .= '<tr><td nowrap="nowrap" class="view_cell '.$xtra_class.'">'.$pre.
                         ' <a href="'.$url_base.'&amp;message_part='.$id.'&amp;download=1">'.$this->user->str[86].'</a></td>';
            }
            $data .= '<td class="small_cell '.$xtra_class.'">'.$pad.$this->user->htmlsafe($vals['type']).' / '.$this->user->htmlsafe($vals['subtype']).'</td>';
            if (isset($vals['filename']) && trim($vals['filename'])) {
                $vals['name'] = $this->user->htmlsafe($vals['filename'], $vals['charset'], true);
            }
            elseif (!isset($vals['name']) || !trim($vals['name'])) {
                $vals['name'] = 'message_'.$id;
            }
            $data .= '<td class="filename_cell '.$xtra_class.'">'.$this->user->htmlsafe($vals['name'], $vals['charset'], true);
            if (isset($message_part_types[strtolower($vals['type'].'/'.$vals['subtype'])]) && strtolower($vals['type']) == 'image') {
                if (isset($this->pd['settings']['image_thumbs']) && $this->pd['settings']['image_thumbs']) {
                    if (!$this->user->use_cookies) {
                        $sess = '&PHPSESSID='.session_id();
                    }
                    else {
                        $sess = '';
                    }
                    $data .= '<br /><img src="?page=message&amp;thumbnail=1&amp;rand='.$page_id.'&amp;show_image=1&amp;mailbox='.
                          urlencode($this->pd['mailbox']).'&amp;uid='.$this->pd['message_uid'].'&amp;message_part='.$id.$sess.'" />';
                }
            } 
            $data .= '</td><td class="description_cell '.$xtra_class.'">';
            $meta = '';
            if (isset($vals['subject']) && $vals['subject']) {
                $meta .= '<b>'.$this->user->str[13].'</b>: '.$this->user->htmlsafe(stripslashes($vals['subject']), $vals['charset'], true).'<br />';
                if (isset($vals['from']) && $vals['from']) {
                    $meta .= '<b>'.$this->user->str[56].'</b>: '.$this->user->htmlsafe(stripslashes($vals['from']), $vals['charset'], true).'<br />';
                }
            }
            if (isset($vals['description']) && $vals['description'] && strtoupper($vals['description']) != 'NIL') {
                $meta .= $this->user->htmlsafe(stripslashes($vals['description']), $vals['charset'], true);
            }
            $data .= $meta.'</td><td class="small_cell '.$xtra_class.'">'.$this->user->htmlsafe($vals['charset']).'</td><td class="small_cell '.
                     $xtra_class.'">'.$this->user->htmlsafe($vals['encoding']).'</td><td class="small_cell '.$xtra_class.'">'.format_size($vals['size']/1024).'</td></tr>';
        }
        if (isset($vals['subs']) && !empty($vals['subs'])) {
            $data .= $this->print_message_parts_inner($vals['subs'], $url_base, ($level + 1));
        }
    }
    return $data;
}
function print_message_parts() {
    $data = '<tr><th></th><th>'.$this->user->str[81].'</th><th>'.$this->user->str[43].'</th><th>'.$this->user->str[82].'</th><th>'.
            $this->user->str[83].'</th><th>'.$this->user->str[84].'</th><th>'.$this->user->str[57].'</th></tr>';
    $url_base = '?page=message&amp;uid='.$this->pd['message_uid'].'&amp;sort_by='.$this->pd['sort_by'].'&amp;filter_by='.$this->pd['filter_by'].
                '&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;mailbox_page='.$this->pd['mailbox_page'];
    $data .= $this->print_message_parts_inner($this->pd['message_struct'], $url_base);
    return $data;
}
function print_message_headers() {
    $short_list = $_SESSION['user_settings']['small_headers'];
    $data = do_display_hook('message_headers_top');
    foreach ($this->pd['message_headers'] as $vals) {
        $name = $this->user->htmlsafe($vals[0], $this->pd['charset'], true);
        switch (strtolower($name)) {
            case 'subject':
                $name = $this->user->str[13];
                break;
            case 'from':
                $name = $this->user->str[56];
                break;
            case 'to':
                $name = $this->user->str[55];
                break;
            case 'date':
                $name = $this->user->str[58];
                break;
        }
        $val = $this->user->htmlsafe($vals[1], $this->pd['charset'], true);
        $data .= '<tr><th>'.$name.': </th><td ';
        if (strtolower($vals[0]) == 'subject') {
            $data .= 'class="subject_cell" ';
        }
        $data .= '>'.$val;
        if (strtolower($vals[0]) == 'date' && $vals[1] && !$this->pd['show_full_headers']) {
            $data .= ' &#160;&#160; ('.print_time(strtotime($vals[1]), $vals[1]).')';
        }
        $data .= '<br /></td></tr>';
    }
    if (in_array('IMAP message flags', $short_list) && isset($this->pd['header_flags'])) {
        $data .= '<tr><th>Flags</th><td>'.str_replace(array(' ', '\\'), array(', ', ''), trim($this->pd['header_flags'])).'</td></tr>';
    }
    if (!isset($this->pd['raw_view']) || !$this->pd['raw_view']) {
        $data .= $this->print_add_contact_form();
    }
    return $data;
}
function print_message_prev_next_part() {
    global $conf;
    $theme = 'default';
    if (isset($this->pd['settings']['theme'])) {
        $user_theme = $this->pd['settings']['theme'];
        if (isset($conf['site_themes'][$user_theme])) {
            if ($conf['site_themes'][$user_theme]['css']) {
                $theme = $user_theme;
            }    
        }
    }
    $data = '<span class="message_parts_heading"><a href="'.$this->sticky_url.'#parts">'.$this->user->str[80].'</a></span>';
    $img_path = 'themes/'.$theme.'/images';


    $prev_part = false;
    $next_part = false;
    $part = $this->pd['message_part'];

    if (isset($this->pd['part_nav_list']) && !empty($this->pd['part_nav_list'])) {
        $count = count($this->pd['part_nav_list']);
        $parts = $this->pd['part_nav_list'];
        for ($i=0;$i<$count;$i++) {
            if ($part == $parts[$i][0]) {
                if (isset($parts[($i - 1)][0])) {
                    $prev_part = $parts[($i - 1)][0];
                }
                if (isset($parts[($i + 1)][0])) {
                    $next_part = $parts[($i + 1)][0];
                }
                break;
            }
        }
    }
    if ($prev_part) {
        $data .= '<a href="?page=message&amp;uid='.$this->pd['message_uid'].'&amp;sort_by='.$this->pd['sort_by'].'&amp;filter_by='.$this->pd['filter_by'].'&amp;mailbox='.
                 urlencode($this->pd['mailbox']).'&amp;message_part='.$prev_part.$this->pd['new_window_arg'].
                 '"><img border="0" src="'.$img_path.'/prev.png" title="'.$this->user->str[318].'" alt="&lt;" /></a>';
    }
    else {
        $data .= '<a><img class="disabled_button" src="'.$img_path.'/prev.png" border="0" alt="&lt;" /></a>';
    }
    if ($next_part) {
        $data .= '<a href="?page=message&amp;uid='.$this->pd['message_uid'].'&amp;sort_by='.$this->pd['sort_by'].'&amp;filter_by='.$this->pd['filter_by'].'&amp;mailbox='.
                 urlencode($this->pd['mailbox']).'&amp;message_part='.$next_part.$this->pd['new_window_arg'].
                 '"><img alt="&gt;" title="'.$this->user->str[319].'" src="'.$img_path.'/next.png" border="0" /></a>';
    }
    else {
        $data .= '<a><img class="disabled_button" src="'.$img_path.'/next.png" alt="&gt;" /></a>';
    }
    return $data;
}
function print_message_prev_next_small() {
    global $conf;
    global $page_id;
    $theme = 'default';
    if (isset($this->pd['settings']['theme'])) {
        $user_theme = $this->pd['settings']['theme'];
        if (isset($conf['site_themes'][$user_theme])) {
            if ($conf['site_themes'][$user_theme]['css']) {
                $theme = $user_theme;
            }    
        }
    }
    $data = '';
    if ($this->pd['new_window_arg']) {
        $new_window_arg = $this->pd['new_window_arg'].'&amp;parent_refresh=1';
    }
    else {
        $new_window_arg = '';
    }
    $img_path = 'themes/'.$theme.'/images';
    if ($this->pd['previous_uid']) {
        $data .= '<a href="?page=message&amp;uid='.$this->pd['previous_uid'].'&amp;sort_by='.$this->pd['sort_by'].'&amp;filter_by='.$this->pd['filter_by'].
                '&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;mailbox_page='.$this->pd['prev_uid_page'].$new_window_arg.'"><complex-'.$page_id.'><img border="0" src="'.
                $img_path.'/prev.png" title="'.$this->user->str[315].'" alt="&lt;" /></complex-'.$page_id.'><simple-'.$page_id.'> &lt; </simple-'.$page_id.'></a>';
    }
    else {
        $data .= '<complex-'.$page_id.'><a><img class="disabled_button" src="'.$img_path.'/prev.png" border="0" alt="&lt;" /></a> </complex-'.$page_id.'>';
    }
    if (!$this->new_window) {
        $data .= '<a href="?page=mailbox&amp;sort_by='.$this->pd['sort_by'].'&amp;filter_by='.$this->pd['filter_by'].'&amp;mailbox='.urlencode($this->pd['mailbox']).
             '&amp;mailbox_page='.urlencode($this->pd['mailbox_page']).'"><complex-'.$page_id.'><img src="'.
              $img_path.'/up.png" title="'.$this->user->str[316].'" alt="Up" border="0" /></complex-'.$page_id.'><simple-'.$page_id.'> up </simple-'.$page_id.'></a>';
    }
    if ($this->pd['next_uid']) {
        $data .= '<a href="?page=message&amp;uid='.$this->pd['next_uid'].'&amp;sort_by='.$this->pd['sort_by'].'&amp;filter_by='.$this->pd['filter_by'].
                '&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;mailbox_page='.$this->pd['next_uid_page'].$new_window_arg.'"><complex-'.$page_id.'><img alt="&gt;" '.
                'title="'.$this->user->str[317].'" src="'.$img_path.'/next.png" border="0" /></complex-'.$page_id.'><simple-'.$page_id.'> &gt; </simple-'.$page_id.'></a>';
    }
    else {
        $data .= '<complex-'.$page_id.'><a><img class="disabled_button" src="'.$img_path.'/next.png" alt="&gt;" /> </a></complex-'.$page_id.'>';
    }
    return $data;
}
function print_message_prev_next() {
    global $conf;
    global $prev_next_actions;
    $theme = 'default';
    if (isset($this->pd['settings']['theme'])) {
        $user_theme = $this->pd['settings']['theme'];
        if (isset($conf['site_themes'][$user_theme])) {
            if ($conf['site_themes'][$user_theme]['css']) {
                $theme = $user_theme;
            }    
        }
    }
    if (isset($this->pd['prev_uid_page'])) {
        $prev_uid_page = $this->pd['prev_uid_page'];
    }
    else {
        $prev_uid_page = $this->pd['mailbox_page'];
    }
    if (isset($this->pd['next_uid_page'])) {
        $next_uid_page = $this->pd['next_uid_page'];
    }
    else {
        $next_uid_page = $this->pd['mailbox_page'];
    }
    $img_path = 'themes/'.$theme.'/images';
    $data = '<complex-'.$this->page_id.'>';
    $data .= '<div id="prev_next_form">';
    if ($this->new_window && !strstr($this->sticky_url, 'new_window')) {
        $form_url = $this->sticky_url.$this->pd['new_window_arg'].'&amp;parent_refresh=1';
    }
    else {
        $form_url = $this->sticky_url;
    }
    $data .= '<form method="post" action="'.$form_url.'"';
    $data .= ' onsubmit="return check_prev_next_del(\''.$this->user->str[64].'\');" ';
    $data .= '>';
    $data .= '<input type="hidden" name="uid" value="'.$this->pd['message_uid'].'" />';
    $data .= '<input type="hidden" name="prev_uid" value="'.$this->pd['previous_uid'].'" />';
    $data .= '<input type="hidden" name="mailbox" value="'.$this->user->htmlsafe($this->pd['mailbox']).'" />';
    $data .= '<input type="hidden" name="next_uid" value="'.$this->pd['next_uid'].'" />';
    $data .= '<input type="hidden" name="sort_by" value="'.$this->pd['sort_by'].'" />';
    $data .= '<input type="hidden" name="filter_by" value="'.$this->pd['filter_by'].'" />';
    $data .= '<input type="hidden" name="mailbox_page" value="'.$this->pd['mailbox_page'].'" />';
    $data .= '<input type="hidden" name="prev_uid_page" value="'.$prev_uid_page.'" />';
    $data .= '<input type="hidden" name="next_uid_page" value="'.$next_uid_page.'" />';
    $data .= '<table><tr>';
    $data .= '<td align="right"><input title="'.$this->user->str[452].'" type="image" ';
    if (!$this->pd['previous_uid']) {
        $data .= 'class="button disabled_button" disabled="disabled" ';
    }
    else {
        $data .= 'class="button" ';
    }
    $data .= 'name="prev_action" src="'.$img_path.'/prev.png" /> ';
    if (!$this->new_window) {
        $data .= '<input type="image" title="'.$this->user->str[454].'" name="up_action" class="button" src="'.$img_path.'/up.png" /> ';
    }
    $data .= '<input type="image" title="'.$this->user->str[453].'" ';
    if (!$this->pd['next_uid']) {
        $data .= 'class="button disabled_button" disabled="disabled" ';
    }
    else {
        $data .= 'class="button" ';
    }
    $data .= 'name="next_action" src="'.$img_path.'/next.png" /></td>';
    $data .= '<td colspan="3" align="center"> &nbsp; and &nbsp; <select onchange="disable_destination();" id="prev_next_action" name="prev_next_action">';
    $selected = false;
    if (isset($this->pd['settings']['default_message_action'])) {
        $selected = $this->pd['settings']['default_message_action'];
    }
    foreach ($prev_next_actions as $i => $v) {
        if (trim($v)) {
            $v = $this->user->str[$v];
        }
        $data .= '<option ';
        if ($i == $selected) {
            $data .= 'selected="selected" ';
        }
        $data .= 'value="'.$i.'">'.$v.'</option>';
    }
    $data .= '</select>&#160;'.$this->user->str[55].': &#160;<select ';
    if ($selected != 'move' && $selected != 'copy') {
        $data .= 'disabled="disabled" ';
    }
    $data .= 'id="prev_next_folder" name="prev_next_folder">'.
              $this->print_folder_option_list($this->pd['folders'], false, 0, array($this->pd['last_prev_next_folder']), true, true).'</select>';
    $data .= '</td>';
    $data .= '</tr></table>';
    $data .= '</form>';
    $data .= '</div>';
    $data .= '</complex-'.$this->page_id.'>'.do_display_hook('message_prev_next_links');
    return $data;
}
function print_message_body() {
    global $page_id;
    global $conf;
    global $user;
    $data = do_display_hook('message_body_top');
    if (!isset($this->pd['message_type'])) {
        $data .= '<div id="message_unkown">Could not open that message/part</div>';
    }
    else {
        if ($this->pd['raw_view']) {
            $this->pd['message_type'] = 'text';
        }
        $data .= '<simple-'.$page_id.'><br /></simple-'.$page_id.'>';
        switch ($this->pd['message_type']) {
            case 'text':
            case 'image':
                $data .= $this->{'print_message_'.$this->pd['message_type']}();
                break;
            case 'html':
                if (isset($conf['html_message_iframe']) && $conf['html_message_iframe']) {
                    if (isset($this->pd['simple_mode']) && $this->pd['simple_mode']) {
                        $data .= $this->{'print_message_'.$this->pd['message_type']}();
                    }
                    else {
                        $data .= $this->print_message_iframe();
                    }
                }
                else {
                    $data .= $this->{'print_message_'.$this->pd['message_type']}();
                }
                break;
            default:
                $data .= '<div id="message_unkown">Unsupported MIME type: '.
                         $this->user->htmlsafe($this->pd['raw_message_type']).'</div>';
                break;
        }
    }
    $data .= do_display_hook('message_body_bottom');
    return $data;
}
function print_message_links() {
    $list_link = false;
    global $conf;
    global $page_id;
    foreach ($this->pd['full_message_headers'] as $vals) {
        if (strtolower($vals[0]) == 'list-id') {
            $list_link = $vals[1];
            break;
        }
    }
    $part = 1;
    if (isset($this->pd['message_part'])) {
        $part = $this->pd['message_part'];
    }
    $theme = 'default';
    if (isset($this->pd['settings']['theme'])) {
        $user_theme = $this->pd['settings']['theme'];
        if (isset($conf['site_themes'][$user_theme])) {
            if ($conf['site_themes'][$user_theme]['css']) {
                $theme = $user_theme;
            }    
        }
    }
    if (isset($this->pd['full_message_headers'])) {
        $mid = false;
        foreach ($this->pd['full_message_headers'] as $v) {
            if (strtolower($v[0]) == 'message-id') {
                $mid = $v[1];
                break;
            }
        }
    }
    $data = '';
    $img_path = 'themes/'.$theme.'/images';
    $hrefs[] = '?page=compose&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;reply_part='.$part.'&amp;reply_type=reply&amp;uid='.$this->pd['message_uid'];
    $hrefs[] = '?page=compose&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;reply_part='.$part.'&amp;reply_type=all&amp;uid='.$this->pd['message_uid'];
    $hrefs[] = '?page=compose&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;reply_part='.$part.'&amp;reply_type=list&amp;uid='.$this->pd['message_uid'];
    $hrefs[] = '?page=compose&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;reply_part='.$part.'&amp;reply_type=forward&amp;uid='.$this->pd['message_uid'];
    $hrefs[] = '?page=compose&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;reply_part='.$part.'&amp;reply_type=forward_attach&amp;uid='.$this->pd['message_uid'];
    $hrefs[] = '?page=compose&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;reply_part='.$part.'&amp;reply_type=resume&amp;uid='.$this->pd['message_uid'];
    $hrefs[] = '?page=compose&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;reply_part='.$part.'&amp;reply_type=new&amp;uid='.$this->pd['message_uid'];
    foreach ($hrefs as $href) {
        if (isset($this->pd['settings']['compose_window']) && $this->pd['settings']['compose_window']) {
            $onclicks[] = 'onclick="open_window(\''.$href.'&amp;new_window=1\', 900, 950); return false;" ';
        }
        else {
            if ($this->new_window) {
                $onclicks[] = 'onclick="open_parent_window(\''.$href.'\'); return false;" ';
            }
            else {
                $onclicks[] = '';
            }
        }
    }
    $data .= '<a '.$onclicks[0].'href="'.$hrefs[0].'">'.$this->user->str[70].'</a> <a '.$onclicks[1].'href="'.$hrefs[1].'">'.$this->user->str[71].'</a> ';
    if ($list_link) {
        $data .= '<a '.$onclicks[2].'href="'.$hrefs[2].'" title="'.$this->user->htmlsafe($list_link).'">'.$this->user->str[37].'</a> ';
    }
    $data .= '<a '.$onclicks[3].'href="'.$hrefs[3].'">'.$this->user->str[72].'</a> ';
    $data .= '<a '.$onclicks[4].'href="'.$hrefs[4].'">'.$this->user->str[60].'</a> ';
    if (isset($this->pd['settings']['draft_folder']) && $this->pd['mailbox'] == $this->pd['settings']['draft_folder']) {
        $data .= '<a '.$onclicks[5].'href="'.$hrefs[5].'">'.$this->user->str[74].'</a>';
    }
    else {
        $data .= '<a '.$onclicks[6].'href="'.$hrefs[6].'">'.$this->user->str[73].'</a>';
    }
    $data .= '&#160;||&#160; ';
    if ((isset($this->pd['show_full_headers']) && $this->pd['show_full_headers']) ||
        ($this->pd['settings']['full_headers_default'] && !$this->pd['show_small_headers'])) {
        $data .= '<a href="?page=message&amp;uid='.$this->pd['message_uid'].'&amp;sort_by='.$this->pd['sort_by'].'&amp;filter_by='.$this->pd['filter_by'].'&amp;mailbox='.
                 urlencode($this->pd['mailbox']).'&amp;mailbox_page='.$this->pd['mailbox_page'].'&amp;message_part='.$this->pd['message_part'].'&amp;small_headers=1'.
                 $this->pd['new_window_arg'].'">'.$this->user->str[69].'</a> ';
    }
    else {
        $data .= '<a href="?page=message&amp;uid='.$this->pd['message_uid'].'&amp;sort_by='.$this->pd['sort_by'].'&amp;filter_by='.$this->pd['filter_by'].'&amp;message_part='.
                 $this->pd['message_part'].'&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;mailbox_page='.$this->pd['mailbox_page'].'&amp;full_headers=1'.
                 $this->pd['new_window_arg'].'">'.$this->user->str[75].'</a> ';
    }
    if ($this->pd['raw_view']) {
        $data .= '<a href="?page=message&amp;uid='.$this->pd['message_uid'].'&amp;sort_by='.$this->pd['sort_by'].'&amp;filter_by='.$this->pd['filter_by'].'&amp;message_part='.
                 $this->pd['message_part'].'&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;mailbox_page='.$this->pd['mailbox_page'].
                 $this->pd['new_window_arg'].'">'.$this->user->str[87].'</a> ';
    }
    else {
        $data .= '<a href="?page=message&amp;uid='.$this->pd['message_uid'].'&amp;sort_by='.$this->pd['sort_by'].'&amp;filter_by='.$this->pd['filter_by'].'&amp;message_part='.
                 $this->pd['message_part'].'&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;mailbox_page='.$this->pd['mailbox_page'].'&amp;raw_view=1'.
                 $this->pd['new_window_arg'].'">'.$this->user->str[76].'</a> ';
    }
    if ($this->dsp_page == 'print_view') {
        $data .= '<a href="?page=message&amp;uid='.$this->pd['message_uid'].'&amp;sort_by='.$this->pd['sort_by'].'&amp;filter_by='.$this->pd['filter_by'].'&amp;message_part='.
                 $this->pd['message_part'].'&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;mailbox_page='.$this->pd['mailbox_page'].
                 $this->pd['new_window_arg'].'">'.$this->user->str[87].'</a> ';
    }
    else {
        $data .= '<a target="_blank" href="?page=message&amp;uid='.$this->pd['message_uid'].'&amp;message_part='.$this->pd['message_part'].'&amp;sort_by='.$this->pd['sort_by'].
                 '&amp;filter_by='.$this->pd['filter_by'].'&amp;message_part='.$this->pd['message_part'].'&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;mailbox_page='.
                 $this->pd['mailbox_page'].'&amp;print_view=1'.$this->pd['new_window_arg'].'">'.$this->user->str[77].'</a> ';
    }
    if (stristr($this->pd['imap_capability'], 'THREAD') && !$this->new_window) {
        $data .= '<a href="?page=thread_view&amp;uid='.$this->pd['message_uid'].'&amp;sort_by='.$this->pd['sort_by'].'&amp;filter_by='.$this->pd['filter_by'].'&amp;message_part='.
                 $this->pd['message_part'].'&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;mailbox_page='.$this->pd['mailbox_page'].'&amp;print_view=1" onclick="display_notice(false, \'Searching for thread members\');">'.
                 $this->user->str[78].'</a> ';
    }
        if ($mid) {
            $data .= '<a href="?page=message&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;current_uid='.$this->pd['message_uid'].'&amp;response_id='.urlencode($mid).'&amp;find_response=1'.$this->pd['new_window_arg'].'">'.$this->user->str[79].'</a> ';
        }
    $data .= do_display_hook('message_links');
    if (isset($this->pd['thread_data'])) {
        if (isset($this->pd['thread_data'])) {
        }
    }
    if (isset($this->pd['search_results'][$this->pd['mailbox']])) {
        $res = $this->pd['search_results'][$this->pd['mailbox']];
        $count = count($res);
        $index = false;
        for ($i=0;$i<$count;$i++) {
            if ($res[$i] == $this->pd['message_uid']) {
                $index = $i;
                break;
            }
        }
        $search_prev = false;
        $search_next = false;
        if ($index !== false) {
            if (isset($res[($index - 1)])) {
                $search_next = $res[($index - 1)];
            }
            if (isset($res[($index + 1)])) {
                $search_prev = $res[($index + 1)];
            }
        }
        if ($search_prev || $search_next) {
            $data .= '<div id="search_links"><a href="?page=search&amp;mailbox='.urlencode($this->pd['mailbox']).'">'.$this->user->str[419].'</a>: ';
            if ($search_prev) {
                $data .= '<a href="?page=message&amp;uid='.$search_prev.'&amp;sort_by='.$this->pd['sort_by'].'&amp;filter_by='.$this->pd['filter_by'].
                         '&amp;mailbox='.urlencode($this->pd['mailbox']).$this->pd['new_window_arg'].'"><complex-'.$page_id.'><img border="0" src="'.
                         $img_path.'/prev.png" title="Previous message" alt="&lt;" /></complex-'.$page_id.'><simple-'.$page_id.'> &lt; </simple-'.$page_id.'></a>';
            }
            else {
                $data .= '<complex-'.$page_id.'><a><img class="disabled_button" src="'.$img_path.'/prev.png" border="0" alt="&lt;" /></a> </complex-'.$page_id.'>';
            }
            if ($search_next) {
                $data .= '<a href="?page=message&amp;uid='.$search_next.'&amp;sort_by='.$this->pd['sort_by'].'&amp;filter_by='.$this->pd['filter_by'].
                         '&amp;mailbox='.urlencode($this->pd['mailbox']).$this->pd['new_window_arg'].'"><complex-'.$page_id.'><img border="0" src="'.
                         $img_path.'/next.png" title="Previous message" alt="&lt;" /></complex-'.$page_id.'><simple-'.$page_id.'> &lt; </simple-'.$page_id.'></a>';
            }
            else {
                $data .= '<complex-'.$page_id.'><a><img class="disabled_button" src="'.$img_path.'/next.png" alt="&gt;" /> </a></complex-'.$page_id.'>';
            }
            $data .= '</div>';
        }
    }
    return $data;
}
function print_add_contact_form() {
    $data = '';
    if (isset($this->pd['new_contacts']) && !empty($this->pd['new_contacts'])) {
        $data .= '<tr><th>'.$this->user->str[8].': </th><td><div id="message_contacts"><form method="post" action="?page=contacts&amp;mailbox='.urlencode($this->pd['mailbox']).'#contactform">';
        $data .= ' <select name="a_email">';
        foreach ($this->pd['new_contacts'] as $v) {
            $data .= '<option value="'.$this->user->htmlsafe($v).'">'.$this->user->htmlsafe($v).'</option>';
        }
        $data .= '</select><input type="submit" name="add_message_contact" value="'.$this->user->str[147].'" /></form></div></td></tr>';
    }
    return $data;
}
function print_part_headers() {
    global $page_id;
    $data = '<simple-'.$page_id.'><tr><td><br /></td></tr></simple-'.$page_id.'>'.do_display_hook('message_part_headers_top').'<complex-'.$page_id.'>';
    foreach ($this->pd['message_part_headers'] as $i => $vals) {
        if (isset($this->pd['message_headers'][$i])) {
            if ($vals[0] == $this->pd['message_headers'][$i][0] &&
                $vals[1] == $this->pd['message_headers'][$i][1]) {
                continue;
            }
        }
        $name = $this->user->htmlsafe($vals[0], $this->pd['charset'], true);
        $val = $this->user->htmlsafe($vals[1], $this->pd['charset'], true);
        $data .= '<tr><th>'.$name.': </th><td ';
        if (strtolower($vals[0]) == 'subject') {
            $data .= 'class="subject_cell" ';
        }
        $data .= '>'.$val;
        if (strtolower($vals[0]) == 'date' && $vals[1]) {
            $data .= ' &#160;&#160; ('.print_time(strtotime($vals[1])).')';
        }
        $data .= '<br /></td></tr>';
    }
    $data .= '<tr><td><div id="prev_next_part">'.$this->print_message_prev_next_part().'</div></td><td></td></tr>'.do_display_hook('message_part_headers_bottom').'</complex-'.$page_id.'>';
    return $data;
}
function print_message_iframe() {
    $data = '<iframe id="msg_iframe" name="msg_iframe" frameborder="0" border="0" onload="autoAdjustIFrame(this)" src="'.$this->user->sticky_url.'&amp;inline_html=1">';
    $data .= '</iframe>';
    return $data;
}
function print_message_iframe_content() {
    $data = $this->print_message_html(true);
    if (!stristr($data, '<body')) {
        $data = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><title>Message</title>
        <style type="text/css">
        body {font-size: 10pt; font-family: '.$this->pd['settings']['font_family'].'; }
        body, select, option, textarea, input { font-size: '.$this->pd['settings']['font_size'].'% }
        </style></head><body>'.$data.'</body></html>';
    }
    return $data;
    }
}
?>
