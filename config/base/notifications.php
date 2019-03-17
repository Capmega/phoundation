<?php
// Notification configuration
$_CONFIG['notifications']                                                       = array('methods'      => array('email'     => true,                                // Set if the email notification method is available or not
                                                                                                                'sms'       => true,                                // Set if the sms notification method is available or not
                                                                                                                'desktop'   => true,                                // Set if the desktop notification method is available or not
                                                                                                                'push'      => true,                                // Set if the push notification method is available or not
                                                                                                                'pushover'  => true,                                // Set if the pushover notification method is available or not
                                                                                                                'prowl'     => true,                                // Set if the prowl notification method is available or not
                                                                                                                'matrix'    => true,                                // Set if the matrix messenger notification method is available or not
                                                                                                                'api'       => true),                               // Set if the API notification method is available or not

                                                                                        'url'          => array('template'  => 'https://domain/notifications/:id',  // The URL to be sent in the short message notifications
                                                                                                                'shortener' => false));                             // If specified, use the specified URL shortener service (IF SUPPORTED, see shortlink library)
?>
