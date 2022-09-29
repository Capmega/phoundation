<?php
/*
 * Sitemap library
 *
 * This library contains functions to manage available site URL's and generate
 * sitemaps from there
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package sitemap
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the ssh library configuration
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sitemap
 *
 * @return void
 */
function sitemap_library_init(){
    try{
        /*
         * sitemap configuration is required for this library
         */
        load_config('sitemap');

    }catch(Exception $e){
        throw new CoreException('sitemap_library_init(): Failed', $e);
    }
}



/*
 * Regenerate (all) sitemap file(s) for the specified languages
 *
 * If sitemap database does not contain any "file" data then only the
 * sitemap.xml will be created. If it does, the sitemap.xml will be the index
 * file, and the other sitemap files will be auto generated one by one
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sitemap
 * @see sitemap_generate_index_file()
 * @see sitemap_get_index_xml()
 * @see sitemap_generate_xml_file()
 *
 * @return natural The amount of sitemap files generated
 */
function sitemap_generate(){
    global $_CONFIG;

    try{
        $files = sitemap_list_files();

        foreach($files as &$file){
            if(!file_exists(ROOT.'www/'.$file['language'])){
                log_console(tr('Skipped sitemap generation for language ":language1", the "www/:language2" directory does not exist. Check the $_CONFIG[language][supported] configuration', array(':language1' => $file['language'], ':language2' => $file['language'])), 'yellow');
                continue;
            }

            $file['tmp'] = sitemap_generate_xml_file($file['language'], $file['file']);
        }

        unset($file);
        $files['index'] = array('tmp'  => sitemap_generate_index_file($files),
                                'path' => ROOT.'www/sitemap.xml');

        return sitemap_install_files($files);

    }catch(Exception $e){
        /*
         * Remove temporary files
         */
        if(!empty($files)){
            try{
                foreach($files as $file){
                    if(empty($file['tmp'])){
                        continue;
                    }

                    file_delete($file['tmp']);
                }

            }catch(Exception $f){
                /*
                 * Deleting temp files failed too, oh noes!
                 */
                $e->addMessage($f->getMessages());
            }
        }

        throw new CoreException('sitemap_generate(): Failed', $e);
    }
}



/*
 * Install all generated sitemap files in the correct locations
 *
 * Data will first be written to a new temp file, and then be moved over the
 * currently existing one, if one exist
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sitemap
 *
 * @return string the XML that was written to the sitemap index file sitemap.xml
 */
function sitemap_install_files($files){
    global $_CONFIG;

    try{
        sitemap_make_backup();

        $insert = sql_prepare('INSERT INTO `sitemaps_generated` (`language`)
                               VALUES                           (:language )');

        if(count($files) !== 1){
            /*
             * Install the sub sitemap files
             */
            foreach($files as $file){
                if(empty($file['tmp'])){
                    continue;
                }

                file_execute_mode(ROOT.'www/', 0770, function($path) use ($insert, $file){
                    file_execute_mode(ROOT.'www/'.isset_get($file['language']), 0770, function($path) use ($insert, $file){
                        /*
                         * Move sub sitemap files in place
                         */
                        if(empty($file['file'])){
                            if($file['language']){
                                $filename = $file['language'].'/sitemap.xml';

                            }else{
                                $filename = '/sitemap.xml';
                            }

                        }else{
                            if($file['language']){
                                file_ensure_path(ROOT.'www/'.$file['language'].'/sitemaps/');
                                $filename = $file['language'].'/sitemaps/'.$file['file'].'.xml';

                            }else{
                                $filename = '/sitemaps/'.$file['file'].'.xml';
                            }
                        }

                        log_console(tr('Installing sitemap file ":file"', array(':file' => $file['path'])), 'VERBOSE/cyan');

                        file_delete($file['path'], ROOT.'www/');
                        file_ensure_path(dirname($file['path']));

                        rename($file['tmp'], $file['path']);
                        chmod(ROOT.'www/'.$filename, 0440);

                        $insert->execute(array(':language' => $file['language']));
                    });
                });
            }
        }

        return count($files);

    }catch(Exception $e){
        throw new CoreException('sitemap_install_files(): Failed', $e);
    }
}



/*
 * Generate the sitemap index file.
 *
 * Data will first be written to a new temp file, and then be moved over the
 * currently existing one, if one exist
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sitemap
 *
 * @return boolean true if the index file was generated, false if not
 */
function sitemap_generate_index_file($files){
    try{
        $xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
                "    <sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        log_console(tr('Generating sitemap index file'), 'cyan');

        if(count($files) === 1){
            /*
             * There is only one sitemap file, no requirement for an index file.
             * Just use the single sitemap file, move it to the location of the
             * default sitemap.xml file.
             */
            $file = array_pop($files);

            if($file['file']){
                rename(TMP.'sitemaps/'.$file['language'].'/sitemaps/'.$file['file'].'.xml', TMP.'sitemaps/sitemaps/'.$file['file'].'.xml');

            }else{
                rename(TMP.'sitemaps/'.$file['language'].'/sitemap.xml', TMP.'sitemaps/sitemap.xml');
            }

            file_delete(TMP.'sitemaps/'.$file['language']);

        }else{
            foreach($files as $file){
                cli_dot(1);
                $xml .= sitemap_get_index_xml($file);
            }

            $xml .= "</sitemapindex>";
            $file = file_temp(false, 'xml');

            file_put_contents($file, $xml);
            chmod($file, 0440);
            cli_dot(false);
        }

        log_console(tr('Generated sitemap index file ":file"', array(':file' => $file)), 'VERBOSE/green');

        return $file;

    }catch(Exception $e){
        throw new CoreException('sitemap_generate_index_file(): Failed', $e);
    }
}



/*
 * Generate a sitemap.xml file with all sitemap entries that have the specified language and file
 *
 * Data will first be written to a new temp file, and then be moved over the
 * currently existing one, if one exist
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sitemap
 *
 * @param string $language
 * @param string $file
 * @return natural The amount of sitemap entries written
 */
function sitemap_generate_xml_file($language = null, $file = null){
    global $_CONFIG;

    try{
        $execute = array(':language' => $language);
        $query   = 'SELECT `id`,
                           `url`,
                           `page_modifiedon`,
                           `change_frequency`,
                           `priority`,
                           `url`

                    FROM   `sitemaps_data`

                    WHERE  `status` IS NULL
                    AND    `language` = :language ';

        if($file){
            $query   .= ' AND `file` = :file ';
            $execute[':file'] = $file;

        }else{
            $query   .= ' AND `file` IS NULL ';
        }

        $count   = 0;
        $file    = file_temp(true, 'xml');
        $entries = sql_query($query.' ORDER BY (`file` IS NOT NULL), `file` DESC, (`priority` IS NOT NULL), `priority` DESC', $execute);
        $xml     = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
                   "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n";

        log_console(tr('Generating sitemap ":file" for language ":language"', array(':file' => str_replace(ROOT, '', $file), ':language' => $language)), 'cyan');

        while($entry = sql_fetch($entries)){
            $count++;
            $xml .= sitemap_get_entry_xml($entry);
            cli_dot(1, '');
        }

        $xml .= "</urlset>\n";

        log_console(tr('Generated ":count" entries in file ":file"', array(':count' => $count, ':file' => str_replace(ROOT, '', $file))), 'green');
        file_put_contents($file, $xml);

        return $file;

    }catch(Exception $e){
        throw new CoreException('sitemap_generate_xml_file(): Failed', $e);
    }
}



/*
 * Get a sitemap entry
 *
 * Data will first be written to a new temp file, and then be moved over the
 * currently existing one, if one exist
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sitemap
 * @see sitemap_generate_xml_file()
 * @see date_convert() Used to convert the sitemap entry dates
 * @table: `sitemap_data`
 * @note:
 * @version 1.22.0: Added documentation
 * @example
 * code
 * $xml = sitemap_get_entry_xml(array('url'              => 'https://capmega.com/en/',
 *                            'page_modifiedon'  => '2018-11-16 15:34:19',
 *                            'change_frequency' => '7',
 *                            'priority'         => '.7'));
 * showdie($xmil);
 * /code
 *
 * This would return
 * code
 * <url>
 *   <loc>https://rideworks.com/about.html</loc>
 *   <lastmod>2017-01-20T23:26:58-06:00</lastmod>
 *   <changefreq>weekly</changefreq>
 *   <priority>0.70</priority>
 * </url>
 * /code
 *
 * @param params $entry A complete sitemap entry from the `sitemap_data` table
 * @params string $entry[url]
 * @params string $entry[page_modifiedon]
 * @params string $entry[change_frequency]
 * @params string $entry[priority]
 * @return string The XML for the specified sitemap entry
 */
function sitemap_get_entry_xml($entry){
    try{
        if(empty($entry['url'])){
            throw new CoreException(tr('sitemap_get_entry_xml(): No URL specified'), 'not-specified');
        }

        $keys = array('url',
                      'page_modifiedon',
                      'change_frequency',
                      'priority');

        foreach($keys as $key){
            if(!empty($entry[$key])){
                switch($key){
                    case 'url':
                        $retval[] = "    <loc>".$entry[$key]."</loc>\n";
                        break;

                    case 'page_modifiedon':
                        $retval[] = "    <lastmod>".date_convert($entry[$key], 'c')."</lastmod>\n";
                        break;

                    case 'change_frequency':
                        $retval[] = "    <changefreq>".$entry[$key]."</changefreq>\n";
                        break;

                    case 'priority':
                        $retval[] = "    <priority>".number_format($entry[$key], 2)."</priority>\n";
                        break;
                }
            }
        }

        return "<url>\n".implode($retval)."</url>\n";

    }catch(Exception $e){
        throw new CoreException('sitemap_get_entry_xml(): Failed', $e);
    }
}



/*
 * Returns a sitemap file entry for a sitemap index file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sitemap
 * @see sitemap_generate_index_file()
 * @version 1.22.0: Added documentation
 *
 * @param string $file
 * @param mixed $lastmod
 * @return string The XML for the specified sitemap file entry
 */
function sitemap_get_index_xml($file, $lastmod = null){
    try{
        if(empty($file)){
            throw new CoreException(tr('sitemap_get_index_xml(): No file specified'), 'not-specified');
        }

        if(empty($lastmod)){
            $lastmod = date('c');
        }

        if($file['file']){
            $url = domain('/sitemaps/'.$file['file'].'.xml', null, null, null, $file['language']);

        }else{
            $url = domain('/sitemap.xml', null, null, null, $file['language']);
        }

        return  "<sitemap>\n".
                "   <loc>".$url."</loc>\n".
                "   <lastmod>".date_convert($lastmod, 'c')."</lastmod>\n".
                "</sitemap>\n";

    }catch(Exception $e){
        throw new CoreException('sitemap_get_index_xml(): Failed', $e);
    }
}



/*
 * Returns an array list with the sitemap files that are used for the site
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sitemap
 * @see sitemap_generate_index_file()
 * @version 1.22.0: Added documentation
 *
 * @return array The files that are used for this website
 */
function sitemap_list_files(){
    static $retval = null;
    global $_CONFIG;

    try{
        if($retval){
            return $retval;
        }

        $retval = array();

        if(empty($_CONFIG['language']['supported'])){
            /*
             * No multiple languages supported. Only use the default
             */
            $_CONFIG['language']['supported'] = array($_CONFIG['language']['default'] => $_CONFIG['language']['default']);
        }

        foreach($_CONFIG['language']['supported'] as $code => $language){
            $files  = sql_query('SELECT   `file`

                                 FROM     `sitemaps_data`

                                 WHERE    `status` IS NULL

                                 GROUP BY `file`');

            if(!$files->rowCount()){
                throw new CoreException(tr('sitemap_list_files(): No sitemap data available to generate sitemap files from'), 'not-available');

            }

            while($file = sql_fetch($files)){
                if($file['file']){
                    file_ensure_path(ROOT.'www/'.$code.'/sitemaps');
                    $file['path'] = ROOT.'www/'.$code.'/sitemaps/'.$file['file'].'.xml';

                }else{
                    $file['path'] = ROOT.'www/'.$code.'/sitemap.xml';
                }

                $file['language'] = $code;
                $retval[] = $file;
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new CoreException('sitemap_list_files(): Failed', $e);
    }
}



/*
 * Clear the sitemap table
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sitemap
 * @see sitemap_generate_index_file()
 * @version 1.22.0: Added documentation
 *
 * @param mixed $groups
 * @return whole The amount of entries that were deleted from the tables
 */
function sitemap_clear($groups = null){
    try{
        if($groups){
            $in = sql_in($groups);
            $r  = sql_query('DELETE FROM `sitemaps_data` WHERE `group` IN ('.sql_in_columns($in).')', $in);

        }else{
            $r = sql_query('DELETE FROM `sitemaps_data`');
            $r = sql_query('DELETE FROM `sitemaps_generated`');
        }

        return $r->rowCount();

    }catch(Exception $e){
        throw new CoreException('sitemap_clear(): Failed', $e);
    }
}



/*
 * Delete indivitual entries from the sitemap table
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sitemap
 * @see sitemap_generate_index_file()
 * @version 1.22.0: Added documentation
 *
 * @param mixed $list
 * @return whole The amount of deleted entries
 */
function sitemap_delete_entry($list){
    try{
        if(is_array($list) or is_numeric($list) or (is_string($list) and strstr($list, ','))){
            /*
             * Delete by one or multiple id's
             */
            $in = sql_in(array_force($list));
            $r  = sql_query('DELETE FROM `sitemaps_data` WHERE `id` IN ('.sql_in_columns($in).')', $in);

        }else{
            /*
             * Delete by URL
             */
            $r = sql_query('DELETE FROM `sitemaps_data` WHERE `url` = :url', array(':url' => $list));
        }

        return $r->rowCount();

    }catch(Exception $e){
        throw new CoreException('sitemap_delete_entry(): Failed', $e);
    }
}



/*
 * Add a new URL to the sitemap table
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sitemap
 * @see sitemap_generate_index_file()
 * @version 1.22.0: Added documentation
 *
 * @param params $entry A complete sitemap entry from the `sitemap_data` table
 * @params string $entry[url]
 * @params string $entry[page_modifiedon]
 * @params string $entry[change_frequency]
 * @params string $entry[priority]
 * @return params The specified and inserted entry, validated
 */
function sitemap_insert_entry($entry){
    try{
        array_ensure($entry);
        array_default($entry, 'url'             , '');
        array_default($entry, 'priority'        , '');
        array_default($entry, 'page_modifiedon' , null);
        array_default($entry, 'change_frequency', '');
        array_default($entry, 'language'        , '');
        array_default($entry, 'group'           , 'standard');
        array_default($entry, 'file'            , null);

        $entry = sitemap_validate_entry($entry);

        if($entry['page_modifiedon']){
            sql_query('INSERT INTO `sitemaps_data` (`createdby`, `url`, `priority`, `page_modifiedon`, `change_frequency`, `language`, `group`, `file`)
                       VALUES                      (:createdby , :url , :priority , :page_modifiedon , :change_frequency , :language , :group , :file )

                       ON DUPLICATE KEY UPDATE `url`              = :url_update,
                                               `modifiedon`       = UTC_TIMESTAMP(),
                                               `modifiedby`       = :modifiedby_update,
                                               `priority`         = :priority_update,
                                               `page_modifiedon`  = :page_modifiedon_update,
                                               `change_frequency` = :change_frequency_update,
                                               `language`         = :language_update,
                                               `file`             = :file_update,
                                               `group`            = :group_update',

                       array(':createdby'               => isset_get($_SESSION['user']['id']),
                             ':url'                     => $entry['url'],
                             ':priority'                => $entry['priority'],
                             ':page_modifiedon'         => date_convert($entry['page_modifiedon'], 'c'),
                             ':change_frequency'        => $entry['change_frequency'],
                             ':language'                => $entry['language'],
                             ':group'                   => $entry['group'],
                             ':file'                    => $entry['file'],
                             ':url_update'              => $entry['url'],
                             ':modifiedby_update'       => isset_get($_SESSION['user']['id']),
                             ':priority_update'         => $entry['priority'],
                             ':page_modifiedon_update'  => date_convert($entry['page_modifiedon'], 'c'),
                             ':change_frequency_update' => $entry['change_frequency'],
                             ':language_update'         => $entry['language'],
                             ':file_update'             => $entry['file'],
                             ':group_update'            => $entry['group']));

        }else{
            sql_query('INSERT INTO `sitemaps_data` (`createdby`, `url`, `priority`, `page_modifiedon`, `change_frequency`, `language`, `group`, `file`)
                       VALUES                      (:createdby , :url , :priority , NOW()            , :change_frequency , :language , :group , :file )

                       ON DUPLICATE KEY UPDATE `url`              = :url_update,
                                               `modifiedon`       = UTC_TIMESTAMP(),
                                               `modifiedby`       = :modifiedby_update,
                                               `priority`         = :priority_update,
                                               `page_modifiedon`  = NOW(),
                                               `change_frequency` = :change_frequency_update,
                                               `language`         = :language_update,
                                               `file`             = :file_update,
                                               `group`            = :group_update',

                       array(':createdby'               => isset_get($_SESSION['user']['id']),
                             ':url'                     => $entry['url'],
                             ':priority'                => $entry['priority'],
                             ':change_frequency'        => $entry['change_frequency'],
                             ':language'                => $entry['language'],
                             ':group'                   => $entry['group'],
                             ':file'                    => $entry['file'],
                             ':url_update'              => $entry['url'],
                             ':modifiedby_update'       => isset_get($_SESSION['user']['id']),
                             ':priority_update'         => $entry['priority'],
                             ':change_frequency_update' => $entry['change_frequency'],
                             ':language_update'         => $entry['language'],
                             ':file_update'             => $entry['file'],
                             ':group_update'            => $entry['group']));
        }

        if(empty($entry['id'])){
            $entry['id'] = sql_insert_id();
        }

        return $entry;

    }catch(Exception $e){
        throw new CoreException('sitemap_insert_entry(): Failed', $e);
    }
}



/*
 * Delete all sitemap tmp and backup files and directories
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sitemap
 * @see sitemap_generate_index_file()
 * @version 1.22.0: Added documentation
 *
 * @param string $language
 * @return void
 */
function sitemap_make_backup(){
    global $_CONFIG;

    try{
        $count  = 0;
        $target = ROOT.'data/backups/sitemaps/'.date_convert(null, 'Ymd-Hmi').'/';

        file_ensure_path($target);
        log_console(tr('Making backup of current sitemape files in ":path"', array(':path' => $target)), 'cyan');

        if(file_exists(ROOT.'www/sitemap.xml')){
            copy(ROOT.'www/sitemap.xml', $target.'sitemap.xml');
            $count++;
        }

        foreach($_CONFIG['language']['supported'] as $code => $language){
            $source = ROOT.'www/'.$code.'/';
            file_ensure_path($target.$code.'/');

            foreach(scandir($source) as $file){
                $filename = basename($file);

                if(preg_match('/sitemap(?:-.*?)?\.xml/', $filename)){
                    /*
                     * This is a sitemap file
                     */
                    copy($source.$file, $target.$code.'/'.$filename);
                    $count++;
                }
            }
        }

        if(!$count){
            /*
             * No backup was made, cleanup
             */
            file_delete($target, ROOT.'data/backups/sitemaps');
        }

    }catch(Exception $e){
        throw new CoreException('sitemap_make_backup(): Failed', $e);
    }
}



/*
 * Validate the specified sitemap entry
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sitemap
 * @see sitemap_generate_index_file()
 * @version 1.22.0: Added documentation
 *
 * @param params $entry
 * @params natural $entry[createdby]
 * @params natural $entry[status]
 * @params natural $entry[url]
 * @params natural $entry[priority]
 * @params natural $entry[page_modification]
 * @params natural $entry[change_frequency]
 * @params natural $entry[language]
 * @params natural $entry[group]
 * @params natural $entry[file]
 * @return params The specified $entry, validated
 */
function sitemap_validate_entry($entry){
    global $_CONFIG;

    try{
        load_libs('validate');

        $v = new ValidateForm($entry, 'createdby,status,url,priority,page_modifiedon,change_frequency,language,group,file');

        $entry['page_modifiedon'] = date_convert($entry['page_modifiedon'], 'mysql');
        $entry['file']            = get_null($entry['file']);

        if($_CONFIG['language']['supported']){
            $v->inArray($entry['language'], $_CONFIG['language']['supported'], tr('Please ensure that the specified language is supported'));

        }else{
            $entry['language'] = $_CONFIG['language']['default'];
        }

        return $entry;

    }catch(Exception $e){
        throw new CoreException('sitemap_validate_entry(): Failed', $e);
    }
}
