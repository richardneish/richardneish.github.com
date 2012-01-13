<?php
/* modified from the Sajax PHP/AJAX include library:
   (c) copyright 2005 modernmethod, inc
*/

function handle_client_request() {
    global $user;
    global $imap;
    global $conf;
    global $include_path;
    global $fd;
    global $ajax_functions;
    if (!isset($_POST['rs'])) {
        return;
    }
    ob_end_clean();
    header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header ("Cache-Control: no-cache, must-revalidate");
    header ("Pragma: no-cache");
    $func_name = $_POST["rs"];
    if (isset($_POST['rsargs'])) {
        $args = $_POST["rsargs"];
        $caller = array_shift($args);
    }
    else {
        $args = array();
    }
    if (isset($_SESSION['plugin_ajax'])) {
        foreach ($_SESSION['plugin_ajax'] as $vals) {
            if ($caller == $vals['plugin']) {
                if (is_readable('plugins'.$fd.$vals['plugin'].$fd.'ajax.php')) {
                    require_once($include_path.'plugins'.$fd.$vals['plugin'].$fd.'ajax.php');
                    $ajax_functions[] = 'ajax_'.$vals['plugin'].'_'.$vals['name'];
                    $args[] = new plugin_tools($caller);
                    break;
                }
            }
        }
    }
    if (! in_array($func_name, $ajax_functions)) {
        echo "-:$func_name not callable";
    }
    else {
        echo "+:";
        $result = call_user_func_array($func_name, $args);
        echo "var res = " . trim(prep_ajax_result($result)) . "; res;";
    }
    $user->clean_up(); 
    $imap->disconnect();
    exit;
}
function prep_ajax_result($value) {
    $type = gettype($value);
    if ($type == "boolean") {
        return ($value) ? "Boolean(true)" : "Boolean(false)";
    } 
    elseif ($type == "integer") {
        return "parseInt($value)";
    } 
    elseif ($type == "double") {
        return "parseFloat($value)";
    } 
    else {
        $val = str_replace("\\", "\\\\", $value);
        $val = str_replace("\r", "\\r", $val);
        $val = str_replace("\n", "\\n", $val);
        $val = str_replace("'", "\\'", $val);
        $val = str_replace('"', '\\"', $val);
        $esc_val = $val;
        $s = "'$esc_val'";
        return $s;
    }
}
function ajax_save_outgoing_message($subject, $body, $to, $cc, $from, $id, $reply_to, $refs, $priortiy, $mdn) {
    global $user;
    global $hastymail_version;
    global $imap;
    global $conf;
    global $include_path;
    global $fd;
    if ($user->user_action->gpc) {
        $to = stripslashes($to);
        $cc = stripslashes($cc);
        $from = stripslashes($from);
        $body = stripslashes($body);
        $subject = stripslashes($subject);
    }
    $path = $conf['attachments_path'];
    if ($user->logged_in) {
        if (trim($body)) {
            if (isset($_SESSION['user_settings']['draft_folder'])) {
                $mailbox = $_SESSION['user_settings']['draft_folder'];
                $select_res = $imap->select_mailbox($mailbox, false, false, true);
            }
            else {
                $mailbox = 'INBOX';
            }
            require_once($include_path.'lib'.$fd.'smtp_class.php');
            $message =& new mime();
            if ($to) {
                $message->to = $to;
            }
            if ($cc) {
                $message->cc = $cc;
            }
            if ($refs) {
                $message->references = $refs;
            }
            if ($reply_to) {
                $message->in_reply_to = $reply_to;
            }
            if ($id) {
                $message->message_id = $id;
            }
            if (isset($_SESSION['user_settings']['profiles'][$from])) {
                $from_atts = $_SESSION['user_settings']['profiles'][$from];
                $message->from = '"'.$from_atts['profile_name'].'" <'.$from_atts['profile_address'].'> ';
                $message->from_address = $from_atts['profile_address'];
                if (isset($from_atts['profile_reply_to']) && $from_atts['profile_reply_to']) {
                    $message->reply_to = '<'.$from_atts['profile_address'].'>';
                }
            }
            $existing_id = false;
            if ($select_res) {
                $search_res = $imap->simple_search('header message-id', false, $message->message_id);
                if (isset($search_res[0])) {
                    $existing_id = $search_res[0];
                }
            }
            if ($message->from_address) {
                $message->subject = $subject;
                $message->body = $body;
                if (!isset($_SESSION['user_settings']['compose_hide_mailer']) ||
                    !$_SESSION['user_settings']['compose_hide_mailer']) {
                    $message->set_header('x_Mailer', $hastymail_version);
                }
                if ($priortiy && $priortiy != 3) {
                    $message->set_header('x_Priority', $priortiy);
                }
                if ($mdn) {
                    $message->set_header('disposition_Notification_To', $message->from_address);
                }
                $message->output_smtp_message();
                $email = $message->output_imap_message();
                $size = $message->get_imap_message_size(strlen($email));
                if ($imap->append_start($mailbox, $size)) {
                    $imap->append_feed($email);
                    if (isset($_SESSION['attachments']) && !empty($_SESSION['attachments'])) {
                        foreach ($_SESSION['attachments'] as $i => $v) {
                            $headers = '--'.$message->boundry."\r\nContent-Type: ".$v['type'].'; name="'.$v['realname']."\"\r\nContent-Disposition: attachment; filename=\"".
                                    $v['realname']."\"\r\nContent-Transfer-Encoding: base64\r\n";
                            if (substr($path, -1) != $fd) {   
                                $filename = $path.$fd.$i;
                            }   
                            else {      
                                $filename = $path.$i;
                            }   
                            if (is_readable($filename)) {
                                $imap->append_feed($headers);
                                $input_file = fopen($filename, 'r');
                                if (is_resource($input_file)) {
                                    while (!feof($input_file)) {
                                        $imap->append_feed(trim(fgets($input_file, 1024)));
                                    }
                                    fclose($input_file);
                                }
                            }
                        }
                    }
                    $status = $imap->append_end();
                    if ($existing_id) {
                        $imap->message_action(array($existing_id), 'DELETE');
                        $imap->message_action(array($existing_id), 'EXPUNGE');
                        $_SESSION['uid_cache_refresh'][$mailbox] = 1;
                        $_SESSION['header_cache_refresh'][$mailbox] = 1;
                    }
                }
            }
        }
    }
    if (isset($message->message_id)) {
        return $message->message_id;
    }
    else {
        return '';
    }
}
function ajax_next_contacts() {
    global $user;
    global $include_path;
    global $conf;
    global $fd;
    if ($user->logged_in) {
        require_once($include_path.'lib'.$fd.'vcard.php');
        $vcard =& new vcard();
        $page = 1;
        if (isset($_SESSION['contact_list_page'])) {
            $page = $_SESSION['contact_list_page'];
        }
        $page++;
        $user->page_data['contact_list_page'] = $page;
        if (isset($_SESSION['active_contact_source'])) {
            $source = $_SESSION['active_contact_source'];
        }
        else {
            $source = 0;
        }
        list($user->page_data['contact_list'], $user->page_data['contact_list_total']) = $vcard->get_quick_list('sort_name', $page, $source);
        if ($user->sub_class_names['url']) {
            $class_name = 'site_page_'.$user->sub_class_names['url'];
            $pd =& new $class_name();
        }
        else {
            $pd =& new site_page();
        }
        return $pd->print_contact_select_box();
    }
}
function ajax_prev_contacts() {
    global $user;
    global $include_path;
    global $conf;
    global $fd;
    if ($user->logged_in) {
        require_once($include_path.'lib'.$fd.'vcard.php');
        $vcard =& new vcard();
        $page = 1;
        if (isset($_SESSION['contact_list_page'])) {
            $page = $_SESSION['contact_list_page'];
        }
        $page--;
        if ($page < 1) {
            $page = 1;
        }
        if (isset($_SESSION['active_contact_source'])) {
            $source = $_SESSION['active_contact_source'];
        }
        else {
            $source = 0;
        }
        $user->page_data['contact_list_page'] = $page;
        list($user->page_data['contact_list'], $user->page_data['contact_list_total']) = $vcard->get_quick_list('sort_name', $page, $source);
        if ($user->sub_class_names['url']) {
            $class_name = 'site_page_'.$user->sub_class_names['url'];
            $pd =& new $class_name();
        }
        else {
            $pd =& new site_page();
        }
        return $pd->print_contact_select_box();
    }
}
function ajax_save_folder_state($id) {
    global $user;
    if ($user->logged_in) {
        $state = false;
        if (isset($_SESSION['folder_state'][$id])) {
            $state = $_SESSION['folder_state'][$id];
        }
        if ($state) {
            $state = false;
        }
        else {
            $state = true;
        }
        $_SESSION['folder_state'][$id] = $state;
    }
}
function ajax_save_folder_vis_state($state) {
    global $user;
    if ($user->logged_in) {
        $_SESSION['hide_folder_list'] = $state;
    }
}
function ajax_update_page($mailbox, $page_id, $title, $new=false, $folder_list=false) {
    $res = array();
    $class_name = 'site_page_new';
    $pd =& new $class_name();
    $continue = true;
    $clock = false;
    $unread = false;
    $title = false;
    $new_page = false;
    $tree = false;
    $dropdown = false;
    if ($new) {
        $quick = true;
        $new_page = refresh_new_page($page_id, $pd);
        if (!$new_page) {
            $continue = false;
        }
    }
    else {
        $quick = false;
    }
    if ($continue) {
        list($dropdown, $clock, $unread) = update_dropdown($mailbox, $quick, $page_id, $pd);
        if (!$dropdown) {
            $continue = false;
        }
    }
    if ($continue) {
        $title = update_title($title);
    }
    if ($folder_list && $continue) {
        $tree = update_folder_list($mailbox, $pd);
    }
    if (!$clock) {
        $clock = $pd->print_clock();
    }
    return implode('^^'.$page_id.'^^', array($new_page, $dropdown, $clock, $unread, $title, $tree));
}
function update_title($title) {
    global $user;
    global $conf;
    if ($user->logged_in) {
        return $_SESSION['total_unread'].' '.$user->str[10].$title.' '.$conf['page_title'];
    }
}
function refresh_new_page($page_id, $pd) {
    global $user;
    global $imap;
    global $conf;
    global $include_path;
    global $fd;
    if ($user->logged_in) {
        $new_unseen_status = $imap->get_unseen_status($_SESSION['user_settings']['folder_check']);
        $unchanged = true;
        if (isset($_SESSION['page_id']) && $_SESSION['page_id'] && $page_id && $_SESSION['page_id'] == $page_id) {
            if (isset($_SESSION['unseen_status'])) {
                foreach ($_SESSION['unseen_status'] as $folder => $vals) {
                    if ($new_unseen_status[$folder][0] != $vals[0] || $new_unseen_status[$folder][1] != $vals[1]) {
                        $unchanged = false;
                        break;
                    }
                }
            } 
            if ($unchanged) {
                return '';
            }
        }
        if ($page_id) {
            $_SESSION['page_id'] = $page_id;
        }
        $_SESSION['unseen_status'] = $new_unseen_status;
        $user->page_data['folders'] = $_SESSION['folders'];
        $user->page_data['toggle_all'] = false;
        $user->page_data['mailbox_page'] = 1;
        $user->page_data['sort_by'] = 'ARRIVAL';
        $new_page_data = array();
        $grand_total = 0;
        $unread_folder_count = 0;
        $configured_folders = 0;
        if (isset($_SESSION['user_settings']['folder_check'])) {
            $configured_folders = count($_SESSION['user_settings']['folder_check']);
            foreach ($_SESSION['user_settings']['folder_check'] as $v) {
                $new_page_data[$v] = array();
                list($total, $uids) = $imap->select_mailbox($v, false, true);
                if ($total) {
                    $unread_folder_count++;
                    $grand_total += $total;
                    if (!empty($uids)) {
                        if (isset($_SESSION['frozen_folders'][$v])) {
                            $new_uids = array();    
                            foreach ($uids as $uid) {
                                if (in_array($uid, $_SESSION['uid_cache'][$v]['uids'])) {
                                    $new_uids[] = $uid;
                                }
                            }
                            $uids = $new_uids;
                        }
                        $total = count($uids);
                        $new_page_data[$v] = array('total' => $total, 'headers' => array_reverse($imap->get_mailbox_page($v, $uids, false)));
                    }
                }
            }
        }
        $_SESSION['total_unread'] = $grand_total;
        $user->page_data['grand_total'] = $grand_total;
        $user->page_data['configured_folders'] = $configured_folders;
        $user->page_data['unread_folder_count'] = $unread_folder_count;
        $user->page_data['new_page_data'] = $new_page_data;
        $user->page_data['folders'] = $_SESSION['folders'];
        $theme = 'default';
        if ($user->logged_in) {
            if (isset($pd->pd['settings']['theme'])) {
                $user_theme = $pd->pd['settings']['theme'];
                if (isset($conf['site_themes'][$user_theme])) {
                    if ($conf['site_themes'][$user_theme]['templates']) {
                        $theme = $user_theme;
                    }    
                }
            }
        }
        if (is_readable('themes/'.$theme.'/config.php')) {
            require_once($include_path.'themes'.$fd.$theme.$fd.'config.php');
            if (isset($new_page_cols)) {
                return $pd->print_new_content($new_page_cols);
            }
        }
        return $pd->print_new_content();
    }
}
function update_folder_list($mailbox, $pd) {
    global $user;
    global $imap;
    if ($user->logged_in) {
        $pd->pd['mailbox'] = $mailbox;
        $user->page_data['folders'] = $_SESSION['folders'];
        return $pd->print_folder_list($_SESSION['folders']);
    }
}
function update_dropdown($mailbox, $quick=false, $page_id=false, $pd) {
    global $user;
    global $imap;
    if ($user->logged_in) {
        if (!$quick) { 
            //$imap->get_unseen_status($user->page_data['settings']['folder_check']);
            $new_unseen_status = $imap->get_unseen_status($_SESSION['user_settings']['folder_check']);
            $unchanged = true;
            if (isset($_SESSION['page_id']) && $_SESSION['page_id'] && $page_id && $_SESSION['page_id'] == $page_id) {
                if (isset($_SESSION['unseen_status'])) {
                    foreach ($_SESSION['unseen_status'] as $folder => $vals) {
                        if ($new_unseen_status[$folder][0] != $vals[0] || $new_unseen_status[$folder][1] != $vals[1]) {
                            $unchanged = false;
                            break;
                        }
                    }
                } 
                if ($unchanged) {
                    return array(false, false, false);
                }
            }
            if ($page_id) {
                $_SESSION['page_id'] = $page_id;
            }
            $_SESSION['unseen_status'] = $new_unseen_status;
        }
        $pd->pd['mailbox'] = $mailbox;
        $user->page_data['folders'] = $_SESSION['folders'];
        return array($pd->print_folder_dropdown($_SESSION['folders']), $pd->print_clock(),
               '<a href="?page=new&amp;mailbox='.urlencode($mailbox).'">'.$user->str[34].': '.$_SESSION['total_unread'].'</a>');
    }
}
?>
