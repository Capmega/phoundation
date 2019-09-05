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
 * @note $notification[code] is obligatory, and either $notification[title] or $notification[message] must be set
 * @example Remember to not use this function directly, use notify() instead!
 * code
 * notify(array('code'    => 'test',
 *              'title'   => 'This is a test!',
 *              'message' => 'This is just a message to test the notification system'));
 *
 * notify(array('code'    => 'foobar',
 *              'groups'  => 'developers,moderators',
 *              'title'   => 'This is a test!',
 *              'message' => 'This is just a message to test the notification system'));
 * /code
 *
 * @param params $notification A parameters array
 * @param mixed $notification[code]
 * @param null natural $notification[priority]
 * @param null string $notification[title]
 * @param null string $notification[message]
 * @param null mixed $notification[data]
 * @param null mixed $notification[groups]
 * @param boolean $log If set to true, will log the notification
 * @param boolean $throw If set to true, if the notification is an exception and the system is non production, it will throw the exception instead of notifying
 * @return natural The notifications id
 */
function notifications($notification, $log, $throw){
    global $_CONFIG, $core;

    try{
        log_file($notification, 'notifications', 'VERYVERBOSE');

        /*
         * Add the notification to the database for later lookup
         */
        if($core->register['script'] === 'init'){
            $notification = notifications_validate($notification, $log, $throw);

        }else{
            $notification = notifications_insert($notification, $log);
        }

        if($notification['exception'] and !$_CONFIG['production'] and $notification['throw']){
            /*
             * Exception in non production environments, don't send
             * notifications since we're working on this project!
             */
            $code = $notification['code'];

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

// :TODO: Implement all required sub sections, then enable
        //notifications_send($notification);

        return isset_get($notification['id']);

    }catch(Exception $e){
        if(!$_CONFIG['production']){
            throw new BException(tr('notifications(): Failed'), $e);
        }

        if(is_array($notification) and !empty($notification['exception'])){
            /*
             * This is just the notification being thrown as an exception, keep
             * on throwing
             */
            log_console(tr('notifications(): Encountered Error / Exception / BException ":e" on a non production system, throwing exception instead of notifying', array(':e' => $e->getMessage())), 'error');
            throw $e;
        }

        log_console(tr('notifications(): Notification system failed with ":exception"', array(':exception' => $e->getMessage())), 'warning');
        log_console(tr('notifications(): No further exception will be thrown to avoid that one causing another notification which then would cause an endless loop'), 'warning');

        if($core->register['script'] != 'init'){
            if(empty($_CONFIG['mail']['developer'])){
                log_console('[notifications() FAILED : '.strtoupper(isset_get($_SESSION['domain'])).' / '.strtoupper(php_uname('n')).' / '.strtoupper(ENVIRONMENT).']', 'error');
                log_console(tr("notifications() failed with: ".implode("\n", $e->getMessages())."\n\nOriginal notification was: \":params\"", array(':params' => $notification)), 'error');
                log_console('WARNING! $_CONFIG[mail][developer] IS NOT SET, EMERGENCY NOTIFICATIONS CANNOT BE SENT!', 'error');

            }else{
                load_libs('email');
                log_console(tr('Attempting to send out a notification email to the $_CONFIG[mail][developer] email ":email" to let them know the notification system has failed', array(':email' => $_CONFIG['mail']['developer'])), 'warning');

                email_send(array('to'      => $_CONFIG['mail']['developer'],
                                 'subject' => '[notifications() FAILED : '.strtoupper(isset_get($_SESSION['domain'], $_CONFIG['domain'])).' / '.strtoupper(php_uname('n')).' / '.strtoupper(ENVIRONMENT).']', "notifications() failed with: ".implode("\n", $e->getMessages()),
                                 'body'    => "Original notification data was:\nEvent: \"".json_encode_custom($notification)."\""));
            }
        }
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
 * @param boolean $log If set to true, will log the notification
 * @return return void()
 */
function notifications_validate($notification, $log, $throw = null){
    global $_CONFIG;

    try{
        load_libs('validate');

        /*
         * Process Error, Exception, and BException objects first
         */
        if(is_object($notification) and ($notification instanceof Error)){
            /*
             * This is a PHP Error.
             */
            $e            = new BException('', $notification);
            $notification = array('title'     => tr('PHP Error'),
                                  'exception' => true,
                                  'throw'     => $throw,
                                  'message'   => $notification->getMessage(),
                                  'real_code' => $notification->getCode(),
                                  'code'      => 'error');

            if($log){
                log_file($notification['title'].' '.$notification['message'], 'notification-'.strtolower($notification['code']), 'error');
            }

        }elseif(is_object($notification) and ($notification instanceof Exception)){
            if(is_object($notification) and ($notification instanceof BException) and (strtolower(substr($notification->getCode(), 0, 3)) !== 'php')){
                /*
                 * Notify about a BException
                 */
                $e            = $notification;
                $notification = array('title'     => ($notification->isWarning() ? tr('Phoundation warning') : tr('Phoundation exception')),
                                      'exception' => true,
                                      'throw'     => $throw,
                                      'message'   => implode("\n", $notification->getMessages()),
                                      'data'      => $notification->getData(),
                                      'real_code' => $notification->getRealCode(),
                                      'code'      => ($notification->isWarning() ? 'warning' : 'error'));

                if($log){
                    log_file($notification['title']  , 'notification-'.strtolower($notification['code']), $notification['code']);
                    log_file($notification['message'], 'notification-'.strtolower($notification['code']), $notification['code']);
                }

            }else{
                /*
                 * Notify about another PHP exception
                 */
                $notification = array('title'     => tr('PHP Exception'),
                                      'throw'     => $throw,
                                      'exception' => true,
                                      'message'   => $notification->getMessage(),
                                      'code'      => 'exception');

                if($log){
                    log_file($notification['title'].' '.$notification['message'], 'notification-'.strtolower($notification['code']), 'exception');
                }
            }

        }else{
            /*
             * This is a normal notification
             */
            array_ensure($notification, 'code,priority,title,message,data,throw');

            $notification['exception'] = false;

            if($log){
                log_file(not_empty($notification['title'].' '.$notification['message'], tr('No message specified')), 'notification-'.strtolower($notification['code']), 'yellow');
            }
        }

        if(isset_get($e) and $e->getRealCode() === 'load-libs-fail'){
            /*
             * A library failed to load. If this is the validation library,
             * then the next ValidateForm thing will mess us up badly!
             */
            if(array_search('load_libs(): Failed to load library "validate"', $e->getMessages())){
                /*
                 * Yeah, validate library hasn't been loaded, so panic
                 */
                throw new BException(tr('notifications_validate(): Failed to validate notification, the validate library failed to load'), $e);
            }
        }

        $v = new ValidateForm($notification, 'users_id,code,title,priority,groups,message,data');

        /*
         * Validate code
         */
        $v->isNotEmpty($notification['code'], tr('Please specify a notification code'));
        $v->isRegex($notification['code'], '/[a-z0-9- ]{1,32}/i', tr('Please specify a valid code, containing 0-9, a-z, A-Z, - or spaces, between 1 and 32 characters'));

        /*
         * Validate title
         */
        if($notification['title']){
            $v->hasMinChars($notification['title'], 4, tr('Please ensure that the notification message has more than 4 characters'));
            $v->hasMaxChars($notification['title'], 4090, tr('Please ensure that the notification message has less than 4090 characters'));
            $v->hasNoHTML($notification['title'], tr('Please ensure that the notification title has no HTML'));

        }else{
            $notification['title'] = '';
        }

        /*
         * Validate message
         * Messages can NOT have HTML!
         */
        if($notification['message']){
            $v->hasMinChars($notification['message'], 4, tr('Please ensure that the notification message has more than 4 characters'));

            /*
             * Force this instead of validating since large error messages will cause endless loops
             */
            $notification['message'] = str_truncate($notification['message'], 4090, '...', 'center');

        }else{
            $notification['message'] = '';

        }

        /*
         * At least title or message must have been specified!
         */
        if(!$notification['title'] and !$notification['message']){
            $v->setError(tr('Please ensure that at least $notification[title] and or $notification[message] have been set'));
        }

        /*
         * Validate data
         */
        $notification['data'] = str_force($notification['data'], 'json');
        $v->hasMaxChars($notification['message'], 4090, tr('Please ensure that the notification data has less than 4090 characters'));

        /*
         * Validate priority
         */
        if(!$notification['priority']){
            $notification['priority'] = isset_get($_CONFIG['notifications']['defaults']['priority']);
        }

        if($notification['priority']){
            $v->isNatural($notification['priority'], 0, tr('Please ensure that the notification priority is a natural number'));
            $v->isBetween($notification['priority'], 0, 9, tr('Please ensure that the notification priority is a natural number between 0 (highest) and 9 (lowest)'));

        }else{
            $notification['priority'] = 5;
        }

        /*
         * Default groups
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

        /*
         * Validate that groups exist
         */
// :TODO: For now, notifications are still mostly disabled until the system is finished
$notification['groups'] = array();
        foreach($notification['groups'] as &$group){
            $groups_id = notifications_get_group($group, 'id');

            if($groups_id){
                $group = $groups_id;

            }else{
                $v->setError(tr('The notifications group ":group" does not exist', array(':group' => $group)));
            }
        }

        unset($group);

        $v->isValid();

        /*
         * Sanitize
         * Add URL
         */
        $notification['code'] = strtolower($notification['code']);
        $notification['url']  = notifications_get_url($notification);

        return $notification;

    }catch(Exception $e){
        throw new BException('notifications_validate(): Failed', $e);
    }
}



/*
 * Validate the specified notification group
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
 * @param params $group The parameters for this notification group
 * @return return void()
 */
function notifications_validate_group($group){
    try{
        load_libs('validate');
        $v = new ValidateForm($group, '');

        return $group;

    }catch(Exception $e){
        throw new BException('notifications_validate_group(): Failed', $e);
    }
}



/*
 * Validate the specified notification member
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
 * @param params $member The parameters for this notification member
 * @return return void()
 */
function notifications_validate_member($member){
    try{
        load_libs('validate');
        $v = new ValidateForm($member, '');

        return $member;

    }catch(Exception $e){
        throw new BException('notifications_validate_member(): Failed', $e);
    }
}



/*
 * Validate the specified notification method
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
 * @param params $method The parameters for this notification method
 * @return return void()
 */
function notifications_validate_method($method){
    try{
        load_libs('validate');
        $v = new ValidateForm($method, '');

        return $method;

    }catch(Exception $e){
        throw new BException('notifications_validate_method(): Failed', $e);
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
function notifications_insert($notification, $log){
    try{
        $notification = notifications_validate($notification, $log);

        sql_query('INSERT INTO `notifications` (`createdby`, `meta_id`, `users_id`, `code`, `url`, `priority`, `data`, `title`, `message`)
                   VALUES                      (:createdby , :meta_id , :users_id , :code , :url , :priority , :data , :title , :message )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':meta_id'   => meta_action(),
                         ':users_id'  => $notification['users_id'],
                         ':code'      => $notification['code'],
                         ':url'       => $notification['url'],
                         ':priority'  => $notification['priority'],
                         ':data'      => $notification['data'],
                         ':title'     => $notification['title'],
                         ':message'   => $notification['message']), 'core');

        $notification['id'] = sql_insert_id('core');
        notifications_insert_groups($notification);

        return $notification;

    }catch(Exception $e){
        throw new BException('notifications_insert(): Failed', $e);
    }
}



/*
 * Insert the specified notification group in the database
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
function notifications_insert_group($group){
    try{
        $group = notifications_validate_group($group);

        sql_query('INSERT INTO `notifications_groups` (`createdby`, `meta_id`, )
                   VALUES                             (:createdby , :meta_id , )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':meta_id'   => meta_action(),
                         ), 'core');

        $group['id'] = sql_insert_id('core');

        return $group;

    }catch(Exception $e){
        throw new BException('notifications_insert_group(): Failed', $e);
    }
}



/*
 * Insert the specified notification member in the database
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
function notifications_insert_member($member){
    try{
        $member = notifications_validate_member($member);

        sql_query('INSERT INTO `notifications_members` (`createdby`, `meta_id`, )
                   VALUES                              (:createdby , :meta_id , )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':meta_id'   => meta_action(),
                         ), 'core');

        $member['id'] = sql_insert_id('core');

        return $member;

    }catch(Exception $e){
        throw new BException('notifications_insert_member(): Failed', $e);
    }
}



/*
 * Insert the specified notification method in the database
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
function notifications_insert_method($method){
    try{
        $method = notifications_validate_method($method);

        sql_query('INSERT INTO `notifications_methods` (`createdby`, `meta_id`, )
                   VALUES                              (:createdby , :meta_id , )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':meta_id'   => meta_action(),
                         ), 'core');

        $method['id'] = sql_insert_id('core');

        return $method;

    }catch(Exception $e){
        throw new BException('notifications_insert_method(): Failed', $e);
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
                               VALUES                             (:notifications_id , :groups_id )', 'core');

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

        $retval = sql_get('SELECT `id`,
                                  `createdby`,
                                  `createdon`,
                                  `meta_id`,
                                  `status`,
                                  `url`,
                                  `code`,
                                  `priority`,
                                  `title`,
                                  `message`,
                                  `data`

                           FROM   `notifications`

                           WHERE  `id`     = :id
                           AND    `status` = NULL',

                           array(':id' => $notifications_id), 'core');

        return $retval;

    }catch(Exception $e){
        throw new BException('notifications_get(): Failed', $e);
    }
}



/*
 * Get and return the specified notifications group from the database
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
function notifications_get_group($group, $column = null){
    try{
        /*
         * Validate
         */
        if(is_natural($group)){
            $where = ' WHERE `id`     = :id
                       AND   `status` = NULL ';
            $execute[':id'] = $group;

        }elseif(is_string($group)){
            $where = ' WHERE `seoname` = :seoname
                       AND   `status`  = NULL ';
            $execute[':seoname'] = $group;

        }else{
            throw new BException(tr('notifications_get_group(): Specified group ":group" is invalid', array(':group' => $group)), 'invalid');
        }

        if($column){
            $single  = true;
            $columns = ' `'.$column.'` ';

        }else{
            $single  = false;
            $columns = '`id`,
                        `createdby`,
                        `createdon`,
                        `meta_id`,
                        `status`, ';
        }

        $retval = sql_get('SELECT '.$columns.'

                           FROM   `notifications_groups`'.

                           $where,

                           $single, $execute, 'core');

        return $retval;

    }catch(Exception $e){
        throw new BException('notifications_get_group(): Failed', $e);
    }
}



/*
 * Get and return the specified notifications method from the database
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
function notifications_get_method($method, $column = null){
    try{
        /*
         * Validate
         */
        if(is_natural($method)){
            $where = ' WHERE `id`     = :id
                       AND   `status` = NULL ';
            $execute[':id'] = $method;

        }elseif(is_string($method)){
            $where = ' WHERE `seoname` = :seoname
                       AND   `status`  = NULL ';
            $execute[':seoname'] = $method;

        }else{
            throw new BException(tr('notifications_get_method(): Specified method ":method" is invalid', array(':method' => $method)), 'invalid');
        }

        if($column){
            $single  = true;
            $columns = ' `'.$column.'` ';

        }else{
            $single  = false;
            $columns = '`id`,
                        `createdby`,
                        `createdon`,
                        `meta_id`,
                        `status`, ';
        }

        $retval = sql_get('SELECT '.$columns.'

                           FROM   `notifications_methods`'.

                           $where,

                           $single, $execute, 'core');

        return $retval;

    }catch(Exception $e){
        throw new BException('notifications_get_method(): Failed', $e);
    }
}



/*
 * Get and return the specified notifications member from the database
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
function notifications_get_member($member, $column = null){
    try{
        /*
         * Validate
         */
        if(is_natural($member)){
            $where = ' WHERE `id`     = :id
                       AND   `status` = NULL ';
            $execute[':id'] = $member;

        }elseif(is_string($member)){
            $where = ' WHERE `seoname` = :seoname
                       AND   `status`  = NULL ';
            $execute[':seoname'] = $member;

        }else{
            throw new BException(tr('notifications_get_member(): Specified member ":member" is invalid', array(':member' => $member)), 'invalid');
        }

        if($column){
            $single  = true;
            $columns = ' `'.$column.'` ';

        }else{
            $single  = false;
            $columns = '`id`,
                        `createdby`,
                        `createdon`,
                        `meta_id`,
                        `status`, ';
        }

        $retval = sql_get('SELECT '.$columns.'

                           FROM   `notifications_members`'.

                           $where,

                           $single, $execute, 'core');

        return $retval;

    }catch(Exception $e){
        throw new BException('notifications_get_member(): Failed', $e);
    }
}



/*
 * List and return the specified notifications groups from the database
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
function notifications_list_groups($members_id){
    try{
        /*
         * Validate
         */
        if(!is_natural($members_id)){
            throw new BException(tr('notifications_list_groups(): Specified members_id ":$members_id" is invalid', array(':members_id' => $members_id)), 'invalid');
        }

        $retval = sql_query('SELECT `id`

                             FROM   `notifications_groups`

                             WHERE ',

                             $execute, 'core');

        return $retval;

    }catch(Exception $e){
        throw new BException('notifications_list_groups(): Failed', $e);
    }
}



/*
 * List and return the specified notifications method from the database
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
function notifications_list_methods($members_id){
    try{
        /*
         * Validate
         */
        if(!is_natural($members_id)){
            throw new BException(tr('notifications_list_methods(): Specified members_id ":$members_id" is invalid', array(':members_id' => $members_id)), 'invalid');
        }

        $retval = sql_query('SELECT `id`

                             FROM   `notifications_methods`

                             WHERE ',

                             $execute, 'core');

        return $retval;

    }catch(Exception $e){
        throw new BException('notifications_list_methods(): Failed', $e);
    }
}



/*
 * List and return the specified notifications member from the database
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
function notifications_list_members($members_id){
    try{
        /*
         * Validate
         */
        if(!is_natural($members_id)){
            throw new BException(tr('notifications_list_members(): Specified members_id ":$members_id" is invalid', array(':members_id' => $members_id)), 'invalid');
        }

        $retval = sql_query('SELECT `id`

                             FROM   `notifications_members`

                             WHERE ',

                             $execute, 'core');

        return $retval;

    }catch(Exception $e){
        throw new BException('notifications_list_members(): Failed', $e);
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
function notifications_get_url($notification){
    global $_CONFIG;

    try{

    }catch(Exception $e){
        throw new BException('notifications_get_url(): Failed', $e);
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

        /*
         * Go over all linked groups
         */
        $groups = notifications_list_methods($notification);

        while($group = sql_fetch($groups)){
            $members = notifications_list_members($group);

            while($member = sql_fetch($members)){
                $methods = notifications_get_methods($member);

                foreach($methods as $method){
                    switch($method){
                        case 'sms':
                            notifications_sms($notification);
                            break;

                        case 'email':
                            notifications_email($notification);
                            break;

                        case 'prowl':
                            notifications_prowl($notification);
                            break;

                        case 'pushover':
                            notifications_pushover($notification);
                            break;

                        case 'pushcap':
                            notifications_pushcap($notification);
                            break;

                        case 'desktop':
                            notifications_desktop($notification);
                            break;

                        case 'web':
                            notifications_web($notification);
                            break;

                        case 'push':
                            notifications_push($notification);
                            break;

                        case 'api':
                            notifications_api($notification);
                            break;

                        case 'jabber':
                            // FALLTHROUH
                        case 'irc':
                            // FALLTHROUH
                        case 'hangouts':
                            // FALLTHROUH
                        case 'matrix':
                            // FALLTHROUH
                        case 'whatsapp':
                            // FALLTHROUH
                        case 'signal':
                            // FALLTHROUH
                        case 'slack':
                            // FALLTHROUH
                        case 'telegram':
                            // FALLTHROUH
                        case 'twitter':
                            notifications_messenger($notification, $method);
                            break;

                        default:
                            throw new BException(tr('notifications_send(): Unknown notification method ":method" spefified', array(':method' => $method)), 'unknown');
                    }
                }
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
 * Send notifications to a messaging system
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package notifications
 * @see notify()
 * @see notification()
 * @version 2.5.14: Added function and documentation
 *
 * @param params $notification The parameters for this notification email
 * @return return void()
 */
function notifications_messenger($notification, $method){
    try{
        load_libs('messenger');
        messenger_send($method, $notification['priority'].' '.$notification['title'].' '.$notification['url']);

    }catch(Exception $e){
        throw new BException('notifications_messenger(): Failed', $e);
    }
}



/*
 * Send HTML5 notifications
 *
 * @author    Camilo Rodriguez <crodriguez@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category  Function reference
 * @package   desktop_notification
 *
 * @param $server      File on the server that verifies notifications
 * @param $check_every Set each when must check if there are new notifications in milliseconds
 * @param $icon        Icon used in notifications
 * @param $js_client    If you want to receive notifications even if the browser tab is open
 * @param $time        Time the notification is displayed not work in firefox, firefox close notification in 4 seconds, 0 means never close
 * @return array
 */
function notifications_webpush($server, $check_every, $icon, $js_client = '', $time = 4000){
    global $_CONFIG;

    try{
        if ($js_client != '') {
            html_script('
            this.onpush = function(event) {
              console.log(event.data);
              // From here we can write the data to IndexedDB, send it to any open
              // windows, display a notification, etc.
            }

            navigator.serviceWorker.register("'.$js_client.'").then(
              function(serviceWorkerRegistration) {
                serviceWorkerRegistration.pushManager.subscribe().then(
                  function(pushSubscription) {
                    console.log(pushSubscription.subscriptionId);
                    console.log(pushSubscription.endpoint);
                    // The push subscription details needed by the application
                    // server are now available, and can be sent to it using,
                    // for example, an XMLHttpRequest.
                  }, function(error) {
                    // During development it often helps to log errors to the
                    // console. In a production environment it might make sense to
                    // also report information about errors back to the
                    // application server.
                    console.log(error);
                  }
                );
              });
            ');
        }
        html_script('
             function showNotify(title,text, link = ""){
                 if(Notification.permission !== "granted"){
                     Notification.requestPermission();
                 }else{
                     var notification = new Notification(title,
                         {
                             icon: "'.$icon.'",
                             body: text
                         }
                     );

                     if(link!=""){
                         notification.onclick = function(){
                             window.open(link);
                         }
                     }
                     '.($time>0?'setTimeout(notification.close.bind(notification), '.$time.');':'').'

                 }
             }

            // switch(){
            //
            // }

             if(Notification.permission !== "granted"){
                 Notification.requestPermission();

             }else if(Notification.permission == "granted"){
                 setInterval(function(){
                     $.ajax({
                         method: "GET",
                         dataType: "json",
                         url: "'.$server.'",
                     })
                     .done(function( data ) {
                         $.each(data["data"], function(i, item) {
                             showNotify(item.title, item.message, item.link);
                         });
                     });
                 }, '.$check_every.');

             }else{

             }
        ');

    }catch(Exception $e){
        throw new BException('notifications_webpush(): Failed', $e);
    }
}
