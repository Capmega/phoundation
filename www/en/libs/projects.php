<?php
/*
 * Projects library
 *
 * This library contains funtions to work with the user projects
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
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
 * @package progress
 *
 * @return void
 */
function projects_library_init() {
    try {
        load_config('projects');

    }catch(Exception $e) {
        throw new CoreException('projects_library_init(): Failed', $e);
    }
}



/*
 * Validate all project data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package
 *
 * @param params $project The project parameters array
 * @param string null $project[seocategory]
 * @param string $project[seocustomer]
 * @param string $project[name] The project name
 * @param string $project[code] The project code to which it can be referenced (beside its seoname)
 * @param string null $project[seoprocess] The process selected for this project
 * @param string null $project[step] If a process has been specified, specifies in what step of the process this project is
 * @param string null $project[documents_id] If a storage system document has been attached, this is the documents_id
 * @param string null 64 $project[api_key] An API key which can be used to execute actions for this project
 * @param string 0-511 $project[fcm_api_key]
 * @param string 0-2047 $project[description] A description for the specified project
 *
 * @return
 */
function projects_validate($project, $reload_only = false) {
    global $_CONFIG;

    try {
        load_libs('validate,seo');

        $v = new ValidateForm($project, 'seocategory,seocustomer,name,code,seoprocess,step,documents_id,api_key,fcm_api_key,description');

        /*
         * Validate category
         */
        if ($project['seocategory']) {
            load_libs('categories');
            $project['categories_id'] = categories_get($project['seocategory'], 'id', null, $_CONFIG['projects']['categories_parent']);

            if (!$project['categories_id']) {
                $v->setError(tr('Specified category does not exist'));
            }

        } else {
            $project['categories_id'] = null;
        }

        /*
         * Validate customer
         */
        if ($project['seocustomer']) {
            load_libs('customers');
            $project['customers_id'] = customers_get(array('columns' => 'id',
                                                           'filters' => array('seoname' => $project['seocustomer'])));

            if (!$project['customers_id']) {
                $v->setError(tr('Specified customer does not exist'));
            }

        } else {
            $project['customers_id'] = null;

            if (!$reload_only) {
                $v->setError(tr('No customer specified'));
            }
        }

        /*
         * Validate process
         */
        if ($project['seoprocess']) {
            $project['processes_id'] = progress_get_process($project['seoprocess'], 'id');

            if (!$project['processes_id']) {
                $v->setError(tr('The specified process does not exist'));
            }

            if ($project['seostep']) {
                $project['steps_id'] = progress_get_step($project['processes_id'], $project['seostep'], 'id');

                if (!$project['steps_id']) {
                    $v->setError(tr('The specified step does not exist for this process'));
                }

            } else {
                /*
                 * No step specified, so it should start with the first step
                 */
                $project['steps_id'] = progress_get_step($project['processes_id'], null, 'id');
            }

        } else {
            $project['processes_id'] = null;
            $project['steps_id']     = null;
        }

        $v->isValid();

        if ($reload_only) {
            return $project;
        }

        /*
         * Validate name
         */
        if (!$v->isNotEmpty ($project['name'], tr('No projects name specified'))) {
            $v->hasMinChars($project['name'], 2, tr('Please ensure the project\'s name has at least 2 characters'));
            $v->hasMaxChars($project['name'], 64, tr('Please ensure the project\'s name has less than 64 characters'));
            $v->isAlphaNumeric($project['name'], tr('Please specify a valid project name'), VALIDATE_IGNORE_ALL);
        }

        $project['name'] = str_clean($project['name']);

        if ($project['code']) {
            $v->hasMinChars($project['code'], 2, tr('Please ensure the project\'s code has at least 2 characters'));
            $v->hasMaxChars($project['code'], 32, tr('Please ensure the project\'s code has less than 32 characters'));
            $v->isAlphaNumeric($project['code'], tr('Please ensure the project\'s code contains no spaces'), VALIDATE_IGNORE_UNDERSCORE);

            $project['code'] = str_clean($project['code']);
            $project['code'] = strtoupper($project['code']);

        } else {
            $project['code'] = null;
        }

        if ($project['api_key']) {
            $v->hasMinChars($project['api_key'], 32, tr('Please ensure the project\'s api_key has at least 32 characters'));
            $v->hasMaxChars($project['api_key'], 64, tr('Please ensure the project\'s api_key has less than 64 characters'));
            $v->isAlphaNumeric($project['api_key'], tr('Please ensure the project\'s api_key contains no spaces'));

        } else {
            $project['api_key'] = null;
        }

        if ($project['fcm_api_key']) {
            $v->hasMinChars($project['fcm_api_key'], 11, tr('Please ensure the project\'s fcm_api_key has at least 11 characters'));
            $v->hasMaxChars($project['fcm_api_key'], 511, tr('Please ensure the project\'s fcm_api_key has less than 511 characters'));
            $v->isAlphaNumeric($project['fcm_api_key'], tr('Please ensure the project\'s fcm_api_key is alpha numeric with only dashes'), VALIDATE_IGNORE_DASH);

        } else {
            $project['fcm_api_key'] = null;
        }

        $v->hasMaxChars($project['description'], 2047, tr('Please ensure the project\'s description has less than 2047 characters'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->isText($project['description'], tr('Please ensure the project\'s description is valid'), VALIDATE_ALLOW_EMPTY_NULL);

        if ($project['documents_id']) {
            $v->isNatural($project['documents_id'], 1, tr('Please ensure the project\'s linked documents_id is a valid number'));

        } else {
            $project['documents_id'] = null;
        }

        /*
         * Structural validation finished, if all is okay continue to check for existence
         */
        $v->isValid();

        /*
         * Does the linked document exist?
         */
        $exists = sql_get('SELECT `id` FROM `storage_documents` WHERE `id` = :id', true, array(':id' => $project['documents_id']));

        if ($exists) {
            $v->setError(tr('The linked document does not exist'));
        }

        /*
         * Does the project name already exist?
         */
        $exists = sql_get('SELECT `id` FROM `projects` WHERE `name` = :name AND `id` != :id', true, array(':name' => $project['name'], ':id' => isset_get($project['id'], 0)));

        if ($exists) {
            $v->setError(tr('The name ":name" already exists for project id ":id"', array(':name' => $project['name'], ':id' => $exists)));
        }

        /*
         * Does the project code already exist?
         */
        $exists = sql_get('SELECT `id` FROM `projects` WHERE `code` = :code AND `id` != :id', true, array(':code' => $project['code'], ':id' => isset_get($project['id'], 0)));

        if ($exists) {
            $v->setError(tr('The project code ":code" already exists for project id ":id"', array(':code' => $project['code'], ':id' => $exists)));
        }

        $v->isValid();

        /*
         * All is good, yay!
         */
        $project['seoname'] = seo_unique($project['name'], 'projects', isset_get($project['id'], 0));

        return $project;

    }catch(Exception $e) {
        throw new CoreException(tr('projects_validate(): Failed'), $e);
    }
}



/*
 * Insert the specified project into the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package projects
 * @see projects_validate()
 * @see projects_update()
 * @table: `project`
 * @version 2.5.92: Added function and documentation
 * @example Insert a project in the database
 *
 * @param params $project The project to be inserted
 * @param string $project[]
 * @param string $project[]
 * @return params The specified project, validated and sanitized
 */
function projects_insert($project) {
    try {
        $project = projects_validate($project);

        sql_query('INSERT INTO `projects` (`createdby`, `meta_id`, `categories_id`, `customers_id`, `processes_id`, `steps_id`, `code`, `name`, `seoname`, `api_key`, `fcm_api_key`, `description`)
                   VALUES                 (:createdby , :meta_id , :categories_id , :customers_id , :processes_id , :steps_id , :code , :name , :seoname , :api_key , :fcm_api_key , :description )',

                   array(':createdby'     =>  isset_get($_SESSION['project']['id']),
                         ':meta_id'       =>  meta_action(),
                         ':categories_id' =>  $project['categories_id'],
                         ':customers_id'  =>  $project['customers_id'],
                         ':processes_id'  =>  $project['processes_id'],
                         ':steps_id'      =>  $project['steps_id'],
                         ':code'          =>  $project['code'],
                         ':name'          =>  $project['name'],
                         ':seoname'       =>  $project['seoname'],
                         ':api_key'       =>  $project['api_key'],
                         ':fcm_api_key'   =>  $project['fcm_api_key'],
                         ':description'   =>  $project['description']));

        $project['id'] = sql_insert_id();

        return $project;

    }catch(Exception $e) {
        throw new CoreException(tr('projects_insert(): Failed'), $e);
    }
}



/*
 * Update the specified project in the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package projects
 * @see projects_validate()
 * @see projects_insert()
 * @table: `project`
 * @version 2.5.92: Added function and documentation
 *
 * @param params $params The project to be updated
 * @param string $project[]
 * @param string $project[]
 * @return boolean True if the user was updated, false if not. If not updated, this might be because no data has changed
 */
function projects_update($project) {
    try {
        $project = projects_validate($project);

        meta_action($project['meta_id'], 'update');

        $update = sql_query('UPDATE `projects`

                             SET    `categories_id` = :categories_id,
                                    `customers_id`  = :customers_id,
                                    `processes_id`  = :processes_id,
                                    `steps_id`      = :steps_id,
                                    `code`          = :code,
                                    `name`          = :name,
                                    `seoname`       = :seoname,
                                    `api_key`       = :api_key,
                                    `fcm_api_key`   = :fcm_api_key,
                                    `description`   = :description

                             WHERE  `id`            = :id',

                             array(':id'            => $project['id'],
                                   ':categories_id' => $project['categories_id'],
                                   ':customers_id'  => $project['customers_id'],
                                   ':processes_id'  => $project['processes_id'],
                                   ':steps_id'      => $project['steps_id'],
                                   ':code'          => $project['code'],
                                   ':name'          => $project['name'],
                                   ':seoname'       => $project['seoname'],
                                   ':api_key'       => $project['api_key'],
                                   ':fcm_api_key'   => $project['fcm_api_key'],
                                   ':description'   => $project['description']));

        $project['_updated'] = (boolean) $update->rowCount();
        return $project;

    }catch(Exception $e) {
        throw new CoreException(tr('projects_update(): Failed'), $e);
    }
}



/*
 * Return HTML for a projects select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available projects
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @customer Function reference
 * @package projects
 *
 * @param array $params The parameters required
 * @param $params name
 * @param $params class
 * @param $params extra
 * @param $params none
 * @param $params selected
 * @param $params parents_id
 * @param $params status
 * @param $params orderby
 * @param $params resource
 * @return string HTML for a projects select box within the specified parameters
 */
function projects_select($params = null) {
    try {
        Arrays::ensure($params);
        array_default($params, 'name'        , 'seoproject');
        array_default($params, 'class'       , 'form-control');
        array_default($params, 'selected'    , null);
        array_default($params, 'status'      , null);
        array_default($params, 'seocustomer' , null);
        array_default($params, 'customers_id', null);
        array_default($params, 'empty'       , tr('No projects available'));
        array_default($params, 'none'        , tr('Select a project'));
        array_default($params, 'orderby'     , '`name`');

        if ($params['seocustomer']) {
            load_libs('customers');
            $params['customers_id'] = customers_get(array('columns' => 'id',
                                                          'filters' => array('seoname' => $params['seocustomer'])));

            if (!$params['customers_id']) {
                throw new CoreException(tr('projects_select(): The reqested customer ":customer" is not available', array(':customer' => $params['seocustomer'])), 'not-available');
            }
        }

        if ($params['customers_id'] !== false) {
            $where[] = ' `customers_id` '.sql_is($params['customers_id'], ':customers_id');
            $execute[':customers_id'] = $params['customers_id'];
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

        $query              = 'SELECT `seoname`, `name` FROM `projects` '.$where.' ORDER BY `name`';
        $params['resource'] = sql_query($query, $execute);
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('projects_select(): Failed', $e);
    }
}




/*
 * Return data for the specified project
 *
 * This function returns information for the specified project. The project can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package projects
 *
 * @param mixed $project The requested project. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @param string $status Filter by the specified status
 * @param natural $categories_id Filter by the specified categories_id. If NULL, the project must NOT belong to any category
 * @return mixed The project data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified project does not exist, NULL will be returned.
 */
function projects_get($params) {
    try {
        Arrays::ensure($params, 'seoproject');

        array_default($params, 'filters', array('projects.seoname' => $params['seoproject'],
                                                'projects.status'  => null));

        array_default($params, 'joins'  , array('LEFT JOIN `categories`
                                                 ON        `categories`.`id` = `projects`.`categories_id`',

                                                'LEFT JOIN `customers`
                                                 ON        `customers`.`id` = `projects`.`customers_id`'));

        array_default($params, 'columns', 'projects.id,
                                           projects.createdon,
                                           projects.createdby,
                                           projects.meta_id,
                                           projects.status,
                                           projects.categories_id,
                                           projects.customers_id,
                                           projects.processes_id,
                                           projects.steps_id,
                                           projects.documents_id,
                                           projects.name,
                                           projects.seoname,
                                           projects.code,
                                           projects.api_key,
                                           projects.fcm_api_key,
                                           projects.last_login,
                                           projects.description,

                                           categories.name    AS category,
                                           categories.seoname AS seocategory,

                                           customers.name    AS customer,
                                           customers.seoname AS seocustomer');

        $params['table'] = 'projects';

        return sql_simple_get($params);

    }catch(Exception $e) {
        throw new CoreException('projects_get(): Failed', $e);
    }
}



/*
 * Return a list of all available projects
 *
 * This function wraps sql_simple_list() and supports all its options, like columns selection, filtering, ordering, and execution method
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @package projects
 * @see sql_simple_list()
 *
 * @param params $params The list parameters
 * @return mixed The list of available templates
 */
function projects_list($params) {
    try {
        Arrays::ensure($params);
        array_default($params, 'columns', 'seoname,name');
        array_default($params, 'orderby', array('name' => 'asc'));

        $params['table'] = 'projects';

        return sql_simple_list($params);

    }catch(Exception $e) {
        throw new CoreException('projects_list(): Failed', $e);
    }
}
?>
