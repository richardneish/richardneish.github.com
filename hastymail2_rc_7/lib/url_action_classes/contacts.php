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
function url_action_contacts($get) {
    global $user;
    global $imap;
    global $include_path;
    global $conf;
    global $fd;
    if ($user->logged_in) {
        do_work_hook('contacts_page_start');
        $user->page_data['contacts_link_class'] = 'current_page';
        $user->dsp_page = 'contacts';
        $user->page_title .= ' | '.$user->str[8].' |';
        $user->page_data['show_card_detail'] = false;
        $page = 1;
        if (isset($get['contacts_page'])) {
            $page = (int) $get['contacts_page'];
            if (!$page) {
                $page = 1;
            }
        }
        $user->page_data['contacts_page'] = $page;
        if (isset($user->page_data['contact_sort'])) {
            $_SESSION['contact_sort_order'] = $user->page_data['contact_sort'];
            require_once($include_path.'lib'.$fd.'vcard.php');
            $vcard =& new vcard();
            $vcard->sort_fld = $user->page_data['contact_sort'];
            $user->page_data['contact_sort_order'] = $vcard->sort_fld;
            $vcard->get_card_list(false, $page);
            $user->page_data['card_total'] = $vcard->card_total;
            $user->page_data['contact_list'] = $vcard->card_list;
        }
        else {        
            if (isset($_SESSION['contact_sort_order'])) {
                $sort = $_SESSION['contact_sort_order'];
            }
            else {
                $sort = 'EMAIL';
            }
            $user->page_data['contact_sort_order'] = $sort;
            if (isset($_SESSION['import_card_detail']) && !empty($_SESSION['import_card_detail'])) {
                require_once($include_path.'lib'.$fd.'vcard.php');
                $vcard =& new vcard();
                $vcard->card = $_SESSION['import_card_detail'];
                unset($_SESSION['import_card_detail']);
                $vcard->sort_fld = $sort;
                $vcard->get_card_list(false, $page);
                $user->page_data['card_total'] = $vcard->card_total;
                $user->page_data['import_vals'] = $vcard->card;
            }
            if (isset($user->page_data['import_card'])) {
                $vcard = $user->page_data['import_card'];
                $vcard->sort_fld = $sort;
                $vcard->get_card_list(false, $page);
                $user->page_data['card_total'] = $vcard->card_total;
                $user->page_data['import_vals'] = $vcard->card;
            }
            else {
                require_once($include_path.'lib'.$fd.'vcard.php');
                $vcard =& new vcard();
                $vcard->sort_fld = $sort;
                $vcard->get_card_list(false, $page);
                $user->page_data['card_total'] = $vcard->card_total;
            }
            $user->page_data['contact_list'] = $vcard->card_list;
            if (isset($get['card_detail'])) {
                $id = $get['card_detail'];
                if (isset($vcard->card_list[$id])) {
                    $user->page_data['show_card_detail'] = true;
                    $user->page_data['card_id'] = $id;
                    $user->page_data['card_detail'] = $vcard->card_list[$id];
                }
            }
            elseif (isset($get['edit_card'])) {
                $id = $get['edit_card'];
                if (isset($vcard->card_list[$id])) {
                    $user->page_data['card_id'] = $id;
                    $user->page_data['edit_vals'] = $vcard->card_list[$id];
                }
            }
            elseif (isset($get['download_card'])) {
                $id = $get['download_card'];
                if ($id == 'all') {
                    $body = '';
                    $vcard->get_card_list(false);
                    foreach ($vcard->card_list as $i => $vals) {
                        list ($nothing, $text) = $vcard->export_card($i);
                        $body .= "\r\n".$text;
                    }
                    ob_end_clean();
                    header("Content-Type:text/x-vcard");
                    header('Content-Disposition: attachment; filename="'.str_replace('@', '_', $_SESSION['user_data']['username']).'_contacts.vcf"');
                    header("Content-Length: ".strlen($body));
                    echo $body;
                    $imap->disconnect();
                    $user->clean_up();
                    exit;
                }
                else {
                    if (isset($vcard->card_list[$id])) {
                        list($filename, $body) = $vcard->export_card($id);
                        ob_end_clean();
                        header("Content-Type:text/x-vcard");
                        header('Content-Disposition: attachment; filename="'.$filename.'"');
                        header("Content-Length: ".strlen($body));
                        echo $body;
                        $imap->disconnect();
                        $user->clean_up();
                        exit;
                    }
                }
            }
        }
        $user->page_data['top_link'] = '<a href="'.$user->sticky_url.'#top">'.$user->str[186].'</a>';
        $user->page_data['folders'] = $_SESSION['folders'];
    }
}
}

class site_page_contacts extends site_page {
function print_import_contact() {
    $data = '<a name="importcontact"></a><h4>'.$this->user->str[156].'</h4><div id="importform">
            <form method="post" action="?page=contacts&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;anchor=contactform#contactform" enctype="multipart/form-data">
            <table cellpadding="2" cellspacing="0"><tr><td>'.$this->user->str[160].':<br /><input type="file" name="card_upload" /></td>'.
            '<td><br /> <input type="submit" name="import_card" value="'.$this->user->str[146].'" /></td></tr></table>
            </form><form method="post" action="?page=contacts&amp;mailbox='.urlencode($this->pd['mailbox']).'" enctype="multipart/form-data">
            <table cellpadding="2" cellspacing="0">'.
            '<tr><td>'.$this->user->str[161].':<br /><input type="file" name="mcard_upload" /></td><td>'.
            '<br /> <input type="submit" name="import_card" value="'.$this->user->str[146].'" /></td></tr></table>'.do_display_hook('import_contact_form').
            '</form></div>';
    return $data;
}
function print_sort_contacts() {
    global $contact_sort_types;
    $data = '<div id="sort_contacts"><form method="post" action="?page=contacts&amp;mailbox='.urlencode($this->pd['mailbox']).'" >'.$this->user->str[39].' &#160;<select '.
            'name="contact_sort" onchange="display_notice(this, \'Resorting contacts...\');">';
    foreach ($contact_sort_types as $i => $v) {
        $data .= '<option ';
        if (isset($this->pd['contact_sort_order']) && $this->pd['contact_sort_order'] == $i) {
            $data .= 'selected="selected" ';
        }
        $data .= 'value="'.$i.'">'.$this->user->str[$v].'</option>';
    }
    $data .= '</select>&#160;<input type="hidden" name="sort_contacts" value="'.$this->user->str[39].'" /><noscript><input type="submit" name="sort_contacts" value="'.$this->user->str[39].'" /></noscript></form></div>';
    return $data;
}
function print_contact_list() {
    global $page_id;
    $data = '';
    if (!empty($this->pd['contact_list'])) {
        $data .= '<tr><th></th><th></th><th>'.$this->user->str[143].'</th><th>'.$this->user->str[16].'</th><th>'.$this->user->str[144].'</th><th>'.$this->user->str[145].'</th></tr>';
        foreach ($this->pd['contact_list'] as $id => $vals) {
            $data .= '<tr><td width="1">'.$id.'</td><td class="contact_table_links"><a href="?page=contacts&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;card_detail='.
                     $id.'&amp;contacts_page='.$this->pd['contacts_page'].'">'.$this->user->str[153].'</a> / <a href="?page=contacts&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;edit_card='.$id.'&amp;token='.$page_id.'&amp;contacts_page='.$this->pd['contacts_page'].'#contactform">'.$this->user->str[154].'</a>
                     / <a href="?page=contacts&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;download_card='.$id.'">'.$this->user->str[155].'</a></td><td>';
            $name = '';
            foreach ($vals as $atts) {
                if (isset($atts['name']) && $atts['name'] == 'FN') {
                    $name = $atts['value'];
                    break;
                }
            } 
            $data .= $this->user->htmlsafe($name).'</td><td>';
            $multi = false;
            foreach ($vals as $atts) {
                if (isset($atts['name']) && $atts['name'] == 'EMAIL') {
                    if ($multi) {
                        $data .= ', ';
                    }
                    $data .= $this->user->htmlsafe($atts['value']);
                    $multi = true;
                }
            }
            $data .= '</td><td>';
            $phone = '';
            foreach ($vals as $atts) {
                if (isset($atts['name']) && $atts['name'] == 'TEL') {
                    if (!empty($atts['properties'])) {
                        $phone .= $atts['properties'][0].':';
                    }
                    $phone .= $this->user->htmlsafe($atts['value']).'<br />';
                }
            } 
            $data .= $phone.'</td><td>';
            foreach ($vals as $atts) {
                if (isset($atts['group']) && $atts['group'] == 'ORG' && isset($atts['name']) && $atts['name'] == 'NAME') {
                    $data .= $this->user->htmlsafe($atts['value']);
                }
            }
            $data .= '</td></tr>';
        }
    }
    else {
        $data .= '<tr><td class="no_contacts">No contacts found</td></tr>';
    }
    $data .= '<tr><td colspan="6" class="contact_links">'.print_contact_page_links($this->pd['card_total'], $this->pd['contacts_page'], $this->pd['mailbox']).'</td></tr>';
    return $data;
}
function print_vcard_form() {
    global $address_types;
    global $address_dsp_types;
    global $phone_types;
    global $phone_dsp_types;
    $a_email = '';
    $b_email = '';
    $c_email = '';
    $d_email = '';
    $n_family = '';
    $n_given = '';
    $n_middle = '';
    $n_prefix = '';
    $n_suffix = '';
    $adr_poaddr = '';
    $adr_extaddr = '';
    $adr_street = '';
    $adr_locality = '';
    $adr_region = '';
    $adr_postalcode = '';
    $adr_countryname = '';
    $adr_type = '';
    $tz = '';
    $geo = '';
    $a_tel_type = '';
    $a_tel = '';
    $fn = '';
    $b_tel_type = '';
    $b_tel = '';
    $c_tel_type = '';
    $c_tel = '';
    $org_name = '';
    $org_unit = '';
    $org_title = '';
    if (isset($this->user->form_vals['card_id'])) {
        $label = 'Edit Contact';
        if (!empty($this->user->form_vals)) {
            foreach ($this->user->form_vals as $i => $v) {
                $$i = $this->user->htmlsafe($v);
            }
        }
        $this->pd['card_id'] = $card_id;
        $form = '<form method="post" action="?page=contacts&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;anchor=contactform#contactform">';
        $button = '<input type="hidden" name="card_id" value="'.$this->pd['card_id'].'" /><input type="submit" value="'.$this->user->str[193].'" name="update_vcard" />
                  &#160;<input type="submit" name="delete_vcard" value="'.$this->user->str[59].'" onclick="return hm_confirm(\''.$this->user->str[426].');" />
                  &#160; <a href="?page=contacts&amp;mailbox='.urlencode($this->pd['mailbox']).'">'.$this->user->str[62].'</a>';
    }
    elseif (isset($this->pd['edit_vals'])) {
        $form = '<form method="post" action="?page=contacts&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;anchor=contactform#contactform">';
        foreach ($this->pd['edit_vals'] as $atts) {
            $name = '';
            if (isset($atts['group']) && $atts['group']) {
                $name .= strtolower($atts['group']).'_';
            }
            if (isset($atts['name'])) {
                $name .= strtolower($atts['name']);
            }
            if ($name == 'b_tel' || $name == 'a_tel' || $name == 'c_tel') {
                if ($atts['properties'][0]) {
                    ${$name.'_type'} = $atts['properties'][0];
                } 
            }
            if ($atts['group'] == 'ADR') {
                if ($atts['properties'][0]) {
                    $adr_type = $atts['properties'][0];
                } 
            }
            $$name = $this->user->htmlsafe($atts['value']);
        }
        $label = $this->user->str[159];
        $button = '<input type="hidden" name="card_id" value="'.$this->pd['card_id'].'" /><input type="submit" value="'.$this->user->str[193].'" name="update_vcard" />
                  &#160;<input type="submit" name="delete_vcard" value="'.$this->user->str[59].'" onclick="return hm_confirm(\'Are you sure you want to remove this contact?\');" />
                  &#160; <a href="?page=contacts&amp;mailbox='.urlencode($this->pd['mailbox']).'">'.$this->user->str[62].'</a>';
    }
    else {
        $form = '<form method="post" action="?page=contacts&amp;mailbox='.urlencode($this->pd['mailbox']).'&amp;anchor=contactform#contactform">';
        $label = $this->user->str[158];
        $button = '<input type="submit" name="add_vcard" value="'.$this->user->str[147].'" />';
        if (isset($this->pd['import_vals'])) {
            foreach ($this->pd['import_vals'] as $atts) {
                $name = '';
                if (isset($atts['group']) && $atts['group']) {
                    $name .= strtolower($atts['group']).'_';
                }
                if (isset($atts['name'])) {
                    $name .= strtolower($atts['name']);
                }
                if ($name == 'b_tel' || $name == 'a_tel' || $name == 'c_tel') {
                    if (isset($atts['properties'][0]) && $atts['properties'][0]) {
                        ${$name.'_type'} = $atts['properties'][0];
                    } 
                }
                if ($atts['group'] == 'ADR') {
                    if (isset($atts['properties'][0]) && $atts['properties'][0]) {
                        $adr_type = $atts['properties'][0];
                    } 
                }
                $$name = $this->user->htmlsafe($atts['value']);
            }
        }
        elseif (isset($this->pd['message_contact'])) {
            $a_email = $this->user->htmlsafe($this->pd['message_contact']);
        }
        elseif (!empty($this->user->form_vals)) {
            foreach ($this->user->form_vals as $i => $v) {
                $$i = $this->user->htmlsafe($v);
            }
        }
    }
    $data = '<a name="contactform"></a>';
    if (strstr($this->user->page_anchor, 'contactform')) {
        $data .= '<div class="notices">'.$this->print_notices().'</div>';
    }
    $data .= '<h4>'.$label.'</h4><div id="contact_form">'.$form.'
             <div class="edit_buttons">'.$button.'</div><h5>'.$this->user->str[16].'</h5><table cellpadding="0" cellspacing="0">
             <tr><td class="contacts_leftcol"><b>'.$this->user->str[162].' *</b></td><td><input type="text" size="32" name="a_email" value="'.$a_email.'" /></td></tr>
             <tr><td class="contacts_leftcol">'.$this->user->str[163].'</td><td><input size="32" type="text" name="b_email" value="'.$b_email.'" /></td></tr>
             <tr><td class="contacts_leftcol">'.$this->user->str[164].'</td><td><input size="32" type="text" name="c_email" value="'.$c_email.'" /></td></tr>
             <tr><td class="contacts_leftcol">'.$this->user->str[165].'</td><td><input size="32" type="text" name="d_email" value="'.$d_email.'" /></td></tr>
             '.do_display_hook('add_contact_email_table').'</table><h5>'.$this->user->str[143].'</h5><table cellpadding="0" cellspacing="0">
             <tr><td class="contacts_leftcol">'.$this->user->str[166].':</td><td><input name="fn" type="text" size="32" maxlength="64" value="'.$fn.'" /></td></tr>
             <tr><td class="contacts_leftcol">'.$this->user->str[150].':</td><td><input name="n_family" type="text" size="32" maxlength="64" value="'.$n_family.'" /></td></tr>
             <tr><td class="contacts_leftcol">'.$this->user->str[151].':</td><td><input name="n_given" type="text" size="32" maxlength="64" value="'.$n_given.'" /></td></tr>
             <tr><td class="contacts_leftcol">'.$this->user->str[167].':</td><td><input name="n_middle" type="text" size="32" maxlength="64" value="'.$n_middle.'" /></td></tr>
             <tr><td class="contacts_leftcol">'.$this->user->str[168].':</td><td><input name="n_prefix" type="text" size="32" maxlength="64" value="'.$n_prefix.'" /></td></tr>
             <tr><td class="contacts_leftcol">'.$this->user->str[169].':</td><td><input name="n_suffix" type="text" size="32" maxlength="64" value="'.$n_suffix.'" /></td></tr>
             '.do_display_hook('add_contact_name_table').'</table><h5>'.$this->user->str[182].'</h5><table cellpadding="0" cellspacing="0">
             <tr><td class="contacts_leftcol">'.$this->user->str[170].':</td><td><input name="adr_poaddr" type="text" size="32" maxlength="64" value="'.$adr_poaddr.'" /></td></tr>
             <tr><td class="contacts_leftcol">'.$this->user->str[171].':</td><td><input name="adr_extaddr" type="text" size="32" maxlength="64" value="'.$adr_extaddr.'" /></td></tr>
             <tr><td class="contacts_leftcol">'.$this->user->str[172].':</td><td><input name="adr_street" type="text" size="62" maxlength="128" value="'.$adr_street.'" /></td></tr>
             <tr><td class="contacts_leftcol">'.$this->user->str[173].':</td><td><input name="adr_locality" type="text" size="16" maxlength="32" value="'.$adr_locality.'" /></td></tr>
             <tr><td class="contacts_leftcol">'.$this->user->str[174].':</td><td><input name="adr_region" type="text" size="16" maxlength="32" value="'.$adr_region.'" /></td></tr>
             <tr><td class="contacts_leftcol">'.$this->user->str[175].':</td><td><input name="adr_postalcode" type="text" size="16" maxlength="32" value="'.$adr_postalcode.'" /></td></tr>
             <tr><td class="contacts_leftcol">'.$this->user->str[176].':</td><td><input name="adr_countryname" type="text" size="16" maxlength="32" value="'.$adr_countryname.'" /></td></tr>
             <tr><td class="contacts_leftcol">'.$this->user->str[177].'</td><td><select name="adr_type">';
    foreach($address_types as $i => $v) {
        $data .= '<option ';
        if ($adr_type == $i || strtoupper($adr_type) == strtoupper($v)) { $data .= 'selected="selected" '; }
        $data .= 'value="'.$i.'">'.$this->user->str[$address_dsp_types[$v]].'</option>';
    }
    $data .= '</select></td></tr>'.do_display_hook('add_contact_address_table').'</table><h5>'.$this->user->str[178].'</h5><table cellpadding="0" cellspacing="0"><tr>';
    $data .= '<td class="contacts_leftcol">'.$this->user->str[179].'</td><td><select name="a_tel_type">';
    foreach($phone_types as $i => $v) {
        $data .= '<option ';
        if ($a_tel_type == $i || strtoupper($a_tel_type) == strtoupper($v)) { $data .= 'selected="selected" '; }
        $data .= 'value="'.$i.'">'.$this->user->str[$phone_dsp_types[$v]].'</option>';
    }
    $data .= '</select>&#160; <input type="text" name="a_tel" size="20" maxlength="40" value="'.$a_tel.'" /></td></tr><tr><td class="contacts_leftcol">'.$this->user->str[180].'</td><td>'.
             '<select name="b_tel_type">';
    foreach($phone_types as $i => $v) {
        $data .= '<option ';
        if ($b_tel_type == $i || strtoupper($b_tel_type) == strtoupper($v)) { $data .= 'selected="selected" '; }
        $data .= 'value="'.$i.'">'.$this->user->str[$phone_dsp_types[$v]].'</option>';
    }
    $data .= '</select>&#160; <input type="text" name="b_tel" size="20" maxlength="40" value="'.$b_tel.'" /></td></tr><tr><td class="contacts_leftcol">'.$this->user->str[181].'</td><td>
              <select name="c_tel_type">';
    foreach($phone_types as $i => $v) {
        $data .= '<option ';
        if ($c_tel_type == $i || strtoupper($c_tel_type) == strtoupper($v)) { $data .= 'selected="selected" '; }
        $data .= 'value="'.$i.'">'.$this->user->str[$phone_dsp_types[$v]].'</option>';
    }
    $data .= '</select>&#160; <input type="text" name="c_tel" size="20" maxlength="40" value="'.$c_tel.'" /></td></tr>'.do_display_hook('add_contact_phone_table').'
    </table><h5>'.$this->user->str[145].'</h5><table cellpadding="0" cellspacing="0"><tr><td class="contacts_leftcol">'.$this->user->str[183].':</td><td><input name="org_name" type="text" '.
    'size="32" maxlength="64" value="'.$org_name.'" /></td></tr><tr><td class="contacts_leftcol">'.$this->user->str[184].':</td><td><input name="org_unit" type="text" size="32" '.
    'maxlength="64" value="'.$org_unit.'" /></td></tr><tr><td class="contacts_leftcol">'.$this->user->str[185].':</td><td><input name="org_title" type="text" size="32" maxlength="64" '.
    'value="'.$org_title.'" /></td></tr>'.do_display_hook('add_contact_org_table').'</table><div class="edit_buttons">'.$button.'</div></form></div>';
    return $data;
}
}
?>
