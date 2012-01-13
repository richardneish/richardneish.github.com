<?php

/*  config.php: Plugin file responsible for defining how the plugin interacts with Hastymail 
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

$filters_hooks = array(
    'work_hooks'        => array('mailbox_page_start'),
    'display_hooks'     => array('mailbox_options_table', 'mailbox_controls_1', 'mailbox_controls_2', 'menu'),
    'page_hook'         => true,
);
$filters_langs = array(
    'en_US' => array(
        1 => 'Mailbox Filters',
        2 => 'Existing Filters',
        3 => 'No Filters Found',
        4 => 'Pattern',
        5 => 'Target',
        6 => 'Action',
        7 => 'Destination Mailbox',
        8 => 'Up',
        9 => 'Down',
        10 => 'Edit',
        11 => 'New Filter',
        12 => 'Edit Filter',
        13 => 'Pattern Target',
        14 => 'Move To',
        15 => 'Mark as Flagged',
        16 => 'Delete Message',
        17 => 'Add',
        18 => 'Update',
        19 => 'Cancel',
        20 => 'Delete',
        21 => 'Filter Options',
        22 => 'Auto Filter INBOX',
        23 => 'Show Filters options in the main menu',
        24 => 'Manage Filters',
        25 => 'Filters',
        26 => 'Custom header field',
        27 => 'Filter',
        28 => 'Move',
        29 => 'Filter Deleted',
        30 => 'Filter Updated',
        31 => 'Filter Added',
        32 => 'Entire message',
        33 => 'Message body',
        34 => 'To field',
        35 => 'Cc field',
        36 => 'From field',
        37 => 'Subject field',
        38 => 'Date',
    ),
    'bg_BG' => array(
        1 => 'Филтри за писмата',
        2 => 'Съществуващи филтри',
        3 => 'Няма намерени филтри',
        4 => 'Критерий',
        5 => 'Търси в',
        6 => 'Действие',
        7 => 'Целева папка',
        8 => 'Нагоре',
        9 => 'Надолу',
        10 => 'Редактирай',
        11 => 'Нов филтър',
        12 => 'Редактирай филтър',
        13 => 'Търси в',
        14 => 'Премести в',
        15 => 'Маркирай с флаг',
        16 => 'Изтрий писмото',
        17 => 'Добави',
        18 => 'Запази',
        19 => 'Отказ',
        20 => 'Изтрий',
        21 => 'Настройки на филтрите',
        22 => 'Автоматично филтрирай папка "ВХОДЯЩИ"',
        23 => 'Показвай бутона "Филтри" в основното меню',
        24 => 'Управление на филтри',
        25 => 'Филтри',
        26 => 'Потребителско хедърно поле',
        27 => 'Филтрирай',
        28 => 'Преместване',
        29 => 'Филтърът е изтрит',
        30 => 'Филтърът е обновен',
        31 => 'Филтърът е добавен',
        32 => 'Цялото писмо',
        33 => 'Тялото на писмото',
        34 => 'Поле "До"',
        35 => 'Поле "Копие"',
        36 => 'Поле "От"',
        37 => 'Поле "Заглавие"',
        38 => 'Дата',
    ),
);
?>
