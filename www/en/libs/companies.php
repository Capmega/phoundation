<?php
/*
 * companies library
 *
 * This library contains functions for the companies management system
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
 * @package companies
 *
 * @return void
 */
function companies_library_init() {
    global $_CONFIG;

    try {
        load_config('companies');

        //if (empty($_GET['seocompany']) and empty($_POST['seocompany'])) {
        //    $_GET['seocompany']  = $_CONFIG['companies']['default'];
        //    $_POST['seocompany'] = $_CONFIG['companies']['default'];
        //}

    }catch(Exception $e) {
        throw new CoreException('companies_library_init(): Failed', $e);
    }
}



/*
 * Validate the specified company
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package companies
 *
 * @param array $company The company to validate
 * @return array The validated and cleaned $company array
 */
function companies_validate($company) {
    try {
        load_libs('validate,seo,customers,providers');

        $v = new ValidateForm($company, 'name,seocategory,seocustomer,seoprovider,description');

        /*
         * Validate category
         */
        if ($company['seocategory']) {
            load_libs('categories');
            $company['categories_id'] = categories_get($company['seocategory'], 'id');

            if (!$company['categories_id']) {
                $v->setError(tr('Specified category does not exist'));
            }

        } else {
            $company['categories_id'] = null;
        }

        /*
         * Validate customer
         */
        if ($company['seocustomer']) {
            load_libs('customers');
            $company['customers_id'] = customers_get(array('columns' => 'id',
                                                           'filters' => array('seoname' => $company['seocustomer'])));

            if (!$company['customers_id']) {
                $v->setError(tr('Specified customer does not exist'));
            }

        } else {
            $company['customers_id'] = null;
        }

        /*
         * Validate provider
         */
        if ($company['seoprovider']) {
            load_libs('providers');
            $company['providers_id'] = providers_get(array('columns' => 'id',
                                                           'filters' => array('seoname' => $company['seoprovider'])));

            if (!$company['providers_id']) {
                $v->setError(tr('Specified provider does not exist'));
            }

        } else {
            $company['providers_id'] = null;
        }

        /*
         * Validate name
         */
        $v->isNotEmpty ($company['name']    , tr('Please specify a company name'));
        $v->hasMinChars($company['name'],  2, tr('Please ensure the company name has at least 2 characters'));
        $v->hasMaxChars($company['name'], 64, tr('Please ensure the company name has less than 64 characters'));

        if (is_numeric(substr($company['name'], 0, 1))) {
            $v->setError(tr('Please ensure that the company name does not start with a number'));
        }

        $v->hasMaxChars($company['name'], 64, tr('Please ensure the company name has less than 64 characters'));

        $company['name'] = str_clean($company['name']);

        /*
         * Does the company already exist within the specified categories_id?
         */
        $exists = sql_get('SELECT `id` FROM `companies` WHERE `categories_id` '.sql_is(isset_get($company['categories_id']), ':categories_id').' AND `name` = :name AND `id` '.sql_is(isset_get($company['id']), ':id', true), true, array(':name' => $company['name'], ':id' => isset_get($company['id']), ':categories_id' => isset_get($company['categories_id'])));

        if ($exists) {
            if ($company['categories_id']) {
                $v->setError(tr('The company name ":company" already exists in the category company ":category"', array(':category' => not_empty($company['seocategory'], $company['categories_id']), ':company' => $company['name'])));

            } else {
                $v->setError(tr('The company name ":company" already exists', array(':company' => $company['name'])));
            }
        }

        /*
         * Validate description
         */
        if (empty($company['description'])) {
            $company['description'] = null;

        } else {
            $v->hasMinChars($company['description'],   16, tr('Please ensure the company description has at least 16 characters'));
            $v->hasMaxChars($company['description'], 2047, tr('Please ensure the company description has less than 2047 characters'));

            $company['description'] = str_clean($company['description']);
        }

        /*
         * All valid?
         */
        $v->isValid();

        /*
         * Set seoname
         */
        $company['seoname'] = seo_unique($company['name'], 'companies', isset_get($company['id']));

      return $company;

    }catch(Exception $e) {
        throw new CoreException('companies_validate(): Failed', $e);
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
 * @package companies
 *
 * @param array $params The parameters required
 * @param $params name
 * @param $params class
 * @param $params empty
 * @param $params none
 * @param $params selected
 * @param $params categories_id
 * @param $params status
 * @param $params orderby
 * @param $params resource
 * @return string HTML for a companies select box within the specified parameters
 */
function companies_select($params = null) {
    global $_CONFIG;

    try {
        Arrays::ensure($params);
        array_default($params, 'name'         , 'seocompany');
        array_default($params, 'class'        , 'form-control');
        array_default($params, 'selected'     , null);
        array_default($params, 'seocategory'  , null);
        array_default($params, 'categories_id', null);
        array_default($params, 'status'       , null);
        array_default($params, 'remove'       , null);
        array_default($params, 'autosubmit'   , true);
        array_default($params, 'empty'        , tr('No companies available'));
        array_default($params, 'none'         , tr('Select a company'));
        array_default($params, 'orderby'      , '`name`');

        if ($params['seocategory']) {
            load_libs('categories');
            $params['categories_id'] = categories_get($params['seocategory'], 'id');

            if (!$params['categories_id']) {
                throw new CoreException(tr('companies_select(): The reqested category ":category" does exist, but is deleted', array(':category' => $params['seocategory'])), 'deleted');
            }
        }

        $execute = array();

        if ($params['categories_id'] !== false) {
            $where[] = ' `categories_id` '.sql_is($params['categories_id'], ':categories_id');
            $execute[':categories_id'] = $params['categories_id'];
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

        if ($params['selected'] === null) {
            /*
             * Select the default company
             */
            if ($_CONFIG['companies']['defaults']['company']) {
                $params['selected'] = companies_get($_CONFIG['companies']['defaults']['company'], 'seoname');

                if (!$params['selected']) {
                    /*
                     * Selected default company does not exist, notify
                     */
                    notify(new CoreException(tr('companies_select(): Specified default company ":company" in $_CONFIG[companies][defaults][company] does not exist', array(':company' => $_CONFIG['companies']['defaults']['company'])), 'not-exist'));
                }
            }
        }

        $query              = 'SELECT `seoname`, `name` FROM `companies` '.$where.' ORDER BY `name`';
        $params['resource'] = sql_query($query, $execute);
        $return             = html_select($params);

        return $return;

    }catch(Exception $e) {
        throw new CoreException('companies_select(): Failed', $e);
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
 * @see sql_simple_get()
 * @package companies
 *
 * @param mixed $company The required company. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The company data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified company does not exist, NULL will be returned.
 */
function companies_get($params, $column = null, $status = null) {
    try {
        array_params($params, 'seoname', 'id');

        array_default($params, 'filters', array('companies.id'      => $params['id'],
                                                'companies.seoname' => $params['seoname']));

        array_default($params, 'joins'  , array('LEFT JOIN `categories`
                                                 ON        `categories`.`id` = `companies`.`categories_id`

                                                 LEFT JOIN `customers`
                                                 ON        `customers`.`id`  = `companies`.`customers_id`

                                                 LEFT JOIN `providers`
                                                 ON        `providers`.`id`  = `companies`.`providers_id`'));

        array_default($params, 'columns', 'companies.id,
                                           companies.createdon,
                                           companies.created_by,
                                           companies.meta_id,
                                           companies.status,
                                           companies.categories_id,
                                           companies.customers_id,
                                           companies.providers_id,
                                           companies.name,
                                           companies.seoname,
                                           companies.description,

                                           categories.name    AS category,
                                           categories.seoname AS seocategory,

                                           customers.name    AS customer,
                                           customers.seoname AS seocustomer,

                                           providers.name    AS provider,
                                           providers.seoname AS seoprovider');

        $params['table']     = 'companies';
        $params['connector'] = 'core';

        return sql_simple_get($params);

    }catch(Exception $e) {
        throw new CoreException('companies_get(): Failed', $e);
    }
}



/*
 * Return a list of all available companies
 *
 * This function wraps sql_simple_list() and supports all its options, like columns selection, filtering, ordering, and execution method
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @customer Function reference
 * @package customers
 * @see sql_simple_list()
 * @version 2.6.27: Added function and documentation
 *
 * @param params $params The list parameters
 * @return mixed The list of available customers
 */
function companies_list($params) {
    try {
        Arrays::ensure($params);
        array_default($params, 'columns', 'seoname,name');
        array_default($params, 'orderby', array('name' => 'asc'));

        $params['table']     = 'companies';
        $params['connector'] = 'core';

        return sql_simple_list($params);

    }catch(Exception $e) {
        throw new CoreException('companies_list(): Failed', $e);
    }
}



/*
 * Validate the specified branch
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package companies
 *
 * @param array $branch The branch to validate
 * @return array The validated and cleaned $branch array
 */
function companies_validate_branch($branch, $reload_only = false) {
    try {
        load_libs('validate,seo');

        $v = new ValidateForm($branch, 'name,seocompany,description');

        /*
         * Validate company
         */
        if ($branch['seocompany']) {
            $branch['companies_id'] = companies_get($branch['seocompany'], 'id');

            if (!$branch['companies_id']) {
                $v->setError(tr('Specified company does not exist'));
            }

        } else {
            $branch['companies_id'] = null;

            if (!$reload_only) {
                $v->setError(tr('No company specified'));
            }
        }

        $v->isValid();

        if ($reload_only) {
            return $branch;
        }

        /*
         * Validate name
         */
        $v->isNotEmpty ($branch['name']    , tr('Please specify a branch name'));
        $v->hasMinChars($branch['name'],  2, tr('Please ensure the branch name has at least 2 characters'));
        $v->hasMaxChars($branch['name'], 64, tr('Please ensure the branch name has less than 64 characters'));

        if (is_numeric(substr($branch['name'], 0, 1))) {
            $v->setError(tr('Please ensure that the branch name does not start with a number'));
        }

        $v->hasMaxChars($branch['name'], 64, tr('Please ensure the branch name has less than 64 characters'));

        $branch['name'] = str_clean($branch['name']);

        /*
         * Does the branch already exist within the specified companies_id?
         */
        $exists = sql_get('SELECT `id` FROM `branches` WHERE `companies_id` '.sql_is(isset_get($branch['companies_id']), ':companies_id').' AND `name` = :name AND `id` '.sql_is(isset_get($branch['id']), ':id', true), true, array(':name' => $branch['name'], ':id' => isset_get($branch['id']), ':companies_id' => isset_get($branch['companies_id'])));

        if ($exists) {
            $v->setError(tr('The branch name ":branch" already exists', array(':branch' => $branch['name'])));
        }

        /*
         * Validate description
         */
        if (empty($branch['description'])) {
            $branch['description'] = null;

        } else {
            $v->hasMinChars($branch['description'],   16, tr('Please ensure the branch description has at least 16 characters'));
            $v->hasMaxChars($branch['description'], 2047, tr('Please ensure the branch description has less than 2047 characters'));

            $branch['description'] = str_clean($branch['description']);
        }

        /*
         * All valid?
         */
        $v->isValid();

        /*
         * Set seoname
         */
        $branch['seoname'] = seo_unique($branch['name'], 'branches', isset_get($branch['id']));

      return $branch;

    }catch(Exception $e) {
        throw new CoreException('companies_validate_branch(): Failed', $e);
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
 * @package companies
 * @see html_select()
 *
 * @param array $params The parameters required
 * @param $params name
 * @param $params class
 * @param $params empty
 * @param $params none
 * @param $params selected
 * @param $params categories_id
 * @param $params status
 * @param $params orderby
 * @param $params resource
 * @return string HTML for a companies select box within the specified parameters
 */
function companies_select_branch($params = null) {
    global $_CONFIG;

    try {
        Arrays::ensure($params);
        array_default($params, 'name'        , 'seobranch');
        array_default($params, 'class'       , 'form-control');
        array_default($params, 'selected'    , null);
        array_default($params, 'seocompany'  , null);
        array_default($params, 'companies_id', null);
        array_default($params, 'status'      , null);
        array_default($params, 'remove'      , null);
        array_default($params, 'autosubmit'  , true);
        array_default($params, 'empty'       , tr('No branches available'));
        array_default($params, 'none'        , tr('Select a branch'));
        array_default($params, 'orderby'     , '`name`');

        if ($params['seocompany']) {
            $params['companies_id'] = companies_get($params['seocompany'], 'id');

            if (!$params['companies_id']) {
                throw new CoreException(tr('companies_select_branch(): The specified company ":company" does not exist or is not available', array(':company' => $params['company'])), 'not-exists');
            }
        }

        $execute = array();

        if ($params['selected'] === null) {
            /*
             * Select the default branch
             */
            if ($_CONFIG['companies']['defaults']['branch']) {
                if ($params['companies_id'] === null) {
                    /*
                     * No companies_id specified, likelyl because no company was
                     * specified yet. Select the default company
                     */
                    if (!$_CONFIG['companies']['defaults']['company']) {
                        throw new CoreException(tr('companies_select_branch(): No default company specified for default branch ":branch", see $_CONFIG[companies][defaults][company]', array(':branch' => $_CONFIG['companies']['defaults']['branch'])), 'not-specified');
                    }

                    $params['companies_id'] = companies_get($_CONFIG['companies']['defaults']['company'], 'id');

                    /*
                     * We can only select the default branch if we have the default company selected
                     */
                    if (!$params['companies_id']) {
                        /*
                         * Selected default company does not exist, notify
                         */
                        notify(new CoreException(tr('companies_select_branch(): Specified default company ":company" in $_CONFIG[companies][defaults][company] does not exist', array(':company' => $_CONFIG['companies']['defaults']['company'])), 'not-exist'));
                    }
                }

                /*
                 * We can only select the default branch if we have the default company selected
                 */
                $default_companies_id = companies_get($_CONFIG['companies']['defaults']['company'], 'id');

                if ($params['companies_id'] == $default_companies_id) {
                    $params['selected'] = companies_get_branch($params['companies_id'], $_CONFIG['companies']['defaults']['branch'], 'seoname');

                    if (!$params['selected']) {
                        /*
                         * Selected default company does not exist, notify
                         */
                        notify(new CoreException(tr('companies_select_branch(): Specified default branch ":branch" in $_CONFIG[companies][defaults][branch] does not exist', array(':branch' => $_CONFIG['companies']['defaults']['branch'])), 'not-exist'));
                    }
                }
            }
        }

        /*
         * Only show branches per company
         */
        if ($params['companies_id']) {
            $where[] = ' `companies_id` = :companies_id ';
            $execute[':companies_id'] = $params['companies_id'];

            if ($params['status'] !== false) {
                $where[] = ' `status` '.sql_is($params['status'], ':status');
                $execute[':status'] = $params['status'];
            }

            if (empty($where)) {
                $where = '';

            } else {
                $where = ' WHERE '.implode(' AND ', $where).' ';
            }

            $query              = 'SELECT `seoname`, `name` FROM `branches` '.$where.' ORDER BY `name`';
            $params['resource'] = sql_query($query, $execute);
        }

        $return = html_select($params);

        return $return;

    }catch(Exception $e) {
        throw new CoreException('companies_select_branch(): Failed', $e);
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
 * @see sql_simple_get()
 * @package companies
 *
 * @param mixed $branch The required company. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The company data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified company does not exist, NULL will be returned.
 */
function companies_get_branch($params, $branch, $column = null, $status = null) {
    try {
        array_params($params, 'seoname', 'id');

        array_default($params, 'filters', array('branches.id'      => $params['id'],
                                                'branches.seoname' => $params['seoname']));

        array_default($params, 'joins'  , array('JOIN      `companies`
                                                 ON        `companies`.`id`     = `branches`.`companies_id`
                                                 AND       `companies`.`status` IS NULL

                                                 LEFT JOIN `categories`
                                                 ON        `categories`.`id`    = `companies`.`categories_id` '));

        array_default($params, 'columns', 'branches.id,
                                           branches.createdon,
                                           branches.created_by,
                                           branches.meta_id,
                                           branches.status,
                                           branches.companies_id,
                                           branches.name,
                                           branches.seoname,
                                           branches.description,

                                           categories.name    AS category,
                                           categories.seoname AS seocategory,

                                           companies.name     AS company,
                                           companies.seoname  AS seocompany');

        $params['table']     = 'branches';
        $params['connector'] = 'core';

        return sql_simple_get($params);

    }catch(Exception $e) {
        throw new CoreException('companies_get_branch(): Failed', $e);
    }
}



/*
 * Return a list of all available branches
 *
 * This function wraps sql_simple_list() and supports all its options, like columns selection, filtering, ordering, and execution method
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @customer Function reference
 * @package companies
 * @see sql_simple_list()
 * @version 2.6.27: Added function and documentation
 *
 * @param params $params The list parameters
 * @return mixed The list of available customers
 */
function companies_list_branches($params) {
    try {
        Arrays::ensure($params);
        array_default($params, 'columns', 'seoname,name');
        array_default($params, 'orderby', array('name' => 'asc'));

        $params['table']     = 'branches';
        $params['connector'] = 'core';

        return sql_simple_list($params);

    }catch(Exception $e) {
        throw new CoreException('companies_list_branches(): Failed', $e);
    }
}



/*
 * Validate the specified department
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package companies
 *
 * @param array $department The department to validate
 * @param $reload_only
 * @return array The validated and cleaned $department array
 */
function companies_validate_department($department, $reload_only = false) {
    try {
        load_libs('validate,seo');

        $v = new ValidateForm($department, 'name,seocompany,seobranch,description');

        /*
         * Validate company
         */
        if ($department['seocompany']) {
            $department['companies_id'] = companies_get($department['seocompany'], 'id');

            if (!$department['companies_id']) {
                $v->setError(tr('Specified company does not exist'));
            }

            if ($department['seobranch']) {
                $department['branches_id'] = companies_get_branch($department['seocompany'], $department['seobranch'], 'id');

                if (!$department['branches_id']) {
                    $v->setError(tr('Specified branch does not exist'));
                }

            } else {
                $department['branches_id'] = null;

                if (!$reload_only) {
                    $v->setError(tr('No branch specified'));
                }
            }

        } else {
            $department['companies_id'] = null;

            if (!$reload_only) {
                $v->setError(tr('No company specified'));
            }
        }

        $v->isValid();

        if ($reload_only) {
            return $department;
        }

        /*
         * Validate name
         */
        $v->isNotEmpty ($department['name']    , tr('Please specify a department name'));
        $v->hasMinChars($department['name'],  2, tr('Please ensure the department name has at least 2 characters'));
        $v->hasMaxChars($department['name'], 64, tr('Please ensure the department name has less than 64 characters'));

        if (is_numeric(substr($department['name'], 0, 1))) {
            $v->setError(tr('Please ensure that the department name does not start with a number'));
        }

        $v->hasMaxChars($department['name'], 64, tr('Please ensure the department name has less than 64 characters'));

        $department['name'] = str_clean($department['name']);

        /*
         * Does the department already exist within the specified companies_id?
         */
        $exists = sql_get('SELECT `id` FROM `departments` WHERE `companies_id` '.sql_is(isset_get($department['companies_id']), ':companies_id').' AND `branches_id` '.sql_is(isset_get($department['branches_id']), ':branches_id').' AND `name` = :name AND `id` '.sql_is(isset_get($department['id']), ':id', true), true, array(':name' => $department['name'], ':id' => isset_get($department['id']), ':companies_id' => isset_get($department['companies_id']), ':branches_id' => isset_get($department['branches_id'])));

        if ($exists) {
            $v->setError(tr('The department name ":department" already exists', array(':department' => $department['name'])));
        }

        /*
         * Validate description
         */
        if (empty($department['description'])) {
            $department['description'] = null;

        } else {
            $v->hasMinChars($department['description'],   16, tr('Please ensure the department description has at least 16 characters'));
            $v->hasMaxChars($department['description'], 2047, tr('Please ensure the department description has less than 2047 characters'));

            $department['description'] = str_clean($department['description']);
        }

        /*
         * All valid?
         */
        $v->isValid();

        /*
         * Set seoname
         */
        $department['seoname'] = seo_unique($department['name'], 'departments', isset_get($department['id']));

      return $department;

    }catch(Exception $e) {
        throw new CoreException('companies_validate_department(): Failed', $e);
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
 * @package companies
 *
 * @param array $params The parameters required
 * @param $params name
 * @param $params class
 * @param $params empty
 * @param $params none
 * @param $params selected
 * @param $params categories_id
 * @param $params status
 * @param $params orderby
 * @param $params resource
 * @return string HTML for a companies select box within the specified parameters
 */
function companies_select_department($params = null) {
    global $_CONFIG;

    try {
        Arrays::ensure($params);
        array_default($params, 'name'        , 'seodepartment');
        array_default($params, 'class'       , 'form-control');
        array_default($params, 'selected'    , null);
        array_default($params, 'seocompany'  , null);
        array_default($params, 'seobranch'   , null);
        array_default($params, 'companies_id', null);
        array_default($params, 'branches_id' , null);
        array_default($params, 'status'      , null);
        array_default($params, 'remove'      , null);
        array_default($params, 'autosubmit'  , true);
        array_default($params, 'empty'       , tr('No departments available'));
        array_default($params, 'none'        , tr('Select a department'));
        array_default($params, 'orderby'     , '`name`');

        if ($params['seocompany']) {
            $params['companies_id'] = companies_get($params['seocompany'], 'id');

            if (!$params['companies_id']) {
                throw new CoreException(tr('companies_select_department(): The reqested company ":company" does not exist or is not available', array(':company' => $params['seocompany'])), 'deleted');
            }
        }

        if ($params['seobranch']) {
            $params['branches_id'] = companies_get_branch($params['companies_id'], $params['seobranch'], 'id');

            if (!$params['branches_id']) {
                throw new CoreException(tr('companies_select_department(): The reqested branch ":branch" does not exist or is not available', array(':branch' => $params['seobranch'])), 'deleted');
            }
        }

        $execute = array();

        if ($params['branches_id'] === null) {
            /*
             * Select the default branch
             */
            if ($_CONFIG['companies']['defaults']['branch']) {
                if ($params['companies_id'] === null) {
                    /*
                     * No companies_id specified, likelyl because no company was
                     * specified yet. Select the default company
                     */
                    if (!$_CONFIG['companies']['defaults']['company']) {
                        throw new CoreException(tr('companies_select_branch(): No default company specified for default branch ":branch", see $_CONFIG[companies][defaults][company]', array(':branch' => $_CONFIG['companies']['defaults']['branch'])), 'not-specified');
                    }

                    $params['companies_id'] = companies_get($_CONFIG['companies']['defaults']['company'], 'id');

                    /*
                     * We can only select the default branch if we have the default company selected
                     */
                    if (!$params['companies_id']) {
                        /*
                         * Selected default company does not exist, notify
                         */
                        notify(new CoreException(tr('companies_select_branch(): Specified default company ":company" in $_CONFIG[companies][defaults][company] does not exist', array(':company' => $_CONFIG['companies']['defaults']['company'])), 'not-exist'));
                    }
                }

                /*
                 * We can only select the default branch if we have the default company selected
                 */
                $default_companies_id = companies_get($_CONFIG['companies']['defaults']['company'], 'id');

                if ($params['companies_id'] == $default_companies_id) {
                    $params['branches_id'] = companies_get_branch($params['companies_id'], $_CONFIG['companies']['defaults']['branch'], 'id');

                    if (!$params['branches_id']) {
                        /*
                         * Selected default company does not exist, notify
                         */
                        notify(new CoreException(tr('companies_select_branch(): Specified default branch ":branch" in $_CONFIG[companies][defaults][branch] does not exist', array(':branch' => $_CONFIG['companies']['defaults']['branch'])), 'not-exist'));
                    }
                }
            }
        }

        if ($params['companies_id'] !== false) {
            $where[] = ' `companies_id` '.sql_is($params['companies_id'], ':companies_id');
            $execute[':companies_id'] = $params['companies_id'];
        }

        if ($params['branches_id'] !== false) {
            $where[] = ' `branches_id` '.sql_is($params['branches_id'], ':branches_id');
            $execute[':branches_id'] = $params['branches_id'];
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

        $query              = 'SELECT `seoname`, `name` FROM `departments` '.$where.' ORDER BY `name`';
        $params['resource'] = sql_query($query, $execute);
        $return             = html_select($params);

        return $return;

    }catch(Exception $e) {
        throw new CoreException('companies_select_department(): Failed', $e);
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
 * @see sql_simple_get()
 * @package companies
 *
 * @param mixed $department The required company. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The company data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified company does not exist, NULL will be returned.
 */
function companies_get_department($params, $branch, $department, $column = null, $status = null) {
    try {
        array_params($params, 'seoname', 'id');

        array_default($params, 'filters', array('departments.id'      => $params['id'],
                                                'departments.seoname' => $params['seoname']));

        array_default($params, 'joins'  , array('JOIN      `companies`
                                                 ON        `companies`.`id`    = `departments`.`companies_id`
                                                 AND       `companies`.`status` IS NULL

                                                 LEFT JOIN `categories`
                                                 ON        `categories`.`id`   = `companies`.`categories_id`

                                                 JOIN      `branches`
                                                 ON        `branches`.`id`     = `departments`.`branches_id`
                                                 AND       `branches`.`status`  IS NULL '));

        array_default($params, 'columns', 'departments.id,
                                           departments.createdon,
                                           departments.created_by,
                                           departments.meta_id,
                                           departments.status,
                                           departments.companies_id,
                                           departments.branches_id,
                                           departments.name,
                                           departments.seoname,
                                           departments.description,

                                           categories.name    AS category,
                                           categories.seoname AS seocategory,

                                           companies.name     AS company,
                                           companies.seoname  AS seocompany,

                                           branches.name      AS branch,
                                           branches.seoname   AS seobranch');

        $params['table']     = 'departments';
        $params['connector'] = 'core';

        return sql_simple_get($params);

    }catch(Exception $e) {
        throw new CoreException('companies_get_department(): Failed', $e);
    }
}



/*
 * Return a list of all available departments
 *
 * This function wraps sql_simple_list() and supports all its options, like columns selection, filtering, ordering, and execution method
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @customer Function reference
 * @package customers
 * @see sql_simple_list()
 * @version 2.6.27: Added function and documentation
 *
 * @param params $params The list parameters
 * @return mixed The list of available customers
 */
function companies_list_departments($params) {
    try {
        Arrays::ensure($params);
        array_default($params, 'columns', 'seoname,name');
        array_default($params, 'orderby', array('name' => 'asc'));

        $params['table']     = 'departments';
        $params['connector'] = 'core';

        return sql_simple_list($params);

    }catch(Exception $e) {
        throw new CoreException('companies_list_departments(): Failed', $e);
    }
}



/*
 * Validate the specified employee
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package companies
 *
 * @param array $employee The employee to validate
 * @param $reload_only
 * @return array The validated and cleaned $employee array
 */
function companies_validate_employee($employee, $reload_only = false) {
    try {
        load_libs('validate,seo');

        $v = new ValidateForm($employee, 'name,username,seocompany,seobranch,seodepartment,description');

        /*
         * Validate user
         */
        if ($employee['username']) {
            if (strstr($employee['username'], '@')) {
                $employee['users_id'] = sql_get('SELECT `id` FROM `users` WHERE `email`    = :email    AND `status` IS NULL', true, array(':email'    => $employee['username']));

            } else {
                $employee['users_id'] = sql_get('SELECT `id` FROM `users` WHERE `username` = :username AND `status` IS NULL', true, array(':username' => $employee['username']));
            }

            if (!$employee['users_id']) {
                $v->setError(tr('Specified user does not exist'));
            }

        } else {
            $employee['users_id'] = null;
        }

        /*
         * Validate company
         */
        if ($employee['seocompany']) {
            $employee['companies_id'] = companies_get($employee['seocompany'], 'id');

            if (!$employee['companies_id']) {
                $v->setError(tr('Specified company does not exist'));
            }

            if ($employee['seobranch']) {
                $employee['branches_id'] = companies_get_branch($employee['seocompany'], $employee['seobranch'], 'id');

                if (!$employee['branches_id']) {
                    $v->setError(tr('Specified branch does not exist'));
                }

                if ($employee['seodepartment']) {
                    $employee['departments_id'] = companies_get_department($employee['seocompany'], $employee['seobranch'], $employee['seodepartment'], 'id');

                    if (!$employee['departments_id']) {
                        $v->setError(tr('Specified department does not exist'));
                    }

                } else {
                    $employee['departments_id'] = null;
                }

            } else {
                $employee['branches_id'] = null;
            }

        } else {
            $employee['companies_id'] = null;

            if (!$reload_only) {
                $v->setError(tr('No company specified'));
            }
        }

        $v->isValid();

        if ($reload_only) {
            return $employee;
        }

        /*
         * Validate name
         */
        $v->isNotEmpty ($employee['name']    , tr('Please specify a employee name'));
        $v->hasMinChars($employee['name'],  2, tr('Please ensure the employee name has at least 2 characters'));
        $v->hasMaxChars($employee['name'], 64, tr('Please ensure the employee name has less than 64 characters'));

        if (is_numeric(substr($employee['name'], 0, 1))) {
            $v->setError(tr('Please ensure that the employee name does not start with a number'));
        }

        $v->hasMaxChars($employee['name'], 64, tr('Please ensure the employee name has less than 64 characters'));

        $employee['name'] = str_clean($employee['name']);

        /*
         * Does the employee already exist within the specified companies_id?
         */
        $exists = sql_get('SELECT `id` FROM `employees` WHERE `companies_id` '.sql_is(isset_get($employee['companies_id']), ':companies_id').' AND `branches_id` '.sql_is(isset_get($employee['branches_id']), ':branches_id').' AND `departments_id` '.sql_is(isset_get($employee['departments_id']), ':departments_id').' AND `name` = :name AND `id` '.sql_is(isset_get($employee['id']), ':id', true), true, array(':name' => $employee['name'], ':id' => isset_get($employee['id']), ':companies_id' => isset_get($employee['companies_id']), ':branches_id' => isset_get($employee['branches_id']), ':departments_id' => isset_get($employee['departments_id'])));

        if ($exists) {
            $v->setError(tr('The employee name ":employee" already exists', array(':employee' => $employee['name'])));
        }

        /*
         * Validate description
         */
        if (empty($employee['description'])) {
            $employee['description'] = null;

        } else {
            $v->hasMinChars($employee['description'],   16, tr('Please ensure the employee description has at least 16 characters'));
            $v->hasMaxChars($employee['description'], 2047, tr('Please ensure the employee description has less than 2047 characters'));

            $employee['description'] = str_clean($employee['description']);
        }

        /*
         * All valid?
         */
        $v->isValid();

        /*
         * Set seoname
         */
        $employee['seoname'] = seo_unique($employee['name'], 'employees', isset_get($employee['id']));

      return $employee;

    }catch(Exception $e) {
        throw new CoreException('companies_validate_employee(): Failed', $e);
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
 * @package companies
 *
 * @param array $params The parameters required
 * @param $params name
 * @param $params class
 * @param $params empty
 * @param $params none
 * @param $params selected
 * @param $params categories_id
 * @param $params status
 * @param $params orderby
 * @param $params resource
 * @return string HTML for a companies select box within the specified parameters
 */
function companies_select_employee($params = null) {
    try {
        Arrays::ensure($params);
        array_default($params, 'name'          , 'seoemployee');
        array_default($params, 'class'         , 'form-control');
        array_default($params, 'selected'      , null);
        array_default($params, 'seocompany'    , null);
        array_default($params, 'seobranch'     , null);
        array_default($params, 'seodepartment' , null);
        array_default($params, 'companies_id'  , null);
        array_default($params, 'branches_id'   , null);
        array_default($params, 'departments_id', null);
        array_default($params, 'status'        , null);
        array_default($params, 'remove'        , null);
        array_default($params, 'autosubmit'    , true);
        array_default($params, 'empty'         , tr('No employees available'));
        array_default($params, 'none'          , tr('Select an employee'));
        array_default($params, 'orderby'       , '`name`');

        if ($params['seocompany']) {
            $params['companies_id'] = companies_get($params['seocompany'], 'id');

            if (!$params['companies_id']) {
                throw new CoreException(tr('companies_select_employee(): The reqested company ":company" does not exist or is not available', array(':company' => $params['seocompany'])), 'deleted');
            }
        }

        if ($params['seobranch']) {
            $params['branches_id'] = companies_get_branch($params['companies_id'], $params['seobranch'], 'id');

            if (!$params['branches_id']) {
                throw new CoreException(tr('companies_select_employee(): The reqested branch ":branch" does not exist or is not available', array(':branch' => $params['seobranch'])), 'deleted');
            }
        }

        if ($params['seodepartment']) {
            $params['departments_id'] = companies_get($params['companies_id'], $params['branches_id'], $params['seodepartment'], 'id');

            if (!$params['departments_id']) {
                throw new CoreException(tr('companies_select_employee(): The reqested department ":department" does not exist or is not available', array(':department' => $params['seodepartment'])), 'deleted');
            }
        }

        $execute = array();

        if ($params['companies_id'] !== false) {
            $where[] = ' `companies_id` '.sql_is($params['companies_id'], ':companies_id');
            $execute[':companies_id'] = $params['companies_id'];
        }

        if ($params['branches_id'] !== false) {
            $where[] = ' `branches_id` '.sql_is($params['branches_id'], ':branches_id');
            $execute[':branches_id'] = $params['branches_id'];
        }

        if ($params['departments_id'] !== false) {
            $where[] = ' `departments_id` '.sql_is($params['departments_id'], ':departments_id');
            $execute[':departments_id'] = $params['departments_id'];
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

        $query              = 'SELECT `seoname`, `name` FROM `employees` '.$where.' ORDER BY `name`';
        $params['resource'] = sql_query($query, $execute);
        $return             = html_select($params);

        return $return;

    }catch(Exception $e) {
        throw new CoreException('companies_select_employee(): Failed', $e);
    }
}



/*
 * Return data for the specified employee
 *
 * This function returns information for the specified company. The company can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @see sql_simple_get()
 * @package companies
 *
 * @param mixed $employee The required company. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The company data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified company does not exist, NULL will be returned.
 */
function companies_get_employee($params) {
    try {
        array_params($params, 'seoname', 'id');
        Arrays::ensure($params, 'companies_id,branches_id,departments_id,seoname,id');

        $params['table']     = 'employees';
        $params['connector'] = 'core';

        array_default($params, 'filters', array('employees.id'             => $params['id'],
                                                'employees.seoname'        => $params['seoname'],
                                                'employees.companies_id'   => $params['companies_id'],
                                                'employees.branches_id'    => $params['branches_id'],
                                                'employees.departments_id' => $params['departments_id']));

        array_default($params, 'joins'  , array('LEFT JOIN `users`
                                                 ON        `users`.`id`       = `employees`.`users_id`

                                                 JOIN      `companies`
                                                 ON        `companies`.`id`   = `employees`.`companies_id`
                                                 AND       `companies`.`status`   IS NULL

                                                 LEFT JOIN `categories`
                                                 ON        `categories`.`id`  = `companies`.`categories_id`

                                                 LEFT JOIN `branches`
                                                 ON        `branches`.`id`    = `employees`.`branches_id`
                                                 AND       `branches`.`status`    IS NULL

                                                 LEFT JOIN `departments`
                                                 ON        `departments`.`id` = `employees`.`departments_id`
                                                 AND       `departments`.`status` IS NULL'));

        array_default($params, 'columns', 'employees.id,
                                           employees.createdon,
                                           employees.created_by,
                                           employees.meta_id,
                                           employees.status,
                                           employees.companies_id,
                                           employees.branches_id,
                                           employees.departments_id,
                                           employees.users_id,
                                           employees.name,
                                           employees.seoname,
                                           employees.description,

                                           users.username,
                                           users.email,

                                           categories.name     AS category,
                                           categories.seoname  AS seocategory,

                                           companies.customers_id,
                                           companies.providers_id,

                                           companies.name      AS company,
                                           companies.seoname   AS seocompany,

                                           branches.name       AS branch,
                                           branches.seoname    AS seobranch,

                                           departments.name    AS department,
                                           departments.seoname AS seodepartment');

        return sql_simple_get($params);

    }catch(Exception $e) {
        throw new CoreException(tr('companies_get_employee(): Failed'), $e);
    }
}



/*
 * Return a list of all available employees
 *
 * This function wraps sql_simple_list() and supports all its options, like columns selection, filtering, ordering, and execution method
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @customer Function reference
 * @package customers
 * @see sql_simple_list()
 * @version 2.6.27: Added function and documentation
 *
 * @param params $params The list parameters
 * @return mixed The list of available customers
 */
function companies_list_employees($params) {
    try {
        Arrays::ensure($params);
        array_default($params, 'columns', 'seoname,name');
        array_default($params, 'orderby', array('name' => 'asc'));

        $params['table']     = 'employees';
        $params['connector'] = 'core';

        return sql_simple_list($params);

    }catch(Exception $e) {
        throw new CoreException('companies_list_employees(): Failed', $e);
    }
}