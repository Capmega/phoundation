<?php
/*
 * Template library
 *
 * This is a library template file
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package template
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
 * @version 2.5.12: Added function and documentation
 *
 * @return void
 */
function template_library_init(){
    try{
        ensure_installed(array('name'      => 'template',
                               'callback'  => 'template_install',
                               'checks'    => ROOT.'libs/external/template/template,'.ROOT.'libs/external/template/foobar',
                               'functions' => 'template,foobar',
                               'which'     => 'template,foobar'));

    }catch(Exception $e){
        throw new BException('template_library_init(): Failed', $e);
    }
}



/*
 * Install the external template library
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @version 2.5.12: Added function and documentation
 * @package template
 *
 * @param
 * @return
 */
function template_install($params){
    try{
        load_libs('apt');
        apt_install('template');

    }catch(Exception $e){
        throw new BException('template_install(): Failed', $e);
    }
}



/*
 * Validate the specified template
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @package templates
 *
 * @param array $template The template to validate
 * @return array The validated and cleaned $template array
 */
function templates_validate($template){
    try{
        load_libs('validate,seo');

        $v = new ValidateForm($template, 'name');

        /*
         * Validate foo
         */
        $v->isNotEmpty ($template['name']    , tr('Please specify a template name'));
        $v->hasMinChars($template['name'],  2, tr('Please ensure the template name has at least 2 characters'));
        $v->hasMaxChars($template['name'], 64, tr('Please ensure the template name has less than 64 characters'));

        /*
         * Validate description
         */
        if(empty($template['description'])){
            $template['description'] = null;

        }else{
            $v->hasMinChars($template['description'],   16, tr('Please ensure the template description has at least 16 characters'));
            $v->hasMaxChars($template['description'], 2047, tr('Please ensure the template description has less than 2047 characters'));

            $template['description'] = str_clean($template['description']);
        }

        /*
         * All valid?
         */
        $v->isValid();

        /*
         * Set seoname
         */
        $template['seoname'] = seo_unique($template['name'], 'templates', isset_get($template['id']));

      return $template;

    }catch(Exception $e){
        throw new BException('templates_validate(): Failed', $e);
    }
}



/*
 * Insert the specified template into the database
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package template
 * @see template_validate()
 * @see template_update()
 * @table: `template`
 * @note: This is a note
 * @version 2.5.12: Added function and documentation
 * @example [Title]
 * code
 * $result = template_insert(array('foo' => 'bar',
 *                                 'foo' => 'bar',
 *                                 'foo' => 'bar'));
 * showdie($result);
 * /code
 *
 * @param params $params The template to be inserted
 * @param string $params[foo]
 * @param string $params[bar]
 * @return array The specified template, validated and sanitized
 */
function template_insert($template){
    try{
        $template = template_validate($template);

        sql_query('INSERT INTO `templates` (`createdby`, `meta_id`, `status`, )
                   VALUES                  (:createdby , :meta_id , :status , )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':meta_id'   => meta_action(),
                         ':status'    => $template['status']));

        $template['id'] = sql_insert_id();

        return $template;

    }catch(Exception $e){
        throw new BException('template_insert(): Failed', $e);
    }
}



/*
 * Update the specified template in the database
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package template
 * @see template_validate()
 * @see template_insert()
 * @table: `template`
 * @note: This is a note
 * @version 2.5.12: Added function and documentation
 * @example [Title]
 * code
 * $result = template_update(array('foo' => 'bar',
 *                                 'foo' => 'bar',
 *                                 'foo' => 'bar'));
 * showdie($result);
 * /code
 *
 * @param params $params The template to be updated
 * @param string $params[foo]
 * @param string $params[bar]
 * @return array The specified template, validated and sanitized
 */
function template_update($template){
    try{
        $template = template_validate($template);

        meta_action($template['meta_id'], 'update');

        $update = sql_query('UPDATE `templates`

                             SET    ``   = :,
                                    ``   = :

                             WHERE  `id` = :id',

                             array(':id' => $template['id'],
                                   ':'   => $template[''],
                                   ':'   => $template['']));

        return $update->rowCount();

    }catch(Exception $e){
        throw new BException('template_update(): Failed', $e);
    }
}



/*
 * Return data for the specified template
 *
 * This function returns information for the specified template. The template can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @package templates
 *
 * @param mixed $template The requested template. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @param string $status
 * @param string $parent
 * @return mixed The template data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified template does not exist, NULL will be returned.
 */
function templates_get($template, $column = null, $status = null, $parent = false){
    try{
        if(is_numeric($template)){
            $where[] = ' `templates`.`id` = :id ';
            $execute[':id'] = $template;

        }else{
            $where[] = ' `templates`.`seoname` = :seoname ';
            $execute[':seoname'] = $template;
        }

        if($status !== false){
            $execute[':status'] = $status;
            $where[] = ' `templates`.`status` '.sql_is($status, ':status');
        }

        if($parent){
            /*
             * Explicitly must be a parent template
             */
            $where[] = ' `templates`.`parents_id` IS NULL ';

        }elseif($parent === false){
            /*
             * Explicitly cannot be a parent template
             */
            $where[] = ' `templates`.`parents_id` IS NOT NULL ';

        }else{
            /*
             * Don't care if its a parent or child template
             */
        }

        $where = ' WHERE '.implode(' AND ', $where).' ';

        if($column){
            $retval = sql_get('SELECT `'.$column.'` FROM `templates` '.$where, true, $execute);

        }else{
            $retval = sql_get('SELECT    `templates`.`id`,
                                         `templates`.`createdon`,
                                         `templates`.`createdby`,
                                         `templates`.`meta_id`,
                                         `templates`.`status`,
                                         `templates`.`parents_id`,
                                         `templates`.`name`,
                                         `templates`.`seoname`,
                                         `templates`.`description`,

                                         `parents`.`name`    AS `parent`,
                                         `parents`.`seoname` AS `seoparent`

                               FROM      `templates`

                               LEFT JOIN `templates` AS `parents`
                               ON        `parents`.`id` = `templates`.`parents_id` '.$where, $execute);
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('templates_get(): Failed', $e);
    }
}



/*
 * Return a list of all available templates filtered by
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @package templates
 *
 * @param null string array $columns The columns to return in the list. Can be specified with an array list, or CVS string. By default this is `seoname`, `name`
 * @param boolean $resource If specified true, the return value will be a PDO statement. If set to false, the list will be returned as an array
 * @return mixed The list of available templates
 */
function templates_list($filters, $columns = null, $resource = true){
    try{
        if(!is_array($filters)){
            throw new BException(tr('templates_list(): The specified filters are invalid, it should be a key => value array'), 'invalid');
        }

        /*
         * Build the where section
         */
        foreach($filters as $key => $value){
            if(is_array($value)){
                $value   = sql_in($value);
                $where[] = ' `'.$key.'` IN ('.sql_in_columns($value).') ';
                $execute = array_merge($execute, $value);

            }else{
                $where[] = ' `'.$key.'` = :'.$key.' ';
                $execute[':'.$key] = $value;
            }
        }

        if(isset($where)){
            $where = ' WHERE '.implode(' AND ', $filters);
        }

        /*
         * Validate the columns
         */
        if(!$columns){
            $columns = '`seoname`, `name`';

        }else{
            $columns = array_force($columns);
            $columns = '`'.implode('`, `', $columns).'`';
        }

        $query = 'SELECT '.$columns.'

                  FROM   `templates`

                  '.$where;

        /*
         * Execute query and return results
         */
        if($resource){
            /*
             * Return a query instead of a list array
             */
            return sql_query($query, $execute);
        }

        /*
         * Return a list array instead of a query
         */
        return sql_list($query, $execute);

    }catch(Exception $e){
        throw new BException('templates_list(): Failed', $e);
    }
}



/*
 * Return HTML for a templates select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available templates
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @package templates
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
 * @return string HTML for a templates select box within the specified parameters
 */
function templates_select($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'      , 'seotemplate');
        array_default($params, 'class'     , 'form-control');
        array_default($params, 'selected'  , null);
        array_default($params, 'seoparent' , null);
        array_default($params, 'autosubmit', true);
        array_default($params, 'parents_id', null);
        array_default($params, 'status'    , null);
        array_default($params, 'remove'    , null);
        array_default($params, 'empty'     , tr('No templates available'));
        array_default($params, 'none'      , tr('Select a template'));
        array_default($params, 'orderby'   , '`name`');

        if($params['seoparent']){
            /*
             * This is a child template
             */
            $params['parents_id'] = sql_get('SELECT `id` FROM `templates` WHERE `seoname` = :seoname AND `parents_id` IS NULL AND `status` IS NULL', true, array(':seoname' => $params['seoparent']));

            if(!$params['parents_id']){
                /*
                 * The template apparently does not exist, auto create it
                 */
                $parent = sql_get('SELECT `id`, `parents_id`, `status` FROM `templates` WHERE `seoname` = :seoname', array(':seoname' => $params['seoparent']));

                if($parent){
                    if($parent['status']){
                        /*
                         * The template exists, but has non NULL status, we cannot continue!
                         */
                        throw new BException(tr('templates_select(): The reqested parent ":parent" does exist, but is not available', array(':parent' => $params['seoparent'])), 'not-available');
                    }

                    /*
                     * The template exists, but it's a child template
                     */
                    throw new BException(tr('templates_select(): The reqested parent ":parent" does exist, but is a child template itself. Child templates cannot be parent templates', array(':parent' => $params['seoparent'])), 'not-available');
                }

                load_libs('seo');

                sql_query('INSERT INTO `templates` (`meta_id`, `name`, `seoname`)
                           VALUES                  (:meta_id , :name , :seoname )',

                           array(':meta_id' => meta_action(),
                                 ':name'    => $params['seoparent'],
                                 ':seoname' => seo_unique($params['seoparent'], 'templates')));

                $params['parents_id'] = sql_insert_id();
            }

        }else{
            /*
             * This is a parent template. Nothing to do, just saying..
             */
        }

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

        $query              = 'SELECT `seoname`, `name` FROM `templates` '.$where.' ORDER BY `name`';
        $params['resource'] = sql_query($query, $execute);
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new BException('templates_select(): Failed', $e);
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
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package template
 * @see template_install()
 * @see date_convert() Used to convert the sitemap entry dates
 * @table: `template`
 * @note: This is a note
 * @version 2.5.12: Added function and documentation
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
function template_function($params){
    try{

    }catch(Exception $e){
        throw new BException('template_function(): Failed', $e);
    }
}
?>
