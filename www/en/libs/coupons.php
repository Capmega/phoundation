<?php
/*
 * Coupons library
 *
 * This is an empty template library file
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package coupons
 */



/*
 * Validate the specified coupon
 *
 * This function will validate and sanitize the specified coupon data for
 * database use
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package coupon
 * @exception A "validation" type exception will be thrown if the validation fails
 * @see coupon_insert()
 * @see coupon_update()
 * @version 1.23.0: Added documentation
 * @example
 * code
 * $clean = coupon_validate($dirty);
 * showdie($clean);
 * /code
 *
 * This would return
 * code
 *
 * /code
 *
 * @param params $coupon A coupon params array containing coupon data
 * @params string $coupon[category]
 * @params string $coupon[code]
 * @params string $coupon[reward]
 * @return params The specified coupon, validated and sanitized
 */
function coupons_validate($coupon) {
    global $_CONFIG;

    try{
        load_libs('validate,seo,categories');

        $v = new ValidateForm($coupon, 'id,category,code,reward,description');

        /*
         * Validate the code
         */
        $v->isNotEmpty($coupon['code'], tr('Please specify a coupon code'));
        $v->isRegex($coupon['code'], '/[a-z0-9-]{2,16}/i', tr('Please specify a valid coupon code: minimum 2 characters, maximum 16 characters, only a-z, A-Z, 0-9 and -'));

        /*
         * Validate the reward
         */
        $v->isNotEmpty($coupon['reward'], tr('Please specify a reward'));
        $v->isRegex($coupon['reward'], '/[0-9-]{1,8}%?/', tr('Please specify a valid reward: minimum 1 number, maximum 8 numbers, may end in %'));

        /*
         * Validate the description
         */
        if ($coupon['description']) {
            $v->hasMinChars($coupon['description'],   16, tr('Please specifiy a minimum of 16 characters for the description'));
            $v->hasMaxChars($coupon['description'], 2047, tr('Please specifiy a maximum of 2047 characters for the description'));

            $coupon['description'] = cfm($coupon['description']);

        } else {
            $coupon['description'] = '';
        }

        /*
         * Validate the category
         */
        if ($coupon['categories_id']) {
            $exist = sql_get('SELECT `id` FROM list_categories WHERE `id` = :id', array('id' => $coupon['categories_id']));

            if (!$exist) {
                $v->setError(tr('The category ":category" does not exist', array(':category' => $coupon['category'])));
            }

        } else {
            $coupon['categories_id'] = null;
        }

        $v->isValid();

        $coupon['seocode'] = seo_unique($coupon['code'], 'coupons', $coupon['id'], 'seocode');

        return $coupon;

    }catch(Exception $e) {
        throw new CoreException('coupons_validate(): Failed', $e);
    }
}



/*
 * Insert specified coupon in the database
 *
 * This function will validate and sanitize the specified coupon and then insert it into the database. Once finished, the validated and sanitized coupon will be returned
 * *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package coupon
 * @exception A "validation" type exception will be thrown if the validation fails
 * @see coupon_insert()
 * @see coupon_update()
 * @version 1.23.0: Added documentation
 * @example
 * code
 * $clean = coupon_insert($dirty);
 * showdie($clean);
 * /code
 *
 * This would return
 * code
 *
 * /code
 *
 * @param params $coupon A coupon params array containing coupon data
 * @params string $coupon[category]
 * @params string $coupon[code]
 * @params string $coupon[reward]
 * @params string $coupon[description]
 * @return params The specified coupon, validated, and sanitized
 */
function coupons_insert($coupon) {
    try{
        $coupon = coupons_validate($coupon);

        sql_query('INSERT INTO `coupons` (`createdby`, `meta_id`, `categories_id`, `code`, `seocode`, `reward`, `description`, `count`, `expires`)
                   VALUES                (:createdby , :meta_id , :categories_id , :code , :seocode , :reward , :description , :count , :expires )',

                   array(':createdby'     => isset_get($_SESSION['user']['id']),
                         ':meta_id'       => meta_action(),
                         ':categories_id' => $coupon['categories_id'],
                         ':code'          => $coupon['code'],
                         ':seocode'       => $coupon['seocode'],
                         ':reward'        => $coupon['reward'],
                         ':count'         => $coupon['count'],
                         ':expires'       => $coupon['expires'],
                         ':description'   => $coupon['description']));

        $coupon['id'] = sql_insert_id();

        return $coupon;

    }catch(Exception $e) {
        throw new CoreException('coupons_insert(): Failed', $e);
    }
}



/*
 * Update the specified coupon in the database
 *
 * This function will validate and sanitize the specified coupon and then update in the database. Once finished, the validated and sanitized coupon will be returned
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package coupon
 * @exception A "validation" type exception will be thrown if the validation fails
 * @see coupon_insert()
 * @see coupon_update()
 * @version 1.23.0: Added documentation
 * @example
 * code
 * $clean = coupon_update($dirty);
 * showdie($clean);
 * /code
 *
 * This would return
 * code
 *
 * /code
 *
 * @param params $coupon A coupon params array containing coupon data
 * @params string $coupon[category]
 * @params string $coupon[code]
 * @params string $coupon[reward]
 * @params string $coupon[description]
 * @return params The specified coupon, validated, and sanitized
 */
function coupons_update($coupon) {
    try{
        $coupon = coupons_validate($coupon);
        meta_action($coupon['meta_id'], 'update');

        sql_query('UPDATE `coupons`

                   SET    `categories_id` = :categories_id,
                          `code`          = :code,
                          `seocode`       = :seocode,
                          `reward`        = :reward,
                          `description`   = :description

                   WHERE  `id`            = :id',

                   array(':id'            =>  $coupon['id'],
                         ':categories_id' => $coupon['categories_id'],
                         ':code'          => $coupon['code'],
                         ':seocode'       => $coupon['seocode'],
                         ':reward'        => $coupon['reward'],
                         ':description'   => $coupon['description']));

        return $coupon;

    }catch(Exception $e) {
        throw new CoreException('coupons_update(): Failed', $e);
    }
}



/*
 * Read the specified coupon from the database and return it
 *
 * This function will read the specified coupon from the databaes and return it
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package coupon
 * @exception A "validation" type exception will be thrown if the validation fails
 * @see coupon_insert()
 * @see coupon_update()
 * @version 1.23.0: Added documentation
 * @example
 * code
 * $clean = coupon_update($dirty);
 * showdie($clean);
 * /code
 *
 * This would return
 * code
 *
 * /code
 *
 * @param mixed $coupon The requested coupon. Can either be specified by id (natural number) or string (seocode)
 * @param string $column The specific column that has to be returned
 * @param string $status Filter by the specified status
 * @param natural $categories_id Filter by the specified categories_id. If NULL, the customer must NOT belong to any category
 * @param boolean $available If set to true, the coupon must have `status` NULL, have `count` NULL or higher than 0, and `expires` NULL or higher than UTC_TIMESTAMP
 * @return array the data for the requested coupon
 */
function coupons_get($coupon, $column = null, $status = null, $categories_id = false, $available = false) {
    try{
        if (is_numeric($coupon)) {
            $where[] = ' `coupons`.`id` = :id ';
            $execute[':id'] = $coupon;

        } else {
            $where[] = ' `coupons`.`seocode` = :seocode ';
            $execute[':seocode'] = $coupon;
        }

        if ($status !== false) {
            $where[] = ' `coupons`.`status` '.sql_is($status, ':status');
            $execute[':status'] = $status;
        }

        if ($categories_id !== false) {
            $where[] = ' `coupons`.`categories_id` '.sql_is($categories_id, ':categories_id');
            $execute[':categories_id'] = $categories_id;
        }

        if ($available) {
            $where[] = '  `status`  IS NULL ';
            $where[] = ' (`count`   IS NULL OR `count`   > 0) ';
            $where[] = ' (`expires` IS NULL OR `expires` = "0000-00-00 00:00:00" OR `expires` > UTC_TIMESTAMP) ';
        }

        $where = ' WHERE '.implode(' AND ', $where).' ';

        /*
         *
         */
        if ($column) {
            $retval = sql_get('SELECT `'.$column.'` FROM `coupons` '.$where, true, $execute);

        } else {
            $retval = sql_get('SELECT    `coupons`.`id`,
                                         `coupons`.`createdon`,
                                         `coupons`.`createdby`,
                                         `coupons`.`meta_id`,
                                         `coupons`.`status`,
                                         `coupons`.`categories_id`,
                                         `coupons`.`code`,
                                         `coupons`.`seocode`,
                                         `coupons`.`reward`,
                                         `coupons`.`count`,
                                         `coupons`.`expires`,
                                         `coupons`.`description`

                               FROM      `coupons`'.$where, $execute);
        }

        /*
         *
         */
        if (!$retval) {
            return array('status' => '_new');
        }

        /*
         *
         */
        $used = sql_get('SELECT `id`

                         FROM   `coupons_used`

                         WHERE  `coupons_id` = :coupons_id
                         AND    `createdby`  = :createdby',

                         array(':coupons_id' => $retval['id'],
                               ':createdby'  => $_SESSION['user']['id']));
        /*
         *
         */
        if ($used) {
            return array('status' => 'used');
        }

        /*
         *
         */
        return $retval;

    }catch(Exception $e) {
        throw new CoreException('coupons_get(): Failed', $e);
    }
}



/*
 * Use a coupon
 *
 * This function will confirm if the specified coupon code can be used, and if so, use one
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package coupon
 * @version 1.24.0: Added function with documentation
 *
 * @param mixed $coupon The requested coupon.
 * @return string the used coupon code
 */
function coupons_use($code) {
    try{
        $coupon = coupons_get($code, null, null, false, true);

        if (!$coupon) {
            throw new CoreException(tr('coupon_use(): Specified coupon code ":code" is not available or does not exist', array(':code' => $code)), 'not-exists');
        }

        sql_query('INSERT INTO `coupons_used` (`createdby`, `coupons_id`, `meta_id`)
                   VALUES                     (:createdby , :coupons_id , :meta_id )',

                   array(':createdby'  => isset_get($_SESSION['user']['id']),
                         ':coupons_id' => $coupon['id'],
                         ':meta_id'    => meta_action()));

        return $coupon;

    }catch(Exception $e) {
        throw new CoreException('coupons_use(): Failed', $e);
    }
}



/*
 * SUB HEADER TEXT
 *
 * PARAGRAPH
 *
 * PARAGRAPH
 *
 * PARAGRAPH
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package template
 * @see template_install()
 * @see date_convert() Used to convert the sitemap entry dates
 * @table: `template`
 * @note: This is a note
 * @version 2.4.11: Added function and documentation
 * @example [Title]
 * code
 * $result = template_function(array('foo' => 'bar'));
 * showdie($result);
 * /code
 *
 * This would return
 * code
 * Foo...bar
 * /code
 *
 * @param params $params A parameters array
 * @param string $params[foo]
 * @param string $params[bar]
 * @return string The result
 */
function coupons_add_coupon($code, $new_amount = 0) {
    try{
        if ($code) {
            $coupon = coupons_get((is_array($code)?$code['code']:$code), null, null, false, true);

            if ($coupon['status'] === null) {
                if (is_numeric($coupon['reward'])) {
                    if ($new_amount < 0) {
                        coupons_add_to_wallet($new_amount*-1);

                    } else {
                        coupons_add_to_wallet($coupon['reward']);
                    }

                    coupons_use((is_array($code) ? $code['code'] : $code));
                    html_flash_set(tr('This coupon ":coupon" was added to your wallet and give you $:amount',
                                   array(':coupon' => $coupon['code'],
                                         ':amount' => number_format(($new_amount<0?($new_amount*-1):$coupon['reward']), 2))), 'success');

                } else {
                    html_flash_set(tr('This coupon ":coupon" reward is :reward you only can use in listing discount', array(':coupon' => $code,
                                                                                                                            ':reward' => $coupon['reward'])), 'warning');
                }

            } else {
                switch($coupon['status']) {
                    case 'used':
                        html_flash_set(tr('You already used this coupon ":coupon"', array(':coupon' => $code)), 'warning/used');
                        break;

                    case '_new':
                        html_flash_set(tr('This coupon ":coupon" does not exist', array(':coupon' => $code)), 'warning');
                        break;
                }
            }
        }

    }catch(Exception $e) {
        throw new CoreException('coupons_discount_coupon(): Failed', $e);
    }
}



/*
 * SUB HEADER TEXT
 *
 * PARAGRAPH
 *
 * PARAGRAPH
 *
 * PARAGRAPH
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package template
 * @see template_install()
 * @see date_convert() Used to convert the sitemap entry dates
 * @table: `template`
 * @note: This is a note
 * @version 2.4.11: Added function and documentation
 * @example [Title]
 * code
 * $result = template_function(array('foo' => 'bar'));
 * showdie($result);
 * /code
 *
 * This would return
 * code
 * Foo...bar
 * /code
 *
 * @param params $params A parameters array
 * @return natural The amount of credits available for this user
 */
function coupons_add_to_wallet($amount) {
    try{
        $amount  = intval($amount);
        $credits = sql_get('SELECT `credits` FROM `users` WHERE `id` = :id', true, array(':id' => $_SESSION['user']['id']));
        $amount  = $credits + $amount;

        sql_query('UPDATE `users` SET `credits` = :credits WHERE `id` = :id', array(':credits' => $amount,
                                                                                    ':id'      => $_SESSION['user']['id']));

        return $amount;

    }catch(Exception $e) {
        throw new CoreException('coupons_add_to_wallet(): Failed', $e);
    }
}
