<?php
/*
 * Customers library
 *
 * This is the customers library file, it contains customers functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copycustomer Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package customers
 *
 * @return void
 */
function customers_library_init(){
    try{
        load_config('customers');

    }catch(Exception $e){
        throw new BException('customers_library_init(): Failed', $e);
    }
}



/*
 * Validate a customer
 *
 * This function will validate all relevant fields in the specified $customer array
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package categories
 *
 * @param array $customer
 * @return string HTML for a categories select box within the specified parameters
 */
function customers_validate($customer){
    try{
        load_libs('validate,seo');

        $v = new ValidateForm($customer, 'seocategory,name,code,url,company,email,phones,address1,address2,address3,zipcode,documents_id,seocountry,seostate,seocity,description');
        $v->isNotEmpty ($customer['name']    , tr('No customers name specified'));
        $v->hasMinChars($customer['name'],  2, tr('Please ensure the customer\'s name has at least 2 characters'));
        $v->hasMaxChars($customer['name'], 64, tr('Please ensure the customer\'s name has less than 64 characters'));
        $v->isRegex    ($customer['name'], '/^[a-zA-Z- ]{2,64}$/', tr('Please ensure the customer\'s name contains only lower case letters, and dashes'));

        /*
         * Validate basic data
         */
        if($customer['description']){
            $v->hasMinChars($customer['description'],    8, tr('Please ensure the description has at least 8 characters'));
            $v->hasMaxChars($customer['description'], 2047, tr('Please ensure the description has less than 2047 characters'));
            $v->hasNoHTML($customer['description'], tr('Please ensure the description has no HTML code'));

            $customer['description'] = str_clean($customer['description']);

        }else{
            $customer['description'] = null;
        }

        if($customer['url']){
            $v->hasMaxChars($customer['url'], 255, tr('Please ensure the URL has less than 255 characters'));
            $v->isURL($customer['url'], tr('Please a valid URL'));

        }else{
            $customer['url'] = null;
        }

        if($customer['company']){
            $v->hasMaxChars($customer['company'], 64, tr('Please ensure the company has less than 64 characters'));

            $customer['company'] = str_clean($customer['company']);

        }else{
            $customer['company'] = null;
        }

        if($customer['email']){
            $v->hasMaxChars($customer['email'], 96, tr('Please ensure the email has less than 96 characters'));
            $v->isEmail($customer['email'], tr('Please specify a valid emailaddress'));

        }else{
            $customer['email'] = null;
        }

        if($customer['url']){
            $v->hasMaxChars($customer['url'], 255, tr('Please ensure the email has less than 255 characters'));
            $v->isUrl($customer['url'], tr('Please specify a valid url'));

        }else{
            $customer['url'] = '';
        }

        if($customer['phones']){
            $v->hasMaxChars($customer['phones'], 36, tr('Please ensure the phones field has less than 36 characters'));

            foreach(array_force($customer['phones']) as &$phone){
                $v->isPhonenumber($phone, tr('Please ensure the phone number ":phone" is valid', array(':phone' => $phone)));
            }

            $customer['phones'] = str_force($customer['phones']);

        }else{
            $customer['phones'] = null;
        }

        if($customer['code']){
            $v->hasMinChars($customer['code'],  2, tr('Please ensure the customer\'s description has at least 2 characters'));
            $v->hasMaxChars($customer['code'], 64, tr('Please ensure the customer\'s description has less than 64 characters'));
            $v->isAlphaNumeric($customer['code'], tr('Please ensure the customer\'s description has less than 64 characters'), VALIDATE_IGNORE_SPACE|VALIDATE_IGNORE_DASH|VALIDATE_IGNORE_UNDERSCORE);

        }else{
            $customer['code'] = null;
        }

        /*
         * Validate linked document
         */
        if($customer['documents_id']){
            load_libs('storage-documents');

            $exists = storage_documents_get('customers', $customer['documents_id'], false, 'id');

            if(!$exists){
                $v->setError(tr('Specified document does not exist'));
            }

        }else{
            $customer['documents_id'] = null;
        }

        /*
         * Validate category
         */
        if($customer['seocategory']){
            load_libs('categories');

            $customer['categories_id'] = categories_get($customer['seocategory'], 'id');

            if(!$customer['categories_id']){
                $v->setError(tr('Specified category does not exist'));
            }

        }else{
            $customer['categories_id'] = null;
        }

        /*
         * Confirm country, state, city
         */
        if($customer['seocountry']){
            load_libs('geo');
            $customer['countries_id'] = geo_get_country($customer['seocountry'], true);

            if(!$customer['countries_id']){
                $v->setError(tr('Specified country does not exist'));
            }

            if($customer['seostate']){
                $customer['states_id'] = geo_get_state($customer['seostate'], true);

                if(!$customer['states_id']){
                    $v->setError(tr('Specified state does not exist in this country'));
                }

                if($customer['seocity']){
                    $customer['cities_id'] = geo_get_city($customer['seocity'], true);

                    if(!$customer['cities_id']){
                        $v->setError(tr('Specified city does not exist in this state'));
                    }

                }else{
                    $customer['cities_id'] = null;
                }
            }else{
                $customer['states_id'] = null;
                $customer['cities_id'] = null;
            }

        }else{
            $customer['countries_id'] = null;
            $customer['states_id']    = null;
            $customer['cities_id']    = null;
        }

        /*
         * Validate address data
         */
        if($customer['address1']){
            $v->hasMinChars($customer['address1'],  8, tr('Please ensure the customer\'s address1 has at least 3 characters'));
            $v->hasMaxChars($customer['address1'], 64, tr('Please ensure the customer\'s address1 has less than 64 characters'));

            $customer['address1'] = str_clean($customer['address1']);

        }else{
            $customer['address1'] = null;
        }

        if($customer['address2']){
            $v->hasMinChars($customer['address2'],  8, tr('Please ensure the customer\'s address2 has at least 3 characters'));
            $v->hasMaxChars($customer['address2'], 64, tr('Please ensure the customer\'s address2 has less than 64 characters'));

            $customer['address2'] = str_clean($customer['address2']);

        }else{
            $customer['address2'] = null;
        }

        if($customer['address3']){
            $v->hasMinChars($customer['address3'],  8, tr('Please ensure the customer\'s address3 has at least 3 characters'));
            $v->hasMaxChars($customer['address3'], 64, tr('Please ensure the customer\'s address3 has less than 64 characters'));

            $customer['address3'] = str_clean($customer['address3']);

        }else{
            $customer['address3'] = null;
        }

        if($customer['address3']){
            $v->hasMinChars($customer['address3'],  8, tr('Please ensure the customer\'s address3 has at least 3 characters'));
            $v->hasMaxChars($customer['address3'], 64, tr('Please ensure the customer\'s address3 has less than 64 characters'));

            $customer['address3'] = str_clean($customer['address3']);

        }else{
            $customer['address3'] = null;
        }

        if($customer['zipcode']){
            if($customer['countries_id']){
                /*
                 * Verify exact country zipcode format for the specified country
                 */
                $regex = geo_countries_get();

            }else{
                $v->hasMinChars($customer['zipcode'], 5, tr('Please ensure the customer\'s zipcode has at least 5 characters'));
                $v->hasMaxChars($customer['zipcode'], 6, tr('Please ensure the customer\'s zipcode has less than 6 characters'));
            }

        }else{
            $customer['zipcode'] = null;
        }


        /*
         * Does the customer already exist?
         */
        $exists = sql_get('SELECT `id` FROM `customers` WHERE `name` = :name AND `id` != :id', true, array(':name' => $customer['name'], ':id' => isset_get($customer['id'])));

        if($exists){
            $v->setError(tr('The customer ":customer" already exists with id ":id"', array(':customer' => $customer['name'], ':id' => $exists)));
        }

        $v->isValid();

        $customer['seoname'] = seo_unique($customer['name'], 'customers', isset_get($customer['id']));

        return $customer;

    }catch(Exception $e){
        throw new BException(tr('customers_validate(): Failed'), $e);
    }
}



/*
 * Insert the specified customer into the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package customers
 * @see customers_validate()
 * @see customers_update()
 * @version 2.5.92: Added function and documentation
 * @example Insert a customer in the database
 * code
 * $result = customers_insert(array('name'        => 'Capmega',
 *                                  'code'        => 'MX_CAPMEGA',
 *                                  'email'       => 'support@capmega.com',
 *                                  'seocompany'  => 'capmega',
 *                                  'seocompany'  => 'capmega',
 *                                  'description' => 'This is a test'));
 * showdie($result);
 * /code
 *
 * @param params $customer The customer to be inserted
 * @param string $customer[name]
 * @param string $customer[code]
 * @param string $customer[email]
 * @param string $customer[]
 * @param string $customer[]
 * @param string $customer[]
 * @param string $customer[]
 * @return params The specified customer, validated and sanitized
 */
function customers_insert($customer){
    try{
        $customer = customers_validate($customer);

        sql_query('INSERT INTO `customers` (`createdby`, `meta_id`, `name`, `seoname`, `code`, `email`, `phones`, `company`, `documents_id`, `categories_id`, `address1`, `address2`, `address3`, `zipcode`, `countries_id`, `states_id`, `cities_id`, `url`, `description`)
                   VALUES                  (:createdby , :meta_id , :name , :seoname , :code , :email , :phones,  :company , :documents_id , :categories_id , :address1 , :address2 , :address3 , :zipcode , :countries_id , :states_id , :cities_id , :url , :description )',

                   array(':createdby'     => $_SESSION['user']['id'],
                         ':meta_id'       => meta_action(),
                         ':name'          => $customer['name'],
                         ':seoname'       => $customer['seoname'],
                         ':code'          => $customer['code'],
                         ':email'         => $customer['email'],
                         ':phones'        => $customer['phones'],
                         ':company'       => $customer['company'],
                         ':documents_id'  => $customer['documents_id'],
                         ':categories_id' => $customer['categories_id'],
                         ':address1'      => $customer['address1'],
                         ':address2'      => $customer['address2'],
                         ':address3'      => $customer['address3'],
                         ':zipcode'       => $customer['zipcode'],
                         ':countries_id'  => $customer['countries_id'],
                         ':states_id'     => $customer['states_id'],
                         ':cities_id'     => $customer['cities_id'],
                         ':url'           => $customer['url'],
                         ':description'   => $customer['description']));

        $customer['id'] = sql_insert_id();

        return $customer;

    }catch(Exception $e){
        throw new BException(tr('customers_insert(): Failed'), $e);
    }
}



/*
 * Update the specified customer in the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package customers
 * @see customers_validate()
 * @see customers_insert()
 * @table: `customer`
 * @version 2.5.38: Added function and documentation
 * @example Update a customer in the database
 *
 * @param params $customer The customer to be inserted
 * @param string $customer[name]
 * @param string $customer[code]
 * @param string $customer[email]
 * @param string $customer[]
 * @param string $customer[]
 * @param string $customer[]
 * @param string $customer[]
 * @return params The specified customer, validated and sanitized
 */
function customers_update($customer){
    try{
        $customer = customers_validate($customer);

        meta_action($customer['meta_id'], 'update');

        $update = sql_query('UPDATE `customers`

                             SET    `name`          = :name,
                                    `seoname`       = :seoname,
                                    `email`         = :email,
                                    `phones`        = :phones,
                                    `code`          = :code,
                                    `company`       = :company,
                                    `documents_id`  = :documents_id,
                                    `categories_id` = :categories_id,
                                    `address1`      = :address1,
                                    `address2`      = :address2,
                                    `address3`      = :address3,
                                    `zipcode`       = :zipcode,
                                    `countries_id`  = :countries_id,
                                    `states_id`     = :states_id,
                                    `cities_id`     = :cities_id,
                                    `url`           = :url,
                                    `description`   = :description

                             WHERE  `id`            = :id',

                             array(':id'            => $customer['id'],
                                   ':name'          => $customer['name'],
                                   ':seoname'       => $customer['seoname'],
                                   ':code'          => $customer['code'],
                                   ':email'         => $customer['email'],
                                   ':phones'        => $customer['phones'],
                                   ':company'       => $customer['company'],
                                   ':documents_id'  => $customer['documents_id'],
                                   ':categories_id' => $customer['categories_id'],
                                   ':address1'      => $customer['address1'],
                                   ':address2'      => $customer['address2'],
                                   ':address3'      => $customer['address3'],
                                   ':zipcode'       => $customer['zipcode'],
                                   ':countries_id'  => $customer['countries_id'],
                                   ':states_id'     => $customer['states_id'],
                                   ':cities_id'     => $customer['cities_id'],
                                   ':url'           => $customer['url'],
                                   ':description'   => $customer['description']));

        $customer['_updated'] = (boolean) $update->rowCount();
        return $customer;

    }catch(Exception $e){
        throw new BException(tr('customers_update(): Failed'), $e);
    }
}



/*
 * Return HTML for a customers select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available customers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package customers
 *
 * @param array $params The parameters required
 * @param $params name
 * @param $params class
 * @param $params extra
 * @param $params tabindex
 * @param $params empty
 * @param $params none
 * @param $params selected
 * @param $params parents_id
 * @param $params status
 * @param $params orderby
 * @param $params resource
 * @return string HTML for a customers select box within the specified parameters
 */
function customers_select($params = null){
    global $_CONFIG;

    try{
        array_ensure($params);
        array_default($params, 'name'         , 'seocustomer');
        array_default($params, 'class'        , 'form-control');
        array_default($params, 'selected'     , null);
        array_default($params, 'seocategory'  , null);
        array_default($params, 'categories_id', false);
        array_default($params, 'status'       , null);
        array_default($params, 'empty'        , tr('No customers available'));
        array_default($params, 'none'         , tr('Select a customer'));
        array_default($params, 'orderby'      , '`name`');

        if($params['seocategory']){
            load_libs('categories');
            $params['categories_id'] = categories_get($params['seocategory'], 'id', null, $_CONFIG['customers']['categories_parent']);

            if(!$params['categories_id']){
                throw new BException(tr('customers_select(): The reqested category ":category" does exist, but is deleted', array(':category' => $params['seocategory'])), 'deleted');
            }
        }

        if($params['categories_id'] !== false){
            $where[] = ' `categories_id` '.sql_is($params['categories_id'], ':categories_id');
            $execute[':categories_id'] = $params['categories_id'];
        }

        if($params['status'] !== false){
            $where[] = ' `status` '.sql_is($params['status'], ':status');
            $execute[':status'] = $params['status'];
        }

        if(empty($where)){
            $where = '';

        }else{
            $where = ' WHERE '.implode(' AND ', $where).' ';
        }

        $query              = 'SELECT `seoname`, `name` FROM `customers` '.$where.' ORDER BY '.$params['orderby'];
        $params['resource'] = sql_query($query, $execute, 'core');
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new BException('customers_select(): Failed', $e);
    }
}



/*
 * Return data for the specified customer
 *
 * This function returns information for the specified customer. The customer can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package customers
 * @version 2.5.50: Added function and documentation
 *
 * @param mixed $customer The requested customer. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @param string $status Filter by the specified status
 * @param natural $categories_id Filter by the specified categories_id. If NULL, the customer must NOT belong to any category
 * @return mixed The customer data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified customer does not exist, NULL will be returned.
 */
function customers_get($params){
    try{
        array_params($params, 'seoname', 'id');

        array_default($params, 'filters', array('customers.id'      => $params['id'],
                                                'customers.seoname' => $params['seoname']));

        array_default($params, 'joins'  , array('LEFT JOIN `geo_countries`
                                                 ON        `geo_countries`.`id` = `customers`.`countries_id`',

                                                'LEFT JOIN `geo_states`
                                                 ON        `geo_states`.`id`    = `customers`.`states_id`',

                                                'LEFT JOIN `geo_cities`
                                                 ON        `geo_cities`.`id`    = `customers`.`cities_id`',

                                                'LEFT JOIN `categories`
                                                 ON        `categories`.`id`    = `customers`.`categories_id`'));

        array_default($params, 'columns', 'customers.id,
                                           customers.createdon,
                                           customers.createdby,
                                           customers.meta_id,
                                           customers.status,
                                           customers.name,
                                           customers.seoname,
                                           customers.code,
                                           customers.company,
                                           customers.email,
                                           customers.phones,
                                           customers.documents_id,
                                           customers.categories_id,
                                           customers.address1,
                                           customers.address2,
                                           customers.address3,
                                           customers.zipcode,
                                           customers.countries_id,
                                           customers.states_id,
                                           customers.cities_id,
                                           customers.url,
                                           customers.description,

                                           categories.name       AS category,
                                           categories.seoname    AS seocategory,

                                           geo_countries.name    AS country,
                                           geo_countries.seoname AS seocountry,

                                           geo_states.name       AS state,
                                           geo_states.seoname    AS seostate,

                                           geo_cities.name       AS city,
                                           geo_cities.seoname    AS seocity');

        $params['table']     = 'customers';
        $params['connector'] = 'core';

        return sql_simple_get($params);

    }catch(Exception $e){
        throw new BException('customers_get(): Failed', $e);
    }
}



/*
 * Return a list of all available customers
 *
 * This function wraps sql_simple_list() and supports all its options, like columns selection, filtering, ordering, and execution method
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @customer Function reference
 * @package customers
 * @see sql_simple_list()
 * @version 2.5.50: Added function and documentation
 *
 * @param params $params The list parameters
 * @return mixed The list of available customers
 */
function customers_list($params){
    try{
        array_ensure($params);
        array_default($params, 'columns', 'seoname,name');
        array_default($params, 'orderby', array('name' => 'asc'));

        $params['table']     = 'customers';
        $params['connector'] = 'core';

        return sql_simple_list($params);

    }catch(Exception $e){
        throw new BException('customers_list(): Failed', $e);
    }
}
