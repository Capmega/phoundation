<?php
/*
 * API configuration file
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */
$_CONFIG['api'] = array('apikey'                                                => '',
                        'domain'                                                => '',
                        'signin_reset_session'                                  => '',

                        'whitelist'                                             => array(),

                        'blacklist'                                             => array(),

                        'list'                                                  => array('localhost' => array('baseurl' => 'http://localhost/api',
                                                                                                              'apikey'  => '')));
?>