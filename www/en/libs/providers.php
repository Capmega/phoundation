<?php
/*
 * Providers library
 *
 * This is the providers library file, it contains providers functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyprovider Sven Oostenbrink <support@capmega.com>
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
 * @package providers
 *
 * @return void
 */
function providers_library_init(){
    try{
        load_config('providers');

    }catch(Exception $e){
        throw new CoreException('providers_library_init(): Failed', $e);
    }
}



/*
 * Validate a provider
 *
 * This function will validate all relevant fields in the specified $provider array
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package providers
 *
 * @param $params $provider
 * @return
 */
function providers_validate($provider){
    try{
        load_libs('validate,seo');

        $v = new ValidateForm($provider, 'seocategory,name,code,url,email,phones,description');
        $v->isNotEmpty ($provider['name']    , tr('No providers name specified'));
        $v->hasMinChars($provider['name'],  2, tr('Please ensure the provider\'s name has at least 2 characters'));
        $v->hasMaxChars($provider['name'], 64, tr('Please ensure the provider\'s name has less than 64 characters'));
        $v->isRegex    ($provider['name'], '/^[a-zA-Z- ]{2,32}$/', tr('Please ensure the provider\'s name contains only lower case letters, and dashes'));

        /*
         * Validate category
         */
        if($provider['seocategory']){
            load_libs('categories');

            $provider['categories_id'] = categories_get($provider['seocategory'], 'id');

            if(!$provider['categories_id']){
                $v->setError(tr('Specified category does not exist'));
            }

        }else{
            $provider['categories_id'] = null;
        }

        /*
         * Validate basic data
         */
        if($provider['description']){
            $v->hasMinChars($provider['description'],    8, tr('Please ensure the description has at least 8 characters'));
            $v->hasMaxChars($provider['description'], 2047, tr('Please ensure the description has less than 2047 characters'));

            $provider['description'] = str_clean($provider['description']);

        }else{
            $provider['description'] = null;
        }

        if($provider['url']){
            $v->hasMaxChars($provider['url'], 255, tr('Please ensure the URL has less than 255 characters'));
            $v->isURL($provider['url'], tr('Please a valid URL'));

        }else{
            $provider['url'] = null;
        }

        if($provider['email']){
            $v->hasMaxChars($provider['email'], 64, tr('Please ensure the email has less than 96 characters'));
            $v->isEmail($provider['email'], tr('Please specify a valid emailaddress'));

        }else{
            $provider['email'] = null;
        }

        if($provider['phones']){
            $v->hasMaxChars($provider['phones'], 36, tr('Please ensure the phones field has less than 36 characters'));

            foreach(array_force($provider['phones']) as &$phone){
                $v->isPhonenumber($phone, tr('Please ensure the phone number ":phone" is valid', array(':phone' => $phone)));
            }

            $provider['phones'] = str_force($provider['phones']);

        }else{
            $provider['phones'] = null;
        }

        if($provider['code']){
            $v->hasMinChars($provider['code'],  2, tr('Please ensure the provider\'s description has at least 2 characters'));
            $v->hasMaxChars($provider['code'], 64, tr('Please ensure the provider\'s description has less than 64 characters'));
            $v->isAlphaNumeric($provider['code'], tr('Please ensure the provider\'s description has less than 64 characters'), VALIDATE_IGNORE_SPACE|VALIDATE_IGNORE_DASH|VALIDATE_IGNORE_UNDERSCORE);

        }else{
            $provider['code'] = null;
        }

        /*
         * Does the provider already exist?
         */
        $exists = sql_get('SELECT `id` FROM `providers` WHERE `name` = :name AND `id` != :id', true, array(':name' => $provider['name'], ':id' => isset_get($provider['id'])));

        if($exists){
            $v->setError(tr('The provider ":provider" already exists with id ":id"', array(':provider' => $provider['name'], ':id' => $exists)));
        }

        $v->isValid();

        $provider['seoname'] = seo_unique($provider['name'], 'providers', isset_get($provider['id']));

        return $provider;

    }catch(Exception $e){
        throw new CoreException(tr('providers_validate(): Failed'), $e);
    }
}



/*
 * Insert the specified provider into the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package providers
 * @see providers_validate()
 * @see providers_update()
 * @version 2.5.92: Added function and documentation
 *
 * @param params $provider The provider to be inserted
 * @param string $provider[name]
 * @param string $provider[code]
 * @param string $provider[email]
 * @param string $provider[]
 * @param string $provider[]
 * @param string $provider[]
 * @param string $provider[]
 * @return params The specified provider, validated and sanitized
 */
function providers_insert($provider){
    try{
        $provider = providers_validate($provider);

        sql_query('INSERT INTO `providers` (`createdby`, `categories_id`, `name`, `seoname`, `code`, `url`, `email`, `phones`, `description`)
                   VALUES                  (:createdby , :categories_id , :name , :seoname , :code , :url , :email , :phones , :description )',

                   array(':createdby'     => $_SESSION['user']['id'],
                         ':categories_id' => $provider['categories_id'],
                         ':name'          => $provider['name'],
                         ':seoname'       => $provider['seoname'],
                         ':code'          => $provider['code'],
                         ':url'           => $provider['url'],
                         ':email'         => $provider['email'],
                         ':phones'        => $provider['phones'],
                         ':description'   => $provider['description']));

        $provider['id'] = sql_insert_id();

        return $provider;

    }catch(Exception $e){
        throw new CoreException(tr('providers_insert(): Failed'), $e);
    }
}



/*
 * Update the specified provider in the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package providers
 * @see providers_validate()
 * @see providers_insert()
 * @table: `provider`
 * @version 2.5.38: Added function and documentation
 *
 * @param params $provider The provider to be inserted
 * @param string $provider[name]
 * @param string $provider[code]
 * @param string $provider[email]
 * @param string $provider[]
 * @param string $provider[]
 * @param string $provider[]
 * @param string $provider[]
 * @return params The specified provider, validated and sanitized
 */
function providers_update($provider){
    try{
        $provider = providers_validate($provider);

        meta_action($provider['meta_id'], 'update');

        $update = sql_query('UPDATE `providers`

                             SET    `categories_id` = :categories_id,
                                    `url`           = :url,
                                    `code`          = :code,
                                    `name`          = :name,
                                    `seoname`       = :seoname,
                                    `email`         = :email,
                                    `phones`        = :phones,
                                    `description`   = :description

                             WHERE  `id`            = :id',

                             array(':id'            => $provider['id'],
                                   ':categories_id' => $provider['categories_id'],
                                   ':name'          => $provider['name'],
                                   ':seoname'       => $provider['seoname'],
                                   ':code'          => $provider['code'],
                                   ':url'           => $provider['url'],
                                   ':email'         => $provider['email'],
                                   ':phones'        => $provider['phones'],
                                   ':description'   => $provider['description']));

        $provider['_updated'] = (boolean) $update->rowCount();
        return $provider;

    }catch(Exception $e){
        throw new CoreException(tr('providers_update(): Failed'), $e);
    }
}



/*
 * Return HTML for a providers select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available providers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package providers
 *
 * @param array $params The parameters required
 * @param $params name
 * @param $params class
 * @param $params empty
 * @param $params none
 * @param $params selected
 * @param $params parents_id
 * @param $params status
 * @param $params orderby
 * @param $params resource
 * @return string HTML for a providers select box within the specified parameters
 */
function providers_select($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'         , 'seoprovider');
        array_default($params, 'class'        , 'form-control');
        array_default($params, 'selected'     , null);
        array_default($params, 'categories_id', false);
        array_default($params, 'status'       , null);
        array_default($params, 'empty'        , tr('No providers available'));
        array_default($params, 'none'         , tr('Select a provider'));
        array_default($params, 'orderby'      , '`name`');

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

        $query              = 'SELECT `seoname`, `name` FROM `providers` '.$where.' ORDER BY '.$params['orderby'];
        $params['resource'] = sql_query($query, $execute);
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new CoreException('providers_select(): Failed', $e);
    }
}



/*
 * Return data for the specified provider
 *
 * This function returns information for the specified provider. The provider can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package providers
 * @version 2.5.50: Added function and documentation
 *
 * @param mixed $provider The requested provider. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @param string $status Filter by the specified status
 * @param natural $categories_id Filter by the specified categories_id. If NULL, the provider must NOT belong to any category
 * @return mixed The provider data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified provider does not exist, NULL will be returned.
 */
function providers_get($params){
    try{
        array_params($params, 'seoname', 'id');

        array_default($params, 'filters', array('providers.id'      => $params['id'],
                                                'providers.seoname' => $params['seoname']));

        array_default($params, 'joins'  , array('LEFT JOIN `categories`
                                                 ON        `categories`.`id` = `providers`.`categories_id`'));

        array_default($params, 'columns', 'providers.id,
                                           providers.createdon,
                                           providers.createdby,
                                           providers.meta_id,
                                           providers.status,
                                           providers.name,
                                           providers.seoname,
                                           providers.email,
                                           providers.phones,
                                           providers.code,
                                           providers.url,
                                           providers.description,

                                           categories.name    AS category,
                                           categories.seoname AS seocategory');

        $params['table']     = 'providers';
        $params['connector'] = 'core';

        return sql_simple_get($params);

    }catch(Exception $e){
        throw new CoreException('providers_get(): Failed', $e);
    }
}



/*
 * Return a list of all available providers
 *
 * This function wraps sql_simple_list() and supports all its options, like columns selection, filtering, ordering, and execution method
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package providers
 * @see sql_simple_list()
 * @version 2.5.50: Added function and documentation
 *
 * @param params $params The list parameters
 * @return mixed The list of available providers
 */
function providers_list($params){
    try{
        array_ensure($params);
        array_default($params, 'columns', 'seoname,name');
        array_default($params, 'orderby', array('name' => 'asc'));

        $params['table']     = 'providers';
        $params['connector'] = 'core';

        return sql_simple_list($params);

    }catch(Exception $e){
        throw new CoreException('providers_list(): Failed', $e);
    }
}
?>
