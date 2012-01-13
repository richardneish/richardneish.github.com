#!/usr/bin/php
<?php
    # Load command line parameters into the REQUEST object.
    if ($argv) { 
        foreach ($argv as $k=>$v) 
        { 
            if ($k==0) continue; 
            $it = explode("=",$argv[$k]); 
            if (isset($it[1])) {
                $_REQUEST[$it[0]] = $it[1]; 
            }
        } 
    } 

    require('config.php');
    define_syslog_variables();
    openlog('send_sms', LOG_PID, LOG_DAEMON);

    function load_user_info($remote_user) {
        global $DEFAULT_FROM_ID, $MAX_FROM_ID_LENGTH;
        global $FROMID_FILE, $MAX_FROMID_LINE_LENGTH;
        global $PHONEBOOK_FILE, $MAX_PHONEBOOK_LINE_LENGTH;
   
        if (!isset($remote_user) || trim($remote_user) == '') {
            # Use defaults
            $from_id = $DEFAULT_FROM_ID;
            $phonebook = null;
        } else {
            # Find from_id for username
            $found == FALSE;
            $fh = fopen($FROMID_FILE, 'r');
            if ($fh != FALSE) {
                while (!$found && 
		       ($csv = fgetcsv($fh, $MAX_FROMID_LINE_LENGTH))
		        != FALSE) {
                    if ($csv[0] == $remote_user) {
                        $found = TRUE;
                        $from_id = $csv[1];
                    }
                }
                fclose($fh);
            }

            if (!$found) {
                $from_id = substr(trim($remote_user), 0, $MAX_FROM_ID_LENGTH);
            }

            # Find phonebook entries for username
            $found == FALSE;
            $fh = fopen($PHONEBOOK_FILE, 'r');
            if ($fh != FALSE) {
                while (!$found &&
                       ($csv = fgetcsv($fh, $MAX_PHONEBOOK_LINE_LENGTH))
                        != FALSE) {
                    if ($csv[0] == $remote_user) {
                        $found = TRUE;
                        #TODO: process phonebook entries
                        $phonebook = null;
                    }
                }
                fclose($fh);
            }

            if (!$found) {
                $phonebook = null;
            }
        }
     
        return $from_id;
    }

    if (isset($_REQUEST['sms']) && trim($_REQUEST['sms']) != '') {
        $from_id = trim($_REQUEST['from_id']);
        if ($from_id == '') {
            $from_id = $DEFAULT_FROM_ID;
        }
        $mobile = trim($_REQUEST['mobile']);
        if ($mobile == '') {
            $mobile = $DEFAULT_MOBILE;
        }
        $url .= "?username=";
        $url .= urlencode($username);
        $url .= "&password=";
        $url .= urlencode($password);
        $url .= "&mobile=";
        $url .= urlencode($mobile);
        $url .= "&sms=";
        $url .= urlencode($_REQUEST['sms']);
        $url .= "&from_id=";
        $url .= urlencode(substr($from_id, 0, $MAX_FROM_ID_LENGTH));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $retval = curl_exec($ch);
        curl_close($ch);
        syslog(LOG_INFO,
               "message='${_REQUEST[sms]}', username='${_SERVER[REMOTE_USER]}', " .
	       "mobile='${mobile}', from_id='${from_id}', result='${retval}'");

        include('request_complete.php');
    } else {
        $from_id = load_user_info($_SERVER['REMOTE_USER']);
        include('sms_form.php');
    }
    closelog();
?>
