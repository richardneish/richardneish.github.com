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

function filters_mailbox_page_start($tools) {
    $mailbox = $tools->get_mailbox();
    if (isset($_POST['plugin_filter']) || ($mailbox == 'INBOX' && $tools->get_setting('auto_filter'))) {
        $filters = $tools->get_setting('filters');
        $matches = 0;
        if (is_array($filters)) {
            $tools->imap_select_mailbox($mailbox, 'ARRIVAL', false, true);
            foreach ($filters as $vals) {
                $res = $tools->imap_search_mailbox($vals[1], $vals[0]);
                $matches += count($res);
                if (is_array($res) && !empty($res)) {
                    if (isset($vals[3]) && $vals[3] != 'move') {
                        if ($vals[3] == 'flag') {
                            $tools->imap_flag_messages($mailbox, $res, 'FLAG');
                        }
                        elseif ($vals[3] == 'delete') {
                            $tools->imap_delete_messages($mailbox, $res);
                        }
                    }
                    else {
                        if ($mailbox != $vals[2]) {
                            $tools->imap_move_messages($mailbox, $res, $vals[2]);
                        }
                    }
                }
            }
        }
        if ($matches == 0 && isset($_POST['plugin_filter'])) {
            $tools->send_notice('No messages matched any filters');
        }
    }
}
?>
