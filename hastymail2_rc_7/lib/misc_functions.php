<?php

/*  misc_functions.php: Various helper functions
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

function get_config($file) {
    $conf = @unserialize(file_get_contents($file));
    if (is_array($conf) && !empty($conf)) {
        if (isset($conf['http_prefix'])) {
            $pre = $conf['http_prefix'];
        }
        elseif (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) == 'ON') {
            $pre = 'https';
        }
        else {
            $pre = 'http';
        }
        $conf['http_prefix'] = $pre;
        if (!isset($conf['host_name']) || !trim($conf['host_name'])) {
            if (isset($_SERVER['HTTP_HOST'])) {
                $conf['host_name'] = $_SERVER['HTTP_HOST'];
            }
            elseif (isset($_SERVER['SERVER_NAME'])) {
                $conf['host_name'] = $_SERVER['SERVER_NAME'];
            }
            elseif (isset($_SERVER['SERVER_ADDR'])) {
                $conf['host_name'] = $_SERVER['SERVER_ADDR'];
            }
            else {
                echo 'Could not determine the webserver hostname!';
                die;
            }
        }
        return $conf;
    }
    else {
        echo 'Configuration file not found or unreadable';
        exit;
        
    }
}
function get_site_config() {
    global $user;
    global $conf;
    global $imap;
    foreach ($conf as $i => $v) {
        if (substr($i, 0, 5) == 'imap_') {
            $name = substr($i, 5);
            $imap->$name = $v;
        }
        elseif (substr($i, 0, 5) == 'site_') {
            $name = substr($i, 5);
            $user->$name = $v;
        }
    }  
    return true;
}
function remove_images_callback($val) {
    if ($val == 'img') {
        return false;
    }
    else {
        return true;
    }
}
function html_2_text ($html, $nl=false) {
    global $include_path;
    global $conf;
    global $fd;
    require_once $include_path.'lib'.$fd.'class.html2text.inc';
    $h2t = new html2text();
    $h2t->set_html($html);
    $text = $h2t->get_text();
    if ($nl) {
        $text = nl2br($text);
    }
    return $text;
}
function filter_html ($body, $allowed) {
    global $conf;
    global $include_path;
    global $fd;
    $tag_list = $allowed;

    if (isset($conf['html_message_iframe']) && $conf['html_message_iframe']) {
        $rm_tags_with_content = Array( 'script', 'style', 'applet', 'embed', 'frameset');
        $tag_list[] = 'body';
    }
    else {
        $rm_tags_with_content = Array( 'script', 'style', 'applet', 'embed', 'head', 'frameset');
    }
    $self_closing_tags =  Array( 'img', 'br', 'hr', 'input');
    $force_tag_closing = true;
    $rm_attnames = Array( '/.*/' => Array('/^on.*/i', '/^dynsrc/i', '/^datasrc/i', '/^data.*/i'));
    $add_attr_to_tag = Array();
    $bad_attvals = Array( '/.*/' => Array(
	        '/.*/' => Array( Array( '/^([\'\"])\s*\S+\s*script\s*:*(.*)([\'\"])/i', '/^([\'\"])\s*https*\s*:(.*)([\'\"])/i',),
		        Array( '\\1blah:\\2\\3', '\\1http:\\2\\3',)),     '/^style/i' =>
                Array( Array( '/expression/i', '/behaviou*r/i', '/binding/i', '/url\(([\'\"]*)\s*https*:.*([\'\"]*)\)/i', '/url\(([\'\"]*)\s*\S+script:.*([\'\"]*)\)/i'),
                Array( 'idiocy', 'idiocy', 'idiocy', 'url(\\1http://securityfocus.com/\\2)', 'url(\\1http://securityfocus.com/\\2)')
            )
        )
    );

    //$tag_list = array_filter($tag_list, 'remove_images_callback');
    //array_push($rm_attnames['/.*/'], '/^background.*/i');

    require_once($include_path.'lib'.$fd.'htmlfilter.inc');        /* htmlfilter code */
    $trusted = sanitize($body, $tag_list, $rm_tags_with_content, $self_closing_tags, $force_tag_closing, $rm_attnames, $bad_attvals, $add_attr_to_tag);
    $trusted = str_replace(array('<a ', '<A '), '<a target="_blank" ', $trusted);
    return $trusted;
}
/* output the page with only $pd available */
function build_page($pd) {
    global $user;
    global $conf;
    global $include_path;
    global $fd;
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
    if (!is_readable('themes'.$fd.$theme.$fd.'templates'.$fd.'main.php')) {
        $theme = 'default';
    }
    $file = 'themes'.$fd.$theme.$fd.'templates'.$fd.'main.php';
    require_check($file);
    require_once($include_path.$file);
}
/* HTTP headers, XML declaration, doc type */
function set_page_headers($force_html=false) {
    global $pd;
    if (!$force_html && isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml') !== false) {
        if ($pd->html_content_type == 'xhtml') {
            header("Content-Type: application/xhtml+xml; charset=utf-8");
        }
        $declaration =  '<?xml version="1.0" encoding="UTF-8"?>'.
                        '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.
                        '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
    }
    else {
        header('Vary: Accept');
        header("Content-Type: text/html; charset=utf-8");
        $declaration =  '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"><html>';
    }
    echo $declaration;
}
/* print timestamp as a difference from now in human readable form */
function print_time2($date_string, $date_format, $date_format_2) {
    global $user;
    $data = '';
    if (!$date_format) {
        $data .= '<span title="'.$user->htmlsafe(trim($date_string)).'">';
        $data .= print_time(strtotime($user->htmlsafe($date_string)), $date_string).'</span>';
    }
    else {
        $data .= '<span title="'.print_time(strtotime($user->htmlsafe($date_string)), $date_string).'">';
        $data .= $user->htmlsafe(date($date_format, strtotime(trim($date_string))));
        if ($date_format_2) {
            $data .= ' '.$user->htmlsafe(date($date_format_2, strtotime(trim($date_string))));
        }
        $data .= '</span>';
    }
    return $data;
}
function print_time($timestamp, $date_str=false) {
    if (!$timestamp) {
        if (preg_match("/UT$/", trim($date_str))) {
            $timestamp = strtotime(substr(trim($date_str), 0, -3).' GMT');
        }
        if (!$timestamp) {
            return 'unkown';
        }
    }
    $diff = time() - $timestamp;
    $output = '';
    $times = array(array('length' => (3600*24*365), 'label' => 'year'),
                   array('length' => (3600*24), 'label' => 'day'),
                   array('length' => 3600, 'label' => 'hour'),
                   array('length' => 60, 'label' => 'minute'),
                   array('length' => 1, 'label' => 'second'),
    );
    $trigger = false;
    $break = false;
    while ($diff > 0) {
        foreach ($times as $index => $vals) {
            if ($diff >= $vals['length']) {
                if ($vals['length'] == 1) {
                    break 2;
                    $diff = 0;
                }
                $trigger = true;
                $unit = floor($diff/$vals['length']);
                if ($unit != 1) {
                    $output .= $unit.' '.$vals['label'].'s, ';
                }
                else {
                    $output .= $unit.' '.$vals['label'].', ';
                }
                $diff = $diff % $vals['length'];
                break;
            }
        }
        if ($break) {
            break;
        }
        if ($trigger) {
            $break = true;
        }
    }
    $output = rtrim($output, ', ').' ago';
    if (trim($output) == 'ago') {
        $output = 'Just now';
    }
    return $output;
}
/* build page links HTML */
function build_page_links ($current, $total, $page_count, $url_base, $label='') {
    global $conf;
    global $user;
    global $page_id;
    $theme = 'default';
    if (isset($_SESSION['user_settings']['theme'])) {
        $user_theme = $_SESSION['user_settings']['theme'];
        if (isset($conf['site_themes'][$user_theme])) {
            if ($conf['site_themes'][$user_theme]['css']) {
                $theme = $user_theme;
            }    
        }
    }
    $img_path = 'themes/'.$theme.'/images';
    if ($total <= $page_count) {
        return '';
    }
    $range = 30;
    $middle = $range/2;
    $pages = floor($total/$page_count);
    if ($total % $page_count != 0) {
        $pages++;
    }
    $output = '';
    $start = $current - $middle;
    if ($start < 0) {
        $start = 1;
        $stop = $range;
    }
    elseif ($start == 0) {
        $start = 1;
        $stop = $range;
    }
    elseif ($start > 0) {
        if ($start = ($current - $middle)) {
            $stop = $current + $middle;
        }
        else {
            $stop = $range - $start;
        }
    }
    if ($stop > $pages) {
        $stop = $pages;
    }
    for ($i=$start; $i<=$stop; $i++) {
        if ($i == 1 && $current == 0) {
            $output .= $i;
        }
        elseif ($i != $current) {
            $output .= '<a href="'.$url_base.'&amp;mailbox_page='.$i.'">'.$i.'</a> ';
        }
        else {
            $output .= '<a class="current_page_link" href="'.$url_base.'&amp;mailbox_page='.$i.'">'.$i.'</a> ';
        }
    }
    $pre = '';
    $post = '';
    if ($current > 1) {
        $pre .= '<a href="'.$url_base.'&amp;mailbox_page='.($current - 1).'">'.
                 '<complex-'.$page_id.'><img src="'.$img_path.'/prev.png" border="0" title="'.$user->str[324].'" alt="Previous" /></complex-'.$page_id.'>'.
                 '<simple-'.$page_id.'>&lt;</simple-'.$page_id.'></a>';
    }
    else {
        $pre .= '<complex-'.$page_id.'><img src="'.$img_path.'/prev.png" border="0" title="'.$user->str[324].'" alt="Previous" style="opacity: .2" /></complex-'.$page_id.'>';
    }
    if ($start != 1 && $current != 1) {
        $pre .= '<a href="'.$url_base.'&amp;mailbox_page=1">1</a> ... &#160;';
    }
    if ($stop != $pages && $current != $pages) {
        $post .= ' ... &#160;<a href="'.$url_base.'&amp;mailbox_page='.$pages.'">'.$pages.'</a>';
    }
    if ($current < $stop) {
        $post .= '<a href="'.$url_base.'&amp;mailbox_page='.($current + 1).'">'.
                 '<complex-'.$page_id.'><img src="'.$img_path.'/next.png" border="0" title="'.$user->str[323].'" alt="Next" /></complex-'.$page_id.'>'.
                 '<simple-'.$page_id.'>&gt;</simple-'.$page_id.'></a>';
    }
    else {
        $post .= '<complex-'.$page_id.'><img src="'.$img_path.'/next.png" border="0" title="'.$user->str[323].'" alt="Next" style="opacity: .2" /></complex-'.$page_id.'>';
    }
    return $label.' &#160;'.$pre.' '.$output.$post;
}
function format_size($val, $extra=false) {
    if ($val == 0) {
        $result = '0 KB';
    }
    elseif ($val < 1) {
        $result = round(($val*1000), 2).' Bytes';
    }
    elseif ($val > 1000) {
        $result = round(($val/1000), 2).' MB';
    }
    else {
        $result = round($val, 2).' KB';
    }
    if ($extra && $val != 0) {
        $result .= $extra;
    }
    return $result;
}
function print_bool($val, $opposite=false) {
    if ($opposite) {
        if ($val) {
            $val = false;
        }
        else {
            $val = true;
        }
    }
    if ($val) {
        return 'Yes';
    }
    else {
        return 'No';
    }
}
function check_view_access($val) {
    global $user;
    $approved = false;
    switch ($val) {
        case 0:
            $approved = true;
            break;
        case 1:
            if ($user->logged_in) {
                $approved = true;
            }
            break;
        case 2:
            if ($user->admin) {
                $approved = true;
            }
            break;
    }
    return $approved;
}
function mt_to_num($val) {
    list($v1, $v2) = explode(' ', $val);
    return $v2.'.'.substr($v1, 2);
}
function echo_r($vals) {
    $data = '<div style="white-space: pre">';
    $data .= htmlentities(print_r($vals, true));
    $data .= '</div>';
    echo $data;
}
function new_folder_sort($a, $b) {
    if (strtoupper($a) == 'INBOX') {
        return -1;
    }
    elseif (strtoupper($b) == 'INBOX') {
        return 1;
    }
    else {
        return strnatcasecmp($a, $b);
    }
}
function folder_sort($a, $b) {
    return strnatcasecmp($a, $b);
}
function clean_from($string) {
    global $user;
    $return = $string;
    if (!trim($string)) {
        return 'No From';
    }
    else {
        if (!strstr($string, ',')) {
            if (strstr($string, '<')) {
                $return = trim(str_replace('"', '', preg_replace("/\<[^>]+\>/", '', $string)));
            }
            if (!$return) {
                $return = $string;
            }
        }
        else {
            $parts = split(',', $string);
            $res = array();
            foreach ($parts as $i => $part) {
                $res[] = clean_from($part);
            }
            if (count($res) > 0) {
                if (count($res) > 2) {
                    $return = join(', ', array_slice($res, 0, 3)).' ...';
                }
                else {
                    $return = join(', ', $res);
                }
            }
        }
    }
    return str_replace(array('<', '>', '"'), '', $return);
}
function hm_strlen($string) {
    global $user;
    global $mb_charset_codes;
    $charset = $user->page_data['charset'];
    if ($user->user_action->mb_support && $charset && in_array(strtoupper($charset), $mb_charset_codes)) {
        return mb_strlen($string, $charset);
    }
    else {
        return strlen($string);
    }
}
function hm_substr($string, $start, $offset=false, $charset=false) {
    global $user;
    global $mb_charset_codes;
    if (!$charset) {
        $charset = $user->page_data['charset'];
    }
    if ($user->user_action->mb_support && $charset && in_array(strtoupper($charset), $mb_charset_codes)) {
        if ($offset) {
            return mb_substr($string, $start, $offset, $charset);
        }
        else {
            return mb_substr($string, $start, mb_strlen($string), $charset);
        }
    }
    else {
        if ($offset) {
            return substr($string, $start, $offset);
        }
        else {
            return substr($string, $start);
        }
    }
}
function prep_html_part($string, $uid, $mailbox, $image_replace=false, $override=false) {
    global $user;
    $regex = "/src=(\"|'|)cid:([^@]+)@[^ ]+(\"|'|)/im";
    if (preg_match_all($regex, $string, $matches)) {
        $locations = $matches[0];
        $filenames = $matches[2];
        foreach ($locations as $i => $v) {
            $string = str_replace($v, 'src="?page=inline_image&amp;mailbox='.urlencode($mailbox).
                                  '&amp;uid='.$uid.'&amp;filename='.urlencode($filenames[$i]).'" alt="'.
                                   $user->htmlsafe($filenames[$i]).'" ', $string);
        }
    }
    if ($image_replace) {
        $regex = "/((background|src))=(\"|'|)((http|ftp|rtsp)s?:\/\/|\/)[^\s]+(\"|'|)/im";
        $replaced = 0;
        if (preg_match_all($regex, $string, $matches)) {
            $outside_sources = $matches[0];
            $replaced = count($outside_sources);
            $src_type = $matches[1];
            foreach ($outside_sources as $i => $src) {
                $string = str_replace($src, $src_type[$i].'=images/place_holder.png', $string);
            }
            if ($replaced) {
                $msg = '<div style="padding: 10px;">'.$replaced.' external images replaced <a href="'.$user->sticky_url.'&amp;show_external_images=1">Show External Images</a></div><br />';
                $string = $msg.$string;
            } 
        }
    }
    if ($override) {
        $url = preg_replace("/\&amp;show_external_images=(1|0)/", '&amp;show_external_images=0', $user->sticky_url);
        $msg = '<div style="padding: 10px;"><a href="'.$url.'">Hide External Images</a></div><br />';
        $string = $msg.$string;
    }
    return $string;
}
function prep_text_part($string, $charset) {
    global $user;
    global $page_id;
    $email_regex = "/(([a-zA-Z0-9_\.\-])+@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+)/m";
    $link_regex = "/((http|ftp|rtsp)s?:\/\/(%[[:digit:]A-Fa-f][[:digit:]A-Fa-f]|[-_\.!~\*';\/\?#:@&=\+$,\[\]%[:alnum:]])+)/m";
    $max = 48;
    $links = false;
    $hl_reply = false;
    $alinks = false;
    if (isset($_SESSION['user_settings']['hl_reply']) && $_SESSION['user_settings']['hl_reply'] && !$user->page_data['raw_view']) {
        $hl_reply = true;
    }
    if (isset($_SESSION['user_settings']['text_email']) && $_SESSION['user_settings']['text_email'] && !$user->page_data['raw_view']) {
        $alinks = true;
    }
    if (isset($_SESSION['user_settings']['text_links']) && $_SESSION['user_settings']['text_links'] && !$user->page_data['raw_view']) {
        $links = true;
    }
    if ($user->page_data['raw_view']) {
        $links = false; 
        $alinks = false;
        $hl_reply = false;
    }
    $new_lines = array();
    $main_index = 0;
    $string = str_replace("\r\n", "\n", $string);
    $string = str_replace("\r", "\n", $string);
    $string = str_replace("&", "&amp;", $string);
    $string = $user->htmlsafe($string, $charset);
    $lines = split("\n", $string);
    $pattern = '/([^ ]{'.$max.'})/';
    foreach ($lines as $line) {
        if ($hl_reply) {
            if (preg_match("/^(\&gt;|\|)/", trim($line))) {
                $new_lines[$main_index] = '<b class="reply">'.$line.'</b>';
            }
            else {
                $new_lines[$main_index] = $line;
            }
        }
        else {
            $new_lines[$main_index] = $line;
        }
        $main_index++;
    }
    unset($lines);
    $string = implode("<br />", $new_lines);
    $string = str_replace(array('"', "'", '&gt;', '&lt;', '&#160;'), array(' "', " '", ' &gt;', ' &lt;', ' &#160;', ' &gt;', '&lt;'), $string);
    if ($links) {
        if (preg_match_all($link_regex, $string, $matches, PREG_OFFSET_CAPTURE)) {
            $offset_adjust = 0;
            foreach ($matches[1] as $vals) {
                $offset = $vals[1] + $offset_adjust;
                $link = str_replace(array('[span-'.$page_id.']'), array(''), $vals[0]);
                $link_tag = '<a class="text_link" href="'.$link.'" title="'.$link.'" target="_blank">link</a> ';
                $offset_adjust += hm_strlen($link_tag);
                if ($offset) {
                    $string = hm_substr($string, 0, $offset).$link_tag. hm_substr($string, $offset);
                }
                else {
                    $string = $link_tag.$string;
                }
            }
        }
    }
    if ($alinks) {
        if (preg_match_all($email_regex, $string, $matches, PREG_OFFSET_CAPTURE)) {
            $offset_adjust = 0;
            foreach ($matches[1] as $vals) {
                $offset = $vals[1] + $offset_adjust;
                $email = str_replace('[span-'.$page_id.']', '', $vals[0]);
                $email_tag = '<a class="text_link" href="?page=compose&amp;to='.urlencode($email).'" title="'.$email.'">email</a> ';
                $offset_adjust += strlen($email_tag);
                if ($offset) {
                    $string = hm_substr($string, 0, $offset).$email_tag. hm_substr($string, $offset);
                }
                else {
                    $string = $email_tag.$string;
                }
            }
        }
    }
    $string = str_replace(array(' "', " '", ' &gt;', ' &lt;', ' &#160;'), array('"', "'", '&gt;', '&lt;', '&#160;'), $string);
    return $string;
}
function prep_text_regex_callback($matches) {
    global $user;
    global $page_id;
    $max = 48;
    $line = $matches[0];
    array_shift($matches);
    foreach ($matches as $v) {
        $line = str_replace($v, (hm_substr($v, 0, $max).'[span-'.$page_id.']'.hm_substr($v, $max)), $line);
    }
    return $line;
}
function timer_display($times) {
    global $page_start;
    $base = mt_to_num($page_start);
    $data = '<br /><table cellpadding="4" cellspacing="0">';
    $last = false;
    foreach ($times as $i => $v) {
        $data .= '<tr><td style="border-bottom: solid 1px #ccc;">'.round((mt_to_num($v) - $base), 4);
        if ($last) {
            $data .= ' ('.(round((mt_to_num($v) - $last), 4)).')';
        }
        $data .= '</td>';
        $data .= '<td style="border-bottom: solid 1px #ccc;">'.$i.' </td></tr>';
        $last = mt_to_num($v);
    }
    $data .= '</table>';
    return $data;
}
function output_filtered_content($tags) {
    global $user;
    global $conf;
    if (!$user->use_cookies && $user->logged_in) {
        ob_end_flush();
    }
    $string = ob_get_clean();
    foreach ($tags as $id => $val) {
        $string = remove_tags($string, $id, $val);
    }
    if (isset($conf['html_message_iframe']) && $conf['html_message_iframe']) {
        $force_html = false;
    }
    else {
        $force_html = true;
    }
    set_page_headers($force_html);
    echo $string;
}
function remove_tags($string, $tag_name, $strip) {
    global $page_id;
    global $conf;
    $new_page = '';
    if ($strip) {
        $marker_length = strlen("<$tag_name-$page_id>");
        $end_marker_length = $marker_length + 1;
        while (strpos($string, "<$tag_name-$page_id>") !== false) {
            $chunk = substr($string, 0, strpos($string, "<$tag_name-$page_id>"));
            $string = substr($string, (strlen($chunk) + $marker_length));
            $new_page .= $chunk;
            $chunk = substr($string, 0, strpos($string, "</$tag_name-$page_id>"));
            $string = substr($string,  (strlen($chunk) + $end_marker_length));
        }
        $new_page .= $string;
    }
    else {
        $new_page = str_replace(array("<$tag_name-$page_id>", "</$tag_name-$page_id>"), '', $string);
    }
    if (isset($conf['html_squish']) && $conf['html_squish']) {
        return str_replace("\n", '', preg_replace("/>\s{2,}</", '> <', $new_page));
    }
    else {
        return ltrim($new_page);
    }
}
function run_template() {
    global $pd;
    global $app_pages;
    global $conf;
    global $tools;
    global $include_path;
    global $fd;
    $found = false;
    $theme = 'default';
    if (in_array($pd->dsp_page, $app_pages)) {
        if (isset($conf['site_themes'][$pd->pd['theme']])) {
            $atts = $conf['site_themes'][$pd->pd['theme']];
        }
        if (isset($atts['templates']) && $atts['templates']) {
            $file = 'themes'.$fd.$pd->pd['theme'].$fd.'templates'.$fd.$pd->dsp_page.'.php';
            $theme = $pd->pd['theme'];
            require_check($file);
            require_once($include_path.$file);
        }
        else {
            $file = 'themes'.$fd.'default'.$fd.'templates'.$fd.$pd->dsp_page.'.php';
            require_check($file);
            require_once($include_path.$file);
        }
        $found = true;
    }
    if (!$found && $pd->user->logged_in) { 
        $plugins = array();
        if (isset($_SESSION['plugins']['page_hooks'])) {
            $plugins = $_SESSION['plugins']['page_hooks'];
            if (!empty($plugins)) {
                foreach ($plugins as $plugin) {
                        if ($pd->dsp_page == $plugin) {
                        $function_name = 'print_'.$plugin;
                        if (function_exists($function_name)) {
                            $pdata = array();
                            if (isset($pd->pd['plugin_data'][$plugin])) {
                                $pdata = $pd->pd['plugin_data'][$plugin];
                            }
                            echo $function_name($pdata, $tools[$plugin]);
                            $found = true;
                            break;
                        }
                    }
                }
            } 
        }
    }
    if (!$found) {
        $file = 'themes/'.$theme.'/templates/not_found.php';
        require_check($file);
        require_once($include_path.$file);
    }
}
function do_work_hook($location, $args=array(), $plugin_array=array()) {
    global $conf;
    global $tools;
    global $include_path;
    global $fd;
    if (empty($plugin_array)) {
        $plugin_array = $_SESSION['plugins'];
    }
    if (isset($plugin_array['work_hooks'])) {
        $plugins = $plugin_array['work_hooks'];
        if (!empty($plugins)) {
            foreach ($plugins as $plugin => $vals) {
                foreach ($vals as $v) {
                    if ($location == $v) {
                        $function_name = $plugin.'_'.$location;
                        $file = 'plugins'.$fd.$plugin.$fd.'work.php';
                        if (is_readable($file)) {
                            require_check($file);
                            require_once($include_path.$file);
                            if (function_exists($function_name)) {
                                if (!$tools) {
                                    $tools[$plugin] = new plugin_tools($plugin);
                                }
                                $function_name($tools[$plugin], $args);
                            }
                        }
                    }
                }
            }
        }
    }
}
function do_display_hook($location, $args=array()) {
    global $conf;
    global $tools;
    global $include_path;
    global $fd;
    $return = '';
    if (isset($_SESSION['plugins']['display_hooks'])) {
        $plugins = $_SESSION['plugins']['display_hooks'];
        if (!empty($plugins)) {
            foreach ($plugins as $plugin => $vals) {
                foreach ($vals as $v) {
                    if ($location == $v) {
                        $function_name = $plugin.'_'.$location;
                        $file = 'plugins'.$fd.$plugin.$fd.'display.php';
                        if (is_readable($file)) {
                            require_check($file);
                            require_once($include_path.$file);
                            if (isset($args[$function_name])) {
                                $return .= $args[$function_name];
                            }
                            elseif (function_exists($function_name)) {
                                $return .= $function_name($tools[$plugin]);
                            }
                        }
                    }
                }
            }
        }
    }
    return $return;
}
function get_plugins($pre_login=false, $force=false) {
    global $user;
    global $conf;
    global $available_display_hooks;
    global $available_work_hooks;
    global $force_plugin_reloading;
    global $include_path;
    global $fd;

    if (isset($conf['plugins'])) {
        $active_plugins = $conf['plugins'];
    }
    else {
        $active_plugins = array();
    }
    $active_page_hooks      = array();
    $active_display_hooks   = array();
    $active_work_hooks      = array();
    $plugin_list = array();
    $plugins_enabled = false;

    if ($force_plugin_reloading || $force || ($user->just_logged_in && is_array($active_plugins))) {
        foreach ($active_plugins as $v) {
            if (is_dir('plugins'.$fd.$v.$fd)) {
                $file = 'plugins'.$fd.$v.$fd.'config.php';
                if (is_readable($file)) {
                    require_check($file);
                    require($include_path.$file);
                    $name = $v.'_hooks';
                    $langs = $v.'_langs';
                    if (isset($$name)) {
                        foreach ($$name as $type => $vals) {
                            if ($type == 'page_hook' && $vals) {
                                    $active_page_hooks[] = $v;
                                    if (!in_array($v, $plugin_list)) {
                                        $plugin_list[] = $v;
                                    }
                                    $plugins_enabled = true;
                            }
                            elseif (is_array($vals)) {
                                foreach ($vals as $val) {
                                    if (in_array($val, ${'available_'.$type})) {
                                        ${'active_'.$type}[$v][] = $val;
                                        $plugins_enabled = true;
                                        if (!in_array($v, $plugin_list)) {
                                            $plugin_list[] = $v;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if (isset($$langs)) {
                        $_SESSION['plugin_strings'][$v] = $$langs;
                    }
                }
            }
        }
        $plugins = array('work_hooks' => $active_work_hooks, 'display_hooks' => $active_display_hooks, 'page_hooks' => $active_page_hooks);
        if (!$pre_login) {
            $_SESSION['plugin_list'] = $plugin_list;
            $_SESSION['plugins'] = $plugins;
            $_SESSION['plugins_enabled'] = $plugins_enabled;
        }
    }
    else {
        $plugins = $_SESSION['plugins'];
    }
    return $plugins;
}
function require_check($file) {
    global $user;
    global $include_path;
    $file = $include_path.$file;
    $bail = false;
    if (stristr($file, '..')) {
        $bail = true;
    }
    elseif (!$include_path && substr(pathinfo($file, PATHINFO_DIRNAME), 0, 1) == '/') {
        $bail = true;
    }
    elseif (substr(pathinfo($file, PATHINFO_EXTENSION), 0, 3) != 'php' &&
            substr(pathinfo($file, PATHINFO_EXTENSION), 0, 3) != 'inc') {
        $bail = true;
    }
    elseif (!$include_path && !preg_match("/^[a-z\/\\\._]+\.(inc|php)$/i", $file)) {
        $bail = true;
    }
    if ($bail) {
        echo 'Required file failure: '.$user->htmlsafe($file);
        exit;
    }
    return true;
}
function print_contact_page_links($total, $page, $mailbox) {
    global $user;
    global $contacts_per_page;
    $start = 1;
    $stop = ceil($total/$contacts_per_page);
    $data = '';
    if ($stop > 1) {
        $data .= $user->str[88].' ';
        $url = '?page=contacts&amp;mailbox='.urlencode($mailbox).'&amp;contacts_page=';
        for ($i=$start;$i<=$stop;$i++) {
            $data .= '<a href="'.$url.$i.'">'.$i.'</a> ';
        }
    }
    return $data;
}
function get_alt_servers($conf) {
    $alt_servers = array();
    foreach ($conf as $i => $v) {
        if (preg_match("/^alt_(\d+)_([^\s]+)$/", $i, $matches)) {
            $alt_servers[$matches[1]][$matches[2]] = $v;
        }
    }
    return $alt_servers;
}
function get_page_action($get, $post) {
    $url_class = 'mailbox';
    $post_class = false;
    
    if (isset($get['page']) && trim($get['page'])) {
        switch ($get['page']) {
            case 'compose':
            case 'contacts':
            case 'options':
            case 'search':
            case 'mailbox':
                $post_class = $get['page'];
                $url_class = $get['page'];
                break;
            case 'folders':
            case 'profile':
                $post_class = $get['page'];
                $url_class = 'misc';
                break;
            case 'message':
            case 'new':
                $url_class = $get['page'];
                break;
            case 'about':
            case 'logout':
            case 'thread_view':
                $url_class = 'misc';
                break;
            case 'login':
                $url_class = 'mailbox';
                $post_class = 'mailbox';
                break;

            default:
                $url_class = false;
                break;
        }
    }
     if (isset($_POST['rs']) && $_POST['rs']) {
        $url_class = 'new';
        switch ($_POST['rs']) {
            case 'ajax_save_outgoing_message':
            case 'ajax_next_contacts':
            case 'ajax_prev_contacts':
                $url_class = 'compose';
                break;
        }
    }
    return array('url' => $url_class, 'post' => $post_class);
}
function get_page_url() {
    $res = false;
    if (isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME']) {
        $res = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);
    }
    if (isset($_SERVER['argv'][0]) && $_SERVER['argv'][0]) {
        $res .= '?'.$_SERVER['argv'][0];
    }
    elseif (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']) {
        $res .= '?'.$_SERVER['QUERY_STRING'];
    }
    return str_replace('&', '&amp;', $res);
}
?>
