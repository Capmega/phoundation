<?php
/*
 * DOC library
 *
 * This library is a documentation scanner / generator. It will scan projects,
 * and generate documentation for them
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
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
 * @package
 *
 * @param
 * @return
 */
function doc_library_init(){
    try{
        load_config('doc');

    }catch(Exception $e){
        throw new bException('doc_library_init(): Failed', $e);
    }
}



/*
 * Have parse THIS project
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 */
function doc_parse_this(){
    try{
        return doc_parse_path(ROOT);

    }catch(Exception $e){
        throw new bException('doc_parse_project(): Failed', $e);
    }
}



/*
 * Parse the specified project
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $path The project that should be parsed
 */
function doc_parse_project($project){
    try{
        $path = ROOT.'../'.$project;

        if(!file_exists($path)){
            throw new bException(tr('doc_parse_project(): Specified project ":project" does not exist', array(':project' => $project)), 'not-exists');
        }

        return doc_parse_file_path($path);

    }catch(Exception $e){
        throw new bException('doc_parse_project(): Failed', $e);
    }
}



/*
 * Parse the specified path
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $path The path that should be parsed
 */
function doc_parse_path($path, $root, $recursive = true){
    try{
        if(!file_exists($path)){
            throw new bException(tr('doc_parse_path(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
        }

        load_libs('validate');

        $count = 0;
        $files = scandir($path);

        foreach($files as $file){
            if(is_dir($file)){
                if($recursive){
                    $count += doc_parse_path($path, $recursive);
                }

                continue;
            }

            if(is_file($file)){
                doc_parse_file($file);
                continue;
            }

            log_console(t('Ignoring file of type ":type"', array(':file' => file_type($file))), 'VERBOSE/yellow');
        }

    }catch(Exception $e){
        throw new bException('doc_parse_path(): Failed', $e);
    }
}



/*
 * Parse the specified file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $file
 */
function doc_parse_file($file, $root, $type){
    try{
        $extension = str_rfrom($file, '.');
        $extension = strtolower($file);

        switch($extension){
            case 'php':
                $contents = file_get_contents($file);

                switch($type){
                    case 'function':
                        return doc_parse_function($contents);

                    case 'class':
                        return doc_parse_class($contents);

                    case 'library':
                        return doc_parse_library($contents);

                    case 'chapter':
                        return doc_parse_chapter($contents);

                    case 'page':
                        return doc_parse_page($contents);

                    case 'webpage':
                        return doc_parse_webpage($contents);

                    case 'script':
                        return doc_parse_script($contents);

                    default:
                        throw new bException(tr('doc_parse_file(): Unknown type ":type" specified', array(':type' => $type)), 'unknown');
                }

            case 'css':
                return doc_parse_css_file($file);

            case 'js':
                return doc_parse_js_file($file);

            default:
                log_console(tr('Ignoring file ":file", it\'s filetype is not supported', array(':file' => $file)), 'VERBOSE/yellow');
        }

    }catch(Exception $e){
        throw new bException('doc_parse_file(): Failed', $e);
    }
}



/*
 * Parse the specified CSS file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $file
 */
function doc_parse_css_file($file){
    try{

    }catch(Exception $e){
        throw new bException('doc_parse_css_file(): Failed', $e);
    }
}



/*
 * Parse the specified javascript file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $file
 */
function doc_parse_js_file($file){
    try{

    }catch(Exception $e){
        throw new bException('doc_parse_js_file(): Failed', $e);
    }
}



/*
 * Parse the header commentary of the specified file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $file
 */
function doc_parse_file_header($contents){
    try{

    }catch(Exception $e){
        throw new bException('doc_parse_file_header(): Failed', $e);
    }
}



/*
 * Parse the specified library file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $file
 */
function doc_parse_library($file){
    try{
        /*
         * Generate page
         * Parse library header
         */

        /*
         * Parse all functions
         */

        /*
         * Parse all classes
         */

    }catch(Exception $e){
        throw new bException('doc_parse_library(): Failed', $e);
    }
}



/*
 * Parse the specified comment section
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $comment
 */
function doc_parse_function($function){
    try{

    }catch(Exception $e){
        throw new bException('doc_parse_function(): Failed', $e);
    }
}



/*
 * Parse the specified file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $tag
 */
function doc_parse_function_doc($tag){
    try{

    }catch(Exception $e){
        throw new bException('doc_parse_function_doc(): Failed', $e);
    }
}



/*
 * Insert a new page in the documentation database
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param params $page
 * @return params
 */
function doc_insert_page($page){
    try{
        $page = doc_validate_page($page);

        sql_query('INSERT INTO `doc_pages` (`createdby`, `meta_id`, `parents_id`, `name`, `seoname`, `package`, `type`)
                   VALUES                  (:createdby , :meta_id , :parents_id , :name , :seoname , :package , :type )',

                   array(':createdby'  => isset_get($_SESSION['user']['id']),
                         ':meta_id'    => meta_action(),
                         ':parents_id' => $page['parents_id'],
                         ':name'       => $page['name'],
                         ':seoname'    => $page['seoname'],
                         ':package'    => $page['package'],
                         ":type"       => $page['type']));

        $page['id'] = sql_insert_id();

        return $page;

    }catch(Exception $e){
        throw new bException('doc_insert_page(): Failed', $e);
    }
}



/*
 * Validate the specified page data
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param params $page
 * @return params
 */
function doc_validate_page($page){
    try{
        $v = new validate_form($page, 'seoparent,name,package,type');

        $v->isNotEmpty($server['name'], tr('Please specifiy a page name'));
        $v->hasMinChars($server['name'],  2, tr('Please specifiy a page name with at least 2 characters'));
        $v->hasMaxChars($server['name'], 32, tr('Please specifiy a page name with at least 32 characters'));

        if($page['package']){
            $v->hasMinChars($server['package'],  2, tr('Please specifiy a page package with at least 2 characters'));
            $v->hasMaxChars($server['package'], 32, tr('Please specifiy a page package with at least 32 characters'));

        }else{
            $page['package'] = null;
        }

        if($page['seoparent']){
            $page['parents_id'] = sql_get('SELECT `id` FROM `doc_pages` WHERE `seoname` = :seoname', true, array(':seoname' => $page['seoparent']));

            if(!$page['parent']){
                $v->setError(tr('The specified parents page ":parent" does not exist', array(':parent' => $page['seoparent'])));
            }
        }

        $v->inArray($server['type'], array('function', 'class', 'library', 'chapter', 'page', 'webpage', 'script'), tr('Unknown page type ":type" specified', array(':type' => $page['type'])));
        $v->isValid();

        return $page;

    }catch(Exception $e){
        throw new bException('doc_validate_page(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 */
function doc_insert_function(){
    try{

    }catch(Exception $e){
        throw new bException('doc_insert_function(): Failed', $e);
    }
}



/*
 * Add documentation about a function or method
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 */
function doc_insert_file_header(){
    try{
        $header = doc_validate_file_header($header);

        sql_query('INSERT INTO `doc_pages` ()
                   VALUES                  ()');

    }catch(Exception $e){
        throw new bException('doc_insert_file_header(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 */
function doc_insert_class(){
    try{

    }catch(Exception $e){
        throw new bException('doc_insert_class(): Failed', $e);
    }
}



/*
 * Generate documentation
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 */
function doc_generate($template, $project = null){
    try{

    }catch(Exception $e){
        throw new bException('doc_generate(): Failed', $e);
    }
}



/*
 * Generate documentation
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 */
function doc_generate_page($template, $page){
    try{

    }catch(Exception $e){
        throw new bException('doc_generate_page(): Failed', $e);
    }
}
?>
