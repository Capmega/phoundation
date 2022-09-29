<?php
/*
 * Inventories library
 *
 * This library contains functions for the inventory system
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package template
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
 * @package inventories
 *
 * @return void
 */
function inventories_library_init() {
    try{
        load_config('inventories');

    }catch(Exception $e) {
        throw new CoreException('inventories_library_init(): Failed', $e);
    }
}



/*
 * Validate the specified inventory entry
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inventories
 *
 * @param array $item The inventory entry to validate
 * @return array The validated and cleaned $item array
 */
function inventories_validate($item, $reload_only = false) {
    try{
        load_libs('validate,seo');

        $v = new ValidateForm($item, 'seocategory,seocompany,seobranch,seodepartment,seoemployee,seocustomer,seoproject,items_id,code,set_with,serial,description');

        /*
         * Validate category
         */
        if($item['seocategory']) {
            load_libs('categories');
            $item['categories_id'] = categories_get($item['seocategory'], 'id');

            if(!$item['categories_id']) {
                $v->setError(tr('Specified category does not exist'));
            }

        } else {
            $item['categories_id'] = null;

            if(!$reload_only) {
                $v->setError(tr('No category specified'));
            }
        }

        /*
         * Validate company
         */
        if($item['seocompany']) {
            load_libs('companies');
            $item['companies_id'] = companies_get($item['seocompany'], 'id');

            if(!$item['companies_id']) {
                $v->setError(tr('Specified company does not exist'));
            }

            /*
             * Validate branch
             */
            if($item['seobranch']) {
                load_libs('companies');
                $item['branches_id'] = companies_get_branch($item['companies_id'], $item['seobranch'], 'id');

                if(!$item['branches_id']) {
                    $v->setError(tr('Specified branch does not exist'));
                }

            } else {
                $item['branches_id']    = null;
                $item['departments_id'] = null;
                $item['employees_id']   = null;

                if(!$reload_only) {
                    $v->setError(tr('No branch specified'));
                }
            }

            /*
             * Validate department
             */
            if($item['seodepartment']) {
                load_libs('companies');
                $item['departments_id'] = companies_get_department($item['companies_id'], $item['branches_id'], $item['seodepartment'], 'id');

                if(!$item['departments_id']) {
                    $v->setError(tr('Specified department does not exist'));
                }

            } else {
                $item['departments_id'] = null;
                $item['employees_id']   = null;
            }

            /*
             * Validate employee
             */
            if($item['seoemployee']) {
                load_libs('companies');
                $item['employees_id'] = companies_get_employee(array('columns' => 'id',
                                                                     'filters' => array('employees.companies_id'   => $item['companies_id'],
                                                                                        'employees.branches_id'    => $item['branches_id'],
                                                                                        'employees.departments_id' => $item['departments_id'],
                                                                                        'employees.seoname'        => $item['seoemployee'])));

                if(!$item['employees_id']) {
                    $v->setError(tr('Specified employee does not exist'));
                }

            } else {
                $item['employees_id'] = null;
            }

        } else {
            $item['companies_id']   = null;
            $item['branches_id']    = null;
            $item['departments_id'] = null;
            $item['employees_id']   = null;
        }

        /*
         * Validate customer
         */
        if($item['seocustomer']) {
            load_libs('customers');
            $item['customers_id'] = customers_get(array('columns' => 'id',
                                                        'filters' => array('seoname' => $item['seocustomer'])));

            if(!$item['customers_id']) {
                $v->setError(tr('Specified customer does not exist'));
            }

        } else {
            $item['customers_id'] = null;

        }

        /*
         * Validate project
         */
        if($item['seoproject']) {
            load_libs('projects');
            $item['projects_id'] = projects_get(array('column'  => 'id',
                                                      'filters' => array('seoname' => $item['seoproject'])));

            if(!$item['projects_id']) {
                $v->setError(tr('Specified project does not exist'));
            }

        } else {
            $item['projects_id'] = null;
        }

        /*
         * Validate item
         */
        if($item['items_id']) {
            $exist = inventories_get_item($item['items_id'], $item['categories_id'], 'id');

            if(!$exist) {
                $item['items_id'] = null;

                if(!$reload_only) {
                    $v->setError(tr('Specified item does not exist'));
                }
            }

        } else {
            $item['items_id'] = null;

            if(!$reload_only) {
                $v->setError(tr('No item specified'));
            }
        }

        $v->isValid();

        if($reload_only) {
            return $item;
        }

        /*
         * Validate code
         */
        if($item['code']) {
            $v->isNotEmpty ($item['code']    , tr('Please specify an inventory entry code'));
            $v->hasMinChars($item['code'],  2, tr('Please ensure the inventory entry code has at least 2 characters'));
            $v->hasMaxChars($item['code'], 64, tr('Please ensure the inventory entry code has less than 64 characters'));

            if(is_numeric(substr($item['code'], 0, 1))) {
                $v->setError(tr('Please ensure that the inventory entry code does not start with a number'));
            }

            $v->hasMaxChars($item['code'], 64, tr('Please ensure the inventory entry code has less than 64 characters'));

            $item['code'] = str_clean($item['code']);

        } else {
            $item['code'] = null;
        }

        /*
         * Validate serial
         */
        if($item['serial']) {
            $v->isNotEmpty ($item['serial']    , tr('Please specify an inventory entry serial code'));
            $v->hasMinChars($item['serial'],  2, tr('Please ensure the inventory entry serial code has at least 2 characters'));
            $v->hasMaxChars($item['serial'], 64, tr('Please ensure the inventory entry serial code has less than 64 characters'));
            $item['serial'] = str_clean($item['serial']);

        } else {
            $item['serial'] = null;
        }

        /*
         * Validate description
         */
        if(empty($item['description'])) {
            $item['description'] = null;

        } else {
            $v->hasMinChars($item['description'],   16, tr('Please ensure the inventory entry description has at least 16 characters'));
            $v->hasMaxChars($item['description'], 2047, tr('Please ensure the inventory entry description has less than 2047 characters'));

            $item['description'] = str_clean($item['description']);
        }

        /*
         * Validate set_with
         */
        if($item['set_with']) {
            foreach(Arrays::force($item['set_with']) as $code) {
                $exist = sql_get('SELECT `id` FROM `inventories` WHERE `code` = :code', true, array(':code' => $code));

                if(!$exist) {
                    $v->setError(tr('Please ensure the specified set code(s) are valid'));

                } elseif($exist == isset_get($item['id'])) {
                    $v->setError(tr('The entry cannot be in a set with itself'));
                }
            }

        } else {
            $item['set_with'] = null;
        }

        /*
         * All valid?
         */
        $v->isValid();

        return $item;

    }catch(Exception $e) {
        throw new CoreException('inventories_validate(): Failed', $e);
    }
}



/*
 * Return HTML for a companies select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available companies
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inventories
 *
 * @param array $params The parameters required
 * @param $params[name]
 * @param $params[class]
 * @param $params[extra]
 * @param $params[tabindex]
 * @param $params[empty]
 * @param $params[none]
 * @param $params[selected]
 * @param $params[categories_id]
 * @param $params[status]
 * @param $params[orderby]
 * @param $params[resource]
 * @return string HTML for a companies select box within the specified parameters
 */
function inventories_select($params) {
    try{
        Arrays::ensure($params);
        array_default($params, 'name'         , 'seocompany');
        array_default($params, 'selected'     , null);
        array_default($params, 'category'     , null);
        array_default($params, 'categories_id', null);
        array_default($params, 'status'       , null);
        array_default($params, 'remove'       , null);
        array_default($params, 'empty'        , tr('No companies available'));
        array_default($params, 'none'         , tr('Select a company'));
        array_default($params, 'orderby'      , '`name`');

        if($params['category']) {
            load_libs('categories');
            $params['categories_id'] = categories_get($params['category'], 'id');

            if(!$params['categories_id']) {
                throw new CoreException(tr('inventories_select(): The reqested category ":category" does exist, but is deleted', array(':category' => $params['category'])), 'deleted');
            }
        }

        $execute = array();

        if($params['categories_id']) {
            $where[] = ' `categories_id` = :categories_id ';
            $execute[':categories_id'] = $params['categories_id'];
        }

        if($params['status'] !== false) {
            $where[] = ' `status` '.sql_is($params['status'], ':status');
            $execute[':status'] = $params['status'];
        }

        if(empty($where)) {
            $where = '';

        } else {
            $where = ' WHERE '.implode(' AND ', $where).' ';
        }

        $query              = 'SELECT `seoname`, `name` FROM `inventories` '.$where.' ORDER BY `name`';
        $params['resource'] = sql_query($query, $execute);
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('inventories_select(): Failed', $e);
    }
}



/*
 * Return HTML for an inventories auto suggest
 *
 * This function will generate HTML for an HTML auto suggest <input>
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inventories
 *
 * @param array $params The parameters required
 * @param $params[name]
 * @param $params[class]
 * @param $params[extra]
 * @param $params[tabindex]
 * @param $params[empty]
 * @param $params[none]
 * @param $params[selected]
 * @param $params[categories_id]
 * @param $params[status]
 * @param $params[orderby]
 * @param $params[resource]
 * @return string HTML for a companies select box within the specified parameters
 */
function inventories_autosuggest($params) {
    try{
        Arrays::ensure($params);
        array_default($params, 'name'         , 'seocompany');
        array_default($params, 'class'        , 'form-control');
        array_default($params, 'selected'     , null);
        array_default($params, 'category'     , null);
        array_default($params, 'categories_id', null);
        array_default($params, 'status'       , null);
        array_default($params, 'remove'       , null);
        array_default($params, 'empty'        , tr('No companies available'));
        array_default($params, 'none'         , tr('Select a company'));
        array_default($params, 'tabindex'     , 0);
        array_default($params, 'extra'        , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby'      , '`name`');

        return html_autosuggest($params);

    }catch(Exception $e) {
        throw new CoreException('inventories_autosuggest(): Failed', $e);
    }
}



/*
 * Return data for the specified company
 *
 * This function returns information for the specified company. The company can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inventories
 *
 * @param mixed $item The required company. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The company data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified company does not exist, NULL will be returned.
 */
function inventories_get($entry, $column = null, $status = null) {
    try{
        if(is_natural($entry)) {
            $where[] = ' `inventories`.`id` = :id ';
            $execute[':id'] = $entry;

        } elseif(is_string($entry)) {
            $where[] = ' `inventories`.`code` = :code ';
            $execute[':code'] = $entry;

        } else {
            throw new CoreException(tr('inventories_get(): Specified entry ":entry" is invalid, it should be natural or string', array(':entry' => $entry)), 'invalid');
        }

        if($status !== false) {
            $execute[':status'] = $status;
            $where[] = ' `inventories`.`status` '.sql_is($status, ':status');
        }

        $where   = ' WHERE '.implode(' AND ', $where).' ';

        if($column) {
            $retval = sql_get('SELECT `'.$column.'` FROM `inventories` '.$where, true, $execute);

        } else {
            $retval = sql_get('SELECT    `inventories`.`id`,
                                         `inventories`.`createdon`,
                                         `inventories`.`createdby`,
                                         `inventories`.`meta_id`,
                                         `inventories`.`status`,
                                         `inventories`.`categories_id`,
                                         `inventories`.`customers_id`,
                                         `inventories`.`projects_id`,
                                         `inventories`.`items_id`,
                                         `inventories`.`companies_id`,
                                         `inventories`.`branches_id`,
                                         `inventories`.`departments_id`,
                                         `inventories`.`employees_id`,
                                         `inventories`.`code`,
                                         `inventories`.`serial`,
                                         `inventories`.`set_with`,
                                         `inventories`.`description`,

                                         `inventories_items`.`providers_id`,

                                         `inventories_items`.`brand`    AS `brand`,
                                         `inventories_items`.`brand`    AS `seobrand`,

                                         `inventories_items`.`model`    AS `model`,
                                         `inventories_items`.`seomodel` AS `seomodel`,

                                         `categories`.`name`            AS `category`,
                                         `categories`.`seoname`         AS `seocategory`,

                                         `providers`.`name`             AS `provider`,
                                         `providers`.`seoname`          AS `seoprovider`,

                                         `customers`.`name`             AS `customer`,
                                         `customers`.`seoname`          AS `seocustomer`,

                                         `projects`.`name`              AS `project`,
                                         `projects`.`seoname`           AS `seoproject`,

                                         `companies`.`name`             AS `company`,
                                         `companies`.`seoname`          AS `seocompany`,

                                         `branches`.`name`              AS `branch`,
                                         `branches`.`seoname`           AS `seobranch`,

                                         `departments`.`name`           AS `department`,
                                         `departments`.`seoname`        AS `seodepartment`,

                                         `employees`.`name`             AS `employee`,
                                         `employees`.`seoname`          AS `seoemployee`

                               FROM      `inventories`

                               LEFT JOIN `categories`
                               ON        `categories`.`id`        = `inventories`.`categories_id`

                               LEFT JOIN `inventories_items`
                               ON        `inventories_items`.`id` = `inventories`.`items_id`

                               LEFT JOIN `customers`
                               ON        `customers`.`id`         = `inventories`.`customers_id`

                               LEFT JOIN `projects`
                               ON        `projects`.`id`          = `inventories`.`projects_id`

                               LEFT JOIN `providers`
                               ON        `providers`.`id`         = `inventories_items`.`providers_id`

                               LEFT JOIN `companies`
                               ON        `companies`.`id`         = `inventories`.`companies_id`

                               LEFT JOIN `branches`
                               ON        `branches`.`id`          = `inventories`.`branches_id`

                               LEFT JOIN `departments`
                               ON        `departments`.`id`       = `inventories`.`departments_id`

                               LEFT JOIN `employees`
                               ON        `employees`.`id`         = `inventories`.`employees_id` '.$where, $execute);
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('inventories_get(): Failed', $e);
    }
}



/*
 * Validate the specified branch
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inventories
 *
 * @param array $branch The branch to validate
 * @return array The validated and cleaned $branch array
 */
function inventories_validate_item($item, $reload_only = false) {
    try{
        load_libs('validate,seo');
        $v = new ValidateForm($item, 'seocategory,seoprovider,brand,model,code,description');

        /*
         * Validate category
         */
        if($item['seocategory']) {
            load_libs('categories');
            $item['categories_id'] = categories_get($item['seocategory'], 'id');

            if(!$item['categories_id']) {
                $v->setError(tr('Specified category does not exist'));
            }

        } else {
            $item['categories_id'] = null;

            if(!$reload_only) {
                $v->setError(tr('No category specified'));
            }
        }

        /*
         * Validate provider
         */
        if($item['seoprovider']) {
            load_libs('providers');
            $item['providers_id'] = providers_get($item['seoprovider'], 'id');

            if(!$item['providers_id']) {
                $v->setError(tr('Specified provider does not exist'));
            }

        } else {
            $item['providers_id'] = null;
        }

        $v->isValid();

        if($reload_only) {
            return $item;
        }

        /*
         * Validate brand
         */
        $v->isNotEmpty ($item['brand']    , tr('Please specify a item brand'));
        $v->hasMinChars($item['brand'],  2, tr('Please ensure the item brand has at least 2 characters'));
        $v->hasMaxChars($item['brand'], 64, tr('Please ensure the item brand has less than 64 characters'));

        if(is_numeric(substr($item['brand'], 0, 1))) {
            $v->setError(tr('Please ensure that the item brand does not start with a number'));
        }

        $v->hasMaxChars($item['brand'], 64, tr('Please ensure the item brand has less than 64 characters'));

        $item['brand']    = str_clean($item['brand']);
        $item['seobrand'] = seo_string($item['brand']);

        /*
         * Validate model
         */
        $v->isNotEmpty ($item['model']    , tr('Please specify a item model'));
        $v->hasMinChars($item['model'],  2, tr('Please ensure the item model has at least 2 characters'));
        $v->hasMaxChars($item['model'], 64, tr('Please ensure the item model has less than 64 characters'));

        if(is_numeric(substr($item['model'], 0, 1))) {
            $v->setError(tr('Please ensure that the item model does not start with a number'));
        }

        $v->hasMaxChars($item['model'], 64, tr('Please ensure the item model has less than 64 characters'));

        $item['model']    = str_clean($item['model']);
        $item['seomodel'] = seo_string($item['model']);

        /*
         * Validate code pattern
         */
        $v->isNotEmpty ($item['code']    , tr('Please specify a item code'));
        $v->hasMinChars($item['code'],  2, tr('Please ensure the item code has at least 2 characters'));
        $v->hasMaxChars($item['code'], 64, tr('Please ensure the item code has less than 64 characters'));

        if(is_numeric(substr($item['code'], 0, 1))) {
            $v->setError(tr('Please ensure that the item code does not start with a number'));
        }

        $item['code'] = trim(strtoupper($item['code']));

        $v->isRegex($item['code'], '/[A-Z0-9]+#/', tr('Please ensure the item code has a valid format. Format should be in the form of the expression "[A-Z0-9]+#"'));

        /*
         * Validate description
         */
        if(empty($item['description'])) {
            $item['description'] = null;

        } else {
            $v->hasMinChars($item['description'],   16, tr('Please ensure the item description has at least 16 characters'));
            $v->hasMaxChars($item['description'], 2047, tr('Please ensure the item description has less than 2047 characters'));

            $item['description'] = str_clean($item['description']);
        }

        /*
         * All valid?
         */
        $v->isValid();

        return $item;

    }catch(Exception $e) {
        throw new CoreException('inventories_validate_item(): Failed', $e);
    }
}



/*
 * Return HTML for a companies select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available companies
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inventories
 *
 * @param array $params The parameters required
 * @param $params name
 * @param $params class
 * @param $params extra
 * @param $params tabindex
 * @param $params empty
 * @param $params none
 * @param $params selected
 * @param $params categories_id
 * @param $params status
 * @param $params orderby
 * @param $params resource
 * @return string HTML for a companies select box within the specified parameters
 */
function inventories_select_item($params = null) {
    try{
        Arrays::ensure($params);
        array_default($params, 'name'         , 'items_id');
        array_default($params, 'class'        , 'form-control');
        array_default($params, 'selected'     , null);
        array_default($params, 'seocategory'  , null);
        array_default($params, 'categories_id', null);
        array_default($params, 'status'       , null);
        array_default($params, 'remove'       , null);
        array_default($params, 'empty'        , tr('No items available'));
        array_default($params, 'none'         , tr('Select an item'));
        array_default($params, 'tabindex'     , 0);
        array_default($params, 'extra'        , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby'      , '`name`');

        if($params['seocategory']) {
            $params['categories_id'] = inventories_get($params['seocategory'], 'id');

            if(!$params['categories_id']) {
                throw new CoreException(tr('inventories_select_items(): The specified category ":category" does not exist or is not available', array(':category' => $params['category'])), 'not-exists');
            }
        }

        $execute = array();

        /*
         * Only show branches per office
         */
        if($params['categories_id']) {
            $where[] = ' `categories_id` = :categories_id ';
            $execute[':categories_id'] = $params['categories_id'];

            if($params['status'] !== false) {
                $where[] = ' `status` '.sql_is($params['status'], ':status');
                $execute[':status'] = $params['status'];
            }

            if(empty($where)) {
                $where = '';

            } else {
                $where = ' WHERE '.implode(' AND ', $where).' ';
            }

            $query              = 'SELECT `id`, CONCAT(`brand`, " ", `model`) AS `name` FROM `inventories_items` '.$where.' ORDER BY `name`';
            $params['resource'] = sql_query($query, $execute);
        }

        $retval = html_select($params);

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('inventories_select_item(): Failed', $e);
    }
}



/*
 * Return data for the specified item
 *
 * This function returns information for the specified item. The item can be specified by id only, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inventories
 *
 * @param mixed $branch The required item. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The item data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified company does not exist, NULL will be returned.
 */
function inventories_get_item($items_id, $category = null, $column = null, $status = null) {
    try{
        /*
         * Filter by specified id
         */
        if(!$items_id) {
            throw new CoreException(tr('inventories_get_item(): No modelspecified'), 'not-specified');
        }

        $where[] = ' `inventories_items`.`id` = :id ';
        $execute[':id'] = $items_id;

        /*
         * Optionally filter by category as well
         */
        if($category) {
            load_libs('categories');
            $categories_id = categories_get($category, 'id');

            if(!$categories_id) {
                throw new CoreException(tr('Specified category ":category" does not exist', array(':category' => $category)), 'not-exists');
            }

            $where[] = ' `inventories_items`.`categories_id` = :categories_id ';
            $execute[':categories_id'] = $categories_id;
        }

        /*
         * Filter by specified status
         */
        if($status !== false) {
            $execute[':status'] = $status;
            $where[] = ' `inventories_items`.`status` '.sql_is($status, ':status');
        }

        $where   = ' WHERE '.implode(' AND ', $where).' ';

        if($column) {
            $retval = sql_get('SELECT `inventories_items`.`'.$column.'`

                               FROM   `inventories_items` '.$where, true, $execute);

        } else {
            $retval = sql_get('SELECT    `inventories_items`.`id`,
                                         `inventories_items`.`createdon`,
                                         `inventories_items`.`createdby`,
                                         `inventories_items`.`meta_id`,
                                         `inventories_items`.`status`,
                                         `inventories_items`.`categories_id`,
                                         `inventories_items`.`providers_id`,
                                         `inventories_items`.`brand`,
                                         `inventories_items`.`seobrand`,
                                         `inventories_items`.`model`,
                                         `inventories_items`.`seomodel`,
                                         `inventories_items`.`code`,
                                         `inventories_items`.`description`,

                                         `providers`.`name`     AS `provider`,
                                         `providers`.`seoname`  AS `seoprovider`,

                                         `categories`.`name`    AS `category`,
                                         `categories`.`seoname` AS `seocategory`

                               FROM      `inventories_items`

                               LEFT JOIN `categories`
                               ON        `categories`.`id` = `inventories_items`.`categories_id`

                               LEFT JOIN `providers`
                               ON        `providers`.`id` = `inventories_items`.`providers_id` '.$where, $execute);
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('inventories_get_item(): Failed', $e);
    }
}



/*
 * Return the default code for the specified item
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inventories
 *
 * @param numeric $items_id
 * @return string
 */
function inventories_get_default_code($items_id, $companies_id) {
    try{
        $item = sql_get('SELECT `id`, `code` FROM `inventories_items` WHERE `id` = :id', array(':id' => $items_id));

        if(!$item) {
            throw new CoreException(tr('inventories_get_default_code(): The specified item ":id" does not exist', array(':id' => $items_id)), 'not-exists');
        }

        if(!$item['code']) {
            throw new CoreException(tr('inventories_get_default_code(): The specified item ":id" has no code specified', array(':id' => $items_id)), 'not-available');
        }

        if(strstr('#', $item['code'])) {
            return $item['code'];
        }

        $code    = substr($item['code'], 0, -1);
        $highest = sql_get('SELECT `code` FROM `inventories` WHERE `companies_id` = :companies_id AND SUBSTR(`code`, 1, '.strlen($code).') = :code ORDER BY `code` DESC LIMIT 1', true, array(':companies_id' => $companies_id, ':code' => $code));

        if(!$highest) {
            return str_replace('#', '1', $item['code']);
        }

        $highest = str_replace($code, '', $highest);
        $highest++;

        return $code.$highest;

    }catch(Exception $e) {
        throw new CoreException('inventories_get_default_code(): Failed', $e);
    }
}
?>