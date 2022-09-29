<?php
/*
 * CRMTEXT library
 *
 * This library contains API functions for CRMTEXT SMS
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */
load_libs('sms');
load_config('crmtext');



///*
// * Authenticate with CRM text
// */
//function crm_authenticate() {
//    global $_CONFIG;
//
//    try{
//        $userid     = 'userid';
//        $passwd     = 'password';
//        $postFields = "method=optincustomer&phone_number=9995551212&firstname=&lastname=";
//        $authString = $userid . ':'. $password . ':'.$keyword ;
//
//        $ch = curl_init();
//
//        curl_setopt($ch, CURLOPT_URL           , $config['central_url']);
//        curl_setopt($ch, CURLOPT_FAILONERROR   , 1);
//        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_TIMEOUT       , 3 );
//        curl_setopt($ch, CURLOPT_POST          , true );
//        curl_setopt($ch, CURLOPT_HTTPAUTH      , CURLAUTH_BASIC);
//        curl_setopt($ch, CURLOPT_USERPWD       , $authString);
//        curl_setopt($ch, CURLOPT_POSTFIELDS    , $postFields );
//
//        crmtext_execute($ch, 'setcallback');
//
//        return $userid;
//
//    }catch(Exception $e) {
//        throw new CoreException('crm_authenticate(): Failed', $e);
//    }
//}



/*
 *
 */
function crmtext_send_message($message, $phone) {
    global $_CONFIG;

    try{
        if(is_array($message)) {
            throw new CoreException(tr('crmtext_send_message(): Specified message is an array which probably indicates an MMS message, which currently is not supported for crmtext SMS'), 'not-supported');
        }

        $phone      = sms_no_country_phones($phone);
        $config     = $_CONFIG['crmtext'];
        $postFields = 'method=sendsmsmsg&phone_number='.$phone.'&message='.urlencode($phone);
        $authString = $config['user'].':'.$config['password']. ':'.$config['keyword'];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL           , $config['central_url']);
        curl_setopt($ch, CURLOPT_FAILONERROR   , 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT       , 3);
        curl_setopt($ch, CURLOPT_POST          , true);
        curl_setopt($ch, CURLOPT_HTTPAUTH      , CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD       , $authString);
        curl_setopt($ch, CURLOPT_POSTFIELDS    , $postFields);

        crmtext_execute($ch, 'sendsmsmsg');

        log_database(tr('Send message "%message%" to "%phone%"', array('%phone%' => $phone, '%message%' => $message)), 'crmtext/message');

        return $message;

    }catch(Exception $e) {
        throw new CoreException('crmtext_send_message(): Failed', $e);
    }
}



/*
 *
 */
function crmtext_set_callback($url = null) {
    global $_CONFIG;

    try{
        $config = $_CONFIG['crmtext'];

        if(!$url) {
            $url = $config['callback_url'];
        }

        $postFields = 'method=setcallback&callback='.urlencode($url);
        $authString = $config['user'].':'.$config['password']. ':'.$config['keyword'];
        $ch         = curl_init();

        curl_setopt($ch, CURLOPT_URL           , $config['central_url']);
        curl_setopt($ch, CURLOPT_FAILONERROR   , 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT       , 3);
        curl_setopt($ch, CURLOPT_POST          , true);
        curl_setopt($ch, CURLOPT_HTTPAUTH      , CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD       , $authString);
        curl_setopt($ch, CURLOPT_POSTFIELDS    , $postFields);

        crmtext_execute($ch, 'setcallback');

        log_database(tr('Set callback to "%callback%"', array('%callback%' => $url)), 'crmtext/set_callback');

        return $url;

    }catch(Exception $e) {
        throw new CoreException('crmtext_set_callback(): Failed', $e);
    }
}



/*
 *
 */
function crmtext_optin($phone, $lastname = '', $firstname = '') {
    global $_CONFIG;

    try{
        $phone      = sms_no_country_phones($phone);
        $config     = $_CONFIG['crmtext'];
        $postFields = 'method=optincustomer&firstname='.$firstname.'&lastname='.$lastname.'&phone_number='.$phone;
        $authString = $config['user'].':'.$config['password']. ':'.$config['keyword'];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL           , $config['central_url']);
        curl_setopt($ch, CURLOPT_FAILONERROR   , 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT       , 3);
        curl_setopt($ch, CURLOPT_POST          , true);
        curl_setopt($ch, CURLOPT_HTTPAUTH      , CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD       , $authString);
        curl_setopt($ch, CURLOPT_POSTFIELDS    , $postFields);

        $result = crmtext_execute($ch, 'optincustomer');

        log_database(tr('Opt-in "%phone%"', array('%phone%' => $phone)), 'crmtext/optin');

        return $phone;

    }catch(Exception $e) {
        throw new CoreException('crmtext_optin_customer(): Failed', $e);
    }
}



/*
 *
 */
function crmtext_optout($phone, $lastname = '', $firstname = '') {
    global $_CONFIG;

    try{
        $phone      = sms_no_country_phones($phone);
        $config     = $_CONFIG['crmtext'];
        $postFields = 'method=optoutcustomer&firstname='.$firstname.'&lastname='.$lastname.'&phone_number='.$phone;
        $authString = $config['user'].':'.$config['password']. ':'.$config['keyword'];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL           , $config['central_url']);
        curl_setopt($ch, CURLOPT_FAILONERROR   , 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT       , 3);
        curl_setopt($ch, CURLOPT_POST          , true);
        curl_setopt($ch, CURLOPT_HTTPAUTH      , CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD       , $authString);
        curl_setopt($ch, CURLOPT_POSTFIELDS    , $postFields);

        $result = crmtext_execute($ch, 'optoutcustomer');

        log_database(tr('Opt-out "%phone%"', array('%phone%' => $phone)), 'crmtext/optout');

        return $phone;

    }catch(Exception $e) {
        throw new CoreException('crmtext_optout_customer(): Failed', $e);
    }
}



/*
 * Execute the cURL request and check for initial errors, then return results
 */
function crmtext_execute($ch, $call) {
    try{
        $xml = curl_exec($ch);

        if($error = curl_error($ch)) {
            throw new CoreException(tr('crmtext_execute(): curl_exec() failed with "%error%"', array('%error%' => $error)), 'CURL'.curl_errno($ch));
        }

        if(Strings::cut(($xml, 'op="', '"') != $call) {
            throw new CoreException(tr('crmtext_execute(): Failed to find requested function call in crmtext results "%results%"', array('%results%' => $xml)), 'call_not_found');
        }

        if(($http_code = Strings::cut(($xml, 'status="', '"')) != 200) {
            throw new CoreException(tr('crmtext_execute(): Got status "%status%" from crmtext with result "%results%"', array('%status%' => $http_code, '%results%' => $xml)), 'HTTP'.$http_code);
        }

        return $xml;

    }catch(Exception $e) {
        throw new CoreException('crmtext_execute(): Failed', $e);
    }
}
?>
