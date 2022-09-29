<?php
/*
 * SSO configuration
 *
 * This file contains the default Single Sign On configuration structure
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @note Use the routing table in ROOT/www/route.php to route the redirect URL's to sso.php
 * @package sso
 */
$_CONFIG['sso']                                                                 = array('cache_config' => 86400,        // Default time for how long an SSO authorization can be cached and does not require to be done again

                                                                                        'facebook' => array('appid'    => '',
                                                                                                            'secret'   => '',
                                                                                                            'scope'    => 'email,publish_stream,status_update,friends_online_presence,user_birthday,user_location,user_work_history',
                                                                                                            'redirect' => 'https://base.localhost/sso/facebook/authorized.html'),

                                                                                        'google' => array('appid'    => '',
                                                                                                          'secret'   => '',
                                                                                                          'scope'    => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/plus.me https://www.google.com/m8/feeds',
                                                                                                          'redirect' => 'https://base.localhost/sso/google/authorized.html'),

                                                                                        'twitter' => array('appid'    => '',
                                                                                                           'secret'   => '',
                                                                                                           'redirect' => 'https://base.localhost/sso/twitter/authorized.html'),

                                                                                        'linkedin' => array('appid'    => '',
                                                                                                            'secret'   => '',
                                                                                                            'scope'    => 'r_fullprofile r_emailaddress',
                                                                                                            'redirect' => 'https://base.localhost/sso/linkedin/authorized.html'),

                                                                                        'microsoft' => array('appid'    => '',
                                                                                                             'secret'   => '',
                                                                                                             'scope'    => 'wl.basic wl.emails wl.birthday wl.skydrive wl.photos',
                                                                                                             'redirect' => 'https://base.localhost/sso/microsoft/authorized.html'),

                                                                                        'paypal' => array('appid'    => '',
                                                                                                          'secret'   => '',
                                                                                                          'scope'    => 'email profile',
                                                                                                          'redirect' => 'https://base.localhost/sso/paypal/authorized.html'),

                                                                                       'reddit' => array('appid'    => '',
                                                                                                         'secret'   => '',
                                                                                                         'scope'    => 'identity',
                                                                                                         'redirect' => 'https://base.localhost/sso/reddit/authorized.html'),

                                                                                        'twitter' => array('appid'    => '',
                                                                                                           'secret'   => '',
                                                                                                           'scope'    => '',
                                                                                                           'redirect' => 'https://base.localhost/sso/twitter/authorized.html'),

                                                                                        'yandex' => array('appid'    => '',
                                                                                                          'secret'   => '',
                                                                                                          'scope'    => '',
                                                                                                           'redirect' => 'https://base.localhost/sso/yandex/authorized.html'));

?>
