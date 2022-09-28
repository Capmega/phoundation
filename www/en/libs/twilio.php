<?php
/*
 * This is the Twilio API library
 *
 * This library contains helper functions for the Twilio API
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <license@capmega.com>
 * @category Function reference
 * @package twilio
 */
use Twilio\Rest\Client;



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 *
 * @return void
 */
function twilio_library_init(){
    try{
        load_config('twilio');

    }catch(Exception $e){
        throw new CoreException('twilio_library_init(): Failed', $e);
    }
}



/*
 * Install the Twilio base library from Github
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @see twilio_load()
 * @version 1.27.0: Added documentation
 *
 * @return void
 */
function twilio_install(){
    try{
        log_console('twilio_install(): Installing Twilio library', 'cyan');

        /*
         * Ensure the ROOT/libs/external path exists
         */
        file_execute_mode(ROOT.'libs', 0770, function(){
            file_ensure_path(ROOT.'libs/external', 0550);

            /*
             * Download the twilio PHP library and install it in
             * ROOT/libs/external
             */
            file_execute_mode(ROOT.'libs/external', 0770, function(){
                file_ensure_path(ROOT.'libs/external');
                file_delete(TMP.'twilio_install.zip');
                file_delete(ROOT.'libs/external/twilio-php-master', ROOT.'libs/external/');
                file_delete(ROOT.'libs/external/twilio'           , ROOT.'libs/external/');

                /*
                 * Get library zip, unzip it to target, and cleanup
                 */
                $file = download('https://github.com/twilio/twilio-php/archive/master.zip');
                $path = cli_unzip($file);

                rename($path.'twilio-php-master', ROOT.'libs/external/twilio');
                file_delete($path);
            });
        });

    }catch(Exception $e){
        throw new CoreException('twilio_install(): Failed', $e);
    }
}



/*
 * Load Twilio base library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @see twilio_install()
 * @version 1.27.0: Added documentation
 *
 * @param string $source The phone number for the account for which twilio needs to be loaded
 * @param boolean $auto_install If set to true and the Twilio library is not found, the system will install the Twilio library automatically
 * @return object Twilio\Rest\Client\Client A Twilio client object interface
 */
function twilio_load($source, $auto_install = true){
    try{
        /*
         * Load Twilio library
         * If Twilio isnt available, then try auto install
         */
        $file = ROOT.'libs/external/twilio/Twilio/autoload.php';

        if(!file_exists($file)){
            log_console('twilio_load(): Twilio API library not found', 'warning');

            if(!$auto_install){
                throw new CoreException(tr('twilio_load(): Twilio API library file ":file" was not found', array(':file' => $file)), 'notinstalled');
            }

            twilio_install();

            if(!file_exists($file)){
                throw new CoreException(tr('twilio_load(): Twilio API library file ":file" was not found, and auto install seems to have failed', array(':file' => $file)), 'notinstalled');
            }
        }

        include_once($file);

        /*
         * Get Twilio object with account data for the specified phone number
         */
        $account = twilio_get_account_by_phone_number($source);

        if(!$account){
            throw new CoreException(tr('twilio_load(): No Twilio account found for source ":source"', array(':source' => $source)), 'not-exists');
        }

        return new Client($account['account_id'], $account['account_token']);

    }catch(Exception $e){
        throw new CoreException('twilio_load(): Failed', $e);
    }
}



/*
 * Return (if possible) a name for the phone number
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @see sms_send_message()
 * @version 1.27.0: Added documentation
 *
 * @param mixed $message The message to be sent.
 * @param optional string $message[message] The message to be sent.
 * @param optional array $message[media] A list of file URLs to send with the message
 * @param string $to The phone number where to send the message to
 * @param string $from The Twilio phone number from which to send the message
 * @return void
 */
function twilio_name_phones($numbers, $non_numeric = null){
    try{
        load_libs('sms');

        $numbers = sms_full_phones($numbers);
        $numbers = array_force($numbers);

        foreach($numbers as &$number){
            if(!is_numeric($number)){
                if($non_numeric){
                    $number = $non_numeric;
                }

            }else{
                $label = sql_get('SELECT `name` FROM `twilio_numbers` WHERE `number` = :number', 'name', array(':number' => $number));

                if($label){
                    $number = $label;
                }
            }
        }

        return str_force($numbers, ', ');

    }catch(Exception $e){
        throw new CoreException('twilio_name_phones(): Failed', $e);
    }
}



/*
 * Verify that the specified phone number exists
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @version 1.27.0: Added documentation
 *
 * @param mixed $number The message to be sent.
 * @return void
 */
function twilio_verify_source_phone($number){
    try{
        load_libs('sms');

        $number = sms_full_phones($number);
        return sql_get('SELECT `number` FROM `twilio_numbers` WHERE `number` = :number', 'number', array(':number' => $number));

    }catch(Exception $e){
        throw new CoreException('twilio_verify_source_phone(): Failed', $e);
    }
}



/*
 * Send an SMS or MMS message through Twilio from the Twilio source number to the target number
 *
 * This function will send an SMS or MMS message over Twilio from the specified Twilio source phone number to the specified target phone number.
 *
 * If the message is larger than 160 characters, Twilio will automatically split the message in multiple messages and send each message individually
 *
 * The message may be specified as a scalar string, or an array that contains the message and a media array that contains the various files to be sent.
 *
 * Once sent, the message will be registered in the `sms_messages` table, and added as a part of a conversation in the `sms_conversations` table
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @see sms_send_message()
 * @version 1.27.0: Added documentation
 *
 * @param mixed $message The message to be sent.
 * @param optional string $message[message] The message to be sent.
 * @param optional array $message[media] A list of file URLs to send with the message
 * @param string $to The phone number where to send the message to
 * @param string $from The Twilio phone number from which to send the message
 * @return void
 */
function twilio_send_message($message, $to, $from = null){
    static $account;

    try{
        $source = sql_get('SELECT `number` FROM `twilio_numbers` WHERE `number` = :number', 'number', array(':number' => $from));

        if(!$source){
            throw new CoreException(tr('twilio_send_message(): Specified source phone ":from" is not known', array(':from' => $from)), 'unknown');
        }

        if(empty($account)){
            $account = twilio_load($source);
        }

        if(is_array($message)){
            /*
             * This is an MMS message
             */
            if(empty($message['message'])){
                throw new CoreException(tr('twilio_send_message(): No message specified'), 'not-specified');
            }

            if(empty($message['media'])){
                throw new CoreException(tr('twilio_send_message(): No media specified'), 'not-specified');
            }

            return $account->messages->create($to, array('body'     => $message['message'],
                                                         'from'     => $source,
                                                         'mediaUrl' => $message['media']));
        }

        /*
         * Send a normal SMS message
         */
        return $account->messages->create($to, array('body' => $message,
                                                     'from' => $source));

    }catch(Exception $e){
        throw new CoreException(tr('twilio_send_message(): Failed'), $e);
    }
}



/*
 * Register an image from an MMS message
 *
 * This function will register the specified image URL for the MMS message with the specified SMS ID
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @version 1.27.0: Added documentation
 *
 * @param natural $messages_id The SMS messages id
 * @param string $url The URL for the image
 * @param string $mimetype The mimetype for the specified URL
 * @return void
 */
function twilio_add_image($messages_id, $url, $mimetype){
    try{
        sql_query('INSERT INTO `sms_images` (`sms_messages_id`, `url`, `mimetype`)
                   VALUES                   (:sms_messages_id , :url , :mimetype )',

                   array(':sms_messages_id' => $messages_id,
                         ':mimetype'        => $mimetype,
                         ':url'             => $url));

        run_background('base/sms getimages');

    }catch(Exception $e){
        throw new CoreException(tr('twilio_add_image(): Failed'), $e);
    }
}



/*
 * Download the specified URL from the specified messages_id and store it in the SMS images directory
 *
 * This function will download the image from the specified URL to the local SMS images directory (ROOT/data/sms/images) and return the file name under which it was stored
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @version 1.27.0: Added documentation
 *
 * @param natural $messages_id The SMS ID of the Twilio SMS message
 * @param string $url The URL for the image
 * @return string The file under which the image was stored
 */
function twilio_download_image($messages_id, $url){
    try{
        $file = download($url);
        $file = file_move_to_target($file, ROOT.'data/sms/images', 'jpg');

        sql_query('UPDATE `sms_images`

                   SET    `downloaded`      = NOW(),
                          `file`            = :file

                   WHERE  `sms_messages_id` = :sms_messages_id
                   AND    `url`             = :url',

                   array(':sms_messages_id' => $messages_id,
                         ':url'             => $url,
                         ':file'            => $file));

        return $file;

    }catch(Exception $e){
        throw new CoreException(tr('twilio_download_image(): Failed'), $e);
    }
}



/*
 * Validate, sanitize and return the specified twilio group data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @see twilio_validate_number()
 * @see twilio_validate_account()
 * @version 1.27.0: Added documentation
 *
 * @param array $number The Twilio group data
 * @return array The specified Twilio group data, validated and sanitized
 */
function twilio_validate_group($group){
    try{
        load_libs('validate,seo');

        $v = new ValidateForm($group, 'name,description');
        $v->isNotEmpty($group['name'], tr('No twilios name specified'));
        $v->hasMinChars($group['name'], 2, tr('Please ensure the twilio name has at least 2 characters'));
        $v->hasMaxChars($group['name'], 32, tr('Please ensure the twilio name has less than 32 characters'));
        $v->isRegex($group['name'], '/^[a-z-]{2,32}$/', tr('Please ensure the twilio name contains only lower case letters, and dashes'));

        if($group['description']){
            $v->hasMinChars($group['description'], 2, tr('Please ensure the twilio description has at least 2 characters'));
            $v->hasMaxChars($group['description'], 2047, tr('Please ensure the twilio description has less than 2047 characters'));

        }else{
            $group['description'] = null;
        }

        if(is_numeric(substr($group['name'], 0, 1))){
            $v->setError(tr('Please ensure that the name does not start with a number'));
        }

        /*
         * Does the twilio phone number already exist?
         */
        if(empty($group['id'])){
            if($id = sql_get('SELECT `id` FROM `twilio_groups` WHERE `name` = :name', array(':name' => $group['name']))){
                $v->setError(tr('The group ":group" already exists with id ":id"', array(':group' => $group['name'], ':id' => $id)));
            }

        }else{
            if($id = sql_get('SELECT `id` FROM `twilio_groups` WHERE `name` = :name AND `id` != :id', array(':name' => $group['name'], ':id' => $group['id']))){
                $v->setError(tr('The group ":group" already exists with id ":id"', array(':group' => $group['name'], ':id' => $id)));
            }
        }

        $v->isValid();

        $group['seoname'] = seo_unique($group['name'], 'twilio_groups', isset_get($group['id']));

        return $group;

    }catch(Exception $e){
        throw new CoreException(tr('twilio_validate_group(): Failed'), $e);
    }
}



/*
 * Read and return all Twilio group data from the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @see twilio_get_account()
 * @package twilio
 * @version 1.27.0: Added documentation
 *
 * @param mixed $group
 * @return array The Twilio group data for the specified Twilio number
 */
function twilio_get_group($group){
    try{
        if(!$group){
            throw new CoreException(tr('twilio_get_group(): No twilio specified'), 'not-specified');
        }

        if(!is_scalar($group)){
            throw new CoreException(tr('twilio_get_group(): Specified twilio ":group" is not scalar', array(':group' => $group)), 'invalid');
        }

        $retval = sql_get('SELECT    `twilio_groups`.`id`,
                                     `twilio_groups`.`meta_id`,
                                     `twilio_groups`.`status`,
                                     `twilio_groups`.`name`,
                                     `twilio_groups`.`seoname`,
                                     `twilio_groups`.`description`,

                                     `createdby`.`name`  AS `createdby_name`,
                                     `createdby`.`email` AS `createdby_email`

                           FROM      `twilio_groups`

                           LEFT JOIN `users` AS `createdby`
                           ON        `twilio_groups`.`createdby` = `createdby`.`id`

                           WHERE     `twilio_groups`.`id`        = :twilio
                           OR        `twilio_groups`.`name`      = :twilio',

                           array(':twilio' => $group));

        return $retval;

    }catch(Exception $e){
        throw new CoreException('twilio_get_group(): Failed', $e);
    }
}



/*
 * Validate, sanitize and return the specified twilio account data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @see twilio_validate_number()
 * @see twilio_validate_group()
 * @version 1.27.0: Added documentation
 *
 * @param array $number The Twilio account data
 * @return array The specified Twilio account data, validated and sanitized
 */
function twilio_validate_account($account){
    try{
        load_libs('validate,seo');

        $v = new ValidateForm($account, 'email,account_id,account_token');
        $v->isNotEmpty($account['email'], tr('No twilio account email specified'));
        $v->isEmail($account['email'], tr('Please ensure the twilio account email has at least 2 characters'));

        $v->isNotEmpty($account['account_id'], tr('No twilio account account id specified'));
        $v->hasMinChars($account['account_id'], 32, tr('Please ensure the twilio account id has at least 32 characters'));
        $v->hasMaxChars($account['account_id'], 40, tr('Please ensure the twilio account id has less than 40 characters'));

        $v->isNotEmpty($account['account_token'], tr('No Account token specified'));
        $v->hasMinChars($account['account_token'], 32, tr('Please ensure the account token has at least 2 characters'));
        $v->hasMaxChars($account['account_token'], 40, tr('Please ensure the twilio token has less than 40 characters'));

        /*
         * Does the twilio already exist?
         */
        if(empty($account['id'])){
            if($id = sql_get('SELECT `id` FROM `twilio_accounts` WHERE `email` = :email', array(':email' => $account['email']))){
                $v->setError(tr('The Twilio account ":account" already exists with id ":id"', array(':account' => $account['email'], ':id' => $id)));
            }

        }else{
            if($id = sql_get('SELECT `id` FROM `twilio_accounts` WHERE `email` = :email AND `id` != :id', array(':email' => $account['email'], ':id' => $account['id']))){
                $v->setError(tr('The Twilio account ":account" already exists with id ":id"', array(':account' => $account['email'], ':id' => $id)));
            }
        }

        $v->isValid();

        $account['seoemail'] = seo_unique($account['email'], 'twilio_accounts', isset_get($account['id']), 'email');

        return $account;

    }catch(Exception $e){
        throw new CoreException(tr('twilio_validate_account(): Failed'), $e);
    }
}



/*
 * Read and return all Twilio account data from the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @see twilio_get_account_by_phone_number()
 * @package twilio
 * @version 1.27.0: Added documentation
 *
 * @param mixed $account
 * @return array The Twilio account data for the specified Twilio number
 */
function twilio_get_account($account){
    try{
        if(!$account){
            throw new CoreException(tr('twilio_get_account(): No twilio account specified'), 'not-specified');
        }

        if(!is_scalar($account)){
            throw new CoreException(tr('twilio_get_account(): Specified twilio account ":account" is not scalar', array(':account' => $account)), 'invalid');
        }

        if(is_numeric($account)){
            $where   = ' WHERE `twilio_accounts`.`id` = :id ';
            $execute = array(':id' => $account);

        }elseif(filter_var($account, FILTER_VALIDATE_EMAIL)){
            $where   = ' WHERE `twilio_accounts`.`email` = :email ';
            $execute = array(':email' => $account);

        }else{
            $where   = ' WHERE `twilio_accounts`.`account_id` = :account_id ';
            $execute = array(':account_id' => $account);
        }

        $retval = sql_get('SELECT    `twilio_accounts`.`id`,
                                     `twilio_accounts`.`meta_id`,
                                     `twilio_accounts`.`status`,
                                     `twilio_accounts`.`email`,
                                     `twilio_accounts`.`seoemail`,
                                     `twilio_accounts`.`account_id`,
                                     `twilio_accounts`.`account_token`,

                                     `createdby`.`name`  AS `createdby_name`,
                                     `createdby`.`email` AS `createdby_email`

                           FROM      `twilio_accounts`

                           LEFT JOIN `users`             AS `createdby`
                           ON        `twilio_accounts`.`createdby` = `createdby`.`id`

                           '.$where,

                           $execute);

        return $retval;

    }catch(Exception $e){
        throw new CoreException('twilio_get_account(): Failed', $e);
    }
}



/*
 * Return all Twilio account data for the specified Twilio phone number
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @see twilio_validate_group()
 * @see twilio_validate_account()
 * @version 1.27.0: Added documentation
 *
 * @param array $number The Twilio phone number for which the account data must be found
 * @return array The Twilio account data for the specified Twilio number
 */
function twilio_get_account_by_phone_number($number){
    try{
        if(!$number){
            throw new CoreException(tr('twilio_get_account_by_phone_number(): No twilio number specified'), 'not-specified');
        }

        if(!is_scalar($number)){
            throw new CoreException(tr('twilio_get_account_by_phone_number(): Specified twilio number ":number" is not scalar', array(':numbers' => $numbers)), 'invalid');
        }

        $retval = sql_get('SELECT    `twilio_accounts`.`id`,
                                     `twilio_accounts`.`meta_id`,
                                     `twilio_accounts`.`status`,
                                     `twilio_accounts`.`email`,
                                     `twilio_accounts`.`seoemail`,
                                     `twilio_accounts`.`account_id`,
                                     `twilio_accounts`.`account_token`,

                                     `createdby`.`name`  AS `createdby_name`,
                                     `createdby`.`email` AS `createdby_email`

                           FROM      `twilio_accounts`

                           LEFT JOIN `users` AS `createdby`
                           ON        `twilio_accounts`.`createdby` = `createdby`.`id`

                           JOIN      `twilio_numbers`
                           ON        `twilio_numbers`.`accounts_id` = `twilio_accounts`.`id`
                           AND       `twilio_numbers`.`number`      = :number',

                           array(':number' => $number));

        return $retval;

    }catch(Exception $e){
        throw new CoreException('twilio_get_account_by_phone_number(): Failed', $e);
    }
}



/*
 * Validate, sanitize and return the specified twilio phone number data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @version 1.27.0: Added documentation
 *
 * @param array $number The Twilio phone number data
 * @return array The specified Twilio phone number data, validated and sanitized
 */
function twilio_validate_number($number){
    try{
        load_libs('validate,seo');

        $v = new ValidateForm($number, 'email,accounts_id,account_token');
        $v->isNotEmpty($number['name'], tr('No name specified'));
        $v->hasMinChars($number['name'], 2, tr('Please ensure the number name has at least 2 characters'));

        $v->isNotEmpty($number['number'], tr('No number description specified'));
        $v->hasMinChars($number['number'], 12, tr('Please ensure the number has at least 12 digits'));
        $v->isPhonenumber($number['number'], tr('Please ensure the number is telphone number valid'));

        $v->isNotEmpty($number['accounts_id'], tr('No account specified'));
        $v->isNumeric($number['accounts_id'], tr('Invalid account specified'));

        if($number['groups_id']){
            $v->isNumeric($number['groups_id'], tr('Invalid group specified'));

        }else{
            $number['groups_id'] = null;
        }

        /*
         * Does the twilio already exist?
         */
        if(empty($number['id'])){
            $id = sql_get('SELECT `id`

                           FROM   `twilio_numbers`

                           WHERE  `name`   = :name
                           OR     `number` = :number',

                          'id', array(':name'   => $number['name'],
                                      ':number' => $number['number']));

            if($id){
                $v->setError(tr('The twilio number ":number" or name ":name" already exists with id ":id"', array(':name' => $number['name'], ':number' => $number['number'], ':id' => $id)));
            }

        }else{
            $id = sql_get('SELECT `id`

                           FROM   `twilio_numbers`

                           WHERE (`name`   = :name
                           OR     `number` = :number)
                           AND    `id`    != :id',

                          'id', array(':id'     => $number['id'],
                                      ':name'   => $number['name'],
                                      ':number' => $number['number']));

            if($id){
                $v->setError(tr('The twilio number ":number" or name ":name" already exists with id ":id"', array(':name' => $number['name'], ':number' => $number['number'], ':id' => $id)));
            }
        }

        $v->isValid();

        $number['seoname'] = seo_unique($number['name'], 'twilio_groups', isset_get($number['id']));

        return $number;

    }catch(Exception $e){
        throw new CoreException(tr('twilio_validate_number(): Failed'), $e);
    }
}



/*
 * Load and return all available data on the specified twilio phone number
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @see twilio_get_account()
 * @see twilio_get_group()
 * @version 1.27.0: Added documentation
 *
 * @param string The Twilio phone number from which the data must be returned
 * @return array All available data on the Twilio phone number
 */
function twilio_get_number($number){
    try{
        if(!$number){
            throw new CoreException(tr('twilio_get_number(): No number specified'), 'not-specified');
        }

        if(!is_scalar($number)){
            throw new CoreException(tr('twilio_get_number(): Specified twilio number ":number" is not scalar', array(':number' => $number)), 'invalid');
        }

        $retval = sql_get('SELECT   `twilio_numbers`.`id`,
                                    `twilio_numbers`.`createdon`,
                                    `twilio_numbers`.`createdby`,
                                    `twilio_numbers`.`name`,
                                    `twilio_numbers`.`seoname`,
                                    `twilio_numbers`.`number`,
                                    `twilio_numbers`.`accounts_id`,
                                    `twilio_numbers`.`groups_id`,
                                    `twilio_numbers`.`status`,
                                    `twilio_numbers`.`type`,
                                    `twilio_numbers`.`data`,

                                    `twilio_groups`.`name`  AS `group`,

                                    `twilio_accounts`.`email` AS `accounts_email`,
                                    `twilio_accounts`.`account_id`,
                                    `twilio_accounts`.`account_token`

                           FROM     `twilio_numbers`

                           LEFT JOIN `twilio_groups`
                           ON        `twilio_groups`.`id`     = `twilio_numbers`.`groups_id`

                           LEFT JOIN `twilio_accounts`
                           ON        `twilio_accounts`.`id`   = `twilio_numbers`.`accounts_id`

                           WHERE    `twilio_numbers`.`name`   = :name
                           OR       `twilio_numbers`.`number` = :number',

                           array(':name'   => $number,
                                 ':number' => $number));

        return $retval;

    }catch(Exception $e){
        throw new CoreException('twilio_get_number(): Failed', $e);
    }
}



/*
 * Load and return a list of all available twilio accounts
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @see twilio_get_account()
 * @version 1.27.0: Added documentation
 *
 * @return array All available twilio accounts
 */
function twilio_list_accounts(){
    try{
        $accounts = sql_list('SELECT `twilio_accounts`.`id`,
                                     `twilio_accounts`.`email`,
                                     `twilio_accounts`.`account_id`,
                                     `twilio_accounts`.`account_token`

                              FROM   `twilio_accounts`

                              WHERE  `status` IS NULL');

        return $accounts;

    }catch(Exception $e){
        throw new CoreException('twilio_list_accounts(): Failed', $e);
    }
}



/*
 * Get and return all available Twilio data on the specified phone number
 *
 * This function will connect to the Twilio API and get the available Twilio phone numbers and return them as an array
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @see twilio_api_list_numbers()
 * @version 1.27.0: Added documentation
 *
 * @param string $number The Twilio phone number
 * @param boolean $array If true, the list will be returned in an array instead of an object
 * @return mixed Array containing the Twilio data for the specified phone number. Returns NULL in case the number does not exist
 */
function twilio_api_get_number($number, $array = true){
    try{
        $account = twilio_get_account_by_phone_number($number);
        $client  = twilio_load($account);
        $numbers = $client->IncomingPhoneNumbers->read();

        foreach($numbers as $number){
            if($number->phoneNumber === $phone_number){
                if($array){
                    return twilio_number_to_array($number);
                }

                return $number;
            }
        }

    }catch(Exception $e){
        throw new CoreException('twilio_api_get_number(): Failed', $e);
    }
}



/*
 * Get and return all available Twilio phone number for the specified account directly from the Twilio API
 *
 * This function will connect to the Twilio API and get the available Twilio phone numbers and return them as an array
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @see twilio_api_get_number()
 * @version 1.27.0: Added documentation
 *
 * @param mixed $account The twilio account
 * @param boolean $array If true, the list will be returned in an array instead of an object
 * @return mixed The available Twilio phone numbers
 */
function twilio_api_list_numbers($account, $array = true){
    try{
        $client  = twilio_load($account);
        $numbers = $client->IncomingPhoneNumbers->read();

        foreach($numbers as $number){
            if($array){
                $retval[$number->phoneNumber] = twilio_number_to_array($number);

            }else{
                $retval[$number->phoneNumber] = $number;
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new CoreException('twilio_api_list_numbers(): Failed', $e);
    }
}



/*
 * Convert a twilio number object to a PHP associative array
 *
 * This function will convert the specified twilio number object to a PHP associate array
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @version 1.27.0: Added documentation
 *
 * @param object $number The twilio number object, must be a ....... class
 * @return array The twilio number data in an associative array
 */
function twilio_number_to_array($number){
    try{
        $retval['accounts_sid']           = $number->accountSid;
        $retval['address_sid']            = $number->addressSid;
        $retval['address_requirements']   = $number->addressRequirements;
        $retval['api_version']            = $number->apiVersion;
        $retval['beta']                   = $number->beta;
        $retval['capabilities']           = $number->capabilities;
        $retval['date_created']           = $number->dateCreated;
        $retval['date_updated']           = $number->dateUpdated;
        $retval['friendly_name']          = $number->friendlyName;
        $retval['identity_sid']           = $number->identitySid;
        $retval['phone_number']           = $number->phoneNumber;
        $retval['origin']                 = $number->origin;
        $retval['sid']                    = $number->sid;
        $retval['sms_application_sid']    = $number->smsApplicationSid;
        $retval['sms_fallback_method']    = $number->smsFallbackMethod;
        $retval['sms_fallback_url']       = $number->smsFallbackUrl;
        $retval['sms_method']             = $number->smsMethod;
        $retval['sms_url']                = $number->smsUrl;
        $retval['status_callback']        = $number->statusCallback;
        $retval['status_callback_method'] = $number->statusCallbackMethod;
        $retval['trunk_sid']              = $number->trunkSid;
        $retval['uri']                    = $number->uri;
        $retval['voice_application_sid']  = $number->voiceApplicationSid;
        $retval['voice_callerid_lookup']  = $number->voiceCallerIdLookup;
        $retval['voice_fallback_method']  = $number->voiceFallbackMethod;
        $retval['voice_fallback_url']     = $number->voiceFallbackUrl;
        $retval['voice_method']           = $number->voiceMethod;
        $retval['voice_url']              = $number->voiceUrl;
        $retval['emergency_status']       = $number->emergencyStatus;
        $retval['emergency_address_sid']  = $number->emergencyAddressSid;

        return $retval;

    }catch(Exception $e){
        throw new CoreException('twilio_number_to_array(): Failed', $e);
    }
}



/*
 * Return HTML for a Twilio account select box
 *
 * This function will generate and return the HTML to display a select with all registered and available Twilio accounts
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @see html_select()
 * @see twilio_select_number()
 * @version 1.27.0: Added documentation
 *
 * @param params $params The parameters for the Twilio account select box
 * @param string $params[name] The HTML name and default id
 * @param string $params[none] The default text displayed on the select box if no Twilio accounts  has been selected yet
 * @param string $params[empty] The default text displayed on the select box if no Twilio accounts are available
 * @return string The HTML for the Twilio number select
 */
function twilio_select_accounts($params){
    try{
        array_ensure($params);
        array_default($params, 'name' , 'number');
        array_default($params, 'none' , tr('Select number'));
        array_default($params, 'empty', tr('No numbers available'));

        $params['resource'] = sql_query('SELECT `account_id`, `email` FROM `twilio_accounts` WHERE `status` IS NULL ORDER BY `email`');

        $html = html_select($params);
        return $html;

    }catch(Exception $e){
        throw new CoreException('twilio_select_accounts(): Failed', $e);
    }
}



/*
 * Return HTML for a Twilio number select box
 *
 * This function will generate and return the HTML to display a select box with all registered and available  Twilio numbers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package twilio
 * @see html_select()
 * @see twilio_select_accounts()
 * @version 1.27.0: Added documentation, added account filtering
 *
 * @param params $params The parameters for the Twilio numbers select box
 * @param string $params[name] The HTML name and default id
 * @param string $params[none] The default text displayed on the select box if no Twilio number has been selected yet
 * @param string $params[empty] The default text displayed on the select box if no Twilio numbers are available
 * @param string $params[account] If specified, the numbers will be filtered to show only the numbers from the specified account
 * @return string The HTML for the Twilio number select
 */
function twilio_select_number($params){
    try{
        array_ensure($params);
        array_default($params, 'name'   , 'number');
        array_default($params, 'none'   , tr('Select number'));
        array_default($params, 'empty'  , tr('No numbers available'));
        array_default($params, 'account', null);

        if($params['account']){
            $accounts_id = twilio_get_account($params['account']);

            if(!$accounts_id){
                throw new CoreException(tr('twilio_select_number(): Specified Twilio account ":account" does not exist', array(':account' => $params['account'])), 'not-exists');
            }

            $where   = 'WHERE    `accounts_id` = :accounts_id
                        AND      `status`      IS NULL
                        AND      `number`     != ""
                        AND      `name`       != ""';

            $execute = array(':accounts_id' => $accounts_id);

        }else{
            $where   = 'WHERE    `status` IS NULL
                        AND      `number` != ""
                        AND      `name`   != ""';

            $execute = null;
        }

        $params['resource'] = sql_query('SELECT   `number` AS `id`,
                                                  `name`

                                         FROM     `twilio_numbers`

                                         '.$where.'

                                         ORDER BY `name`',

                                         $execute);

        $html = html_select($params);
        return $html;

    }catch(Exception $e){
        throw new CoreException('twilio_select_number(): Failed', $e);
    }
}
