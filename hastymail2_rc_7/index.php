<?php

/*  index.php: Main index file. All requests start here 
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

/* configuration file */
$hm2_config = '/etc/hastymail2/hastymail2.rc';

/* include file prefix. This should be left blank unless you want to use an
   absolute path for file includes. In that case it should be set to a
   filesystem path ending with a delimiter that leads to the main Hastymail2
   directory, for example:
   $include_path = '/var/www/hastymail2/'
 */
$include_path = '';

/* the filesystem delimiter to use when building include statements */
$fd = '/';

/* capture any accidental output */
ob_start();

/* timer debug prep */
$page_start = microtime();

/* required includes */
require_once($include_path.'lib'.$fd.'misc_functions.php');    /* various helpers */
require_once($include_path.'lib'.$fd.'utility_classes.php');   /* base classes    */
require_once($include_path.'lib'.$fd.'url_action_class.php');  /* GET processing  */
require_once($include_path.'lib'.$fd.'imap_class.php');        /* IMAP routines   */
require_once($include_path.'lib'.$fd.'site_page_class.php');   /* print functions */

$conf = get_config($hm2_config);

/* unique page id */
$page_id = md5(uniqid(rand(),1));

/* verison */
$hastymail_version = 'Hastymail2 RC7';

/* Available languages */
$langs = array(
    'bg_BG' => 'Bulgarian',
    'ca_ES' => 'Catalan',
    'zh_CN' => 'Chinese',
    'nl_NL' => 'Dutch',
    'en_US' => 'English',
    'fi_FI' => 'Finnish',
    'fr_FR' => 'French',
    'de_DE' => 'German',
    'it_IT' => 'Italian',
    'ja_JP' => 'Japanese',
    'pl_PL' => 'Polish',
    'ro_RO' => 'Romanian',
    'es_ES' => 'Spanish',
    'tr_TR' => 'Turkish',
    'uk_UA' => 'Ukranian',
);

/* Plugin display hooks */
$available_display_hooks = array(
    'page_top',                 'icon',                   'clock',
    'menu',                     'folder_list_top',        'folder_list_bottom',
    'notices_top',              'notices_bottom',         'content_bottom',
    'footer',                   'mailbox_top',            'mailbox_meta',
    'mailbox_sort_form',        'mailbox_controls_1',     'mailbox_controls_2',
    'mailbox_search',           'mailbox_bottom',         'message_top',
    'message_meta',             'message_headers_bottom', 'message_bottom',
    'new_page_top',             'new_page_title_row',     'new_page_controls',
    'new_page_bottom',          'search_page_top',        'search_result_meta',
    'search_result_controls',   'search_result_bottom',   'search_form_top',
    'search_form_bottom',       'search_page_bottom',     'about_page_top',
    'about_table_bottom',       'about_page_bottom',      'options_page_top',
    'options_page_title_row',   'general_options_table',  'folder_options_table',
    'message_options_table',    'mailbox_options_table',  'new_options_table',
    'options_page_bottom',      'contacts_page_top',      'contact_detail_top',
    'contact_detail_bottom',    'contacts_quick_links',   'existing_contacts_top',
    'existing_contacts_bottom', 'contacts_page_bottom',   'import_contact_form',
    'add_contact_email_table',  'add_contact_name_table', 'add_contact_address_table',
    'add_contact_phone_table',  'add_contact_org_table',  'folders_page_top',
    'folder_controls_bottom',   'folder_options_top',     'folder_options_bottom',
    'folders_page_bottom',      'compose_options_table',  'compose_top',
    'compose_form_top',         'compose_form_bottom',    'compose_contacts_top',
    'compose_contacts_bottom',  'compose_above_from',     'compose_options',
    'compose_after_message',    'compose_bottom',
);

/* Plugin work hooks */
$available_work_hooks  = array(
    'init',                 'thread_view_start',            'about_page_start', 
    'not_found_start',      'search_page_start',            'folders_page_start',
    'logged_out',           'mailbox_page_start',           'message_page_start',
    'compose_page_start',   'options_page_start',           'contacts_page_start',
    'profile_page_start',   'new_page_start',               'update_settings',
    'message_send',         'compose_contact_list',         'first_time_login',
    'just_logged_in',       'register_contacts_source',     'on_login',
);

/* tags the HTML filter allows */
$allowed_tag_list  = array(true,
    'table', 'tr', 'td', 'tbody', 'th', 'ul', 'ol', 'li', 'hr',
    'em', 'u', 'font', 'br', 'strong', 'span', 'a', 'p', 'img',
    'blockquote', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'
);

/* mbstring available charsets */
$mb_charset_codes = array(
    'UCS-4',        'UCS-4BE',      'UCS-4LE',      'UCS-2',        'UCS-2BE',
    'UCS-2LE',      'UTF-32',       'UTF-32BE',     'UTF-32LE',     'UTF-16',
    'UTF-16BE',     'UTF-16LE',     'UTF-7',        'UTF7-IMAP',    'UTF-8',
    'ASCII',        'EUC-JP',       'SJIS',         'EUCJP-WIN',    'SJIS-WIN',
    'ISO-2022-JP',  'JIS',          'ISO-8859-1',   'ISO-8859-2',   'ISO-8859-3',
    'ISO-8859-4',   'ISO-8859-5',   'ISO-8859-6',   'ISO-8859-7',   'ISO-8859-8',
    'ISO-8859-9',   'ISO-8859-10',  'ISO-8859-13',  'ISO-8859-14',  'ISO-8859-15',
    'EUC-CN',       'CP936',        'HZ',           'EUC-TW',       'CP950',
    'BIG-5',        'BIG5',         'EUC-KR',       'UHC',          'CP949',
    'ISO-2022-KR',  'WINDOWS-1251', 'CP1251',       'WINDOWS-1252', 'CP1252',
    'CP866',        'IBM866',       'KOI8-R',       'GB2312'
);

/* available internal charset conversions */
$charset_codes = array(
    'iso-8859-1',   'iso-8859-2',   'iso-8859-3' ,  'iso-8859-4',
    'iso-8859-5',   'iso-8859-6',   'iso-8859-7',   'iso-8859-8',
    'iso-8859-9',   'iso-8859-10',  'iso-8859-11',  'iso-8859-14',
    'iso-8859-15',  'iso-8859-16',  'koi8-r',       'koi8-u',
    'windows-1252', 'windows-1251', 'ibm-850',      'windows-1256'
);

/* sort types available for server side sorting */
$sort_types = array ( 
    'ARRIVAL'   => 279, 'R_ARRIVAL' => 280,
    'DATE'      => 281, 'R_DATE'    => 282,
    'FROM'      => 283, 'R_FROM'    => 284,
    'SUBJECT'   => 285, 'R_SUBJECT' => 286,
    'CC'        => 287, 'R_CC'      => 288,
    'TO'        => 289, 'R_TO'      => 290,
    'R_SIZE'    => 291, 'SIZE'      => 292,
    'THREAD_R'  => 293, 'THREAD_O'  => 294,
);

/* main application pages */
$app_pages = array(
    'login',    'logout',  'new', 'inline_image',
    'contacts', 'profile', 'options', 'compose',  'folders',
    'search',   'thread_view', 'mailbox', 'message', 'about'
);

/* sort types for client side sorting */
$client_sort_types = array (
    'ARRIVAL'   => 279, 'R_ARRIVAL' => 280,
    'DATE'      => 281, 'R_DATE'    => 282,
    'FROM'      => 283, 'R_FROM'    => 284,
    'SUBJECT'   => 285, 'R_SUBJECT' => 286,
);
/* IMAP SEARCH CHARSET options */
$imap_search_charsets = array(
    'utf-8' => 'CHARSET UTF-8',
    'ascii' => 'CHARSET US-ASCII',
    ''      => ''
);

/* ajax function names */
$ajax_functions = array( 
    'ajax_save_outgoing_message', 'ajax_prev_contacts',
    'ajax_next_contacts', 'ajax_save_folder_state',
    'ajax_save_folder_vis_state', 'ajax_update_page',
);

/* viewabled message parts */
$message_part_types = array( 
    'message/disposition-notification'   => 'text',   /* text part for MDN                       */
    'message/delivery-status'            => 'text',   /* text part for message bounce            */
    'message/rfc822-headers'             => 'text',   /* text part for message headers           */
    'text/csv'                           => 'text',
    'text/plain'                         => 'text',   /* normal text message                     */
    'text/unknown'                       => 'text',   /* normal text message                     */
    'text/html'                          => 'html',   /* HTML message (blech)                    */
    'text/x-vcard'                       => 'text',   /* Vcard                                   */
    'text/calendar'                      => 'text',   /* Vcard                                   */
    'text/enriched'                      => 'text',   /* enriched text                           */
    'text/rfc822-headers'                => 'text',   /* another text part for message headers   */
    'image/jpeg'                         => 'image',  /* JPEG images                             */
    'image/pjpeg'                        => 'image',  /* JPEG images                             */
    'image/jpg'                          => 'image',  /* JPEG images                             */
    'image/png'                          => 'image',  /* PNG images                              */
    'image/bmp'                          => 'image',  /* BMP images                              */
    'image/gif'                          => 'image',  /* GIF images                              */
    'application/pgp-signature'          => 'text',   /* PGP signatures                          */
);

/* small headers available for user selection */
$small_header_options = array(
    'subject',            'from',          'to',               'date',
    'cc',                 'x-spam-status', 'x-spam-level',     'envelope-to',
    'received',           'content-type',  'message-id',       'sender',
    'list-id',            'precedence',    'dilevery-date',    'x-priority',
    'in-reply-to',        'references',    'list-unsubscribe', 'list-subscribe',
    'IMAP message flags', 'x-mailer',      'user-agent',       'content-transfer-encoding'
);

/* date and time format options */
$date_formats = array(
    'm/d/y'  => 'mm/dd/yy',
    'm/d/Y'  => 'mm/dd/yyyy',
    'm-d-y'  => 'mm-dd-yy',
    'm/d/Y'  => 'mm-dd-yyyy',
    'M j, Y' => 'mon dd, yyyy',
    'M j, y' => 'mon dd, yy',
    'M j'    => 'mon dd   ',
    'F d, Y' => 'month dd, yyyy',
    'F d, y' => 'month dd, yy',
    'r'      => 'rfc822',
    'd/m/Y'  => 'dd/mm/yyyy ',
    'd/m/y'  => 'dd/mm/yy',
    'Y-m-d'  => 'yyyy-mm-dd',
    'y-m-d'  => 'yy-mm-dd',
    'd.m.Y'  => 'dd.mm.yyyy',
    'd.m.y'  => 'dd.mm.yy',
);

$time_formats = array(
    'g:i:s a' => '12:00:00',
    'H:i:s'   => '24:00:00',
    'g:i a'   => '12:00',
    'H:i'     => '24:00',
);

/* first page after login options */
$start_pages = array(
    'mailbox' => 22,
    'new' => 10,
    'options' => 4,
    'compose' => 3,
    'contacts' => 8,
    'profile' => 236,
    'folders' => 7,
    'about' => 2,
);

/* sort types for the contacts page */
$contact_sort_types = array(
    'EMAIL'  => 16,
    'FN'     => 149,
    'FAMILY' => 150,
    'GIVEN'  => 151,
    'NAME'   => 152,
);

/* phone types for the contacts page */
$phone_types = array(
    1 => 'Work',
    2 => 'Home',
    3 => 'Cell',
    4 => 'Voice',
    5 => 'Fax',
    6 => 'Preferred'
);

/* phone display types for translations */
$phone_dsp_types = array(
    'Work'  => 325,
    'Home'  => 326,
    'Cell'  => 327,
    'Voice' => 328,
    'Fax'   => 329,
    'Preferred' => 330,
);

/* address types for the contacts page */
$address_types = array(
    1 => 'Work',
    2 => 'Home',
    3 => 'Parcel',
    4 => 'Postal'
);
/* address display types for string translations */
$address_dsp_types = array(
    'Work' => 325,
    'Home' => 326,
    'Parcel' => 331,
    'Postal' => 332,
);

/* text output encoding options */
$text_encodings = array(
    0 => 308,
    1 => 309,
    2 => 310,
);

/* text output format options */
$text_formats = array(
    0 => 305,
    1 => 306,
    2 => 307,
);

/* smtp auth mechs available */
$smtp_auth_mechs = array(
    'none',
    'plain',
    'login',
    'cram-md5',
    'external',
);
/* smtp auth mechs for translations */
$smtp_dsp_mechs = array(
    'none' => 242,
    'plain' => 311,
    'login' => 312,
    'cram-md5' => 313,
    'external' => 314,
); 

/* output filter tags */
$hm_tags = array(
    'complex' => false,
    'simple' => true,
);

/* previous and next options */
$prev_next_actions = array(
    ' ' => 428,
    'move' => 66,
    'copy' => 67,
    'unread' => 34,
    'flag' => 35,
    'delete' => 59,
    'expunge' => 68,
);
/* contact list per page count on the compose page */
$contacts_per_page = 20;

/* maximum read length for message parts (0 is unlimited)
   this only applies to text or html parts being viewed */
$max_read_length = 350000;

$force_plugin_reloading = false;

/* can be either xhtml or html. xhtml will send the application/xhtml-xml http header */
$http_content_header = 'html';

/*  start required objects and prep global space for possible use */
$dbase = false;
$tools = false;
$message = false;
$smtp = false;
$imap =& new imap();
$user =& new fw_user();

/* apply the site config */
get_site_config();

/* start the user object checks */
$user->init();

/* start sajax system if we need it. If we are handling an ajax
   request we do not return from ajax_functions.php */
if ($user->ajax_enabled && isset($_POST['rs'])) {
    require_once($include_path.'lib'.$fd.'ajax_functions.php');
    handle_client_request();
}

/* setup template data */
if ($user->sub_class_names['url']) {
    $class_name = 'site_page_'.$user->sub_class_names['url'];
    $pd =& new $class_name();
}
else {
    $pd =& new site_page();
}

/* clean up */
if ($imap->connected) {
    $imap->disconnect();
}

/* build the page XHTML */
build_page($pd);

/* filter the output before sending to the browser */
output_filtered_content($hm_tags);

/* imap debug */
if (isset($conf['show_imap_debug']) && $conf['show_imap_debug']) {
    if (isset($conf['show_full_debug']) && $conf['show_full_debug']) {
        $imap->puke(true);
    }
    else {
        $imap->puke();
    }
}

/* PHP session cache usage */
if (isset($conf['show_cache_usage']) && $conf['show_cache_usage']) {
    $imap->show_cache();
    if (function_exists('memory_get_peak_usage')) {
        echo '<br />Peak PHP memory usage : '.(sprintf("%0.2f", memory_get_peak_usage()/1024)).'KB';
    }
}

/* clean up */
$user->clean_up();

/* DB debug statements */
if (is_object($dbase) && isset($conf['db_debug']) && $conf['db_debug']) {
    echo $dbase->puke(true);
}
?>
