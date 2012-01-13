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


function context_init($tools) {

    require('settings.php');
    $tools->register_ajax_callback('fetch', 1, 'context_store');
    $tools->add_js_onload('document.body.onmouseup = function() {check_selection();};');
    $tools->add_js_onload('try {msg_iframe.document.body.onmouseup = function() {check_selection();};}catch (e) {}');
    $id_array = array();
    foreach ($context_btn as $btn) {
        $id_array[] = '"'.$btn['id'].'"';
    }
    $id_str = join(',', $id_array);
    $js = '
    <script type="text/javascript">
        '.$tools->start_cdata().'
        function check_selection() {
            var i;
            var txt = get_selection();
            var btns = ['.$id_str.'];
            for (i=0;i<btns.length;i++) {
                if (document.getElementById(btns[i])) {
                    if (!txt.length) {
                        document.getElementById(btns[i]).disabled = true;
                        document.getElementById(btns[i]).className = "disabled_button" ;
                    }
                    else {
                        document.getElementById(btns[i]).disabled = false;
                        document.getElementById(btns[i]).className = "";
                    }
                }
            }
        }
        function get_selection() {
            var txt = "";
            if (window.getSelection) {
                txt = window.getSelection();
            }
            if (!txt.length && document.getSelection) {
                txt = document.getSelection();
            }
            if (!txt.length && document.selection) {
                txt = document.selection.createRange().text;
            }
            if (!txt.length) {
                try {
                    if (msg_iframe.window.getSelection) {
                        txt = msg_iframe.window.getSelection();
                    }
                    if (!txt.length && msg_iframe.document.getSelection) {
                        txt = msg_iframe.document.getSelection();
                    }
                    if (!txt.length && msg_iframe.document.selection) {
                        txt = msg_iframe.document.selection.createRange().text;
                    }
                } catch (e) {}
            }
            return txt;
        }
        function callback_context_fetch(output) {
            alert(output + "test");
        }
        function context_search(location) {
            var txt = get_selection();
            if (txt.length) {';
        foreach ($context_btn as $index => $btn) {
            $js .= '
                if (location == "'.$index.'") {
                    var url = "'.$btn['href'].'".replace(/\%q/, txt);
                    window.open(url, "_blank", "");
                }';
        }
        $js .= '
            }
            //hm_ajax_context_fetch(\'context\', txt, \'context_store\');
            return false;
        }
        '.$tools->end_cdata().'
    </script>';
    $tools->add_js($js);
}
?>
