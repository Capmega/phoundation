<?php
/*
 * Uglify library
 *
 * This library contains functions to manage the uglifycss and uglify-js Node.JS programs
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 */



load_libs('node');
load_config('deploy');



/*
 * Ensure that npm is available
 */
function uglify_check(){
    global $npm, $node, $node_modules;

    try{
        $node = node_check();
        $npm  = node_check_npm();

    }catch(Exception $e){
        throw new BException(tr('uglify_check(): Failed'), $e);
    }
}



/*
 * Install uglifycss
 */
function uglify_css_install(){
    global $npm;

    try{
        log_console(tr('uglify_css_install(): Installing uglifycss'), 'VERBOSE/cyan');
        passthru($npm.' install uglifycss');
        log_console(tr('uglify_css_install(): Finished installing uglifycss'), 'VERBOSE/green');

    }catch(Exception $e){
        throw new BException(tr('uglify_css_install(): Failed'), $e);
    }
}



/*
 * Check availability of uglifycss installation, and install if needed
 */
function uglify_css_check(){
    global $npm, $node_modules;

    try{
        uglify_check();
        log_console(tr('uglify_css_check(): Checking uglifycss availability'), 'VERBOSE/cyan');

        $result = safe_exec(array('ok_exitcodes' => 1,
                                  'commands'     => array($npm, array('list', 'uglifycss'))));

        if(empty($result[1])){
            throw new BException(tr('uglify_js_check(): npm list uglifycss returned invalid results'), 'invalid');
        }

        if(substr($result[1], -7, 7) == '(empty)'){
            /*
             * uglifycss is not available, install it now.
             */
            log_console(tr('uglify_css_check(): No uglifycss found, trying to install now'), 'VERBOSE/yellow');
            uglify_css_install($npm);
        }

        $result[1] = 'uglify'.str_from($result[1], 'uglifycss');

        $node_modules = node_check_modules();

        log_console(tr('uglify_css_check(): Using uglifycss ":file"', array(':file' => $result[1])), 'VERBOSE/green');

    }catch(Exception $e){
        throw new BException(tr('uglify_css_check(): Failed'), $e);
    }
}



/*
 * Uglify all CSS files in www/en/pub/css
 */
function uglify_css($paths = null, $force = false){
    global $npm, $node, $node_modules, $_CONFIG;
    static $check;

    try{
        if(empty($check)){
            $check = true;

            uglify_css_check($npm);
            log_console(tr('uglify_css(): Compressing all CSS files using uglifycss'), 'VERBOSE');
        }

        if(empty($paths)){
            /*
             * Start at the base css path
             */
            $paths = ROOT.'www/en/pub/css/,'.ROOT.'www/en/admin/pub/css/';
        }

        foreach(array_force($paths) as $path){
            if(!file_exists($path)) continue;

            log_console(tr('uglify_css(): Compressing all CSS files in ":path"', array(':path' => $path)), 'VERBOSEDOT');

            if(is_dir($path)){
                $path = slash($path);

                log_console(tr('uglify_css(): Compressing all CSS files in directory ":path"', array(':path' => $path)), 'VERBOSEDOT');
                file_check_dir($path);

            }elseif(is_file($path)){
                log_console(tr('uglify_css(): Compressing CSS file ":path"', array(':path' => $path)), 'VERBOSEDOT');

            }else{
                throw new BException(tr('uglify_css(): Specified file ":path" is neither a file or a directory', array(':path' => $path)), 'unknow');
            }

             /*
             * Replace all symlinks with copies of the target file. This way, later
             * on we dont have to worry about if source or target is min file or
             * not, etc.
             */
            foreach(file_list_tree($path) as $file){
                if(substr(str_rfrom($file, '/'), 0, 7) === 'bundle-'){
                    continue;
                }

                if(is_link($file)){
                    if(substr($file, -7, 7) == '.min.js'){
                        /*
                         * If is minified then we have to copy
                         * from no-minified to minified
                         */
                        copy(substr($file, 0, -7).'.js', $file);

                    }elseif(substr($file, -3, 3) == '.js'){
                        /*
                         * If is no-minified then we have to copy
                         * from minified to no-minified
                         */
                        copy(substr($file, 0, -3).'.min.js', $file);
                    }
                }
            }

            foreach(file_list_tree($path) as $file){
                if(substr(str_rfrom($file, '/'), 0, 7) === 'bundle-'){
                    continue;
                }

				/*
                 * Update path for each file since the file may be in a sub directory
                 */
                $path = slash(dirname($file));

                if(is_dir($file)){
                    /*
                     * Recurse into sub directories
                     */
                    uglify_css($file);

                    $processed[str_rfrom($file, '/')] = true;
                    continue;
                }

                //if(is_link($file)){
                //    /*
                //     * The file is a symlink
                //     */
                //    $target = readlink($file);
                //
                //    if(substr($file, -8, 8) == '.min.css'){
                //        /*
                //         * Delete the minimized symlinks, we'll regenerate them for the normal files
                //         */
                //        file_delete($file);
                //
                //        $processed[str_rfrom($file, '/')] = true;
                //        continue;
                //
                //    }elseif(substr($file, -4, 4) == '.css'){
                //        /*
                //         * If the symlink target does not exist, we can just ignore it
                //         */
                //        if(!file_exists($path.$target)){
                //            if(VERBOSE){
                //                log_console('uglify_css(): Ignorning symlink "'.str_log($file).'" with non existing target "'.str_log($path.$target).'"', 'yellow');
                //            }
                //
                //            $processed[str_rfrom($file, '/')] = true;
                //            continue;
                //        }
                //
                //        /*
                //         * If the symlink points to any path above or outside the current path, then only ensure there is a .min symlink for it
                //         */
                //        if(!strstr($path.$target, str_runtil($file, '/'))){
                //            if(VERBOSE){
                //                log_console('uglify_css(): Found symlink "'.str_log($file).'" with target "'.str_log($target).'" that points to location outside symlink path, ensuring minimized version pointing to the same file', 'yellow');
                //            }
                //
                //            if(file_exists(substr($file, 0, -4).'.min.css')){
                //                file_delete(substr($file, 0, -4).'.min.css');
                //            }
                //
                //            symlink($target, substr($file, 0, -4).'.min.css');
                //
                //            $processed[str_rfrom($file, '/')] = true;
                //            continue;
                //        }
                //
                //        if(substr(basename($file), 0, -4) == substr($target, 0, -8)){
                //            /*
                //             * This non minimized version points towards a minimized version of the same file. Move the minimized version to the normal version,
                //             * and make a minimized version
                //             */
                //            if(VERBOSE){
                //                log_console('uglify_css(): Found symlink "'.str_log($file).'" pointing to its minimized version. Switching files', 'yellow');
                //            }
                //
                //            file_delete($file);
                //            rename($path.$target, $file);
                //            copy($file, $path.$target);
                //
                //            $processed[str_rfrom($file, '/')] = true;
                //            continue;
                //        }
                //
                //        /*
                //         * Create a symlink for the minimized file to the minimized version
                //         */
                //        if(substr($target, -8, 8) != '.min.css'){
                //            /*
                //             * Correct the targets file extension
                //             */
                //            $target = substr($target, 0, -4).'.min.css';
                //        }
                //
                //        if(VERBOSE){
                //            log_console('uglify_css(): Created minimized symlink for file "'.str_log($file).'"');
                //        }
                //        file_delete(substr($file, 0, -4).'.min.css');
                //        symlink($target, substr($file, 0, -4).'.min.css');
                //
                //        $processed[str_rfrom($file, '/')] = true;
                //        continue;
                //
                //    }else{
                //        if(VERBOSE){
                //            log_console('uglify_css(): Ignorning non css symlink "'.str_log($file).'"', 'yellow');
                //        }
                //
                //        $processed[str_rfrom($file, '/')] = true;
                //        continue;
                //    }
                //}

                if(!is_file($file)){
                    log_console(tr('uglify_css(): Ignorning unknown type file ":file"', array(':file' => $file)), 'VERBOSE/yellow');
                    $processed[str_rfrom($file, '/')] = true;
                    continue;
                }

                if(substr($file, -8, 8) == '.min.css'){
                    /*
                     * This file is already minified. IF there is a source .css file, then remove it (it will be minified again later)
                     * If no source .css is availalbe, then make this the source now, and it will be minified later.
                     *
                     * Reason for this is that sometimes we only have minified versions available.
                     */
                    if(file_exists(substr($file, 0, -8).'.css') and !is_link(substr($file, 0, -8).'.css')){
                        log_console(tr('uglify_css(): Ignoring minified file ":file" as a source is available', array(':file' => $file)), 'VERBOSE');
    //                    file_delete($file);

                    }else{
                        log_console(tr('uglify_css(): Using minified file ":file" as source is available', array(':file' => $file)), 'VERBOSE');
                        rename($file, substr($file, 0, -8).'.css');
                    }

                    $file = substr($file, 0, -8).'.css';
                }

                if(substr($file, -4, 4) != '.css'){
                    if(substr($file, -3, 3) == '.js'){
                        /*
                         * Found a js file in the CSS path
                         */
                        log_console(tr('uglify_css(): Found js file ":file" in CSS path, switching to uglifyjs', array(':file' => $file)), 'VERBOSE/yellow');
                        uglify_js($file);

                        $processed[str_rfrom($file, '/')] = true;
                        continue;
                    }

                    log_console(tr('uglify_css(): Ignorning non CSS file ":file"', array(':file' => $file)), 'VERBOSE/yellow');
                    $processed[str_rfrom($file, '/')] = true;
                    continue;
                }

                try{
                    /*
                     * If file exists and FORCE option wasn't given then proceed
                     */
                    $minfile = str_runtil($file, '.').'.min.css';

                    if(file_exists($minfile)){
                        /*
                         * Compare filemtimes, if they match then we will assume that
                         * the file has not changed, so we can skip compressing
                         */
                        if((filemtime($minfile) == filemtime($file)) and !$force){
                            /*
                             * Do not compress, just continue with next file
                             */
                            log_console(tr('uglify_css(): NOT Compressing CSS file ":file", file has not changed'), 'VERBOSE/yellow');
                            continue;
                        }
                    }

                    /*
                     * Compress file
                     */
                    log_console(tr('uglify_css(): Compressing CSS file ":file"', array(':file' => $file)), 'VERBOSEDOT');
                    file_delete(substr($file, 0, -4).'.min.css', false, false, ROOT.'www/en/pub/js,'.ROOT.'www/en/pub/css,'.ROOT.'www/en/admin/pub/js,'.ROOT.'www/en/admin/pub/css');

                    try{
                        if(filesize($file)){
                            safe_exec(array('commands' => array($node, array($node_modules.'uglifycss/uglifycss', $file, 'redirect' => substr($file, 0, -4).'.min.css'))));

                        }else{
                            touch(substr($file, 0, -4).'.min.css');
                        }

                    }catch(Exception $e){
                        /*
                         * If uglify fails then make a copy of min file
                         */
                        copy($file, substr($file, 0, -4).'.min.css');
                    }

                    $processed[str_rfrom($file, '/')] = true;

                    /*
                     * Make mtime equal
                     */
                    $time = time();

                    if(empty($_CONFIG['deploy'][ENVIRONMENT]['sudo'])){
// :TODO: Replace this with file_touch();
                        touch(str_runtil($file, '.').'.css'    , $time, $time);
                        touch(str_runtil($file, '.').'.min.css', $time, $time);

                    }else{
                        $time = date_convert($time, 'Y-m-d H:i:s');

// :TODO: Replace this with file_touch();
                        safe_exec(array('commands' => array('touch', array('sudo' => true, '--date="'.$time.'"', str_runtil($file, '.').'.css'),
                                                            'touch', array('sudo' => true, '--date="'.$time.'"', str_runtil($file, '.').'.min.css'))));
                    }

                }catch(Exception $e){
                    log_console(tr('Failed to compress CSS file ":file" because ":e"', array(':file' => $file, ':e' => $e->getMessage())), 'yellow');
                }
            }
        }

    }catch(Exception $e){
        throw new BException(tr('uglify_css(): Failed'), $e);
    }
}



/*
 * Install uglify-js
 */
function uglify_js_install(){
    global $npm, $node_modules;

    try{
        log_console(tr('uglify_js_install(): Installing uglify-js'), 'VERBOSE/cyan');
        passthru($npm.' install uglify-js');
        log_console(tr('uglify_js_install(): Finished installing uglify-js'), 'VERBOSE/green');

    }catch(Exception $e){
        throw new BException(tr('uglify_js_install(): Failed'), $e);
    }
}



/*
 * Check availability of uglify-js installation, and install if needed
 */
function uglify_js_check(){
    global $npm, $node_modules;

    try{
        uglify_check();
        log_console(tr('uglify_js_check(): Checking uglify-js availability'), 'VERBOSE/cyan');

        $result = safe_exec(array('ok_exitcodes' => 1,
                                  'commands'     => array($npm, array('list', 'uglify-js'))));

        if(empty($result[1])){
            throw new BException(tr('uglify_js_check(): npm list uglify-js returned invalid results'), 'invalid_result');
        }

        if(substr($result[1], -7, 7) == '(empty)'){
            /*
             * uglify-js is not available, install it now.
             */
            log_console(tr('uglify_js_check(): No uglify-js found, trying to install now'), 'VERBOSE/yellow');
            uglify_js_install($npm);
        }

        $result[1]    = 'uglify'.str_from($result[1], 'ugliyfyjs');
        $node_modules = node_check_modules();

        log_console(tr('uglify_js_check(): Using uglify-js ":file"', array(':file' => $result[1])), 'VERBOSE/green');

    }catch(Exception $e){
        throw new BException(tr('uglify_js_check(): Failed'), $e);
    }
}



/*
 * Uglify all js files in www/en/pub/js
 */
function uglify_js($paths = null, $force = false){
    global $npm, $node, $node_modules;
    static $check;

    try{
        if(empty($check)){
            $check = true;

            uglify_js_check($npm);
            log_console(tr('uglify_js(): Compressing all specified javascript files using uglifyjs'), 'VERBOSE');
        }

        if(empty($paths)){
            /*
             * Start at the base js path
             */
            $paths = ROOT.'www/en/pub/js/,'.ROOT.'www/en/admin/pub/js/';
        }

        foreach(array_force($paths) as $path){
            if(!file_exists($path)) continue;

            log_console(tr('uglify_js(): Compressing all javascript files in ":path"', array(':path' => $path)), 'VERBOSEDOT');

            if(is_dir($path)){
                $path = slash($path);

                log_console(tr('uglify_js(): Compressing all javascript files in directory ":path"', array(':path' => $path)), 'VERBOSEDOT');
                file_check_dir($path);

            }elseif(is_file($path)){
                log_console(tr('uglify_js(): Compressing javascript file ":path"', array(':path' => $path)), 'VERBOSEDOT');

            }else{
                throw new BException(tr('uglify_js(): Specified file ":path" is neither a file or a directory', array(':path' => $path)), 'unknow');
            }

            /*
             * Replace all symlinks with copies of the target file. This way, later
             * on we dont have to worry about if source or target is min file or
             * not, etc.
             */
            foreach(file_list_tree($path) as $file){
                if(is_link($file)){
                    if(substr($file, -7, 7) == '.min.js'){
                        /*
                         * If is minified then we have to copy
                         * from no-minified to minified
                         */
                        copy(substr($file, 0, -7).'.js', $file);

                    }elseif(substr($file, -3, 3) == '.js'){
                        /*
                         * If is no-minified then we have to copy
                         * from minified to no-minified
                         */
                        copy(substr($file, 0, -3).'.min.js', $file);
                    }
                }
            }


            foreach(file_list_tree($path) as $file){
                /*
                 * Update path for each file since the file may be in a sub directory
                 */
                $path = slash(dirname($file));

                if(is_dir($file)){
                    /*
                     * Recurse into sub directories
                     */
                    uglify_js($file);

                    $processed[str_rfrom($file, '/')] = true;
                    continue;
                }

    //            if(is_link($file)){
    //                /*
    //                 * The file is a symlink
    //                 */
    //                $target = readlink($file);
    //
    //
    //                if(substr($file, -7, 7) == '.min.js'){
    //                    /*
    //                     * Delete the minimized symlinks, we'll regenerate them for the normal files
    //                     */
    //                    file_delete($file);
    //                    $processed[str_rfrom($file, '/')] = true;
    //                    continue;
    //
    //                }elseif(substr($file, -3, 3) == '.js'){
    //                    /*
    //                     * If the symlink target does not exist, we can just ignore it
    //                     */
    //                    if(!file_exists($path.$target)){
    //                        if(VERBOSE){
    //                            log_console('uglify_js(): Ignorning symlink "'.str_log($file).'" with non existing target "'.str_log($path.$target).'"', 'yellow');
    //                        }
    //
    //                        $processed[str_rfrom($file, '/')] = true;
    //                        continue;
    //                    }
    //
    //                    /*
    //                     * If the symlink points to any path above or outside the current path, then only ensure there is a .min symlink for it
    //                     */
    //                    if(!strstr($path.$target, str_runtil($file, '/'))){
    //                        if(VERBOSE){
    //                            log_console('uglify_js(): Found symlink "'.str_log($file).'" with target "'.str_log($target).'" that points to location outside symlink path, ensuring minimized version pointing to the same file', 'yellow');
    //                        }
    //
    //                        if(file_exists(substr($file, 0, -3).'.min.js')){
    //                            file_delete(substr($file, 0, -3).'.min.js');
    //                        }
    //
    //                        symlink($target, substr($file, 0, -3).'.min.js');
    //
    //                        $processed[str_rfrom($file, '/')] = true;
    //                        continue;
    //                    }
    //
    //                    if(substr(basename($file), 0, -3) == substr($target, 0, -7)){
    //                        /*
    //                         * This non minimized version points towards a minimized version of the same file. Move the minimized version to the normal version,
    //                         * and make a minimized version
    //                         */
    //                        if(VERBOSE){
    //                            log_console('uglify_js(): Found symlink "'.str_log($file).'" pointing to its minimized version. Switching files', 'yellow');
    //                        }
    //
    //                        file_delete($file);
    //                        rename($path.$target, $file);
    //                        copy($file, $path.$target);
    //
    //                        $processed[str_rfrom($file, '/')] = true;
    //                        continue;
    //                    }
    //
    //                    /*
    //                     * Create a symlink for the minimized file to the minimized version
    //                     */
    //                    if(substr($target, -7, 7) != '.min.js'){
    //                        /*
    //                         * Correct the targets file extension
    //                         */
    //                        $target = substr($target, 0, -3).'.min.js';
    //                    }
    //
    //                    if(VERBOSE){
    //                        log_console('uglify_js(): Created minimized symlink for file "'.str_log($file).'"');
    //                    }
    //                    file_delete(substr($file, 0, -3).'.min.js');
    //                    symlink($target, substr($file, 0, -3).'.min.js');
    //
    //                    $processed[str_rfrom($file, '/')] = true;
    //                    continue;
    //
    //                }else{
    //                    if(VERBOSE){
    //                        log_console('uglify_js(): Ignorning non js symlink "'.str_log($file).'"', 'yellow');
    //                    }
    //
    //                    $processed[str_rfrom($file, '/')] = true;
    //                    continue;
    //                }
    //            }

                if(!is_file($file)){
                    log_console(tr('uglify_js(): Ignorning unknown type file ":file"', array(':file' => $file)), 'VERBOSE/yellow');
                    $processed[str_rfrom($file, '/')] = true;
                    continue;
                }

                if(substr($file, -7, 7) == '.min.js'){
                    /*
                     * This file is already minified. IF there is a source .js file, then remove it (it will be minified again later)
                     * If no source .js is availalbe, then make this the source now, and it will be minified later.
                     *
                     * Reason for this is that sometimes we only have minified versions available.
                     */
                    if(file_exists(substr($file, 0, -7).'.js') and !is_link(substr($file, 0, -7).'.js')){
                        log_console(tr('uglify_js(): Ignoring minified file ":file" as a source is available', array(':file' => $file)), 'VERBOSE');
    //                    file_delete($file);

                    }else{
                        log_console(tr('uglify_js(): Using minified file ":file" as source is available', array(':file' => $file)), 'VERBOSE');
                        rename($file, substr($file, 0, -7).'.js');
                    }

                    $file = substr($file, 0, -7).'.js';
                }

                if(substr($file, -3, 3) != '.js'){
                    if(substr($file, -4, 4) == '.css'){
                        /*
                         * Found a CSS file in the javascript path
                         */
                        log_console(tr('uglify_js(): Found CSS file ":file" in javascript path, switching to uglifycss', array(':file' => $file)), 'VERBOSE/yellow');
                        uglify_css($file);

                        $processed[str_rfrom($file, '/')] = true;
                        continue;
                    }

                    log_console(tr('uglify_js(): Ignorning non javascript file ":file"', array(':file' => $file)), 'VERBOSE/yellow');
                    $processed[str_rfrom($file, '/')] = true;
                    continue;
                }

                try{
                     /*
                     * If file exists and FORCE option wasn't given then proceed
                     */
                    $minfile = str_runtil($file, '.').'.min.js';

                    if(file_exists($minfile)){
                        /*
                         * Compare filemtimes, if they match then we will assume that
                         * the file has not changed, so we can skip compressing
                         */
                        if((filemtime($minfile) == filemtime($file)) and !$force){
                            /*
                             * Do not compress, just continue with next file
                             */
                            log_console(tr('uglify_js(): NOT Compressing javascript file ":file", file has not changed', array(':file' => $file)), 'VERBOSE/yellow');
                            continue;
                        }
                    }

                    /*
                     * Compress file
                     */
                    log_console(tr('uglify_js(): Compressing javascript file ":file"', array(':file' => $file)), 'VERBOSEDOT');
                    file_delete(substr($file, 0, -3).'.min.js', false, false, ROOT.'www/en/pub/js,'.ROOT.'www/en/pub/css,'.ROOT.'www/en/admin/pub/js,'.ROOT.'www/en/admin/pub/css');

                    try{
                        if(filesize($file)){
                            safe_exec(array('commands' => array($node, array($node_modules.'uglify-js/bin/uglifyjs', '--output', substr($file, 0, -3).'.min.js', $file))));

                        }else{
                            touch(substr($file, 0, -4).'.min.js');
                        }

                    }catch(Exception $e){
                        /*
                         * If uglify fails then make a copy of min file
                         */
                        copy($file, substr($file, 0, -3).'.min.js');
                    }

                    $processed[str_rfrom($file, '/')] = true;

                    /*
                     * Make mtime equal
                     */
                    $time = time();

                    if(empty($_CONFIG['deploy'][ENVIRONMENT]['sudo'])){
                        touch(str_runtil($file, '.').'.css'    , $time, $time);
                        touch(str_runtil($file, '.').'.min.css', $time, $time);

                    }else{
                        $time = date_convert($time, 'Y-m-d H:i:s');

                        safe_exec(array('commands' => array('touch', array('sudo' => true, '--date="'.$time.'"', str_runtil($file, '.').'.js'),
                                                            'touch', array('sudo' => true, '--date="'.$time.'"', str_runtil($file, '.').'.min.js'))));
                    }

                }catch(Exception $e){
                    log_console(tr('Failed to compress javascript file ":file" because ":e"', array(':file' => $file, ':e' => $e->getMessage())), 'yellow');

                }
            }
        }

    }catch(Exception $e){
        throw new BException(tr('uglify_js(): Failed'), $e);
    }
}
?>
