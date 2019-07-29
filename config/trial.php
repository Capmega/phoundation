<?php
/*
 * ALL CONFIGURATION ENTRIES ARE ORDERED ALPHABETICALLY, ONLY "debug" IS ON TOP FOR CONVENIENCE
 */

// Debug or not?
$_CONFIG['debug']['enabled']                                                    = true;

// Content configuration
$_CONFIG['content']['autocreate']                                               = true;

// Always use NON minimized files for development!
$_CONFIG['cdn']['min']                                                          = false;
$_CONFIG['cdn']['bundler']                                                      = false;

//domain
$_CONFIG['domain']                                                              = 'phoundation.org.t.cpamega.com';

// This is not a production environment!
$_CONFIG['production']                                                          = false;

$_CONFIG['notifications']['force']                                              = true;

// Session configuration
$_CONFIG['sessions']['domain']                                                  = 'phoundation.org.t.cpamega.com';
$_CONFIG['sessions']['secure']                                                  = false;

// Shutdown configuration
$_CONFIG['shutdown']['check_disk']['interval']                                  = 0;
$_CONFIG['shutdown']['log_rotate']['interval']                                  = 0;
