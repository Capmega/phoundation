<?php
/*
 * Uglify library
 *
 * This library contains functions to manage the uglifycss and uglify-js Node.JS programs
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
 * @version 2.6.14: Added function and documentation
 * @category Function reference
 * @package node
 *
 * @return void
 */
function uglify_library_init() {
    try{
        load_libs('node');
        load_config('deploy');

        /*
         * Find the node commands
         */
        node_find();
        node_find_npm();

    }catch(Exception $e) {
        throw new CoreException('uglify_library_init(): Failed', $e);
    }
}



/*
 * Install uglifycss
 */
function uglify_css_setup() {
    global $core;

    try{
        log_console(tr('uglify_css_setup(): Installing uglifycss'), 'VERBOSE/cyan');
        node_install_npm('uglifycss');
        log_console(tr('uglify_css_setup(): Finished installing uglifycss'), 'VERBOSE/green');

    }catch(Exception $e) {
        throw new CoreException(tr('uglify_css_setup(): Failed'), $e);
    }
}



/*
 * Check availability of uglifycss installation, and install if needed
 */
function uglify_css_find() {
    global $core;

    try{
        log_console(tr('uglify_css_find(): Checking uglifycss availability'), 'VERBOSE/cyan');

        $result = safe_exec(array('ok_exitcodes' => 1,
                                  'commands'     => array($core->register['npm'], array('list', 'uglifycss'))));

        if(empty($result[1])) {
            throw new CoreException(tr('uglify_css_find(): npm list uglifycss returned invalid results'), 'invalid');
        }

        if(substr($result[1], -7, 7) == '(empty)') {
            /*
             * uglifycss is not available, install it now.
             */
            log_console(tr('uglify_css_find(): No uglifycss found, trying to install now'), 'yellow');
            uglify_css_setup();
        }

        $result[1] = 'uglify'.Strings::from($result[1], 'uglifycss');

        node_find_modules();
        log_console(tr('uglify_css_find(): Using uglifycss ":file"', array(':file' => $result[1])), 'VERBOSE/green');

    }catch(Exception $e) {
        throw new CoreException(tr('uglify_css_find(): Failed'), $e);
    }
}



/*
 * Uglify all CSS files in www/en/pub/css
 */
function uglify_css($paths = null, $force = false) {
    global $core, $_CONFIG;
    static $check;

    try{
        if(empty($check)) {
            $check = true;

            uglify_css_find();
            log_console(tr('uglify_css(): Minifying all CSS files using uglifycss'), 'VERBOSE');
        }

        if(empty($paths)) {
            /*
             * Start at the base css path
             */
            if($_CONFIG['language']['supported']) {
                $languages = $_CONFIG['language']['supported'];

            } else {
                $languages = array($_CONFIG['language']['default'] => tr('Default'));
            }

            foreach($languages as $code => $language) {
                $paths[] = ROOT.'www/'.$code.'/pub/css/';
                $paths[] = ROOT.'www/'.$code.'/admin/pub/css/';
            }

            $paths = implode(',', $paths);
        }

        foreach(Arrays::force($paths) as $path) {
            if(!file_exists($path)) continue;

            log_console(tr('uglify_css(): Minifying all CSS files in ":path"', array(':path' => $path)), 'VERBOSEDOT');

            if(is_dir($path)) {
                $path = slash($path);

                log_console(tr('uglify_css(): Minifying all CSS files in directory ":path"', array(':path' => $path)), 'VERBOSEDOT');
                file_check_dir($path);

            } elseif(is_file($path)) {
                log_console(tr('uglify_css(): Minifying CSS file ":path"', array(':path' => $path)), 'VERBOSEDOT');

            } else {
                throw new CoreException(tr('uglify_css(): Specified file ":path" is neither a file or a directory', array(':path' => $path)), 'unknow');
            }

             /*
             * Replace all symlinks with copies of the target file. This way, later
             * on we dont have to worry about if source or target is min file or
             * not, etc.
             */
            foreach(file_list_tree($path) as $file) {
                if(substr(Strings::fromReverse($file, '/'), 0, 7) === 'bundle-') {
                    continue;
                }

                if(is_link($file)) {
                    file_execute_mode(dirname($file), 0770, function() use ($file) {
                        if(substr($file, -7, 7) == '.min.js') {
                            /*
                             * If is minified then we have to copy
                             * from no-minified to minified
                             */
                            copy(substr($file, 0, -7).'.js', $file);

                        } elseif(substr($file, -3, 3) == '.js') {
                            /*
                             * If is no-minified then we have to copy
                             * from minified to no-minified
                             */
                            copy(substr($file, 0, -3).'.min.js', $file);
                        }
                    });
                }
            }

            foreach(file_list_tree($path) as $file) {
                if(substr(Strings::fromReverse($file, '/'), 0, 7) === 'bundle-') {
                    continue;
                }

				/*
                 * Update path for each file since the file may be in a sub directory
                 */
                $path = slash(dirname($file));

                if(is_dir($file)) {
                    /*
                     * Recurse into sub directories
                     */
                    uglify_css($file);

                    $processed[Strings::fromReverse($file, '/')] = true;
                    continue;
                }

                //if(is_link($file)) {
                //    /*
                //     * The file is a symlink
                //     */
                //    $target = readlink($file);
                //
                //    if(substr($file, -8, 8) == '.min.css') {
                //        /*
                //         * Delete the minimized symlinks, we'll regenerate them for the normal files
                //         */
                //        file_delete($file);
                //
                //        $processed[Strings::fromReverse($file, '/')] = true;
                //        continue;
                //
                //    } elseif(substr($file, -4, 4) == '.css') {
                //        /*
                //         * If the symlink target does not exist, we can just ignore it
                //         */
                //        if(!file_exists($path.$target)) {
                //            if(VERBOSE) {
                //                log_console('uglify_css(): Ignorning symlink "'.str_log($file).'" with non existing target "'.str_log($path.$target).'"', 'yellow');
                //            }
                //
                //            $processed[Strings::fromReverse($file, '/')] = true;
                //            continue;
                //        }
                //
                //        /*
                //         * If the symlink points to any path above or outside the current path, then only ensure there is a .min symlink for it
                //         */
                //        if(!strstr($path.$target, Strings::untilReverse($file, '/'))) {
                //            if(VERBOSE) {
                //                log_console('uglify_css(): Found symlink "'.str_log($file).'" with target "'.str_log($target).'" that points to location outside symlink path, ensuring minimized version pointing to the same file', 'yellow');
                //            }
                //
                //            if(file_exists(substr($file, 0, -4).'.min.css')) {
                //                file_delete(substr($file, 0, -4).'.min.css');
                //            }
                //
                //            symlink($target, substr($file, 0, -4).'.min.css');
                //
                //            $processed[Strings::fromReverse($file, '/')] = true;
                //            continue;
                //        }
                //
                //        if(substr(basename($file), 0, -4) == substr($target, 0, -8)) {
                //            /*
                //             * This non minimized version points towards a minimized version of the same file. Move the minimized version to the normal version,
                //             * and make a minimized version
                //             */
                //            if(VERBOSE) {
                //                log_console('uglify_css(): Found symlink "'.str_log($file).'" pointing to its minimized version. Switching files', 'yellow');
                //            }
                //
                //            file_delete($file);
                //            rename($path.$target, $file);
                //            copy($file, $path.$target);
                //
                //            $processed[Strings::fromReverse($file, '/')] = true;
                //            continue;
                //        }
                //
                //        /*
                //         * Create a symlink for the minimized file to the minimized version
                //         */
                //        if(substr($target, -8, 8) != '.min.css') {
                //            /*
                //             * Correct the targets file extension
                //             */
                //            $target = substr($target, 0, -4).'.min.css';
                //        }
                //
                //        if(VERBOSE) {
                //            log_console('uglify_css(): Created minimized symlink for file "'.str_log($file).'"');
                //        }
                //        file_delete(substr($file, 0, -4).'.min.css');
                //        symlink($target, substr($file, 0, -4).'.min.css');
                //
                //        $processed[Strings::fromReverse($file, '/')] = true;
                //        continue;
                //
                //    } else {
                //        if(VERBOSE) {
                //            log_console('uglify_css(): Ignorning non css symlink "'.str_log($file).'"', 'yellow');
                //        }
                //
                //        $processed[Strings::fromReverse($file, '/')] = true;
                //        continue;
                //    }
                //}

                if(!is_file($file)) {
                    log_console(tr('uglify_css(): Ignorning unknown type file ":file"', array(':file' => $file)), 'VERBOSE/yellow');
                    $processed[Strings::fromReverse($file, '/')] = true;
                    continue;
                }

                if(substr($file, -8, 8) == '.min.css') {
                    /*
                     * This file is already minified. IF there is a source .css file, then remove it (it will be minified again later)
                     * If no source .css is availalbe, then make this the source now, and it will be minified later.
                     *
                     * Reason for this is that sometimes we only have minified versions available.
                     */
                    if(file_exists(substr($file, 0, -8).'.css') and !is_link(substr($file, 0, -8).'.css')) {
                        log_console(tr('uglify_css(): Ignoring minified file ":file" as a source is available', array(':file' => $file)), 'VERBOSE');
    //                    file_delete($file);

                    } else {
                        log_console(tr('uglify_css(): Using minified file ":file" as source is available', array(':file' => $file)), 'VERBOSE');
                        rename($file, substr($file, 0, -8).'.css');
                    }

                    $file = substr($file, 0, -8).'.css';
                }

                if(substr($file, -4, 4) != '.css') {
                    if(substr($file, -3, 3) == '.js') {
                        /*
                         * Found a js file in the CSS path
                         */
                        log_console(tr('uglify_css(): Found js file ":file" in CSS path, switching to uglifyjs', array(':file' => $file)), 'VERBOSE/yellow');
                        uglify_js($file);

                        $processed[Strings::fromReverse($file, '/')] = true;
                        continue;
                    }

                    log_console(tr('uglify_css(): Ignorning non CSS file ":file"', array(':file' => $file)), 'VERBOSE/yellow');
                    $processed[Strings::fromReverse($file, '/')] = true;
                    continue;
                }

                try{
                    /*
                     * If file exists and FORCE option wasn't given then proceed
                     */
                    $minfile = Strings::untilReverse($file, '.').'.min.css';

                    if(file_exists($minfile)) {
                        /*
                         * Compare filemtimes, if they match then we will assume that
                         * the file has not changed, so we can skip minifying
                         */
                        if((filemtime($minfile) == filemtime($file)) and !$force) {
                            /*
                             * Do not minify, just continue with next file
                             */
                            log_console(tr('uglify_css(): NOT Minifying CSS file ":file", file has not changed', array(':file' => $file)), 'VERBOSE/yellow');
                            continue;
                        }
                    }

                    /*
                     * Compress file
                     */
                    file_execute_mode(dirname($file), 0770, function() use ($file) {
                        global $core;

                        log_console(tr('uglify_css(): Minifying CSS file ":file"', array(':file' => $file)), 'VERBOSEDOT');
                        file_delete(substr($file, 0, -4).'.min.css', dirname($file));

                        try{
                            if(filesize($file)) {
                                safe_exec(array('commands' => array($core->register['node'], array($core->register['node_modules'].'uglifycss/uglifycss', $file, 'redirect' => '> '.substr($file, 0, -4).'.min.css'))));

                            } else {
                                touch(substr($file, 0, -4).'.min.css');
                            }

                        }catch(Exception $e) {
                            /*
                             * If uglify fails then make a copy of min file
                             */
                            copy($file, substr($file, 0, -4).'.min.css');
                        }
                    });

                    $processed[Strings::fromReverse($file, '/')] = true;

                    /*
                     * Make mtime equal
                     */
                    $time = time();

                    if(empty($_CONFIG['deploy'][ENVIRONMENT]['sudo'])) {
// :TODO: Replace this with file_touch();
                        touch(Strings::untilReverse($file, '.').'.css'    , $time, $time);
                        touch(Strings::untilReverse($file, '.').'.min.css', $time, $time);

                    } else {
                        $time = date_convert($time, 'Y-m-d H:i:s');

// :TODO: Replace this with file_touch();
                        safe_exec(array('commands' => array('touch', array('sudo' => true, '--date="'.$time.'"', Strings::untilReverse($file, '.').'.css'),
                                                            'touch', array('sudo' => true, '--date="'.$time.'"', Strings::untilReverse($file, '.').'.min.css'))));
                    }

                }catch(Exception $e) {
                    log_console(tr('Failed to minify CSS file ":file" because ":e"', array(':file' => $file, ':e' => $e->getMessage())), 'yellow');
                }
            }
        }

    }catch(Exception $e) {
        throw new CoreException(tr('uglify_css(): Failed'), $e);
    }
}



/*
 * Install uglify-js
 */
function uglify_js_setup() {
    global $core;

    try{
        log_console(tr('uglify_js_setup(): Installing uglify-js'), 'VERBOSE/cyan');
        node_install_npm('uglify-js');
        log_console(tr('uglify_js_setup(): Finished installing uglify-js'), 'VERBOSE/green');

    }catch(Exception $e) {
        throw new CoreException(tr('uglify_js_setup(): Failed'), $e);
    }
}



/*
 * Check availability of uglify-js installation, and install if needed
 */
function uglify_js_find() {
    global $core;

    try{
        log_console(tr('uglify_js_find(): Checking uglify-js availability'), 'VERBOSE/cyan');

        $result = safe_exec(array('ok_exitcodes' => 1,
                                  'commands'     => array($core->register['npm'], array('list', 'uglify-js'))));

        if(empty($result[1])) {
            throw new CoreException(tr('uglify_js_find(): npm list uglify-js returned invalid results'), 'invalid_result');
        }

        if(substr($result[1], -7, 7) == '(empty)') {
            /*
             * uglify-js is not available, install it now.
             */
            log_console(tr('uglify_js_find(): No uglify-js found, trying to install now'), 'yellow');
            uglify_js_setup();
        }

        $result[1] = 'uglify'.Strings::from($result[1], 'ugliyfyjs');

        node_find_modules();
        log_console(tr('uglify_js_find(): Using uglify-js ":file"', array(':file' => $result[1])), 'VERBOSE/green');

    }catch(Exception $e) {
        throw new CoreException(tr('uglify_js_find(): Failed'), $e);
    }
}



/*
 * Uglify all js files in www/en/pub/js
 */
function uglify_js($paths = null, $force = false) {
    global $core;
    static $check;

    try{
        if(empty($check)) {
            $check = true;

            uglify_js_find();
            log_console(tr('uglify_js(): minifying all specified javascript files using uglifyjs'), 'VERBOSE');
        }

        if(empty($paths)) {
            /*
             * Start at the base js path
             */
            $paths = ROOT.'www/'.LANGUAGE.'/pub/js/,'.ROOT.'www/'.LANGUAGE.'/admin/pub/js/';
        }

        foreach(Arrays::force($paths) as $path) {
            if(!file_exists($path)) continue;

            log_console(tr('uglify_js(): Minifying all javascript files in ":path"', array(':path' => $path)), 'VERBOSEDOT');

            if(is_dir($path)) {
                $path = slash($path);

                log_console(tr('uglify_js(): Minifying all javascript files in directory ":path"', array(':path' => $path)), 'VERBOSEDOT');
                file_check_dir($path);

            } elseif(is_file($path)) {
                log_console(tr('uglify_js(): Minifying javascript file ":path"', array(':path' => $path)), 'VERBOSEDOT');

            } else {
                throw new CoreException(tr('uglify_js(): Specified file ":path" is neither a file or a directory', array(':path' => $path)), 'unknow');
            }

            /*
             * Replace all symlinks with copies of the target file. This way, later
             * on we dont have to worry about if source or target is min file or
             * not, etc.
             */
            foreach(file_list_tree($path) as $file) {
                if(is_link($file)) {
                    file_execute_mode(dirname($file), 0770, function() use ($file) {
                        if(substr($file, -7, 7) == '.min.js') {
                            /*
                             * If is minified then we have to copy
                             * from no-minified to minified
                             */
                            copy(substr($file, 0, -7).'.js', $file);

                        } elseif(substr($file, -3, 3) == '.js') {
                            /*
                             * If is no-minified then we have to copy
                             * from minified to no-minified
                             */
                            copy(substr($file, 0, -3).'.min.js', $file);
                        }
                    });
                }
            }


            foreach(file_list_tree($path) as $file) {
                /*
                 * Update path for each file since the file may be in a sub directory
                 */
                $path = slash(dirname($file));

                if(is_dir($file)) {
                    /*
                     * Recurse into sub directories
                     */
                    uglify_js($file);

                    $processed[Strings::fromReverse($file, '/')] = true;
                    continue;
                }

    //            if(is_link($file)) {
    //                /*
    //                 * The file is a symlink
    //                 */
    //                $target = readlink($file);
    //
    //
    //                if(substr($file, -7, 7) == '.min.js') {
    //                    /*
    //                     * Delete the minimized symlinks, we'll regenerate them for the normal files
    //                     */
    //                    file_delete($file);
    //                    $processed[Strings::fromReverse($file, '/')] = true;
    //                    continue;
    //
    //                } elseif(substr($file, -3, 3) == '.js') {
    //                    /*
    //                     * If the symlink target does not exist, we can just ignore it
    //                     */
    //                    if(!file_exists($path.$target)) {
    //                        if(VERBOSE) {
    //                            log_console('uglify_js(): Ignorning symlink "'.str_log($file).'" with non existing target "'.str_log($path.$target).'"', 'yellow');
    //                        }
    //
    //                        $processed[Strings::fromReverse($file, '/')] = true;
    //                        continue;
    //                    }
    //
    //                    /*
    //                     * If the symlink points to any path above or outside the current path, then only ensure there is a .min symlink for it
    //                     */
    //                    if(!strstr($path.$target, Strings::untilReverse($file, '/'))) {
    //                        if(VERBOSE) {
    //                            log_console('uglify_js(): Found symlink "'.str_log($file).'" with target "'.str_log($target).'" that points to location outside symlink path, ensuring minimized version pointing to the same file', 'yellow');
    //                        }
    //
    //                        if(file_exists(substr($file, 0, -3).'.min.js')) {
    //                            file_delete(substr($file, 0, -3).'.min.js');
    //                        }
    //
    //                        symlink($target, substr($file, 0, -3).'.min.js');
    //
    //                        $processed[Strings::fromReverse($file, '/')] = true;
    //                        continue;
    //                    }
    //
    //                    if(substr(basename($file), 0, -3) == substr($target, 0, -7)) {
    //                        /*
    //                         * This non minimized version points towards a minimized version of the same file. Move the minimized version to the normal version,
    //                         * and make a minimized version
    //                         */
    //                        if(VERBOSE) {
    //                            log_console('uglify_js(): Found symlink "'.str_log($file).'" pointing to its minimized version. Switching files', 'yellow');
    //                        }
    //
    //                        file_delete($file);
    //                        rename($path.$target, $file);
    //                        copy($file, $path.$target);
    //
    //                        $processed[Strings::fromReverse($file, '/')] = true;
    //                        continue;
    //                    }
    //
    //                    /*
    //                     * Create a symlink for the minimized file to the minimized version
    //                     */
    //                    if(substr($target, -7, 7) != '.min.js') {
    //                        /*
    //                         * Correct the targets file extension
    //                         */
    //                        $target = substr($target, 0, -3).'.min.js';
    //                    }
    //
    //                    if(VERBOSE) {
    //                        log_console('uglify_js(): Created minimized symlink for file "'.str_log($file).'"');
    //                    }
    //                    file_delete(substr($file, 0, -3).'.min.js');
    //                    symlink($target, substr($file, 0, -3).'.min.js');
    //
    //                    $processed[Strings::fromReverse($file, '/')] = true;
    //                    continue;
    //
    //                } else {
    //                    if(VERBOSE) {
    //                        log_console('uglify_js(): Ignorning non js symlink "'.str_log($file).'"', 'yellow');
    //                    }
    //
    //                    $processed[Strings::fromReverse($file, '/')] = true;
    //                    continue;
    //                }
    //            }

                if(!is_file($file)) {
                    log_console(tr('uglify_js(): Ignorning unknown type file ":file"', array(':file' => $file)), 'VERBOSE/yellow');
                    $processed[Strings::fromReverse($file, '/')] = true;
                    continue;
                }

                if(substr($file, -7, 7) == '.min.js') {
                    /*
                     * This file is already minified. IF there is a source .js file, then remove it (it will be minified again later)
                     * If no source .js is availalbe, then make this the source now, and it will be minified later.
                     *
                     * Reason for this is that sometimes we only have minified versions available.
                     */
                    if(file_exists(substr($file, 0, -7).'.js') and !is_link(substr($file, 0, -7).'.js')) {
                        log_console(tr('uglify_js(): Ignoring minified file ":file" as a source is available', array(':file' => $file)), 'VERBOSE');
    //                    file_delete($file);

                    } else {
                        log_console(tr('uglify_js(): Using minified file ":file" as source is available', array(':file' => $file)), 'VERBOSE');
                        rename($file, substr($file, 0, -7).'.js');
                    }

                    $file = substr($file, 0, -7).'.js';
                }

                if(substr($file, -3, 3) != '.js') {
                    if(substr($file, -4, 4) == '.css') {
                        /*
                         * Found a CSS file in the javascript path
                         */
                        log_console(tr('uglify_js(): Found CSS file ":file" in javascript path, switching to uglifycss', array(':file' => $file)), 'VERBOSE/yellow');
                        uglify_css($file);

                        $processed[Strings::fromReverse($file, '/')] = true;
                        continue;
                    }

                    log_console(tr('uglify_js(): Ignorning non javascript file ":file"', array(':file' => $file)), 'VERBOSE/yellow');
                    $processed[Strings::fromReverse($file, '/')] = true;
                    continue;
                }

                try{
                     /*
                     * If file exists and FORCE option wasn't given then proceed
                     */
                    $minfile = Strings::untilReverse($file, '.').'.min.js';

                    if(file_exists($minfile)) {
                        /*
                         * Compare filemtimes, if they match then we will assume that
                         * the file has not changed, so we can skip minifying
                         */
                        if((filemtime($minfile) == filemtime($file)) and !$force) {
                            /*
                             * Do not minify, just continue with next file
                             */
                            log_console(tr('uglify_js(): NOT Minifying javascript file ":file", file has not changed', array(':file' => $file)), 'VERBOSE/yellow');
                            continue;
                        }
                    }

                    /*
                     * Compress file
                     */
                    file_execute_mode(dirname($file), 0770, function() use ($file) {
                        global $core;

                        log_console(tr('uglify_js(): Minifying javascript file ":file"', array(':file' => $file)), 'VERBOSEDOT');
                        file_delete(substr($file, 0, -3).'.min.js', ROOT.'www/'.LANGUAGE.'/pub/js,'.ROOT.'www/'.LANGUAGE.'/pub/css,'.ROOT.'www/'.LANGUAGE.'/admin/pub/js,'.ROOT.'www/'.LANGUAGE.'/admin/pub/css');

                        try{
                            if(filesize($file)) {
                                safe_exec(array('commands' => array($core->register['node'], array($core->register['node_modules'].'uglify-js/bin/uglifyjs', '--output', substr($file, 0, -3).'.min.js', $file))));

                            } else {
                                touch(substr($file, 0, -4).'.min.js');
                            }

                        }catch(Exception $e) {
                            /*
                             * If uglify fails then make a copy of min file
                             */
                            copy($file, substr($file, 0, -3).'.min.js');
                        }
                    });

                    $processed[Strings::fromReverse($file, '/')] = true;

                    /*
                     * Make mtime equal
                     */
                    $time = time();

                    if(empty($_CONFIG['deploy'][ENVIRONMENT]['sudo'])) {
                        touch(Strings::untilReverse($file, '.').'.js'    , $time, $time);
                        touch(Strings::untilReverse($file, '.').'.min.js', $time, $time);

                    } else {
                        $time = date_convert($time, 'Y-m-d H:i:s');

                        safe_exec(array('commands' => array('touch', array('sudo' => true, '--date="'.$time.'"', Strings::untilReverse($file, '.').'.js'),
                                                            'touch', array('sudo' => true, '--date="'.$time.'"', Strings::untilReverse($file, '.').'.min.js'))));
                    }

                }catch(Exception $e) {
                    log_console(tr('Failed to minify javascript file ":file" because ":e"', array(':file' => $file, ':e' => $e->getMessage())), 'yellow');
                }
            }
        }

    }catch(Exception $e) {
        throw new CoreException(tr('uglify_js(): Failed'), $e);
    }
}
?>
