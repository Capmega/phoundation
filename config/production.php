<?php
/*
 * ALL CONFIGURATION ENTRIES ARE ORDERED ALPHABETICALLY, ONLY "debug" IS ON TOP FOR CONVENIENCE
 */

// Debug or not?
$_CONFIG['debug']['enabled']                                                    = false;

// Database configuration
$_CONFIG['db']['core']['db']                                                    = '';
$_CONFIG['db']['core']['user']                                                  = 'base';
$_CONFIG['db']['core']['pass']                                                  = 'base';
$_CONFIG['db']['core']['timezone']                                              = 'UTC';

//domain
$_CONFIG['domain']                                                              = 'phoundation.org';

// Date / time format configuration
$_CONFIG['formats']                                                             = array('date'           => 'Ymd',
                                                                                        'time'           => 'YmdHis',
                                                                                        'human_date'     => 'F j, Y',
                                                                                        'human_time'     => 'H:i:s A',
                                                                                        'human_datetime' => 'd/m/Y H:i:s A');

// Mail configuration
$_CONFIG['mail']['developers']                                                  = array(array('name'  => '',
                                                                                              'email' => ''));

//
$_CONFIG['mobile']['viewport']                                                  = 'width=device-width, initial-scale=1';

// Name of the website
$_CONFIG['name']                                                                = 'base';

// SSO configuration
$_CONFIG['sso']['facebook']                                                     = array('appid'    => '',
                                                                                        'secret'   => '',
                                                                                        'scope'    => 'email,publish_stream,status_update,friends_online_presence,user_birthday,user_location,user_work_history',
                                                                                        'redirect' => 'http://base.localhost/tests/sso.php?provider=facebook');

$_CONFIG['sso']['google']                                                       = array('appid'    => '',
                                                                                        'secret'   => '',
                                                                                        'scope'    => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/plus.me https://www.google.com/m8/feeds',
                                                                                        'redirect' => 'http://base.localhost/tests/sso.php?provider=google');

// Session configuration
$_CONFIG['sessions']['domain']                                                  = 'phoundation.org';
$_CONFIG['sessions']['secure']                                                  = false;

// Shutdown configuration
$_CONFIG['shutdown']['check_disk']['interval']                                  = 0;
$_CONFIG['shutdown']['log_rotate']['interval']                                  = 0;

// Title
$_CONFIG['title']                                                               = 'Phoundation project';
?>
