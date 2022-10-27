<?php
/*
 * Template library
 *
 * This is a library template file
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package templates
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package templates
 * @version 2.5.38: Added function and documentation
 *
 * @return void
 */
function templates_library_init() {
    try {
        ensure_installed(array('name'      => 'template',
                               'callback'  => 'templates_install',
                               'checks'    => ROOT.'libs/external/template/template,'.ROOT.'libs/external/template/foobar',
                               'functions' => 'template,foobar',
                               'which'     => 'template,foobar'));

    }catch(Exception $e) {
        throw new CoreException(tr('templates_library_init(): Failed'), $e);
    }
}



/*
 * Install the external template library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @version 2.5.38: Added function and documentation
 * @package templates
 *
 * @param
 * @return
 */
function templates_install($params) {
    try {
        load_libs('apt');
        apt_install('template');

    }catch(Exception $e) {
        throw new CoreException(tr('templates_install(): Failed'), $e);
    }
}



/*
 * Validate the specified template
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @package templates
 *
 * @param array $template The template to validate
 * @return array The validated and cleaned $template parameter array
 */
function templates_validate($template) {
    try {
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
        if (empty($template['description'])) {
            $template['description'] = null;

        } else {
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

    }catch(Exception $e) {
        throw new CoreException(tr('templates_validate(): Failed'), $e);
    }
}



/*
 * Insert the specified template into the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package templates
 * @see templates_validate()
 * @see templates_update()
 * @table: `template`
 * @note: This is a note
 * @version 2.5.38: Added function and documentation
 * @example Insert a template in the database
 * code
 * $result = templates_insert(array('foo' => 'bar',
 *                                 'foo' => 'bar',
 *                                 'foo' => 'bar'));
 * showdie($result);
 * /code
 *
 * @param params $template The template to be inserted
 * @param string $template[foo]
 * @param string $template[bar]
 * @return params The specified template, validated and sanitized
 */
function templates_insert($template) {
    try {
        $template = templates_validate($template);

        sql_query('INSERT INTO `templates` (`created_by`, `meta_id`, `status`, )
                   VALUES                  (:created_by , :meta_id , :status , )',

                   array(':created_by' => isset_get($_SESSION['user']['id']),
                         ':meta_id'   => meta_action(),
                         ':status'    => $template['status']));

        $template['id'] = sql_insert_id();

        return $template;

    }catch(Exception $e) {
        throw new CoreException(tr('templates_insert(): Failed'), $e);
    }
}



/*
 * Update the specified template in the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package templates
 * @see templates_validate()
 * @see templates_insert()
 * @table: `template`
 * @note: This is a note
 * @version 2.5.38: Added function and documentation
 * @example Update a template in the database
 * code
 * $result = templates_update(array('id'  => 42,
 *                                  'foo' => 'bar',
 *                                  'foo' => 'bar',
 *                                  'foo' => 'bar'));
 * showdie($result);
 * /code
 *
 * @param params $params The template to be updated
 * @param string $params[foo]
 * @param string $params[bar]
 * @return boolean True if the user was updated, false if not. If not updated, this might be because no data has changed
 */
function templates_update($template) {
    try {
        $template = templates_validate($template);

        meta_action($template['meta_id'], 'update');

        $update = sql_query('UPDATE `templates`

                             SET    ``   = :,
                                    ``   = :

                             WHERE  `id` = :id',

                             array(':id' => $template['id'],
                                   ':'   => $template[''],
                                   ':'   => $template['']));

        $template['_updated'] = (boolean) $update->rowCount();
        return $template;

    }catch(Exception $e) {
        throw new CoreException(tr('templates_update(): Failed'), $e);
    }
}



/*
 * Return data for the specified template
 *
 * This function returns information for the specified template. The template can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @package templates
 * @see sql_simple_get()
 * @version 2.5.38: Added function and documentation
 *
 * @param params $params The parameters for this get request
 * @param string $params[columns] The specific column that has to be returned
 * @param string $params[filters]
 * @param string $params[joins]
 * @return mixed The template data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified template does not exist, NULL will be returned.
 */
function templates_get($params) {
    try {
        array_params($params, 'seotemplate', 'id');

        $params['table']   = 'templates';

        array_default($params, 'filters', array('id'      => $params['id'],
                                                'seoname' => $params['seotemplate'],
                                                'status'  => null));

        array_default($params, 'joins'  , array('LEFT JOIN `geo_countries`
                                                 ON        `geo_countries`.`id` = `customers`.`countries_id`',

                                                'LEFT JOIN `categories`
                                                 ON        `categories`.`id`    = `customers`.`categories_id`'));

        array_default($params, 'columns', 'templates.id,
                                           templates.createdon,
                                           templates.created_by,
                                           templates.meta_id,
                                           templates.status,
                                           templates.name,
                                           templates.seoname,
                                           templates.description,

                                           categories.name       AS category,
                                           categories.seoname    AS seocategory,

                                           geo_countries.name    AS country,
                                           geo_countries.seoname AS seocountry');

        return sql_simple_get($params);

    }catch(Exception $e) {
        throw new CoreException(tr('templates_get(): Failed'), $e);
    }
}



/*
 * Return a list of all available templates filtered by
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @see sql_simple_list()
 * @package templates
 * @version 2.5.38: Added function and documentation
 *
 * @param params $params The list parameters
 * @return mixed The list of available templates
 */
function templates_list($params) {
    try {
        array_params($params, 'status');

        $params['table'] = 'templates';

        array_default($params, 'columns', 'templates.seoname,templates.name');

        array_default($params, 'filters', array('templates.seoname' => $params['status'],
                                                'foobar.status'     => 'available',
                                                'foobar.status'     => null));

        array_default($params, 'joins'  , array('LEFT JOIN `foobar`
                                                 ON        `foobar`.`id` = `templates`.`foobar_id`'));

        array_default($params, 'orderby', array('templates' => 'asc'));

        return sql_simple_list($params);

    }catch(Exception $e) {
        throw new CoreException(tr('templates_list(): Failed'), $e);
    }
}



/*
 * Return HTML for a templates select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available templates
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @package templates
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
 * @return string HTML for a templates select box within the specified parameters
 */
function templates_select($params = null) {
    try {
        Arrays::ensure($params);
        array_default($params, 'name'      , 'seotemplate');
        array_default($params, 'class'     , 'form-control');
        array_default($params, 'selected'  , null);
        array_default($params, 'autosubmit', true);
        array_default($params, 'parents_id', null);
        array_default($params, 'status'    , null);
        array_default($params, 'remove'    , null);
        array_default($params, 'empty'     , tr('No templates available'));
        array_default($params, 'none'      , tr('Select a template'));
        array_default($params, 'orderby'   , '`name`');

        $execute = array();

        if ($params['remove']) {
            if (count(Arrays::force($params['remove'])) == 1) {
                /*
                 * Filter out only one entry
                 */
                $where[] = ' `id` != :id ';
                $execute[':id'] = $params['remove'];

            } else {
                /*
                 * Filter out multiple entries
                 */
                $in      = sql_in(Arrays::force($params['remove']));
                $where[] = ' `id` NOT IN ('.implode(', ', array_keys($in)).') ';
                $execute = array_merge($execute, $in);
            }
        }

        if ($params['parents_id']) {
            $where[] = ' `parents_id` = :parents_id ';
            $execute[':parents_id'] = $params['parents_id'];

        } else {
            $where[] = ' `parents_id` IS NULL ';
        }

        if ($params['status'] !== false) {
            $where[] = ' `status` '.sql_is($params['status'], ':status');
            $execute[':status'] = $params['status'];
        }

        if (empty($where)) {
            $where = '';

        } else {
            $where = ' WHERE '.implode(' AND ', $where).' ';
        }

        $query              = 'SELECT `seoname`, `name` FROM `templates` '.$where.' ORDER BY `name`';
        $params['resource'] = sql_query($query, $execute);
        $return             = html_select($params);

        return $return;

    }catch(Exception $e) {
        throw new CoreException(tr('templates_select(): Failed'), $e);
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
 * @package templates
 * @see templates_install()
 * @see date_convert() Used to convert the sitemap entry dates
 * @table: `template`
 * @note: This is a note
 * @version 2.5.38: Added function and documentation
 * @example
 * code
 * $result = templates_function(array('foo' => 'bar'));
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
function templates_function($params) {
    try {

    }catch(Exception $e) {
        throw new CoreException(tr('templates_function(): Failed'), $e);
    }
}
