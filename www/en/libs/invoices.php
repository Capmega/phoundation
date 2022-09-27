<?php
/*
 * Invoices library
 *
 * This is a library invoice file
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package invoices
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
 * @package invoices
 * @version 2.5.38: Added function and documentation
 *
 * @return void
 */
function invoices_library_init(){
    try{
        ensure_installed(array('name'      => 'invoice',
                               'callback'  => 'invoices_install',
                               'checks'    => ROOT.'libs/external/invoice/invoice,'.ROOT.'libs/external/invoice/foobar',
                               'functions' => 'invoice,foobar',
                               'which'     => 'invoice,foobar'));

    }catch(Exception $e){
        throw new BException('invoices_library_init(): Failed', $e);
    }
}



/*
 * Install the external invoice library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @version 2.5.38: Added function and documentation
 * @package invoices
 *
 * @param
 * @return
 */
function invoices_install($params){
    try{
        load_libs('apt');
        apt_install('invoice');

    }catch(Exception $e){
        throw new BException('invoices_install(): Failed', $e);
    }
}



/*
 * Validate the specified invoice
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @invoice Function reference
 * @package invoices
 *
 * @param array $invoice The invoice to validate
 * @return array The validated and cleaned $invoice parameter array
 */
function invoices_validate($invoice){
    try{
        load_libs('validate,seo');

        $v = new ValidateForm($invoice, 'name');

        /*
         * Validate foo
         */
        $v->isNotEmpty ($invoice['name']    , tr('Please specify a invoice name'));
        $v->hasMinChars($invoice['name'],  2, tr('Please ensure the invoice name has at least 2 characters'));
        $v->hasMaxChars($invoice['name'], 64, tr('Please ensure the invoice name has less than 64 characters'));

        /*
         * Validate description
         */
        if(empty($invoice['description'])){
            $invoice['description'] = null;

        }else{
            $v->hasMinChars($invoice['description'],   16, tr('Please ensure the invoice description has at least 16 characters'));
            $v->hasMaxChars($invoice['description'], 2047, tr('Please ensure the invoice description has less than 2047 characters'));

            $invoice['description'] = str_clean($invoice['description']);
        }

        /*
         * All valid?
         */
        $v->isValid();

        /*
         * Set seoname
         */
        $invoice['seoname'] = seo_unique($invoice['name'], 'invoices', isset_get($invoice['id']));

      return $invoice;

    }catch(Exception $e){
        throw new BException('invoices_validate(): Failed', $e);
    }
}



/*
 * Insert the specified invoice into the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package invoices
 * @see invoices_validate()
 * @see invoices_update()
 * @table: `invoice`
 * @note: This is a note
 * @version 2.5.38: Added function and documentation
 * @example [Title]
 * code
 * $result = invoices_insert(array('foo' => 'bar',
 *                                 'foo' => 'bar',
 *                                 'foo' => 'bar'));
 * showdie($result);
 * /code
 *
 * @param params $invoice The invoice to be inserted
 * @param string $invoice[foo]
 * @param string $invoice[bar]
 * @return params The specified invoice, validated and sanitized
 */
function invoices_insert($invoice){
    try{
        $invoice = invoices_validate($invoice);

        sql_query('INSERT INTO `invoices` (`createdby`, `meta_id`, `status`, )
                   VALUES                  (:createdby , :meta_id , :status , )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':meta_id'   => meta_action(),
                         ':status'    => $invoice['status']));

        $invoice['id'] = sql_insert_id();

        return $invoice;

    }catch(Exception $e){
        throw new BException('invoices_insert(): Failed', $e);
    }
}



/*
 * Update the specified invoice in the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package invoices
 * @see invoices_validate()
 * @see invoices_insert()
 * @table: `invoice`
 * @note: This is a note
 * @version 2.5.38: Added function and documentation
 * @example [Title]
 * code
 * $result = invoices_update(array('foo' => 'bar',
 *                                 'foo' => 'bar',
 *                                 'foo' => 'bar'));
 * showdie($result);
 * /code
 *
 * @param params $params The invoice to be updated
 * @param string $params[foo]
 * @param string $params[bar]
 * @return boolean True if the user was updated, false if not. If not updated, this might be because no data has changed
 */
function invoices_update($invoice){
    try{
        $invoice = invoices_validate($invoice);

        meta_action($invoice['meta_id'], 'update');

        $update = sql_query('UPDATE `invoices`

                             SET    ``   = :,
                                    ``   = :

                             WHERE  `id` = :id',

                             array(':id' => $invoice['id'],
                                   ':'   => $invoice[''],
                                   ':'   => $invoice['']));

        return (boolean) $update->rowCount();

    }catch(Exception $e){
        throw new BException('invoices_update(): Failed', $e);
    }
}



/*
 * Return data for the specified invoice
 *
 * This function returns information for the specified invoice. The invoice can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @invoice Function reference
 * @package invoices
 * @version 2.5.38: Added function and documentation
 *
 * @param mixed $invoice The requested invoice. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @param string $status
 * @param string $parent
 * @return mixed The invoice data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified invoice does not exist, NULL will be returned.
 */
function invoices_get($invoice, $column = null, $status = null, $parent = false){
    try{
        array_ensure($params, 'seoinvoice');

        $params['table']   = 'invoices';

        array_default($params, 'filters', array('seoname' => $params['seoinvoice'],
                                                'status'  => null));

        array_default($params, 'joins'  , array('LEFT JOIN `geo_countries`
                                                 ON        `geo_countries`.`id` = `customers`.`countries_id`',

                                                'LEFT JOIN `categories`
                                                 ON        `categories`.`id`    = `customers`.`categories_id`'));

        array_default($params, 'columns', 'invoices.id,
                                           invoices.createdon,
                                           invoices.createdby,
                                           invoices.meta_id,
                                           invoices.status,
                                           invoices.name,
                                           invoices.seoname,
                                           invoices.description,

                                           categories.name       AS category,
                                           categories.seoname    AS seocategory,

                                           geo_countries.name    AS country,
                                           geo_countries.seoname AS seocountry');

        return sql_simple_get($params);

    }catch(Exception $e){
        throw new BException('invoices_get(): Failed', $e);
    }
}



/*
 * Return a list of all available invoices filtered by
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @invoice Function reference
 * @see sql_simple_list()
 * @package invoices
 * @version 2.5.38: Added function and documentation
 *
 * @param params $params The list parameters
 * @return mixed The list of available invoices
 */
function invoices_list($params){
    try{
        array_ensure($params);

        $params['table'] = 'invoices';

        return sql_simple_list($params);

    }catch(Exception $e){
        throw new BException('invoices_list(): Failed', $e);
    }
}



/*
 * Return HTML for a invoices select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available invoices
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @invoice Function reference
 * @package invoices
 * @see sql_simple_get()
 * @version 2.5.38: Added function and documentation
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
 * @return string HTML for a invoices select box within the specified parameters
 */
function invoices_select($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'      , 'seoinvoice');
        array_default($params, 'class'     , 'form-control');
        array_default($params, 'selected'  , null);
        array_default($params, 'autosubmit', true);
        array_default($params, 'parents_id', null);
        array_default($params, 'status'    , null);
        array_default($params, 'remove'    , null);
        array_default($params, 'empty'     , tr('No invoices available'));
        array_default($params, 'none'      , tr('Select a invoice'));
        array_default($params, 'orderby'   , '`name`');

        $execute = array();

        if($params['remove']){
            if(count(array_force($params['remove'])) == 1){
                /*
                 * Filter out only one entry
                 */
                $where[] = ' `id` != :id ';
                $execute[':id'] = $params['remove'];

            }else{
                /*
                 * Filter out multiple entries
                 */
                $in      = sql_in(array_force($params['remove']));
                $where[] = ' `id` NOT IN ('.implode(', ', array_keys($in)).') ';
                $execute = array_merge($execute, $in);
            }
        }

        if($params['parents_id']){
            $where[] = ' `parents_id` = :parents_id ';
            $execute[':parents_id'] = $params['parents_id'];

        }else{
            $where[] = ' `parents_id` IS NULL ';
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

        $query              = 'SELECT `seoname`, `name` FROM `invoices` '.$where.' ORDER BY `name`';
        $params['resource'] = sql_query($query, $execute);
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new BException('invoices_select(): Failed', $e);
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
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package invoices
 * @see invoices_install()
 * @see date_convert() Used to convert the sitemap entry dates
 * @table: `invoice`
 * @note: This is a note
 * @version 2.5.38: Added function and documentation
 * @example [Title]
 * code
 * $result = invoices_function(array('foo' => 'bar'));
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
function invoices_function($params){
    try{

    }catch(Exception $e){
        throw new BException('invoices_function(): Failed', $e);
    }
}
?>
