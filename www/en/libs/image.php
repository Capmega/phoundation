<?php
/*
 * Image library
 *
 * This contains image processing related functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package image
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the ssh library configuration
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package image
 *
 * @return void
 */
function image_library_init() {
    try {
        if (!class_exists('Imagick')) {
            try {
                load_libs('linux');
                linux_install_package(null, 'php-imagick');

            }catch(Exception $f) {
                throw new CoreException(tr('image: php module "imagick" appears not to be installed, and automatic installation failed. Please install the module first. On Ubuntu and alikes, use "sudo apt-get -y install php-imagick; sudo phpenmod imagick" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php-imagick" to install the module. After this, a restart of your webserver or php-fpm server might be needed'), $f);
            }
        }

        load_config('images');
        file_ensure_path(ROOT.'data/log');

    }catch(Exception $e) {
        throw new CoreException('image_library_init(): Failed', $e);
    }
}



/*
 * Get and return text for image
 */
function image_get_text($image) {
    global $_CONFIG;

    try {
        $tmpfile = file_tmp();

        safe_exec([
            'commands' => [
                'tesseract', [$image, $tmpfile]
            ]
        ]);

        $retval = file_get_contents($tmpfile);

        file_delete($tmpfile);

        return $retval;

    }catch(Exception $e) {
        if (!file_which('tesseract')) {
            try {
                load_libs('linux');
                linux_install_package(null, 'tesseract-ocr');

            }catch(Exception $f) {
                throw new CoreException('image_get_text(): Failed to find the "tesseract" command, and automatic installation failed. On Ubuntu, use "sudo apt-get install tesseract-ocr" to install the required command', $f);
            }
        }

        throw new CoreException(tr('image_get_text(): Failed to get text from image ":image"', array(':image' => $image)), $e);
    }
}



/*
 * Standard image conversion function
 */
function image_rotate($degrees, $source, $target = null) {
    try {
        if (!$target) {
            $target = $source;
        }

        return image_convert(array('spurce'  => $source,
            'target'  => $target,
            'method'  => 'rotate',
            'degrees' => $degrees));

    }catch(Exception $e) {
        throw new CoreException(tr('image_rotate(): Failed'), $e);
    }
}



/*
 * Standard image conversion function
 */
function image_convert($params) {
    global $_CONFIG;

    try {
        Arrays::ensure($params, 'source,target,format');

        /*
         * Extract source and target
         */
        $source = $params['source'];
        $target = $params['target'];

        /*
         * Validations
         */
        if (!file_exists($source)) {
            throw new CoreException(tr('image_convert(): The specified source file ":source" does not exist', array(':source' => $source)), 'not-exists');
        }

        if (file_exists($target) and $target != $source) {
            throw new CoreException(tr('image_convert(): Destination file ":file" already exists', array(':file' => $target)), 'exists');
        }

        Arrays::ensure($params, 'log,nice');

        ///*
        // * Validate format
        // */
        //if (empty($format) and !empty($target)) {
        //    $format = substr($target, -3, 3);
        //
        //} elseif (!empty($format) and !empty($target)) {
        //    if ($format != substr($target, -3, 3)) {
        //        throw new CoreException(tr('image_convert(): Specified format ":format1" differ from the given destination format ":format2"', array(':format1' => substr($target, -3, 3), ':format2' => $format)));
        //    }
        //}

        /*
         * Get imagemagick executable
         * Ensure we have a local copy of the file to work with
         */
        $imagick = $_CONFIG['images']['imagemagick'];
        $source  = file_get_local($source);


        /*
         * Build command
         */
        $command   = $imagick['convert'];
        $arguments = array();

        if ($imagick['nice']) {
            $arguments['nice'] = $imagick['nice'];
        }

        if ($params['log']) {
            if ($params['log'] === true) {
                $params['log'] = ROOT.'data/log/syslog';
            }

            $arguments['redirect'] = ' >> '.$params['log'];
        }

        Arrays::ensure($params);
        array_default($params, 'degrees'   , null);
        array_default($params, 'x'         , null);
        array_default($params, 'y'         , null);
        array_default($params, 'h'         , null);
        array_default($params, 'w'         , null);
        array_default($params, 'to_h'      , null);
        array_default($params, 'to_w'      , null);
        array_default($params, 'method'    , null);
        array_default($params, 'format'    , null);
        array_default($params, 'background', null);
        array_default($params, 'timeout'   , 60);
        array_default($params, 'defaults'  , $imagick['defaults']);

        /*
         * Check format and update destination file name to match
         */
        $source_path = dirname($source);
        $source_file = basename($source);
        $dest_path   = dirname($target);
        $dest_file   = basename($target);

        switch ($params['format']) {
            case 'gif':
                //FALLTHROUGH
            case 'png':
                /*
                 * Preserve transparent background
                 */
                array_params($params, 'background', 'none');
                $dest_file = Strings::untilReverse($dest_file, '.').'.'.$params['format'];
                break;

            case 'jpeg':
                // no-break
            case 'jpg':
                array_params($params, 'background', 'white');
                $dest_file = Strings::untilReverse($dest_file, '.').'.'.$params['format'];
                break;

            case 'webp':
                array_params($params, 'background', 'white');
                $dest_file = Strings::untilReverse($dest_file, '.').'.'.$params['format'];
                break;

            case '':
                /*
                 * Use current format. If source file has no extension (Hello PHP temporary upload files!)
                 * then let the dest file keep its own extension
                 */
                $extension = Strings::fromReverse($source_file, '.');

                if (!$extension) {
                    $dest_file = Strings::untilReverse($dest_file, '.').'.'.$extension;
                }

                break;

            default:
                throw new CoreException(tr('image_convert(): Unknown format ":format" specified.', array(':format' => $params['format'])), 'unknown');
        }

        $target = Strings::slash($dest_path).$dest_file;

        /*
         * Remove the log file so we surely have data from only this session
         *
         * Yeah, bullshit, with parrallel sessions, others sessions might
         * delete it while this is in process, etc.
         */
        file_ensure_path(ROOT.'data/log');

        if ($_CONFIG['log']['single']) {
            array_default($params, 'log', ROOT.'data/log/syslog');

        } else {
            /*
             * The imagemagic-convert log shows only the last entry
             */
            array_default($params, 'log', ROOT.'data/log/imagemagic-convert');
            file_delete(ROOT.'data/log/imagemagick-convert', false);
        }

        if ($params['defaults']) {
            array_default($params, 'quality'         , $imagick['quality']);
            array_default($params, 'interlace'       , $imagick['interlace']);
            array_default($params, 'strip'           , $imagick['strip']);
            array_default($params, 'blur'            , $imagick['blur']);
            array_default($params, 'defines'         , $imagick['defines']);
            array_default($params, 'sampling_factor' , $imagick['sampling_factor']);
            array_default($params, 'keep_aspectratio', $imagick['keep_aspectratio']);
            array_default($params, 'limit_memory'    , $imagick['limit']['memory']);
            array_default($params, 'limit_map'       , $imagick['limit']['map']);

        } else {
            array_default($params, 'quality'         , null);
            array_default($params, 'interlace'       , null);
            array_default($params, 'strip'           , null);
            array_default($params, 'blur'            , null);
            array_default($params, 'defines'         , null);
            array_default($params, 'sampling_factor' , null);
            array_default($params, 'keep_aspectratio', null);
            array_default($params, 'limit_memory'    , null);
            array_default($params, 'limit_map'       , null);
        }

        if ($params['format'] === 'webp') {
            $webp = $_CONFIG['images']['webp'];

            foreach ($webp as $key => $value) {
                if ($value === null) {
                    continue;
                }

                if (is_bool($value)) {
                    $value = Strings::boolean($value);
                }

                $params['defines'][] = 'webp:'.$key.'='.$value;
            }
        }

        foreach ($params as $key => $value) {
            switch ($key) {
                case 'limit_memory':
                    if ($value) {
                        $arguments[] = '-limit memory';
                        $arguments[] = $value;
                    }

                    break;

                case 'limit_map':
                    if ($value) {
                        $arguments[] = '-limit map';
                        $arguments[] = $value;
                    }

                    break;

                case 'quality':
                    if ($value) {
                        $arguments[] = '-quality';
                        $arguments[] = $value.'%';
                    }

                    break;

                case 'blur':
                    if ($value) {
                        $arguments[] = '-gaussian-blur';
                        $arguments[] = $value;
                    }

                    break;

                case 'keep_aspectratio':
                    break;

                case 'sampling_factor':
                    if ($value) {
                        $arguments[] = '-sampling-factor';
                        $arguments[] = $value;
                    }

                    break;

                case 'defines':
                    if ($value) {
                        foreach ($value as $define) {
                            $arguments[] = '-define';
                            $arguments[] = $define;
                        }
                    }

                    break;

                case 'strip':
                    //FALLTHROUGH
                case 'exif':
                    $arguments[] = '-strip';
                    break;

                case 'flatten':
                    $arguments[] = '-flatten';
                    break;

                case 'background':
                    if ($value) {
                        $arguments[] = '-background';
                        $arguments[] = $value;
                    }

                    break;

                case 'interlace':
                    if ($value) {
                        $value       = image_interlace_valid(strtolower($value));
                        $arguments[] = '-interlace';
                        $arguments[] = $value;
                    }

                    break;

                case 'updatemode':
                    if ($params['updatemode'] === true) {
                        $params['updatemode'] = $_CONFIG['file']['dir_mode'];
                    }

                case 'x':
                    //do nothing (x-pos)
                    // no-break
                case 'y':
                    //do nothing (y-pos)
                    // no-break
                case 'h':
                    //do nothing (height)
                    // no-break
                case 'w':
                    //do nothing (width)
                    // no-break
                case 'custom':
                    //do nothing (custom imagemagick parameters)
                    //FALLTHROUGH
                case 'log':
                    //do nothing (custom imagemagick parameters)
                    //FALLTHROUGH
                case 'method':
                    //do nothing (function method)
                case 'format':
                    //do nothing (forced format)
                    break;
            }
        }

        /*
         * Check width / height
         *
         * If either width or height is not specified then
         */
        if (!$params['x'] or !$params['y']) {
            $size = getimagesize($source);

            if ($params['keep_aspectratio'] and $size[0] and $size[1]) {
                $ar = $size[1] / $size[0];

            } else {
                $ar = 1;
            }

            if (!$params['x']) {
                $params['x'] = not_empty($params['y'], $size[1]) * (1 / $ar);
            }

            if (!$params['y']) {
                $params['y'] = not_empty($params['y'], $size[0]) * $ar;
            }
        }

        /*
         * Execute command to convert image
         */
        switch ($params['method']) {
            case 'rotate':
                $arguments[] = '-rotate';
                $arguments[] = $params['degrees'];
                $arguments[] = $source;
                $arguments[] = $target;

                safe_exec([
                    'commands' => [$command, $arguments],
                    'timeout'  => $params['timeout']
                ]);
                break;

            case 'thumb':
                $arguments[] = '-thumbnail';
                $arguments[] = $params['x'].'x'.$params['y'].'^';
                $arguments[] = '-gravity';
                $arguments[] = 'center';
                $arguments[] = '-extent';
                $arguments[] = $params['x'].'x'.$params['y'];
                $arguments[] = $source;
                $arguments[] = $target;

                safe_exec([
                    'commands' => [$command, $arguments],
                    'timeout'  => $params['timeout']
                ]);
                break;

            case 'resize-w':
                $arguments[] = '-resize';
                $arguments[] = $params['x'].'x\>';
                $arguments[] = $source;
                $arguments[] = $target;

                safe_exec([
                    'commands' => [$command, $arguments],
                    'timeout'  => $params['timeout']
                ]);
                break;

            case 'resize':
                $arguments[] = '-resize';
                $arguments[] = $params['x'].'x'.$params['y'].'^';
                $arguments[] = $source;
                $arguments[] = $target;

                safe_exec([
                    'commands' => [$command, $arguments],
                    'timeout'  => $params['timeout']
                ]);
                break;

            case 'thumb-circle':
                $tmpfname    = tempnam("/tmp", "CVRT_");
                $arguments[] = '-thumbnail';
                $arguments[] = $params['x'].'x'.$params['y'].'^';
                $arguments[] = '-gravity';
                $arguments[] = 'center';
                $arguments[] = '-extent';
                $arguments[] = $params['x'].'x'.$params['y'];
                $arguments[] = '-background';
                $arguments[] = 'white';
                $arguments[] = $source;
                $arguments[] = $tmpfname;

                $arguments2[] = '-size';
                $arguments2[] = $params['x'].'x'.$params['y'];
                $arguments2[] = 'xc:none';
                $arguments2[] = '-fill';
                $arguments2[] = $tmpfname;
                $arguments2[] = '-draw';
                $arguments2[] = 'circle '.(floor($params['x'] / 2) - 1).','.(floor($params['y'] / 2) - 1).' '.($params['x']/2).',0';
                $arguments2[] = $target;

                safe_exec([
                    'commands' => [$command, $arguments],
                    'timeout'  => $params['timeout']
                ]);

                safe_exec([
                    'commands' => [$command, $arguments2],
                    'timeout'  => $params['timeout']
                ]);

                file_delete($tmpfname);
                break;

            case 'crop-resize':
                $arguments[] = $source;
                $arguments[] = '-crop';
                $arguments[] = cfi($params['w'], false).'x'.cfi($params['h'], false).'+'.cfi($params['x'], false).'+'.cfi($params['y'], false);
                $arguments[] = '-resize';
                $arguments[] = cfi($params['to_w'], false).'x'.cfi($params['to_h'], false);
                $arguments[] = $target;

                safe_exec(array('commands' => array($command, $arguments),
                    'timeout'  => $params['timeout']));
                break;

            case 'custom':
                $arguments[] = $source;
                $arguments[] = $target;

                safe_exec(array('commands' => array($command, $arguments),
                    'timeout'  => $params['timeout']));
                break;

            case '':
                throw new CoreException(tr('image_convert(): No method specified.'), 'not-specified');

            default:
                throw new CoreException(tr('image_convert(): Unknown method ":method" specified. Ensure method is one of thumb, resize-w, resize, thumb-circle, crop-resize, custom', array(':method' => $params['method'])), 'unknown');
        }

        /*
         * Verify results
         */
        if (!file_exists($target)) {
            throw new CoreException(tr('image_convert(): Destination file ":file" not found after conversion', array(':file' => $target)), 'not-exists');
        }

        if (!empty($params['updatemode'])) {
            chmod($target, $params['updatemode']);
        }

        return $target;

    }catch(Exception $e) {
        switch ($e->getCode()) {
            case 'not-installed':
                /*
                 * Imagemagick command "convert" missing
                 */
                log_console(tr('image_convert(): The "convert" command could not be found, trying to install imagemagick'), 'warning');

                try {
                    load_libs('linux');
                    linux_install_package(null, 'imagemagick');

                    return image_convert($params);

                }catch(Exception $f) {
                    throw new CoreException(tr('image_convert(): The "convert" command could not be found. This probably means that imagemagick has not been installed. Phoundation tried to install the package automatically but this failed. Please install imagemagick yourself. On Debian and derrivates this can be done with the command "sudo apt -y install imagemagick". On Redhat and derrivates this can be done with the command "sudo yum install imagemagick"'), $f);
                }
        }

        if (!is_array($params)) {
            throw new CoreException(tr('image_convert(): Invalid parameters specified, expected params array but received ":params"', array(':params' => $params)), 'invalid');
        }

        /*
         * webp support missing?
         */
        if (isset_get($params['format']) === 'webp') {
            $line = $e->getData();
            $line = array_pop($line);

            if (str_contains($line, 'delegate failed') and str_contains($line, 'error/delegate.c/InvokeDelegate')) {
                /*
                 * WebP conversion failed. Very likely this is due to webp being
                 * not installed. Install it and retry
                 */
                try {
                    load_libs('linux');
                    linux_install_package(null, 'webp');

                    return image_convert($params);

                }catch(Exception $f) {
                    throw new CoreException(tr('image_convert(): The "convert" command failed because webp is not supported. On Debian and derrivates this may require installing webp, which was tried and failed. Please try installing the package manually using "sudo apt -y install webp".'), $f);
                }
            }
        }

        /*
         * Get error information from the imagemagic_convert log file
         */
        try {
            if (file_exists(ROOT.'data/log/imagemagic_convert.log')) {
                $contents = safe_exec(array('commands' => array('tail', array('-n', '3', ROOT.'data/log/imagemagic_convert.log'))));
            }

        }catch(Exception $e) {
            $contents = tr('image_convert(): Failed to get contents of imagemagick log file ":file"', array(':file' => ROOT.'data/log/imagemagic_convert.log'));
        }

        if (empty($contents)) {
            throw new CoreException(tr('image_convert(): Failed'), $e);

        } else {
            foreach (Arrays::force($contents) as $line) {
                if (strstr($line, '/usr/bin/convert: not found')) {
                    /*
                     * Dumbo! You don't have imagemagick installed!
                     */
                    throw new CoreException(tr('image_convert(): /usr/bin/convert could not be found, which means you probably do not have imagemagick installed. To resolve this, try on Ubuntu-alikes, try "sudo apt-get install imagemagick", or on RedHat-alikes, try "yum install imagemagick"'), 'notinstalled');
                }
            }

        }

        throw new CoreException(tr('image_convert(): Failed, with *possible* log data "%contents%"', array('%contents%' => $contents)), $e);
    }
}



/*
 *
 */
function image_interlace_valid($value, $source = false) {
    if ($source) {
        $check = Strings::until($value, '-');

    } else {
        $check = Strings::from($value, '-');
    }

    switch ($check) {
        case 'jpeg':
            // no-break
        case 'gif':
            // no-break
        case 'png':
            // no-break
        case 'line':
            // no-break
        case 'partition':
            // no-break
        case 'plane':
            return $check;

        case 'none':
            return '';

        case 'auto':
            if (file_size($source) > 10240) {
                /*
                 * Use specified interlace
                 */
                return image_interlace_valid($value);
            }

            /*
             * Don't use interlace
             */
            break;

        default:
            throw new CoreException(tr('image_interlace_valid(): Unknown interlace value ":value" specified', array(':value' => $value)), 'unknown');
    }
}



/*
 * Validates if the specified file is an image and optionally if the minimim image width and heights are okay
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package image
 * @version 2.5.38: Added function and documentation
 *
 * @param string $file
 * @return string The image mimetype
 */
function image_is_valid($file, $min_width = 0, $min_height = 0) {
    try {
        $mimetype = file_mimetype($file);

        if (Strings::until($mimetype, '/') !== 'image') {
            throw new CoreException(tr('image_is_valid(): Specified file ":file" is not an image but an ":mimetype"', array(':file' => $file, ':mimetype' => $mimetype)), 'invalid');
        }

        if (!$img_size = getimagesize($file)) {
            throw new CoreException(tr('image_is_valid(): Failed to get width / height data from specified image ":file"', array(':file' => $file)), 'failed');
        }

        if (($img_size[0] < $min_width) or ($img_size[1] < $min_height)) {
            throw new CoreException(tr('image_is_valid(): File ":file" has width x height ":actual" where a minimum wxh of ":required" is required', array(':file' => $file, ':actual' => $img_size[0].' x '.$img_size[1], ':required' => $min_width.' x '.$min_height)), 'rejected');
        }

        return $mimetype;

    }catch(Exception $e) {
        log_file(new CoreException('image_is_valid(): Failed', $e->makeWarning(true)), 'image-is-valid', 'warning');
        return false;
    }
}



/*
 * Create all required avatars for the specified image file
 */
function image_create_avatars($file) {
    global $_CONFIG;

    try {
        $destination = file_assign_target(ROOT.'www/avatars/');

        foreach ($_CONFIG['avatars']['types'] as $name => $type) {
            if (count($type  = explode('x', $type)) != 3) {
                throw new CoreException('image_create_avatar(): Invalid avatar type configuration for type "'.Strings::Log($name).'"', 'invalid/config');
            }

            image_convert(array('source' => $file['tmp_name'][0],
                'target' => ROOT.'www/avatars/'.$destination.'_'.$name.'.'.file_get_extension($file['name'][0]),
                'x'      => $type[0],
                'y'      => $type[1],
                'method' => $type[2]));
        }

        return $destination;

    }catch(Exception $e) {
        throw new CoreException('image_create_avatar(): Failed to create avatars for image file "'.Strings::Log($file).'"', $e);
    }
}



/*
 * Returns image type name or false if file is valid image or not
 */
function is_image($file) {
    try {
        return (boolean) image_type($file);

    }catch(Exception $e) {
        if ($e->getCode() === 'not-file') {
            /*
             * Specified path is just not a file
             */
            return false;
        }

        throw new CoreException('is_image(): Failed', $e);
    }
}



/*
 *
 */
function image_info($file, $no_exif = false) {
    global $_CONFIG;

    try {
        $mime = file_mimetype($file);

        if (Strings::until($mime, '/') !== 'image') {
            throw new CoreException(tr('image_info(): The specified file ":file" is not an image', array(':file' => $file)), 'invalid');
        }

        $size = getimagesize($file);

        $retval['filename'] = basename($file);
        $retval['size']     = filesize($file);
        $retval['path']     = Strings::slash(dirname($file));
        $retval['mime']     = $mime;
        $retval['bits']     = $size['bits'];
        $retval['x']        = $size[0];
        $retval['y']        = $size[1];

        /*
         * Get EXIF information from JPG or TIFF image files
         */
        switch (Strings::from($mime, '/')) {
            case 'jpeg':
                try {
                    $retval['compression'] = safe_exec(array('commands' => array($_CONFIG['images']['imagemagick']['identify'], array('-format', '%Q', $file))));
                    $retval['compression'] = array_shift($retval['compression']);

                }catch(Exception $e) {
                    log_console(tr('Failed to get compression information for file ":file" because ":e"', array(':e' => $e->getMessage(), ':file' => $file)), 'red');
                }

                if (!$no_exif) {
                    try {
                        $retval['exif'] = exif_read_data($file, null, true, true);

                    }catch(Exception $e) {
                        $retval['exif'] = array(tr('Failed to get EXIF information because ":error"', array(':error' => $e->getMessage())));
                    }
                }

                break;

            case 'tiff':
                if (!$no_exif) {
                    try {
                        $retval['exif'] = exif_read_data($file, null, true, true);

                    }catch(Exception $e) {
                        $retval['exif'] = array(tr('Failed to get EXIF information because ":error"', array(':error' => $e->getMessage())));
                    }
                }

                break;
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('image_info(): Failed', $e);
    }
}



/*
 * Returns image type name or false if file is valid image or not
 */
function image_type($file) {
    try {
        if (Strings::until(file_mimetype($file), '/') == 'image') {
            return Strings::from(file_mimetype($file), '/');
        }

        return false;

    }catch(Exception $e) {
        throw new CoreException('image_type(): Failed', $e);
    }
}



/*
 * Sends specified image file to the client
 */
function image_send($file, $cache_maxage = 86400) {
    try {
        if (!file_exists($file)) {
            /*
             * Requested image does not exist
             */
            Web::execute(404);
        }

        /*
         * Get headers sent by the client.
         */
        $headers = apache_request_headers();

        /*
         * Check if the client is validating his cache and if it is current.
         */
        if ($cache_maxage and isset($headers['If-Modified-Since']) and (strtotime($headers['If-Modified-Since']) >= filemtime($file))) {
            /*
             * Client's cache IS current, so we just respond '304 Not Modified'.
             */
            header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($file)).' GMT', true, 304);

        } else {
            /*
             * Image not cached or cache outdated, we respond '200 OK' and output the image.
             */
            header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($file)).' GMT', true, 200);

            if ($cache_maxage) {
                header('Cache-Control: max-age='.$cache_maxage.', public');
                header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_maxage));
            }

            header('Content-Length: '.filesize($file));
            header('Content-Type: '.file_mimetype($file));
            readfile($file);
            die();
        }

    }catch(Exception $e) {
        throw new CoreException('image_send(): Failed', $e);
    }
}



/*
 * Compares the image file type with the extension, and if it
 * does not match, will fix the extension
 */
function image_fix_extension($file) {
    try {
        /*
         * Get specified extension and determine file mimetype
         */
        $mimetype  = file_mimetype($file);
        $extension = strtolower(file_get_extension($file));

        if (($extension == 'jpg') or ($extension == 'jpeg')) {
            $specified = 'jpeg';

        } else {
            $specified = $extension;
        }

        /*
         * If the file is not an image then we're done
         */
        if (Strings::until($mimetype, '/') != 'image') {
            throw new CoreException('image_fix_extension(): Specified file "'.Strings::Log($file).'" is not an image', 'invalid');
        }

        /*
         * If the extension specified type differs from the mimetype, then autorename the file to the correct extension
         */
        if ($specified != Strings::from($mimetype, '/')) {
            $new = Strings::from($mimetype, '/');

            if ($new == 'jpeg') {
                $new = 'jpg';
            }

            $new = Strings::untilReverse($file, '.'.$extension).'.'.$new;

            rename($file, $new);
            return $new;
        }

        return $file;

    }catch(Exception $e) {
        throw new CoreException('image_fix_extension(): Failed', $e);
    }
}



/*
 * Add fancybox image support
 *
 * Example
 *
 * <a href="pub/img/test/image.jpg" rel="example_group" class="hover_image">
 *     <span class="mask"></span>
 *    '.html_img('/pub/img/test/montage/image.jpg" >
 * </a>
 *
 * image_fancybox(array(options...);
 *
 * See http://www.fancyapps.com/fancybox/#docs for documentation on options
 */
function image_fancybox($params = null) {
    try {
        array_params($params);
        array_default($params, 'selector', '.fancy');
        array_default($params, 'options' , array());

        array_default($params['options'], 'openEffect'    , 'fade');
        array_default($params['options'], 'closeEffect'   , 'fade');
        array_default($params['options'], 'arrows'        , true);
        array_default($params['options'], 'titleShow'     , true);
        array_default($params['options'], 'titleFromAlt'  , true);
        array_default($params['options'], 'fitToView'     , true);
        //array_default($params['options'], 'width'         , null);
        //array_default($params['options'], 'height'        , null);
        array_default($params['options'], 'aspectRatio'   , false);
        array_default($params['options'], 'autoSize'      , true);
        array_default($params['options'], 'autoDimensions', true);
        array_default($params['options'], 'centerOnScroll', true);
        array_default($params['options'], 'titlePosition', 'outside'); // over, outside, inside

        html_load_js('base/fancybox/jquery.fancybox');
        html_load_css('base/fancybox/jquery.fancybox');

        return html_script('$("'.$params['selector'].'").fancybox('.json_encode_custom($params['options']).');');

    }catch(Exception $e) {
        throw new CoreException('image_fancybox(): Failed', $e);
    }
}



/*
 * Place a watermark over an image
 */
function image_watermark($params) {
    try {
        Arrays::ensure($params);
        array_default($params, 'image'    , '');
        array_default($params, 'watermark', '');
        array_default($params, 'target'   , '');
        array_default($params, 'opacity'  , '50%');
        array_default($params, 'margins'  , array());

        array_default($params['margins'], 'top'   , '0');
        array_default($params['margins'], 'left'  , '0');
        array_default($params['margins'], 'right' , '10');
        array_default($params['margins'], 'bottom', '10');

        /*
         * Verify image and water mark image
         */
        foreach (array('image' => $params['image'], 'watermark' => $params['watermark']) as $type => $filename) {
            if (!file_exists($params['target'])) {
                throw new CoreException(tr('image_watermark(): The specified %type% file ":file" does not exists', array('%type%' => $type, ':file' => Strings::Log($filename))), 'imagenotexists');
            }

            if (!$size = getimagesize($filename)) {
                throw new CoreException(tr('image_watermark(): The specified %type% file ":file" is not a valid image', array('%type%' => $type, ':file' => Strings::Log($filename))), 'imagenotvalid');
            }
        }

        unset($size);

        /*
         * Make sure the target does not yet exist, UNLESS we're writing to the same image
         */
        if ((realpath($params['target']) != realpath($params['image'])) and file_exists($params['target'])) {
            throw new CoreException('image_watermark(): The specified target "'.Strings::Log($params['target']).'" already exists', 'targetexists');
        }

        /*
         * Load the image and watermark into memory
         */
        $image     = imagecreatefromany($params['image']);
        $watermark = imagecreatefromany($params['watermark']);

        $sx        = imagesx($watermark);
        $sy        = imagesy($watermark);

        /*
         * Merge the stamp onto our photo with the specified opacity
         */
        imagecopymerge_alpha($image, $watermark, imagesx($image) - $sx - $params['margins']['right'], imagesy($image) - $sy - $params['margins']['bottom'], 0, 0, imagesx($watermark), imagesy($watermark), 50);

        /*
         * Save the image to file and free memory
         */
        imagepng($image, $params['target']);

        imagedestroy($image);
        imagedestroy($watermark);

    }catch(Exception $e) {
        throw new CoreException('image_watermark(): Failed', $e);
    }
}



/*
 * One function to open any type of image in GD
 *
 * FUCK YOU PHP for making me having to use the @ operator here,
 * but apparently, GD just throws text into the output buffer without
 * actually generating an error..
 *
 * Google "Parse error</b>:  imagecreatefromjpeg(): gd-jpeg, libjpeg: recoverable error:" for more information
 */
function imagecreatefromany($filename) {
    try {
        switch (exif_imagetype($filename)) {
            case IMAGETYPE_GIF:
                $resource = @imagecreatefromgif ($filename);
                break;

            case IMAGETYPE_JPEG:
                $resource = @imagecreatefromjpeg($filename);
                break;

            case IMAGETYPE_PNG:
                $resource = @imagecreatefrompng($filename);
                break;

            case IMAGETYPE_WBMP:
                $resource = @imagecreatefrombmp($filename);
                break;

            case IMAGETYPE_SWF:
                // no-break
            case IMAGETYPE_PSD:
                // no-break
            case IMAGETYPE_BMP:
                // no-break
            case IMAGETYPE_TIFF_II: // (intel byte order)
                // no-break
            case IMAGETYPE_TIFF_MM: // (motorola byte order)
                // no-break
            case IMAGETYPE_JPC:
                // no-break
            case IMAGETYPE_JP2:
                // no-break
            case IMAGETYPE_JPX:
                // no-break
            case IMAGETYPE_JB2:
                // no-break
            case IMAGETYPE_SWC:
                // no-break
            case IMAGETYPE_IFF:
                // no-break
            case IMAGETYPE_XBM:
                // no-break
            case IMAGETYPE_ICO:
                throw new CoreException('imagecreatefromany(): Image types "'.exif_imagetype($filename).'" of file "'.Strings::Log($filename).'" is not supported', 'notsupported');

            default:
                throw new CoreException('imagecreatefromany(): The file "'.exif_imagetype($filename).'" is not an image', 'notsupported');
        }

        if (!$resource) {
            throw new CoreException('imagecreatefromany(): Failed to open image type "'.exif_imagetype($filename).'" file "'.$filename.'"', 'failed');
        }

        return $resource;

    }catch(Exception $e) {
        if (!file_exists($filename)) {
            throw new CoreException('imagecreatefromany(): Specified file "'.Strings::Log($filename).'" does not exist', $e);
        }

        throw new CoreException('imagecreatefromany(): Failed', $e);
    }
}



/*
 * Taken from http://www.php.net/manual/en/function.imagecopymerge.php, thanks to user Sina Salek

 * PNG ALPHA CHANNEL SUPPORT for imagecopymerge();
 * by Sina Salek
 *
 * Bugfix by Ralph Voigt (bug which causes it
 * to work only for $src_x = $src_y = 0.
 * Also, inverting opacity is not necessary.)
 * 08-JAN-2011
 *
 **/
function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct) {
    try {
        // creating a cut resource
        $cut = imagecreatetruecolor($src_w, $src_h);

        // copying relevant section from background to the cut resource
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);

        // copying relevant section from watermark to the cut resource
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);

        // insert cut resource to destination image
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);

    }catch(Exception $e) {
        throw new CoreException('imagecopymerge_alpha(): Failed for source image "'.Strings::Log($src_im).'"', $e);
    }
}



/*
 * Create an HTML / JQuery image picker that sets the selected images as form values
 */
function image_picker($params) {
    try {
        html_load_js('image-picker/image-picker');
        html_load_css('image-picker');

        Arrays::ensure($params);
        array_default($params, 'resource'  , null);
        array_default($params, 'name'      , 'image-picker');
        array_default($params, 'id'        , 'image-picker');
        array_default($params, 'path'      , null);
        array_default($params, 'class'     , 'image-picker show-html');
        array_default($params, 'masonry'   , true);
        array_default($params, 'loaded'    , true);
        array_default($params, 'none'      , false);
        array_default($params, 'show_label', false);

        if ($params['masonry']) {
            html_load_js('masonry.pkgd');
            $params['class'] .= ' masonry';
        }

        /*
         * If resource is a string, then assume its a path to an image directory
         */
        if (is_string($params['resource'])) {
            $params['resource'] = scandir($params['resource']);
            $params['resource'] = array_merge_keys_values($params['resource'], $params['resource']);
        }

        /*
         * Convert image file names into URL's
         * Remove ., .., and hidden files
         */
        if (!empty($params['url'])) {
            foreach ($params['resource'] as $key => &$image) {
                if (!$image) continue;

                if ($image[0] == '.') {
                    unset($params['resource'][$key]);
                }

                $image = str_replace(':image', $image, $params['url']);
            }
        }

        unset($image);

        /*
         * Add required data info for html_select();
         */
        if (empty($params['data_resources'])) {
            $params['data_resources'] = array();
        }

        $params['data_resources']['img-src'] = $params['resource'];

        $retval = html_select($params).
            html_script('$("#'.$params['id'].'").imagepicker(
                    { show_label : '.Strings::boolean($params['show_label']).'}
                  );');

        if ($params['masonry']) {
            if ($params['loaded']) {
                html_load_js('imagesloaded');
                $retval .= html_script('
                    var $grid = $("#'.$params['id'].'").masonry({
                        itemSelector: "li",
                        columnWidth: 200
                    });

console.log($grid);
                    // layout Masonry after each image loads
                    $grid.imagesLoaded().progress( function() {
console.log("imagesloaded");
                        $grid.masonry("layout");
                    });');

            } else {
                $retval .= html_script('
                    $("'.$params['masonry'].'").masonry({
                        // options
                        itemSelector: "li",
                        columnWidth: 200
                    });');
            }
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('image_picker(): Failed', $e);
    }
}



/*
 * Returns HTML and loads JS and CSS for sliders.
 *
 * Supported sliders are:
 * A-Slider : http://varunnaik.github.io/A-Slider/
 *            https://github.com/varunnaik/A-Slider
 *
 * Jssor    : http://www.jssor.com/support.html
 *
 */
function image_slider($params = null) {
    try {
        Arrays::ensure($params);
        array_default($params, 'library' , 'bxslider');
        array_default($params, 'selector', '#slider');
        array_default($params, 'options'  , array());

        switch ($params['library']) {
            case 'aslider':
                ensure_installed(array('checks'    => 'aslider',
                    'checks'    => '',
                    'locations' => array('js'  => ROOT.'pub/js/aslider',
                        'css' => ROOT.'pub/css/aslider'),
                    'install'   => 'http://varunnaik.github.io/A-Slider/a-slider.zip'));
// :TODO: Implement
                break;

            case 'bxslider':
                /*
                 * http://bxslider.com/
                 * https://github.com/stevenwanderski/bxslider-4
                 * GIT REPO: https://github.com/stevenwanderski/bxslider-4.git
                 */
                ensure_installed(array('name'      => 'bxslider',
                    'checks'    => ROOT.'pub/js/bxslider',
                    'locations' => array('src/js'     => ROOT.'pub/js/bxslider',
                        'src/css'    => ROOT.'pub/css/bxslider',
                        'src/vendor' => ROOT.'pub/js'),
                    'url'       => 'https://github.com/stevenwanderski/bxslider-4.git'));

                html_load_js('jquery,bxslider/bxslider');
                html_load_css('bxslider/bxslider');

                $html = html_script('$(document).ready(function() {
                    $("'.$params['selector'].'").bxSlider({'.array_implode_with_keys($params['options'], ',', ':').'});
                });');

                return $html;

            default:
                throw new CoreException(tr('image_picker(): Unknown library ":library" specified', array(':library' => $params['library'])), 'unknown');
        }

    }catch(Exception $e) {
        throw new CoreException('image_slider(): Failed', $e);
    }
}



/*
 * Modify the specified image to make it look glitched
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package image
 *
 * @param string $file The image to be modified
 * @param null mixed $server The server on which to execute this glitch command
 * @return string The result
 */
function image_glitch($file, $server = null) {
    try {
        $mimetype = image_is_valid($file);

        if (Strings::from($mimetype, '/') !== 'png') {
            throw new CoreException(tr('image_glitch(): This function only supports PNG images. The specified file ":file" is a ":type" type file', array(':file' => $file, ':type' => Strings::from($mimetype, '/'))), 'not-supported');
        }

        $file_out = file_temp();

        if ($server) {
// :TODO: Git doesnt support multi server yet
            under_construction();
        }

        load_libs('go');

        if (!go_exists('corrupter/corrupter')) {
            load_libs('git');
            log_console('Corrupter program not setup yet, creating now');

            linux_file_delete($server, array('patterns'     => ROOT.'data/go/corrupter',
                'restrictions' => false));

            git_clone('https://github.com/r00tman/corrupter', ROOT.'data/go');
            go_build(ROOT.'data/go/corrupter', $server);
        }

        go_exec(array('commands' => array('corrupter/corrupter', array($file, $file_out))));

        return $file_out;

    }catch(Exception $e) {
        throw new CoreException(tr('image_glitch(): Failed'), $e);
    }
}



/*
 * OBSOLETE
 * Please use view();
 */
function image_view($file, $background = true) {
    try {
        load_libs('view');
        return view($file);

    }catch(Exception $e) {
        throw new CoreException('image_view(): Failed', $e);
    }
}
?>
