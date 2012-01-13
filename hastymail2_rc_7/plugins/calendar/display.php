<?php

/*  display.php: Plugin file responsible for the output of XHTML into existing Hastymail pages.
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

/*  DISPLAY HOOKS FUNCTIONS
    For every display hook the plugin registers in config.php there must
    be a corresponding function in this file called <plugin name>_<hook name>
    Output from these functions should be built into a string and returned when complete.
*/

/*  The menu hook outputs between the "Compose" and "Logout" links in the main menu.
    See the docs/display_hooks.txt file for hook location descriptions
    The following adds a link to the menu to our calendar page.
*/  
function calendar_menu($tools) {
    $mailbox = $tools->get_mailbox();
    $tools->set_mailbox($mailbox);
    $data = '<a class="cal_link" href="?page=calendar&amp;mailbox='.urlencode($mailbox).'">Calendar</a>&#160; ';
    return $data;
}
function calendar_general_options_table($tools) {
    $show_summary = $tools->get_setting('calendar_event_summary');
    $data = '<tr><td class="opt_leftcol">Show event summary below folder list</td><td><input type="checkbox" ';
    if ($show_summary) {
        $data .= 'checked="checked" ';
    }
    $data .= 'name="calendar_event_summary" /></td></tr>';
    return $data;
}
function calendar_folder_list_bottom($tools) {
    if ($tools->get_setting('calendar_event_summary')) {
        $cnt = $tools->get_from_store('event_cnt');
        if (!$cnt) {
            $cnt = 0;
        }
        $year = date("Y");
        $month = strtolower(date("F"));
        $day = date("j");
        $url = '?page=calendar&amp;year='.$year.'&amp;month='.$month.'&amp;day='.$day;
            return '<a style="font-size: 80%; display: block; padding-left: 30px; padding-bottom: 15px;" href="'.
                    $url.'" id="folder_event">Events Today: <b>'.$cnt.'</b></a>';
    }
}

?>
