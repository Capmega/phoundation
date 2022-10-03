<?php
/*
 * DOC library
 *
 * This library is a documentation scanner / generator. It will scan projects,
 * and generate documentation for them
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
 * @package
 *
 * @param
 * @return
 */
function doc_library_init() {
    try {
        load_config('doc');

    }catch(Exception $e) {
        throw new CoreException('doc_library_init(): Failed', $e);
    }
}



/*
 * Have parse THIS project
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 */
function doc_parse_this() {
    try {
        load_libs('validate,seo');
        doc_clear(PROJECT);

        $project = doc_insert_project(PROJECT, LANGUAGE);
        $count   = doc_parse_path($project, ROOT, ROOT);
        $errors  = doc_errors();

        if ($errors) {
            log_console(tr('Finished documentation parsing with the following issues:'));

            foreach ($errors as $error) {
                log_console($error, 'yellow');
            }

        } else {
            log_console(tr('Finished documentation parsing succesfully'));
        }

        return $count;

    }catch(Exception $e) {
        throw new CoreException('doc_parse_project(): Failed', $e);
    }
}



/*
 * Parse the specified project
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $path The project that should be parsed
 */
function doc_parse_project($project) {
    try {
        $path = ROOT.'../'.$project;

        if (!file_exists($path, $path)) {
            throw new CoreException(tr('doc_parse_project(): Specified project ":project" does not exist', array(':project' => $project)), 'not-exists');
        }

        load_libs('validate,seo');
        doc_clear($project);

        $project = doc_insert_project($project, LANGUAGE);
        $count   = doc_parse_path($project, $path, $path);
        $errors  = doc_errors();

        if ($errors) {
            log_console(tr('Finished documentation parsing with the following issues:'));

            foreach ($errors as $error) {
                log_console($error, 'yellow');
            }

        } else {
            log_console(tr('Finished documentation parsing succesfully'));
        }

        return $count;

    }catch(Exception $e) {
        throw new CoreException('doc_parse_project(): Failed', $e);
    }
}



/*
 * Parse the specified path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $path The path that should be parsed
 */
function doc_parse_path($project, $path, $root, $recursive = true) {
    try {
        if (!file_exists($path)) {
            throw new CoreException(tr('doc_parse_path(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
        }

        log_console(tr('Parsing path ":path"', array(':path' => $path)), 'VERBOSEDOT/cyan');

        $count = 0;
        $files = scandir($path);

        foreach ($files as $file) {
            if (substr($file, 0, 1) == '.') {
                continue;
            }

            if (!file_exists($path.$file)) {
                if (is_link($path.$file)) {
                    log_console(tr('Ignoring broken symlink ":file"', array(':file' => $file)), 'VERYVERBOSEDOT/yellow');
                    continue;
                }

                throw new CoreException(tr('doc_parse_path(): Found non existing file ":file"', array(':file' => $path.$file)), 'not-exists');
            }

            if (is_dir($path.$file)) {
                if (is_link($path.$file)) {
                    log_console(tr('Ignoring symlink directory ":path"', array(':path' => $path)), 'VERYVERBOSE/yellow');
                    continue;
                }

                if ($recursive) {
                    $count += doc_parse_path($project, $path.$file.'/', $root, $recursive);
                }

                continue;
            }

            if (is_file($path.$file)) {
                if (is_link($path.$file)) {
                    log_console(tr('Ignoring symlink file ":file"', array(':file' => $file)), 'VERYVERBOSE/yellow');
                    continue;
                }

                doc_parse_file($project, $path.$file, $root);
                continue;
            }

            log_console(tr('Ignoring file ":file" of type ":type"', array(':file' => $file, ':type' => file_type($path.$file))), 'VERBOSEDOT/yellow');
        }

    }catch(Exception $e) {
        throw new CoreException('doc_parse_path(): Failed', $e);
    }
}



/*
 * Parse the specified file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $file
 */
function doc_parse_file($project, $file, $root) {
    try {
        $extension = Strings::fromReverse($file, '.');
        $extension = strtolower($extension);
        $path      = Strings::from($file, $root);
        $path      = dirname($path);
        $type      = 'unknown';

        /*
         * Parse all supported extensions
         */
        switch ($extension) {
            case 'php':
                /*
                 * Detect file type
                 */
                if (preg_match('/^config$/', $path)) {
                    $type = 'configuration';

                } elseif (preg_match('/^config\/base$/', $path)) {
                    $type = 'configuration';

                } elseif (preg_match('/^scripts$/', $path)) {
                    $type = 'script';

                } elseif (preg_match('/^scripts\/base$/', $path)) {
                    $type = 'script';

                } elseif (preg_match('/^www\/en\/libs$/', $path)) {
                    $type = 'library';

                } elseif (preg_match('/^www\/[a-z]{2}\/libs$/', $path)) {
                    $type = 'ignore';

                } elseif (preg_match('/^www\/en$/', $path)) {
                    $type = 'webpage';

                } elseif (preg_match('/^www\/api$/', $path)) {
                    $type = 'api';

                } elseif (preg_match('/^www\/en\/ajax$/', $path)) {
                    $type = 'ajax';

                } elseif (preg_match('/^www\/[a-z]{2}$/', $path)) {
                    $type = 'ignore';

                } elseif (preg_match('/^init\/[a-z-]+$/', $path)) {
                    $type = 'init';

                }

                /*
                 * Get file contents
                 */
                switch ($type) {
                    case 'ignore':
                        // no-break
                    case 'unknown':
                        /*
                         * Do not process these types
                         */
                        break;

                    default:
                        $contents = file_get_contents($file);

                        if (!preg_match('/^<\?php/', $contents)) {
                            doc_errors(tr('File ":file" is a PHP file but is missing a valid PHP open tag', array(':file' => $file)));
                            return false;
                        }
                }

                /*
                 * Parse all supported file types
                 */
                switch ($type) {
                    case 'library':
                        return doc_parse_library($project, $file, $contents);

                    case 'chapter':
                        return doc_parse_chapter($project, $file, $contents);

                    case 'page':
                        return doc_parse_page($project, $file, $contents);

                    case 'webpage':
                        return doc_parse_webpage($project, $file, $contents);

                    case 'api':
                        return doc_parse_api($project, $file, $contents);

                    case 'ajax':
                        return doc_parse_ajax($project, $file, $contents);

                    case 'script':
                        return doc_parse_script($project, $file, $contents);

                    case 'configuration':
                        return doc_parse_configuration($project, $file, $contents);

                    case 'init':
                        return doc_parse_init($project, $file, $contents);

                    case 'ignore':
                        log_console(tr('Ignoring file ":file"', array(':file' => $file)), 'VERYVERBOSE/yellow');
                        return;

                    case 'unknown':
                        log_console(tr('Ignoring unknown file ":file"', array(':file' => $file)), 'VERBOSE/yellow');
                        return;

                    default:
                        throw new CoreException(tr('doc_parse_file(): Unknown type ":type" specified', array(':type' => $type)), 'unknown');
                }

            case 'css':
                $contents = file_get_contents($file);
                return doc_parse_css_file($project, $file, $contents);

            case 'js':
                $contents = file_get_contents($file);
                return doc_parse_js_file($project, $file, $contents);

            default:
                log_console(tr('Ignoring file ":file", it\'s filetype is not supported', array(':file' => $file)), 'VERBOSE/yellow');
        }

    }catch(Exception $e) {
        throw new CoreException('doc_parse_file(): Failed', $e);
    }
}



/*
 * Parse the specified CSS file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $file
 */
function doc_parse_css_file($project, $file, $content) {
    try {

    }catch(Exception $e) {
        throw new CoreException('doc_parse_css_file(): Failed', $e);
    }
}



/*
 * Parse the specified javascript file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $file
 */
function doc_parse_js_file($project, $file, $content) {
    try {

    }catch(Exception $e) {
        throw new CoreException('doc_parse_js_file(): Failed', $e);
    }
}



/*
 * Parse the header commentary of the specified file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $file
 */
function doc_parse_file_header($page, $file, $contents) {
    try {
        if (!preg_match_all('/^<\?php\s\/\*(.+?)\*\//imus', $contents, $matches)) {
            doc_errors(tr('File ":file" does not have a valid file header'));
            return false;
        }

        $headers = $matches[1][0];
        $headers = explode("\n", $headers);
        $current = 'title';

        foreach ($headers as $header) {
            try {
                $header = Strings::from($header, '*');
                $header = trim($header);

                if (!$header) {
                    continue;
                }

                if (substr($header, 0, 1) === '@') {
                    $value             = doc_parse_value($header);
                    $value['pages_id'] = $page['id'];
                    doc_insert_value($value);

                } else {
                    switch ($current) {
                        case 'title':
                            doc_insert_value(array('pages_id' => $page['id'],
                                                   'key'      => 'title',
                                                   'value'    => $header));
                            $current = 'paragraph';
                            break;

                        case 'paragraph':
                            doc_insert_value(array('pages_id' => $page['id'],
                                                   'key'      => 'paragraph',
                                                   'value'    => $header));
                            break;
                    }
                }

            }catch(Exception $e) {
                /*
                 * Register parsing error and continue
                 */
                doc_errors(tr('Failed to parse file header line ":line" in file ":file" with error ":e"', array(':file' => $file, ':line' => $header, ':e' => $e->getMessage())));
            }
        }

    }catch(Exception $e) {
        throw new CoreException('doc_parse_file_header(): Failed', $e);
    }
}



/*
 * Parse the specified configuration file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $file
 */
function doc_parse_configuration($project, $file, $content) {
    try {

    }catch(Exception $e) {
        throw new CoreException('doc_parse_configuration(): Failed', $e);
    }
}



/*
 * Parse the specified init file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $file
 */
function doc_parse_init($project, $file, $content) {
    try {

    }catch(Exception $e) {
        throw new CoreException('doc_parse_init(): Failed', $e);
    }
}



/*
 * Parse the specified library file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $file
 */
function doc_parse_webpage($project, $file, $content) {
    try {

    }catch(Exception $e) {
        throw new CoreException('doc_parse_webpage(): Failed', $e);
    }
}



/*
 * Parse the specified api file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $file
 */
function doc_parse_api($project, $file, $content) {
    try {

    }catch(Exception $e) {
        throw new CoreException('doc_parse_api(): Failed', $e);
    }
}



/*
 * Parse the specified ajax file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $file
 */
function doc_parse_ajax($project, $file, $content) {
    try {

    }catch(Exception $e) {
        throw new CoreException('doc_parse_ajax(): Failed', $e);
    }
}



/*
 * Parse the specified library file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $file
 */
function doc_parse_library($project, $file, $content) {
    try {
        /*
         * Generate page
         * Parse library header
         */
        $name    = Strings::until(basename($file), '.');
        $libpage = doc_insert_page(array('projects_id' => $project['id'],
                                         'type'        => 'library',
                                         'name'        => $name,
                                         'package'     => $name));

        log_console(tr('Parsing library file ":file"', array(':file' => $file)), 'VERBOSEDOT/cyan');
        doc_parse_file_header($libpage, $file, $content);

        /*
         * Parse all function headers
         * First cut on "\*\/ function" so that we can parse each individual
         * function body easily. Then add the header from THIS function to the
         * NEXT one since the "easy" cut that we made left the headers of each
         * next function at the bottom of each curent function snippet
         */
        $functions = preg_split('/\*\/\sfunction /imus', $content);

        if (count($functions) < 2) {
            doc_errors(tr('File ":file" does not contain any function headers', array(':file' => $file)));

        } else {
            /*
             * The first iterration will NOT have a function but will have the
             * header for the function for the next iterration
             */
            $function = array_shift($functions);
            $header   = Strings::fromReverse($function, '/*');

            foreach ($functions as $function) {
                /*
                 * Split function into function / header sections
                 */
                $header_next = Strings::fromReverse($function, '/*');
                $function    = Strings::untilReverse($function, '/*');
                $functons[]  = doc_parse_function($project, $libpage, $file, $function);

                doc_parse_function_header($page, $libpage, $file, $header);

                /*
                 * Set the header for the next function
                 */
                $header = $header_next;
            }
        }

        /*
         * Add all functions to library index
         */
        foreach ($functions as $function) {
            doc_insert_value(array('pages_id' => $libpage['id'],
                                   'key'      => 'function',
                                   'value'    => 'seoname'));
        }

        unset($functions);
        unset($function);

        /*
         * Parse all classes
         */

    }catch(Exception $e) {
        throw new CoreException('doc_parse_library(): Failed', $e);
    }
}



/*
 * Parse the specified comment section
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param params $project
 * @param string $library
 * @param string $file
 * @param string $content
 * @return params
 */
function doc_parse_function($project, $parent, $file, $content) {
    try {
        /*
         * Create function page
         */
        $name = trim(Strings::until($content, '('));
        $page = doc_insert_page(array('projects_id' => $project['id'],
                                      'parents_id'  => $parent['id'],
                                      'type'        => 'function',
                                      'name'        => $name,
                                      'package'     => $parent['name']));

        log_console(tr('Processing function ":function"', array(':function' => $name)), 'VERYVERBOSE/cyan');

        /*
         * Parse function signature
         */

        return $page;

    }catch(Exception $e) {
        throw new CoreException('doc_parse_function(): Failed', $e);
    }
}



/*
 * Parse the specified function header
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param params $page
 * @param string $parent
 * @param string $tag
 * @param string $tag
 */
function doc_parse_function_header($page, $parent, $file, $content) {
    try {
        $content = explode("\n", $content);
        $current = 'title';

        foreach ($content as $header) {
            try {
                $header = Strings::from($header, '*');
                $header = trim($header);

                if (!$header) {
                    continue;
                }

                if (substr($header, 0, 1) === '@') {
                    switch ($current) {
                        case 'example':
                            /*
                             * End multi line tag
                             */
                            doc_insert_value($value);
                    }

                    $value             = doc_parse_value($header);
                    $value['pages_id'] = $page['id'];

                    $current = $value['key'];

                    doc_insert_value($value);

                } else {
                    switch ($current) {
                        case 'example':
                            /*
                             * This tag can span multiple lines
                             */
                            $value['value'] .= "\n".$header;
                            break;

                        case 'title':
                            doc_insert_value(array('pages_id' => $page['id'],
                                                   'key'      => 'title',
                                                   'value'    => $header));
                            $current = 'paragraph';
                            break;

                        case 'paragraph':
                            doc_insert_value(array('pages_id' => $page['id'],
                                                   'key'      => 'paragraph',
                                                   'value'    => $header));
                            break;

                        default:
                            throw new CoreException(tr('doc_parse_function_header(): Unknown current ":current" encountered', array(':current' => $current)), 'unknown');
                    }
                }

            }catch(Exception $e) {
                /*
                 * Register parsing error and continue
                 */
                doc_errors(tr('Failed to parse function header line ":line" in file ":file" with error ":e"', array(':file' => $file, ':line' => $header, ':e' => $e->getMessage())));
            }
        }

    }catch(Exception $e) {
        throw new CoreException('doc_parse_function_header(): Failed', $e);
    }
}



/*
 * Insert a new project in the documentation database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param params $project
 * @return params
 */
function doc_insert_project($project, $language = null) {
    try {
        $project = doc_validate_project($project, $language);

        sql_query('INSERT INTO `doc_projects` (`createdby`, `meta_id`, `name`, `seoname`, `language`)
                   VALUES                     (:createdby , :meta_id , :name , :seoname , :language )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':meta_id'   => meta_action(),
                         ':name'      => $project['name'],
                         ':seoname'   => $project['seoname'],
                         ':language'  => $project['language']));

        $project['id'] = sql_insert_id();

        return $project;

    }catch(Exception $e) {
        throw new CoreException('doc_insert_project(): Failed', $e);
    }
}



/*
 * Validate the specified project data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param params $project
 * @return params
 */
function doc_validate_project($project, $language = null) {
    try {
        if (is_string($project)) {
            $project = array('name' => $project);
        }

        $v = new ValidateForm($project, 'name,language');

        array_default($project, 'language', $language);

        $v->isNotEmpty($project['name'], tr('Please specifiy a project name'));
        $v->hasMinChars($project['name'],  2, tr('Please specifiy a project name with at least 2 characters'));
        $v->hasMaxChars($project['name'], 32, tr('Please specifiy a project name with less than 32 characters'));

        $v->isNotEmpty($project['language'], tr('Please specifiy a project name'));
        $v->hasMinChars($project['language'], 2, tr('Please specifiy a project language of 2 characters'));
        $v->hasMaxChars($project['language'], 2, tr('Please specifiy a project language of 2 characters'));

        $v->isValid();

        $project['seoname'] = seo_unique($project['name'], 'doc_projects', isset_get($project['id'], 0));

        return $project;

    }catch(Exception $e) {
        throw new CoreException('doc_validate_project(): Failed', $e);
    }
}



/*
 * Insert a new page in the documentation database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param params $page
 * @return params
 */
function doc_insert_page($page) {
    try {
        $page = doc_validate_page($page);

        sql_query('INSERT INTO `doc_pages` (`createdby`, `meta_id`, `projects_id`, `parents_id`, `name`, `seoname`, `package`, `type`)
                   VALUES                  (:createdby , :meta_id , :projects_id , :parents_id , :name , :seoname , :package , :type )',

                   array(':createdby'   => isset_get($_SESSION['user']['id']),
                         ':meta_id'     => meta_action(),
                         ':projects_id' => $page['projects_id'],
                         ':parents_id'  => $page['parents_id'],
                         ':name'        => $page['name'],
                         ':seoname'     => $page['seoname'],
                         ':package'     => $page['package'],
                         ':type'        => $page['type']));

        $page['id'] = sql_insert_id();

        return $page;

    }catch(Exception $e) {
        throw new CoreException('doc_insert_page(): Failed', $e);
    }
}



/*
 * Validate the specified page data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param params $page
 * @return params
 */
function doc_validate_page($page) {
    try {
        $v = new ValidateForm($page, 'parents_id,projects_id,name,package,type');

        $v->isNotEmpty($page['name'], tr('Please specifiy a page name'));
        $v->hasMinChars($page['name'],  2, tr('Please specifiy a page name with at least 2 characters'));
        $v->hasMaxChars($page['name'], 64, tr('Please specifiy a page name with less than 64 characters'));

        /*
         * Validate the projects_id
         */
        $v->isNotEmpty($page['projects_id'], tr('Please specifiy a projects id'));

        $exists = sql_get('SELECT `id` FROM `doc_projects` WHERE `id` = :id', true, array(':id' => $page['projects_id']));

        if (!$exists) {
            $v->setError(tr('The specified projects_id ":projects_id" does not exist', array(':projects_id' => $page['projects_id'])));
        }

        /*
         * Validate the package
         */
        if ($page['package']) {
            $v->hasMinChars($page['package'],  2, tr('Please specifiy a page package with at least 2 characters'));
            $v->hasMaxChars($page['package'], 64, tr('Please specifiy a page package with less than 64 characters'));

        } else {
            $page['package'] = null;
        }

        /*
         * Validate the parent
         */
        if ($page['parents_id']) {
            $exists = sql_get('SELECT `id` FROM `doc_pages` WHERE `id` = :id', true, array(':id' => $page['parents_id']));

            if (!$exists) {
                $v->setError(tr('The specified parents page ":parent" does not exist', array(':parent' => $page['seoparent'])));
                $page['parents_id'] = null;
            }

        } else {
            $page['parents_id'] = null;
        }

        $v->inArray($page['type'], array('function', 'class', 'library', 'chapter', 'page', 'webpage', 'script'), tr('Unknown page type ":type" specified', array(':type' => $page['type'])));
        $v->isValid();

        $page['seoname'] = seo_unique($page['name'], 'doc_pages', isset_get($page['id'], 0));

        return $page;

    }catch(Exception $e) {
        throw new CoreException('doc_validate_page(): Failed', $e);
    }
}



/*
 *  the specified page data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param string $line
 * @return params
 */
function doc_parse_value(string $line) {
    try {
        if (!preg_match_all('/^@\s?([a-z-]+)\s?:?\s?(.+)/', $line, $matches)) {
            throw new CoreException(tr('doc_parse_value(): Specified line ":line" is of an incorrect format', array(':line' => $line)), 'invalid');
        }

        $value['key']   = $matches[1][0];
        $value['value'] = $matches[2][0];

        return $value;

    }catch(Exception $e) {
        throw new CoreException('doc_parse_value(): Failed', $e);
    }
}



/*
 * Insert a new value in the documentation database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param params $value
 * @return params
 */
function doc_insert_value($value) {
    try {
        $value = doc_validate_value($value);

        sql_query('INSERT INTO `doc_values` (`createdby`, `meta_id`, `pages_id`, `key`, `value`)
                   VALUES                   (:createdby , :meta_id , :pages_id , :key , :value )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':meta_id'   => meta_action(),
                         ':pages_id'  => $value['pages_id'],
                         ':key'       => $value['key'],
                         ':value'     => $value['value']));

        $value['id'] = sql_insert_id();

        return $value;

    }catch(Exception $e) {
        throw new CoreException('doc_insert_value(): Failed', $e);
    }
}



/*
 * Validate the specified value data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 * @param params $value
 * @return params
 */
function doc_validate_value($value) {
    try {
        $v = new ValidateForm($value, 'pages_id,key,value');

        $v->isNotEmpty($value['key'], tr('Please specifiy a key'));
        $v->inArray($value['key'], array('category', 'package', 'title', 'paragraph', 'author', 'copyright', 'license', 'see', 'table', 'note', 'version', 'example', 'params', 'param', 'return', 'exception'), tr('Unknown key ":key" specified', array(':key' => $value['key'])));

        $v->isNotEmpty($value['pages_id'], tr('Please specifiy a pages_id'));

        $exists = sql_get('SELECT `id` FROM `doc_pages` WHERE `id` = :id', true, array(':id' => $value['pages_id']));

        if (!$exists) {
            $v->setError(tr('The specified page with id ":pages_id" does not exist', array(':pages_id' => $value['pages_id'])));
        }

        $v->isValid();

        return $value;

    }catch(Exception $e) {
        throw new CoreException('doc_validate_value(): Failed', $e);
    }
}



/*
 * Generate documentation
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 */
function doc_generate($project = null) {
    try {
        if (!$project) {
            $project = PROJECT;
        }

        $projects_id = sql_get('SELECT `id` FROM `doc_projects` WHERE `name` = :name', true, array(':name' => $project));
        $formats     = array('txt', 'html', 'include', 'pdf');
        $formats     = array('txt', 'html');

        if (!$projects_id) {
            throw new CoreException(tr('Failed to generate documentation for project ":project", it does not exist', array(':project' => $project)), 'not-exists');
        }

        log_console(tr('Generating documentation for project ":project"', array(':project' => $project)), 'white');
        log_console(tr('Deleting old documentation'));

        file_delete(array(ROOT.'data/doc/pdf',
                          ROOT.'data/doc/txt',
                          ROOT.'data/doc/html',
                          ROOT.'data/doc/include'), ROOT.'data/doc');

        log_console(tr('Generating documentation in ":format" format', array(':format' => $format)), 'cyan');

        foreach ($formats as $format) {
            $pages = sql_query('SELECT `id`, `name`, `seoname`, `type` FROM `doc_pages` WHERE `projects_id` = :projects_id', array(':projects_id' => $projects_id));

            foreach ($pages as $page) {
                try {
                    $page['values'] = sql_list('SELECT `id`, `key`, `value` FROM `doc_values` WHERE `pages_id` = :pages_id', array(':pages_id' => $page['id']));
                    doc_generate_page($format, $page);

                }catch(Exception $e) {
                    doc_errors(tr('Failed to generate documentation page ":page" because of error ":e"', array(':page' => $page, ':e' => $e->getMessage())));
                }
            }
        }

        $errors  = doc_errors();

        if ($errors) {
            log_console(tr('Finished documentation parsing with the following issues:'));

            foreach ($errors as $error) {
                log_console($error, 'yellow');
            }

        } else {
            log_console(tr('Finished documentation parsing succesfully'));
        }

    }catch(Exception $e) {
        throw new CoreException('doc_generate(): Failed', $e);
    }
}



/*
 * Generate documentation
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 */
function doc_generate_page($format, $page) {
    try {
        $path = ROOT.'data/doc/';
        $keys = array('category',
                      'package',
                      'title',
                      'author',
                      'copyright',
                      'license',
                      'return');

        $multis = array('params',
                        'paragraphs',
                        'examples',
                        'exceptons',
                        'notes',
                        'sees',
                        'tables',
                        'versions');

        log_console(tr('Generating page ":page"', array(':page' => $page['name'])), 'VERYVERBOSEDOT/cyan');
        file_ensure_path($path.$format);

        $data = file_get_contents($path.'templates/'.$format.'/'.$page['type'].'.'.$format);
        $data = str_replace(':name', isset_get($page['name']), $data);

        foreach ($multis as $multi) {
            $replace = '';

            /*
             * Replace all "multi values", that is to say, the values that have
             * only one key in the template, but multiple values in the database
             */
            foreach ($page['values'] as $key => $value) {
                if ($value['key'] == $multi) {
                    $replace .= $value['value']."\n";
                    unset($page['values'][$key]);
                }
            }

            if ($replace) {
                $data = str_replace(':'.$multi."\n", $replace, $data);
            }
        }

        foreach ($keys as $key) {
            /*
             * Replace all the single values
             */
            foreach ($page['values'] as $value) {
                if ($value['key'] == $key) {
                    $data = str_replace(':'.$key, $value['value'], $data);
                    break;
                }
            }
        }

        file_put_contents($path.$format.'/'.$page['name'].'.'.$format, $data);

    }catch(Exception $e) {
        throw new CoreException('doc_generate_page(): Failed', $e);
    }
}



/*
 * Register errors
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 */
function doc_errors($error = null) {
    static $errors = array();

    try {
        if ($error) {
            $errors[] = $error;
            return $error;
        }

        return $errors;

    }catch(Exception $e) {
        throw new CoreException('doc_errors(): Failed', $e);
    }
}



/*
 * Clears documentation database by clearing everything from the doc_pages and doc_values tables
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package doc
 *
 */
function doc_clear($project) {
    try {
        $id = sql_get('SELECT `id` FROM `doc_projects` WHERE `name` = :name OR `seoname` = :seoname', true, array(':name' => $project, ':seoname' => $project));

        if (!$project) {
            log_console(tr('Not deleting project ":project", it does not exist', array(':project' => $project)), 'yellow');
            return false;
        }

        sql_query('DELETE FROM `doc_pages`    WHERE `projects_id` = :projects_id', array(':projects_id' => $id));
        sql_query('DELETE FROM `doc_projects` WHERE `id`          = :id'         , array(':id'          => $id));
        return true;

    }catch(Exception $e) {
        throw new CoreException('doc_errors(): Failed', $e);
    }
}
?>
