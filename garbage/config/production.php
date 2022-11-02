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

// CDN configuration
$_CONFIG['cdn']['css']['load_delayed']                                          = true;
$_CONFIG['cdn']['domain']                                                       = 'cdn.phoundation.org';
$_CONFIG['cdn']['js']['internal_to_file']                                       = true;
$_CONFIG['cdn']['img']['lazy_load']                                             = true;
$_CONFIG['cdn']['img']['auto_convert']['jpg']                                   = 'webp';
$_CONFIG['cdn']['img']['auto_convert']['png']                                   = 'webp';

//domain
$_CONFIG['domain']                                                              = 'phoundation.org';

// Date / time format configuration
$_CONFIG['formats']['date']                                                     = 'Ymd';
$_CONFIG['formats']['time']                                                     = 'YmdHis';
$_CONFIG['formats']['human_date']                                               = 'F j, Y';
$_CONFIG['formats']['human_time']                                               = 'H:i:s A';
$_CONFIG['formats']['human_datetime']                                           = 'd/m/Y H:i:s A';

// Mail configuration
$_CONFIG['mail']['developers']                                                  = array(array('name'  => '',
                                                                                              'email' => ''));

//
$_CONFIG['mobile']['viewport']                                                  = 'width=device-width, initial-scale=1';

// Name of the website
$_CONFIG['name']                                                                = 'base';

// Session configuration
$_CONFIG['sessions']['domain']                                                  = 'phoundation.org';
$_CONFIG['sessions']['secure']                                                  = false;

// Shutdown configuration
$_CONFIG['shutdown']['check_disk']['interval']                                  = 0;
$_CONFIG['shutdown']['log_rotate']['interval']                                  = 0;

// Title
$_CONFIG['title']                                                               = 'Phoundation project';

// Whitelabel CDN
$_CONFIG['whitelabels']                                                         = 'cdn.phoundation.org';
