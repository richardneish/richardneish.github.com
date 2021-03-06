<?php
/*
 * SimpleID
 *
 * Copyright (C) Kelvin Mo 2009
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public
 * License along with this program; if not, write to the Free
 * Software Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 * 
 * $Id$
 */

/**
 * Implements the Attribute Exchange extension.
 * 
 *
 * @package simpleid
 * @subpackage extensions
 * @filesource
 */

/** Namespace for the AX extension */
define('OPENID_NS_AX', 'http://openid.net/srv/ax/1.0');

/** @ignore */
global $ax_sreg_map;

/**
 * A mapping between Type URIs defined for Attribute Exchange and the corresponding
 * property for the Simple Registration Extension
 *
 * @link http://www.axschema.org/types/#sreg
 * @global array
 */
$ax_sreg_map = array(
    'http://axschema.org/namePerson/friendly' => 'nickname',
    'http://axschema.org/contact/email' => 'email',
    'http://axschema.org/namePerson' => 'fullname',
    'http://axschema.org/birthDate' => 'dob',
    'http://axschema.org/person/gender' => 'gender',
    'http://axschema.org/contact/postalCode/home' => 'postcode',
    'http://axschema.org/contact/country/home' => 'country',
    'http://axschema.org/pref/language' => 'language',
    'http://axschema.org/pref/timezone' => 'timezone',
    'http://openid.net/schema/namePerson/friendly' => 'nickname',
    'http://openid.net/schema/contact/internet/email' => 'email',
    'http://openid.net/schema/gender' => 'gender',
    'http://openid.net/schema/contact/postalCode/home' => 'postcode',
    'http://openid.net/schema/contact/country/home' => 'country',
    'http://openid.net/schema/language/pref' => 'language',
    'http://openid.net/schema/timezone' => 'timezone'
);

/**
 * Returns the support for AX in SimpleID XRDS document
 *
 * @return array
 * @see hook_xrds_types()
 */
function ax_xrds_types() {
    return array(OPENID_NS_AX);
}

/**
 * @see hook_response()
 */
function ax_response($assertion, $request) {
    global $user;
    global $version;
    global $ax_sreg_map;
    
    // We only deal with positive assertions
    if (!$assertion) return array();
    
    // We only respond if the extension is requested
    if (!openid_extension_requested(OPENID_NS_AX, $request)) return array();
    
    $request = openid_extension_filter_request(OPENID_NS_AX, $request);
    if (!isset($request['mode'])) return array();
    $mode = $request['mode'];
    
    $response = array();
    $alias = openid_extension_alias(OPENID_NS_AX);
    $response['openid.ns.' . $alias] = OPENID_NS_AX;
    
    if ($mode == 'fetch_request') {
        $response['openid.' . $alias . '.mode'] = 'fetch_response';
        
        $required = (isset($request['required'])) ? explode(',', $request['required']) : array();
        $optional = (isset($request['if_available'])) ? explode(',', $request['if_available']) : array();
        $fields = array_merge($required, $optional);
        
        foreach ($fields as $field) {
            $type = $request['type.' . $field];
            $response['openid.' . $alias . '.type.' . $field] = $type;
            $value = _ax_get_value($type);
            
            if ($value == NULL) {
                $response['openid.' . $alias . '.count.' .  $field] = 0;
            } elseif (is_array($value)) {
                $response['openid.' . $alias . '.count.' .  $field] = count($value);
                for ($i = 0; $i < count($value); $i++) {
                    $response['openid.' . $alias . '.value.' .  $field . '.' . ($i + 1)] = $value[$i];
                }
            } else {
                $response['openid.' . $alias . '.value.' .  $field] = $value;
            }
        }
    } elseif ($mode == 'store_request') {
        // Sadly, we don't support storage at this stage
        $response['openid.' . $alias . '.mode'] = 'store_response_failure';
        $response['openid.' . $alias . '.error'] = 'OpenID provider does not support storage of attributes';
    }
    
    return $response;
}

/**
 * Returns an array of fields that need signing.
 *
 * @see hook_signed_fields()
 */
function ax_signed_fields($response) {
    // We only respond if the extension is requested
    if (!openid_extension_requested(OPENID_NS_AX, $response)) return array();
    
    $fields = array_keys(openid_extension_filter_request(OPENID_NS_AX, $response));
    $alias = openid_extension_alias(OPENID_NS_AX);
    $signed_fields = array();

    if (isset($response['openid.ns.' . $alias])) $signed_fields[] = 'ns.' . $alias;
    foreach ($fields as $field) {
        if (isset($response['openid.' . $alias . '.' . $field])) $signed_fields[] = $alias . '.' . $field;
    }
    
    return $signed_fields;
}

/**
 * @see hook_consent_form()
 */
function ax_consent_form($request, $response, $rp) {
    global $user;
    
    // We only respond if the extension is requested
    if (!openid_extension_requested(OPENID_NS_AX, $request)) return '';
    
    $request = openid_extension_filter_request(OPENID_NS_AX, $request);
    if (!isset($request['mode'])) return '';
    $mode = $request['mode'];
    
    $xtpl2 = new XTemplate('extensions/ax/ax.xtpl');
    
    if ($mode == 'fetch_request') {
        $xtpl2->assign('alias', openid_extension_alias(OPENID_NS_AX));
        
        $required = (isset($request['required'])) ? explode(',', $request['required']) : array();
        $optional = (isset($request['if_available'])) ? explode(',', $request['if_available']) : array();
        $fields = array_merge($required, $optional);
        
        foreach ($fields as $field) {
            $type = $request['type.' . $field];
            $value = _ax_get_value($type);
            
            if (is_array($value)) {
                $xtpl2->assign('name', htmlspecialchars($type, ENT_QUOTES, 'UTF-8'));
                $xtpl2->assign('value', htmlspecialchars(implode(',', $value), ENT_QUOTES, 'UTF-8'));
                $xtpl2->parse('fetch_request.ax');
            } elseif ($value != NULL) {
                $xtpl2->assign('name', htmlspecialchars($type, ENT_QUOTES, 'UTF-8'));
                $xtpl2->assign('value', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
                $xtpl2->parse('fetch_request.ax');
            }
        }
        
        $xtpl2->parse('fetch_request');
        return $xtpl2->text('fetch_request');
    } elseif ($mode == 'store_request') {
        // Sadly, we don't support storage at this stage
        $xtpl2->parse('store_request');
        return $xtpl2->text('store_request');
    }
}

/**
 * @see hook_page_profile()
 */
function ax_page_profile() {
    global $user;
    $xtpl2 = new XTemplate('extensions/ax/ax.xtpl');
    
    if (isset($user['ax'])) {
        foreach ($user['ax'] as $name => $value) {
            $xtpl2->assign('name', htmlspecialchars($name, ENT_QUOTES, 'UTF-8'));
            $xtpl2->assign('value', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            $xtpl2->parse('user_page.ax');
        }
    }    
    
    $xtpl2->parse('user_page');
    
    return array(array(
        'id' => 'ax',
        'title' => 'Attribute Exchange Extension',
        'content' => $xtpl2->text('user_page')
    ));
}

function _ax_get_value($type) {
    global $user;
    global $ax_sreg_map;
    
    if (isset($user['ax'][$type])) {
        return $user['ax'][$type];
    } elseif (isset($ax_sreg_map[$type]) && isset($user['sreg'][$ax_sreg_map[$type]])) {
        return $user['sreg'][$ax_sreg_map[$type]];
    } else {
        return NULL;
    }
}
?>
