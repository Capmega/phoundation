<?php
/*
 * ALL CONFIGURATION ENTRIES ARE ORDERED ALPHABETICALLY, ONLY "debug" IS ON TOP FOR CONVENIENCE
 */

// Debug or not?
$_CONFIG['debug']['enabled']                                                    = true;

// Content configuration
$_CONFIG['content']['autocreate']                                               = true;

// CDN configuration
$_CONFIG['cdn']['min']                                                          = false;
$_CONFIG['cdn']['bundler']['enabled']                                           = false;

// Mail configuration
$_CONFIG['mail']['developer']                                                   = 'support@capmega.com';

// Database configuration
$_CONFIG['db']['core']['user']                                                  = 'base';
$_CONFIG['db']['core']['pass']                                                  = 'base';

//domain
$_CONFIG['domain']                                                              = 'phoundation.org.l.cpamega.com';

// This is not a production environment!
$_CONFIG['production']                                                          = false;

$_CONFIG['notifications']['force']                                              = true;

// Session configuration
$_CONFIG['sessions']['domain']                                                  = 'phoundation.org.l.cpamega.com';
$_CONFIG['sessions']['secure']                                                  = false;

// Shutdown configuration
$_CONFIG['shutdown']['check_disk']['interval']                                  = 0;
$_CONFIG['shutdown']['log_rotate']['interval']                                  = 0;

// Whitelabel CDN
$_CONFIG['whitelabels']                                                         = 'cdn.phoundation.org';
