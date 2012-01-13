<?php

/*  post_action_class.php: Process POST forms
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
class fw_post_action_contacts extends fw_user_action_with_post {
function set_post_page_vars() {
    global $user;
    $forms = array(
    'update_vcard' => array(
        'card_id' => array('int', 1, 'Card ID'),
        'a_email' => array('email', 1, 'Address 1'),
        'b_email' => array('email', 0, 'Address 2'),
        'c_email' => array('email', 0, 'Address 3'),
        'd_email' => array('email', 0, 'Address 4'),
        'n_family' => array('string', 0, 'Family Name'),
        'n_given' => array('string', 0, 'Given Name'),
        'fn' => array('string', 0, 'Display Name'),
        'n_middle' => array('string', 0, 'Middel Name'),
        'n_prefix' => array('string', 0, 'Name Prefix'),
        'n_suffix' => array('string', 0, 'Name Suffix'),
        'adr_poaddr' => array('string', 0, 'Post Office Address'),
        'adr_extaddr' => array('string', 0, 'Extended Address'),
        'adr_street' => array('string', 0, 'Street Address'),
        'adr_locality' => array('string', 0, 'City'),
        'adr_region' => array('string', 0, 'Region'),
        'adr_postalcode' => array('string', 0, 'Postal Code'),
        'adr_countryname' => array('string', 0, 'Country'),
        'adr_type' => array('int', 0, 'Address Type'),
        'a_tel_type' => array('int', 0, 'Phone 1 Type'),
        'a_tel' => array('string', 0, 'Phone 1'),
        'b_tel_type' => array('int', 0, 'Phone 2 Type'),
        'b_tel' => array('string', 0, 'Phone 2'),
        'c_tel_type' => array('int', 0, 'Phone 3 Type'),
        'c_tel' => array('string', 0, 'Phone 3'),
        'org_name' => array('string', 0, 'Company Name'),
        'org_unit' => array('string', 0, 'Company Unit'),
        'org_title' => array('string', 0, 'Title'),
    ),
    'add_vcard' => array(
        'a_email' => array('email', 1, 'Address 1'),
        'b_email' => array('email', 0, 'Address 2'),
        'c_email' => array('email', 0, 'Address 3'),
        'd_email' => array('email', 0, 'Address 4'),
        'n_family' => array('string', 0, 'Family Name'),
        'n_given' => array('string', 0, 'Given Name'),
        'n_middle' => array('string', 0, 'Middel Name'),
        'n_prefix' => array('string', 0, 'Name Prefix'),
        'fn' => array('string', 0, 'Display Name'),
        'n_suffix' => array('string', 0, 'Name Suffix'),
        'adr_poaddr' => array('string', 0, 'Post Office Address'),
        'adr_extaddr' => array('string', 0, 'Extended Address'),
        'adr_street' => array('string', 0, 'Street Address'),
        'adr_locality' => array('string', 0, 'City'),
        'adr_region' => array('string', 0, 'Region'),
        'adr_postalcode' => array('string', 0, 'Postal Code'),
        'adr_countryname' => array('string', 0, 'Country'),
        'adr_type' => array('int', 0, 'Address Type'),
        'a_tel_type' => array('int', 0, 'Phone 1 Type'),
        'a_tel' => array('string', 0, 'Phone 1'),
        'b_tel_type' => array('int', 0, 'Phone 2 Type'),
        'b_tel' => array('string', 0, 'Phone 2'),
        'c_tel_type' => array('int', 0, 'Phone 3 Type'),
        'c_tel' => array('string', 0, 'Phone 3'),
        'org_name' => array('string', 0, 'Company Name'),
        'org_unit' => array('string', 0, 'Company Unit'),
        'org_title' => array('string', 0, 'Title'),
    ),
    'import_card' => array(
    ),
    'delete_vcard' => array(
        'card_id' => array('int', 1, 'Card ID'),
    ),
    ); return $forms;
}
function form_action_sort_contacts($form, $post) {
    global $user;
    global $contact_sort_types;
    if ($user->logged_in) {
        if (isset($contact_sort_types[$post['contact_sort']])) {
            $user->page_data['contact_sort'] = $post['contact_sort'];
        }
    }
}
function form_action_import_card($form, $post) {
    global $user;
    global $include_path;
    global $conf;
    global $fd;
    if ($user->logged_in) {
        $ufiles = array();
        $utype = false;
        if (isset($_FILES['card_upload']) && !empty($_FILES['card_upload'])) {
            $ufiles = $_FILES['card_upload'];
            $utype = 'single';
        }
        if (isset($_FILES['mcard_upload']) && !empty($_FILES['mcard_upload'])) {
            $ufiles = $_FILES['mcard_upload'];
            $utype = 'multiple';
        }
        if (!empty($ufiles)) {
            if (!$ufiles['error']) {
                $type = strtolower($ufiles['type']);
                if (strtolower(trim($type)) == 'text/x-vcard' || strtolower(trim($type)) == 'text/directory' || strtolower(trim($type)) == 'application/octet-stream') {
                    $src = $ufiles['tmp_name'];
                    $size = $ufiles['size'];
                    if ($ufiles['size']) {
                        $data = file($src);
                        if (!empty($data)) {
                            require_once($include_path.'lib'.$fd.'vcard.php');
                            if ($utype == 'single') {
                                $vcard =& new vcard();
                                $vcard->import_card($data);
                                $user->page_data['import_card'] = $vcard; 
                            }
                            else {
                                $vcard =& new vcard();
                                $vcard->get_card_list();
                                $res = $vcard->import_multiple_cards($data);
                                if ($res) {
                                    $vcard->write_cards();
                                    $this->errors[] = $user->str[358].': '.$res;
                                }
                                else {
                                    $this->errors[] = $user->str[359];
                                }
                            }
                        }
                        else {
                            $this->errors[] = $user->str[360];
                        }
                    }
                    else {
                        $this->errors[] = $user->str[361];
                    }
                }
                else {
                    $this->errors[] = $user->str[362].': '.$user->htmlsafe($type);
                }
            }
            else {
                switch ($ufiles['error']) {
                    case 4:
                        $this->errors[] = $user->str[363];
                        break;
                    default:
                        $this->errors[] = $user->str[364];
                        break;
                }
            }
        }
    }
}
function form_action_delete_vcard($form, $post) {
    global $user;
    global $include_path;
    global $conf;
    global $fd;
    if ($user->logged_in) {
        require_once($include_path.'lib'.$fd.'vcard.php');
        $vcard =& new vcard();
        if (isset($_SESSION['contact_sort_order'])) {
            $vcard->sort_fld = $_SESSION['contact_sort_order'];
        }
        else {
            $vcard->sort_fld = 'EMAIL';
        }
        $vcard->get_card_list();
        if (isset($vcard->card_list[$post['card_id']])) {
            $cards = array();
            $n = 1;
            foreach ($vcard->card_list as $id => $vals) {
                if ($id != $post['card_id']) {
                    $cards[$n] = $vals;
                    $n++;
                }
            }
            $vcard->card_list = $cards;
            $res = $vcard->write_cards();
            if ($res) {
                unset($_GET['edit_card']);
                $this->errors[] = $user->str[365];
                $this->form_redirect = true;
            }
        }
    }
}
function form_action_update_vcard($form, $post) {
    global $user;
    global $include_path;
    global $conf;
    global $fd;
    if ($user->logged_in) {
        $atts = array();
        foreach ($form as $name => $vals) {
            if (isset($post[$name]) && trim($post[$name])) {
                $index = trim(strtoupper(str_replace('_', '.', $name)));
                $atts[$index] = $post[$name];
            }
        }
        if (!empty($atts)) {
            require_once($include_path.'lib'.$fd.'vcard.php');
            $vcard =& new vcard();
            if (isset($_SESSION['contact_sort_order'])) {
                $vcard->sort_fld = $_SESSION['contact_sort_order'];
            }
            else {
                $vcard->sort_fld = 'EMAIL';
            }
            $vcard->get_card_list();
            $vcard->build_card($atts);
            if (isset($vcard->card_list[$post['card_id']])) {
                $vcard->set_card($post['card_id']);
                $res = $vcard->write_cards();
                if ($res) {
                    $this->errors[] = $user->str[366];
                    $this->form_redirect = true;
                }
            }
        }
    }
}
function form_action_add_vcard($form, $post) {
    global $user;
    global $include_path;
    global $conf;
    global $fd;
    if ($user->logged_in) {
        $atts = array();
        foreach ($form as $name => $vals) {
            if (isset($post[$name]) && trim($post[$name])) {
                $index = trim(strtoupper(str_replace('_', '.', $name)));
                $atts[$index] = $post[$name];
            }
        }
        if (!empty($atts)) {
            require_once($include_path.'lib'.$fd.'vcard.php');
            $vcard =& new vcard();
            if (isset($_SESSION['contact_sort_order'])) {
                $vcard->sort_fld = $_SESSION['contact_sort_order'];
            }
            else {
                $vcard->sort_fld = 'EMAIL';
            }
            $vcard->get_card_list();
            $vcard->build_card($atts);
            $vcard->set_card();
            $res = $vcard->write_cards();
            if ($res) {
                $this->errors[] = $user->str[367];
                $this->form_redirect = true;
            }
        }
    }
}
}?>
