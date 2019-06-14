<?php
/*
 * Files library
 *
 * This is the generic files storage and management library
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package files
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
 * @package templates
 * @version 2.5.38: Added function and documentation
 *
 * @return void
 */
function files_library_init(){
    try{
        load_config('files');

    }catch(Exception $e){
        throw new BException('files_library_init(): Failed', $e);
    }
}



/*
 * Insert a file in the files database and store it in the files area
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package files
 * @version 2.5.46: Added function and documentation
 *
 * @param $file The file to be inserted and stored in the files system
 * @param boolean $require_unique If set to true, the file has to be unique in the system
 * @return params The specified template, validated and sanitized
 */
function files_insert($file, $require_unique = false){
    global $_CONFIG;

    try{
        if(is_string($file)){
            $file = array('filename' => $file,
                          'original' => basename($file));

        }elseif(isset($file['name']) and isset($file['tmp_name'])){
            /*
             * This is a PHP uploaded file array. Correct file names
             */
            $file['filename'] = $file['tmp_name'];
            $file['original'] = $file['name'];
        }

        array_ensure($file, 'filename,type,status,original,meta1,meta2,description');

        /*
         * Ensure that the files base path exists
         */
        file_ensure_path($base_path);

        $extension = str_rfrom($file['filename'], '.');
        $base_path = slash($base_path);
        $target    = file_assign_target($base_path, $extension);

        if(isset($file['name']) and isset($file['tmp_name'])){
            /*
             * Move uploaded file to its final position
             */
            move_uploaded_file($file['filename'], $base_path.$target);

        }else{
            /*
             * Move the normal file to the base path position
             */
            rename($file['filename'], $base_path.$target);
        }

        /*
         * Get file mimetype data
         */
        $meta = file_mimetype($base_path.$target);

        $file['meta1'] = str_until($meta, '/');
        $file['meta2'] = str_from($meta , '/');
        $file['hash']  = hash($_CONFIG['files']['hash'], file_get_contents($base_path.$target));

        /*
         * File must be unique?
         */
        if($require_unique){
            $exists = sql_get('SELECT `id` FROM `files` WHERE `hash` = :hash', array($file['hash']));

            if($exists){
                throw new BException(tr('files_insert(): Specified file ":filename" already exists with id ":id"', array(':filename' => $base_path.$target, ':id' => $exists)), 'exists');
            }
        }

        /*
         * Store and return file data
         */
        sql_query('INSERT INTO `files` (`meta_id`, `status`, `filename`, `original`, `hash`, `type`, `meta1`, `meta2`, `description`)
                   VALUES              (:meta_id , :status , :filename , :original , :hash , :type , :meta1 , :meta2 , :description )',

                   array(':meta_id'     => meta_action(),
                         ':status'      => $file['status'],
                         ':filename'    => $target,
                         ':original'    => $file['original'],
                         ':hash'        => $file['hash'],
                         ':type'        => $file['type'],
                         ':meta1'       => $file['meta1'],
                         ':meta2'       => $file['meta2'],
                         ':description' => $file['description']));

        $file['id']       = sql_insert_id();
        $file['filename'] = $target;

        return $file;

    }catch(Exception $e){
        throw new BException('files_insert(): Failed', $e);
    }
}



/*
 * Delete a file
 */
function files_delete($file, $base_path = ROOT.'data/files/'){
    try{
        $dbfile = files_get($file);

        if(!$dbfile){
            throw new BException(tr('files_delete(): Specified file ":file" does not exist', array(':file' => $file)), 'not-exists');
        }

        sql_query('DELETE FROM `files` WHERE `id` = :id', array(':id' => $dbfile['id']));
        file_delete(slash($base_path).$dbfile['filename'], $base_path);

        log_console(tr('Deleted files library file ":file"', array(':file' => $dbfile['filename'])), 'green');

        return $dbfile;

    }catch(Exception $e){
        throw new BException('files_delete(): Failed', $e);
    }
}



/*
 * Retrieve history for specified file
 */
function files_get_history($file){
    try{
        $meta_id = sql_get('SELECT `meta_id` FROM `files` WHERE `name` = :name, `hash` = :hash', true, array(':name' => $file, ':hash' => $file));

        if(!$meta_id){
            throw new BException(rt('files_get_history(): Specified file ":file" does not exist', array(':file' => $file)), 'not-exists');
        }

        return meta_history($meta_id);

    }catch(Exception $e){
        throw new BException('files_get_history(): Failed', $e);
    }
}



/*
 * Return data for the specified file
 *
 * This function returns information for the specified file. The file can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package files
 *
 * @param mixed $file The requested file. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @param string $status Filter by the specified status
 * @param natural $categories_id Filter by the specified categories_id. If NULL, the file must NOT belong to any category
 * @return mixed The file data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified file does not exist, NULL will be returned.
 */
function files_get($params){
    try{
        array_ensure($params, 'filename');

        array_default($params, 'filters', array('filename' => $params['filename']));

        array_default($params, 'columns', 'id,
                                           meta_id,
                                           status,
                                           filename,
                                           hash,
                                           type,
                                           meta1,
                                           meta2,
                                           description');

        return sql_simple_get($params);

    }catch(Exception $e){
        throw new BException('files_get(): Failed', $e);
    }
}



/*
 * Return a list of all available files
 *
 * This function wraps sql_simple_list() and supports all its options, like columns selection, filtering, ordering, and execution method
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @package files
 * @see sql_simple_list()
 *
 * @param params $params The list parameters
 * @return mixed The list of available templates
 */
function files_list($params){
    try{
        array_ensure($params);
        array_default($params, 'columns', 'hash,filename');
        array_default($params, 'orderby', array('filename' => 'asc'));

        $params['table'] = 'files';

        return sql_simple_list($params);

    }catch(Exception $e){
        throw new BException('files_list(): Failed', $e);
    }
}



/*
 * Search for files without an entry in the `files` table, or entries in the `files` table with the file missing, and execute the specified action
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @package files
 *
 * @return natural The amount of orphaned files, and orphaned `files` entries found and processed
 */
function files_search_orphans(){
    try{
        $root       = ROOT.'data/files/';
        $quarantine = $root.'quarantine/orphans/';
        $files      = files_list(array('columns' => 'filename,hash',
                                       'filters' => array('!status' => 'orphaned')));

        log_file('Searching `files` table for orphaned entries', 'cyan');
        file_ensure_path($quarantine);

        foreach($files as $file){
            if(!file_exists($root.$file['file'])){
                $update->execute(array(':hash' => $file['file']));
                log_file('Files entry `:file` has the file missing, set the entry to status "orphaned"', array(':file' => $file['hash']), 'yellow');
            }
        }

        file_tree_execute(array('path'     => $root,
                                'function' => function($entry) use ($update, $root, $quarantine, &$count){
                                    $exists = file_exists($entry);

                                    if(!$exists){
                                        $count++;
                                        $file = file_from($entry, $root);

                                        /*
                                         * Ensure that the path for the file
                                         * exists
                                         *
                                         * Ensure that the file itself does not
                                         * exist
                                         */
                                        file_ensure_path(dirname($quarantine.$file));
                                        file_delete($quarantine.$file, ROOT.'data/files/quarantine/orphans/');

                                        /*
                                         * Move the file to quarantine
                                         */
                                        rename($entry, $quarantine.$file);
                                        log_file('Files library file `:file` has no database entry, moved the file to quarantine location ":location"', array(':file' => $file, ':location' => $quarantine.$file['file']), 'yellow');
                                    }
                                }));

        return $count;

    }catch(Exception $e){
        throw new BException('files_search_orphans(): Failed', $e);
    }
}



/*
 * Search for files without an entry in the `files` table, or entries in the `files` table with the file missing, and execute the specified action
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @package files
 *
 * @return natural The amount of orphaned files, and orphaned `files` entries found and processed
 */
function files_clear_quarantine($section = null){
    try{
        $path = ROOT.'data/files';

        if($section){
            log_console(tr('Clearing all quarantined files in the ":section" section', array(':section' => $section)), 'yellow');

            if(!is_string($section)){
                throw new BException(tr('files_clear_quarantine(): Invalid section ":section" specified', array(':section' => $section)), $e);
            }

            $path .= '/'.$section;

        }else{
            log_console(tr('Clearing all quarantined files'), 'yellow');
        }

        return file_delete($path, ROOT.'data/files');

    }catch(Exception $e){
        throw new BException(tr('files_clear_quarantine(): Failed'), $e);
    }
}
