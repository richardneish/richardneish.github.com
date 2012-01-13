<?php

/*  work.php: Plugin file responsible for the backend processing
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


/*  WORK HOOKS FUNCTIONS
    For every work hook the plugin registers in config.php there must
    be a corresponding function in this file called <plugin name>_<hook name>
    See docs/work_hooks.txt for a list of work hooks and descriptions.
*/
function html_mail_update_settings($tools) {
    if (isset($_POST['html_format_mail']) && $_POST['html_format_mail']) {
        $tools->save_options_page_setting('html_format_mail', 1);
    }
    else {
        $tools->save_options_page_setting('html_format_mail', 0);
    }
}
function html_mail_compose_page_start($tools) {
    if ($tools->get_setting('html_format_mail')) {
        $js = '<style type="text/css">#compose_message_tbl td{padding: 0px !important;}</style>
        <script type="text/javascript" src="plugins/html_mail/tiny_mce/tiny_mce_gzip.js"></script>
        <script type="text/javascript">
        '.$tools->start_cdata().'
        tinyMCE_GZ.init({
            plugins : "table, advlink, insertdatetime, paste, style, xhtmlxtras, visualchars",
            themes : "advanced",
            languages : "en",
            disk_cache : true,
            debug : false
        });
        '.$tools->end_cdata().'
        </script>
        <script type="text/javascript">
        '.$tools->start_cdata().'
        tinyMCE.init({
            force_br_newlines : true,
            forced_root_block : \'\' ,
            relative_urls : false,
            width: "635",
            mode: "textareas",
            theme: "advanced",
            theme_advanced_toolbar_location: "top",
            theme_advanced_toolbar_align : "left",
            cleanup_on_startup : true,
            convert_newlines_to_brs : true,
            convert_fonts_to_spans : true,
            theme_advanced_buttons1_add : "fontsizeselect, fontselect, forecolor",
            theme_advanced_buttons2_add : "styleprops, cite, ins, del, abbr, acronym, attribs, insertdate, inserttime, backcolor",
            theme_advanced_buttons3_add : "visualchars, copy, cut, paste, tablecontrols",
            theme_advanced_disable : "help,styleselect,image",
            extended_valid_elements : "hr[class|width|size|noshade]",
            plugins : "table, advlink, insertdatetime, paste, style, xhtmlxtras, visualchars",
            gecko_spellcheck : true,
            debug : false
        });
        '.$tools->end_cdata().'
        </script>';
        $tools->add_js($js);
    }
}
function html_mail_message_send($tools, $args) {
    if ($tools->get_setting('html_format_mail')) {
        $body = $args[0];
        $alt_body = $tools->html2text($body);
        $tools->alter_compose_type('text/html', $body, $alt_body, '8-bit');
    }
}
?>
