<?php
/*
 * Storage pages library
 *
 * This library manages storage pages, see storage library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 */



/*
 * Initialize the library
 * Auto executed by libs_load
 */
function storage_pages_library_init(){
    try{
        load_libs('storage');

    }catch(Exception $e){
        throw new BException('storage_pages_library_init(): Failed', $e);
    }
}



/*
 * Generate a new storage page
 */
function storage_pages_get($section, $page = null, $auto_create = false){
    try{
        $section = storage_ensure_section($section);

        if(empty($section['id'])){
            throw new BException(tr('storage_pages_get(): No sections id specified'), 'not-specified');
        }

        if(empty($page)){
            /*
             * Get a _new record for the current user
             */
            if(empty($_SESSION['user']['id'])){
                $where   = ' WHERE  `storage_pages`.`sections_id` = :sections_id
                             AND    `storage_documents`.`status`  = "_new"
                             AND    `storage_pages`.`createdby`   IS NULL LIMIT 1';

                $execute = array(':sections_id' => $section['id']);

            }else{
                $where   = ' WHERE  `storage_pages`.`sections_id` = :sections_id
                             AND    `storage_documents`.`status`  = "_new"
                             AND    `storage_pages`.`createdby`   = :createdby LIMIT 1';

                $execute = array(':sections_id' => $section['id'],
                                 ':createdby'   => $_SESSION['user']['id']);
            }

        }elseif(is_numeric($page)){
            /*
             * Assume this is pages id
             */
            $where   = ' WHERE  `storage_pages`.`sections_id` = :sections_id
                         AND    `storage_pages`.`id`          = :id
                         AND    `storage_documents`.`status`  IN ("published", "unpublished", "_new")';

            $execute = array(':sections_id' => $section['id'],
                             ':id'          => $page);

        }elseif(is_string($page)){
            /*
             * Assume this is pages seoname
             */
            $where   = ' WHERE  `storage_pages`.`sections_id` = :sections_id
                         AND    `storage_pages`.`seoname`     = :seoname
                         AND    `storage_documents`.`status`  IN ("published", "unpublished", "_new")';

            $execute = array(':sections_id' => $section['id'],
                             ':seoname'     => $page);

        }else{
            throw new BException(tr('storage_pages_get(): Invalid page specified, is datatype ":type", should be null, numeric id, or seoname string', array(':type' => gettype($page))), 'invalid');
        }

        $page = sql_get('SELECT   `storage_documents`.`id`      AS `documents_id`,
                                  `storage_documents`.`meta_id` AS `documents_meta_id`,
                                  `storage_documents`.`sections_id`,
                                  `storage_documents`.`masters_id`,
                                  `storage_documents`.`parents_id`,
                                  `storage_documents`.`rights_id`,
                                  `storage_documents`.`assigned_to_id`,
                                  `storage_documents`.`customers_id`,
                                  `storage_documents`.`providers_id`,
                                  `storage_documents`.`status`,
                                  `storage_documents`.`featured_until`,
                                  `storage_documents`.`category1`,
                                  `storage_documents`.`category2`,
                                  `storage_documents`.`category3`,
                                  `storage_documents`.`upvotes`,
                                  `storage_documents`.`downvotes`,
                                  `storage_documents`.`priority`,
                                  `storage_documents`.`level`,
                                  `storage_documents`.`views`,
                                  `storage_documents`.`rating`,
                                  `storage_documents`.`comments`,

                                  `storage_pages`.`id`,
                                  `storage_pages`.`createdon`,
                                  `storage_pages`.`createdby`,
                                  `storage_pages`.`meta_id`,
                                  `storage_pages`.`language`,
                                  `storage_pages`.`name`,
                                  `storage_pages`.`seoname`,
                                  `storage_pages`.`description`,
                                  `storage_pages`.`body`,

                                  `customers`.`name` AS `customer`,

                                  `providers`.`name` AS `provider`

                         FROM      `storage_pages`

                         LEFT JOIN `storage_documents`
                         ON        `storage_documents`.`id` = `storage_pages`.`documents_id`

                         LEFT JOIN `customers`
                         ON        `customers`.`id` = `storage_documents`.`customers_id`

                         LEFT JOIN `providers`
                         ON        `providers`.`id` = `storage_documents`.`providers_id`

                         '.$where,

                         $execute);

        if(empty($page) and empty($page) and $auto_create){
            $page = storage_pages_add(array('status'       => '_new',
                                            'sections_id'  => $section['id'],
                                            'documents_id' => $page['documents_id'],
                                            'language'     => LANGUAGE));
        }

        return $page;

    }catch(Exception $e){
        throw new BException('storage_pages_get(): Failed', $e);
    }
}



/*
 * Generate a new storage page
 */
function storage_pages_add($page, $section = null){
    try{
        load_libs('storage-documents');

        if(!$section){
            $section = storage_sections_get($page['sections_id']);
        }

        if($section['random_ids']){
            $page['id'] = sql_random_id('storage_pages');
        }

        $page = storage_pages_validate($page);

        if(empty($page['documents_id'])){
            /*
             * This page has no document
             * Generate a new document for this page
             */
            $document = storage_documents_add($page, $section);

        }else{
            /*
             * Get document information for this page
             */
            $document = storage_documents_get($page['documents_id']);
        }

        $page['documents_id'] = $document['id'];
        $page['sections_id']  = $document['sections_id'];

        sql_query('INSERT INTO `storage_pages` (`id`, `createdby`, `meta_id`, `sections_id`, `documents_id`, `language`)
                   VALUES                      (:id , :createdby , :meta_id , :sections_id , :documents_id , :language )',

                   array(':id'           => $page['id'],
                         ':createdby'    => $_SESSION['user']['id'],
                         ':meta_id'      => meta_action(),
                         ':sections_id'  => $page['sections_id'],
                         ':documents_id' => $page['documents_id'],
                         ':language'     => $page['language']));

        $page['id'] = sql_insert_id();
        return $page;

    }catch(Exception $e){
        throw new BException('storage_pages_add(): Failed', $e);
    }
}



/*
 * Update the specified storage page
 */
function storage_pages_update($page, $params){
    try{
        load_libs('storage-documents');

        $page           = storage_pages_validate($page, $params);
        $document       = $page;
        $document['id'] = $page['documents_id'];
        $document       = storage_documents_update($document, $page['_new']);

        unset($document['id']);
        $page = array_merge($page, $document);

        meta_action($page['meta_id'], ($page['_new'] ? 'create-update' : 'update'));

        sql_query('UPDATE `storage_pages`

                   SET    `language`    = :language,
                          `name`        = :name,
                          `seoname`     = :seoname,
                          `description` = :description,
                          `body`        = :body

                   WHERE  `id`          = :id',

                   array(':id'          => $page['id'],
                         ':language'    => $page['language'],
                         ':name'        => $page['name'],
                         ':seoname'     => $page['seoname'],
                         ':description' => $page['description'],
                         ':body'        => $page['body']));

        return $page;

    }catch(Exception $e){
        throw new BException('storage_pages_update(): Failed', $e);
    }
}



/*
 * Validate and return the specified storage page
 */
function storage_pages_validate($page, $params = false){
    try{
        load_libs('validate,seo');

        $empty = !$params;

        array_ensure($params, 'errors', array());
        array_default($params['errors'], 'valid_id'                , tr('Please specify a valid created by id'));
        array_default($params['errors'], 'valid_meta_id'           , tr('Please specify a valid meta id'));
        array_default($params['errors'], 'valid_sections_id'       , tr('Please specify a valid sections id'));
        array_default($params['errors'], 'valid_documents_id'      , tr('Please specify a valid documents id'));
        array_default($params['errors'], 'valid_assigned_to_id'    , tr('Please specify a valid assigned_to id'));
        array_default($params['errors'], 'required_assigned_to_id' , tr('Required assigned_to id is not set'));
        array_default($params['errors'], 'not_exist_assigned_to_id', tr('Specified assigned_to id does not exist'));
        array_default($params['errors'], 'valid_language'          , tr('Please specify a valid language'));
        array_default($params['errors'], 'valid_pagename'          , tr('Please specify a valid page name'));
        array_default($params['errors'], 'page_64'                 , tr('Please specify a page name of less than 64 characters'));
        array_default($params['errors'], 'valid_pageid'            , tr('Please specify a valid page id'));
        array_default($params['errors'], 'pagename_1'              , tr('Please specify a document name of at least 1 character'));
        array_default($params['errors'], 'valid_description'       , tr('Please specify a valid description'));
        array_default($params['errors'], 'description_256'         , tr('Please specify a description of less than 255 characters'));
        array_default($params['errors'], 'description_16'          , tr('Please specify a description of at least 16 characters'));
        array_default($params['errors'], 'valid_body'              , tr('Please specify a valid body'));
        array_default($params['errors'], 'body_16mb'               , tr('Please specify a body of less than 16 MegaByte'));
        array_default($params['errors'], 'body_16'                 , tr('Please specify a body of at least 16 characters'));

        $v = new ValidateForm($page, '_new,id,createdby,meta_id,sections_id,documents_id,assigned_to_id,category1,category2,category3,language,name,seoname,description,body');

        $v->isNatural($page['id'], 1, $params['errors']['valid_pageid'], VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($page['createdby'], 1, $params['errors']['valid_id'], VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($page['meta_id'], 1, $params['errors']['valid_meta_id'], VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($page['sections_id'], 1, $params['errors']['valid_sections_id'], VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($page['documents_id'], 1, $params['errors']['valid_documents_id'], VALIDATE_ALLOW_EMPTY_NULL);
        $v->isAlpha($page['language'], $params['errors']['valid_language'], VALIDATE_ALLOW_EMPTY_NULL);
        $v->isAlphaNumeric($page['name'], $params['errors']['valid_pagename'], VALIDATE_IGNORE_ALL|VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($page['name'], 64, $params['errors']['page_64'], VALIDATE_IGNORE_ALL|VALIDATE_ALLOW_EMPTY_NULL);

        /*
         * Validate basics
         */
        if(!$empty){
            $v->hasMinChars($page['name'], 1, $params['errors']['pagename_1'], VALIDATE_IGNORE_ALL);
        }


        /*
         * Validate assigned_to_id
         */
        if(empty($page['assigned_to_id'])){
            /*
             * assigned_to_id not set, ensure NULL
             */
            $page['assigned_to_id'] = null;

            if(isset_get($params['entry']['assigned_to_id'])){
                /*
                 * assigned_to_id is required!
                 */
                $v->setError($params['errors']['valid_assigned_to_id']);
            }

        }else{
            if(isset_get($params['entry']['assigned_to_id'])){
                /*
                 * assigned_to_id is not used at all! Just ignore
                 */
                unset($page['assigned_to_id']);

            }else{
                /*
                 * assigned_to_id is set. Ensure validity and existence
                 */
                $v->isNatural($page['assigned_to_id'], 1, $params['errors']['valid_assigned_to_id'], VALIDATE_ALLOW_EMPTY_NULL);

                $exists = sql_get('SELECT `id` FROM `users` WHERE `id` = :id AND `status` IS NULL', array(':id' => $page['assigned_to_id']));

                if(!$exists){
                    $v->setError($params['errors']['not_exist_assigned_to_id']);
                }
            }
        }

        /*
         * Validate description
         */
        if(empty($params['labels']['description']) or $empty){
            $page['description'] = null;

        }else{
            /*
             * Validate description. $params[entry][description] being false
             * means the entry is available on the UI, but it is not required
             */
            $v->isAlphaNumeric($page['description'], $params['errors']['valid_description'], VALIDATE_IGNORE_ALL|((isset_get($params['entry']['description']) === false) ? VALIDATE_ALLOW_EMPTY_NULL : null));
            $v->hasMaxChars($page['description'], 255, $params['errors']['description_256'], VALIDATE_ALLOW_EMPTY_NULL);
            $v->hasMinChars($page['description'], 16, $params['errors']['description_16'], VALIDATE_IGNORE_ALL);
        }

        if(empty($params['show']['body']) or $empty){
            $page['body'] = null;

        }else{
            $v->isAlphaNumeric($page['body'], $params['errors']['valid_body'], VALIDATE_IGNORE_ALL|VALIDATE_IGNORE_HTML);
            $v->hasMaxChars($page['body'], 16777215, $params['errors']['body_16mb']);
            $v->hasMinChars($page['body'], 16, $params['errors']['body_16']);
        }

        /*
         * Done!
         */
        $v->isValid();

        $page['seoname'] = seo_unique($page['name'], 'storage_pages', $page['id']);

        return $page;

    }catch(Exception $e){
        throw new BException('storage_pages_validate(): Failed', $e);
    }
}



/*
 *
 */
function storage_page_attach_file($pages_id, $file){
    try{
        load_libs('files');

        if(!is_array($file)){
            if(!is_numeric($file)){
            }

            /*
             * Assume this is a files_id from the files library
             */

            $file = files_get($file);
        }

    }catch(Exception $e){
        throw new BException('storage_page_attach_file(): Failed', $e);
    }
}



/*
 *
 */
function storage_page_has_access($pages_id, $users_id = null){
    try{
        if(empty($users_id)){
            $users_id = $_SESSION['user']['id'];
        }

    }catch(Exception $e){
        throw new BException('storage_page_has_access(): Failed', $e);
    }
}
?>
