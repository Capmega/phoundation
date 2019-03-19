<?php
/*
 * Notifications library
 *
 * This library contains notifications functions, functions related to sending notifications back to ourselves in case of problems, events, etc.
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package notifications
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package template
 * @version 2.4.11: Added function and documentation
 *
 * @return void
 */
function notifications_library_init(){
    try{
        load_config('notifications');

    }catch(Exception $e){
        throw new BException('notifications_library_init(): Failed', $e);
    }
}



/*
 * Send notifications
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package notifications
 * @see template_install()
 * @see notify()
 * @version 2.5.0: Added function and documentation
 * @example Remember to not use this function directly, use notify() instead!
 * code
 * notify(array('code'    => 'test',
 *              'title'   => 'This is a test!',
 *              'message' => 'This is just a message to test the notification system'));
 * showdie($result);
 * /code
 *
 * @param params $notification A parameters array
 * @param mixed $notification[code]
 * @param null string $notification[title]
 * @param null string $notification[message]
 * @param null midex $notification[groups]
 * @return natural The notifications id
 */
function notifications($notification){
    global $_CONFIG, $core;

    try{
        /*
         * Add the notification to the database for later lookup
         */
        $notification = notifications_insert($notification);

        if($notification['exception'] and !$_CONFIG['production']){
            /*
             * Exception in non production environments, don't send
             * notifications since we're working on this project!
             */
            $code = $notification['message']->getCode();

            if(str_until($code, '/') === 'warning'){
                /*
                 * Just ignore warnings in non production environments, they
                 * already have been logged.
                 */
                return false;
            }

            /*
             * This is a real exception, so a real problem. Since we are on a
             * non production system, instead of notifying, throw an exception
             * that can be fixed bythe developer
             */
            throw new BException($notification['message'], $notification['code']);
        }

        notifications_send($notification);

        return $notification['id'];

    }catch(Exception $e){
        log_console(tr('notifications(): Notification system failed with ":exception"', array(':exception' => $e->getMessage())), 'error');
        log_console(tr('notifications(): No further exception will be thrown to avoid that one causing another notification which then would cause an endless loop'), 'error');

        if($core->register['script'] != 'init'){
            if(empty($_CONFIG['mail']['developer'])){
                log_console('[notifications() FAILED : '.strtoupper($_SESSION['domain']).' / '.strtoupper(php_uname('n')).' / '.strtoupper(ENVIRONMENT).']', 'error');
                log_console(tr("notifications() failed with: ".implode("\n", $e->getMessages())."\n\nOriginal notification $notification was: \":params\"", array(':params' => $notification)), 'error');
                log_console('WARNING! $_CONFIG[mail][developer] IS NOT SET, NOTIFICATIONS CANNOT BE SENT!', 'error');

            }else{
                mail($_CONFIG['mail']['developer'], '[notifications() FAILED : '.strtoupper(isset_get($_SESSION['domain'], $_CONFIG['domain'])).' / '.strtoupper(php_uname('n')).' / '.strtoupper(ENVIRONMENT).']', "notifications() failed with: ".implode("\n", $e->getMessages())."\n\nOriginal notification event was:\nEvent: \"".cfm($event)."\"\nMessage: \"".cfm($message)."\"");
            }
        }
    }
}



/*
 * Send out the specified notification. If no notification was specified, then try sending out all pending notifications
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package notifications
 * @see notify()
 * @see notification()
 * @version 2.5.0: Added function and documentation
 * @note If a notification was specified and it was already sent out, it will be sent again
 *
 * @param params $notification The parameters for this notification email
 * @return return void()
 */
function notifications_send($notification = null){
    global $_CONFIG;

    try{
        if(!$notification){
            /*
             * Attempt to send all notifications that have not yet been sent out
             */
            $count         = 0;
            $notifications = notifications_list();

            foreach($notifications as $notification){
                notifications_send($notification);
                $count++;
            }

            return $count;
        }

        if(is_numeric($notification)){
            $notification = notifications_get($notification);
        }

        $methods = notifications_get_methods($notification);

        foreach($methods as $method){
            switch($method){
                case 'sms':
                    return notifications_sms($notification);

                case 'email':
                    return notifications_email($notification);

                case 'prowl':
                    return notifications_prowl($notification);

                case 'pushover':
                    return notifications_pushover($notification);

                case 'pushcap':
                    return notifications_pushcap($notification);

                case 'desktop':
                    return notifications_desktop($notification);

                case 'web':
                    return notifications_web($notification);

                case 'push':
                    return notifications_push($notification);

                case 'api':
                    return notifications_api($notification);

                default:
                    throw new BException();
            }
        }

    }catch(Exception $e){
        throw new BException('notifications_send(): Failed', $e);
    }
}



/*
 * Send notifications over email
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package notifications
 * @see notify()
 * @see notification()
 * @version 2.5.0: Added function and documentation
 *
 * @param params $notification The parameters for this notification email
 * @return return void()
 */
function notifications_email($notification){
    global $_CONFIG;

    try{
        load_libs('email');
        email_send(array());

    }catch(Exception $e){
        throw new BException('notifications_email(): Failed', $e);
    }
}



/*
 * Send notifications over SMS
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package notifications
 * @see notify()
 * @see notification()
 * @version 2.5.0: Added function and documentation
 *
 * @param params $notification The parameters for this notification email
 * @return return void()
 */
function notifications_sms($event, $message, $users){
    global $_CONFIG;

    try{

    }catch(Exception $e){
        throw new BException('notifications_sms(): Failed', $e);
    }
}



/*
 * Send notifications over prowl
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package notifications
 * @see notify()
 * @see notification()
 * @version 2.5.0: Added function and documentation
 *
 * @param params $notification The parameters for this notification email
 * @return return void()
 */
function notifications_prowl($event, $message, $users){
    global $_CONFIG;

    try{

    }catch(Exception $e){
        throw new BException('notifications_prowl(): Failed', $e);
    }
}



/*
 * Send notifications over pushcap
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package notifications
 * @see notify()
 * @see notification()
 * @version 2.5.0: Added function and documentation
 *
 * @param params $notification The parameters for this notification email
 * @return return void()
 */
function notifications_pushcap($event, $message, $users){
    global $_CONFIG;

    try{

    }catch(Exception $e){
        throw new BException('notifications_pushcap(): Failed', $e);
    }
}



/*
 * Send notifications on the desktop
 *
 * KDE uses the default kdialog --passivepopup "Example text"
 * GNome uses notify-send "Example text" which requires sudo apt-get install libnotify-bin
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package notifications
 * @see notify()
 * @see notification()
 * @version 2.5.0: Added function and documentation
 *
 * @param params $notification The parameters for this notification email
 * @return return void()
 */
function notifications_desktop($notification){
    try{

    }catch(Exception $e){
        throw new BException('notifications_desktop(): Failed', $e);
    }
}



/*
 * Send notifications to the web browser
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package notifications
 * @see notify()
 * @see notification()
 * @version 2.5.0: Added function and documentation
 *
 * @param params $notification The parameters for this notification email
 * @return return void()
 */
function notifications_web($notification){
    try{

    }catch(Exception $e){
        throw new BException('notifications_web(): Failed', $e);
    }
}



/*
 * Send push notifications
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package notifications
 * @see notify()
 * @see notification()
 * @version 2.5.0: Added function and documentation
 *
 * @param params $notification The parameters for this notification email
 * @return return void()
 */
function notifications_push($notification){
    try{

    }catch(Exception $e){
        throw new BException('notifications_push(): Failed', $e);
    }
}



/*
 * Show notifications over a predefined API
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package notifications
 * @see notify()
 * @see notification()
 * @version 2.5.0: Added function and documentation
 *
 * @param params $notification The parameters for this notification email
 * @return return void()
 */
function notifications_api($notification){
    try{

    }catch(Exception $e){
        throw new BException('notifications_api(): Failed', $e);
    }
}



/*
 * Validate the specified notification
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package notifications
 * @see notify()
 * @see notification()
 * @version 2.5.0: Added function and documentation
 *
 * @param params $notification The parameters for this notification email
 * @return return void()
 */
function notifications_validate($notification){
    try{
        load_libs('validate');

        if(is_object($notification) and ($notification instanceof Error)){
            /*
             * This is a PHP Error. Weird, but not impossible, I guess
             */
            $notification  = array('title'     => tr('PHP Error'),
                                   'exception' => true,
                                   'message'   => $notification->getMessage(),
                                   'code'      => 'error');

            log_file($notification['title'].' '.$notification['message'], 'notification-'.strtolower($notification['title']));

        }elseif(is_object($notification) and ($notification instanceof Exception)){
            if(is_object($notification) and ($notification instanceof BException)){
                /*
                 * Notify about a BException
                 */
                $notification  = array('title'     => ($notification->isWarning() ? tr('Phoundation warning') : tr('Phoundation exception')),
                                       'exception' => true,
                                       'message'   => $notification->getMessages(),
                                       'code'      => 'BException');

                log_file($notification['title'].' '.$notification['message'], 'notification-'.strtolower($notification['title']));

            }else{
                /*
                 * Notify about another PHP exception
                 */
                $notification  = array('title'     => tr('PHP Exception'),
                                       'exception' => true,
                                       'message'   => $notification->getMessage(),
                                       'code'      => 'exception');

                log_file($notification['title'].' '.$notification['message'], 'notification-'.strtolower($notification['title']));
            }

        }else{
            /*
             * This is a normal notification
             */
            array_ensure($notification, 'code,title,message');

            $notification['exception'] = false;

            log_file(not_empty($notification['title'].' '.$notification['message'], tr('No message specified')), 'notification-'.not_empty($notification['title'], tr('without-title')), 'yellow');
        }

        $v = new ValidateForm($notification, 'code,title,groups,message');

        /*
         * Validate code
         */
        $v->isNotEmpty($notification['code'], tr('Please specify a notification code'));
        $v->isRegex($notification['code'], '/[a-z0-9- ]{1,32}/i', tr('Please specify a valid code, containing 0-9, a-z, A-Z, - or spaces, between 1 and 32 characters'));

        /*
         * Validate title
         */
        $v->hasMinChars($notification['title'], 4, tr('Please ensure that the notification message has more than 4 characters'));
        $v->hasMaxChars($notification['title'], 4090, tr('Please ensure that the notification message has less than 4090 characters'));
        $v->hasNoHTML($notification['title'], tr('Please ensure that the notification title has no HTML'));

        /*
         * Validate message
         * Messages can NOT have HTML!
         */
        $v->hasMinChars($notification['message'], 4   , tr('Please ensure that the notification message has more than 4 characters'));
        $v->hasMaxChars($notification['message'], 4090, tr('Please ensure that the notification message has less than 4090 characters'));
        $v->hasNoHMTL($notification['message'], 4090, tr('Please ensure that the message does not contain any HTML'));

        /*
         * Validate the groups
         */
        switch($notification['code']){
            case 'error':
                // FALLTHROUGH
            case 'exception':
                // FALLTHROUGH
            case 'bexception':
                /*
                 * These are always only for the system developers, override
                 * whatever was specified!
                 */

                break;

            default:
                $notification['groups'] = array_force($notification['groups']);
        }

        $v->isValid();

        /*
         * Add URL
         */
        $notification['url'] = notifications_get_url($notification);

        return $notification;

    }catch(Exception $e){
        throw new BException('notifications_validate(): Failed', $e);
    }
}



/*
 * Insert the specified notification in the database
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package notifications
 * @see notify()
 * @see notification()
 * @see notifications_validate()
 * @version 2.5.0: Added function and documentation
 *
 * @param params $notification The parameters for this notification
 * @return return array The notification, validated and sanitized with the database id added
 */
function notifications_insert($notification){
    try{
        $notification = notifications_validate($notification);

        sql_query('INSERT INTO `notifications` (`createdby`, `meta_id`, `code`, `title`, `message`)
                   VALUES                      (`createdby`, `meta_id`, `code`, `title`, `message`)');

        $notification['id'] = sql_insert_id();
        notifications_insert_groups($notification);

        return $notification;

    }catch(Exception $e){
        throw new BException('notifications_insert(): Failed', $e);
    }
}



/*
 * Link the notification to the specified groups
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package notifications
 * @see notification_insert()
 * @see notifications_validate()
 * @version 2.5.0: Added function and documentation
 *
 * @param params $notification The parameters for this notification
 * @return return void()
 */
function notifications_insert_groups($notification){
    try{
        $insert = sql_prepare('INSERT INTO `notifications_groups` (`notifications_id`, `groups_id`)
                               VALUES                             (`notifications_id`, `groups_id`)');

        foreach($notification['groups'] as $groups_id){
            $insert->execute(array(':groups_id'        => $groups_id,
                                   ':notifications_id' => $notification['id']));
        }

    }catch(Exception $e){
        throw new BException('notifications_insert(): Failed', $e);
    }
}



/*
 * Get and return the specified notification from the database
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package notifications
 * @see notify()
 * @see notification()
 * @see notifications_insert()
 * @version 2.5.0: Added function and documentation
 *
 * @param natural The id for the required notification
 * @return return array the specified notification
 */
function notifications_get($notifications_id){
    try{
        if(!is_natural($notifications_id)){
            throw new BException(tr('notifications_get(): Invalid notifications id ":id" specified', array(':id' => $notifications_id)), 'invalid');
        }

    }catch(Exception $e){
        throw new BException('notifications_get(): Failed', $e);
    }
}



/*
 * Create a new URL for the specified notification
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package notifications
 * @see notify()
 * @see notification()
 * @see notifications_validate()
 * @version 2.5.0: Added function and documentation
 *
 * @param natural The id for the required notification
 * @return return array the specified notification
 */
function notifications_url($notification){
    global $_CONFIG;

    try{


    }catch(Exception $e){
        throw new BException('notifications_url(): Failed', $e);
    }
}

?>
