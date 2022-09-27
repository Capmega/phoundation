<?php
/*
 * scanimage library
 *
 * This library allows to run the scanimage program, scan images and save them to disk
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package scanimage
 * @see https://support.brother.com/g/s/id/linux/en/download_scn.html Brother scanner models and required drivers
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the ssh library configuration
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package scanimage
 *
 * @return void
 */
function scanimage_library_init(){
    try{
        load_config('scanimage');
        load_libs('linux,image,devices');

    }catch(Exception $e){
        throw new BException('scanimage_library_init(): Failed', $e);
    }
}



/*
 * Scan image using the scanimage command line program
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package scanimage
 * @example scanimage --progress  --buffer-size --contrast 50 --gamma 1.8 --jpeg-quality 80 --transfer-format JPEG --mode Color --resolution 300 --format jpeg > test.jpg
 *
 * @param params $params
 * @params string device
 * @params string jpeg_quality
 * @params string buffer_size
 * @params string options
 * @params string format
 * @params string file
 * @return string the file name for the scanned image
 */
function scanimage($params){
    try{
        $server = servers_get($params['domain']);
        $params = scanimage_validate($params);

        /*
         * Finish scan command and execute it
         */
        try{
            if($params['domain'] !== ''){
                load_libs('rsync');
            }

            if($params['batch']){
                log_console(tr('Batch scanning to path ":path"', array(':path' => $params['path'])), 'cyan');

                /*
                 * Batch scanning is done to a PATH, not a FILE!
                 */
                if($params['domain'] === ''){
                    /*
                     * This is the own machine
                     */
                    $results = safe_exec(array('ok_exitcodes' => '0,2',
                                               'timeout'      => $params['timeout'],
                                               'commands'     => array('scanimage', array_merge(array('sudo' => $params['sudo'], '--format', 'tiff'), $params['options']))));

                    $result  = array_pop($results);
                    $result  = str_cut($result, ',', 'pages');
                    $result  = trim($result);

                    $params['results'] = $results;
                    $params['result']  = $result;
                    return $params;

                }else{
                    /*
                     * This is a remote server
                     */
                    $remote = linux_ensure_path($server, $params['path']);
                    $pid    = servers_exec($server, array('ok_exitcodes' => '0,2',
                                                          'timeout'      => $params['timeout'],
                                                          'background'   => true,
                                                          'output_log'   => true,
                                                          'commands'     => array('scanimage', array_merge(array('sudo' => $params['sudo'], '--format', 'tiff'), $params['options']))));

                    rsync(array('source'              => $server['domain'].':'.$params['path'],
                                'target'              => $params['local']['batch'],
                                'monitor_pid'         => $pid,
                                'exclude'             => '*.part',
                                'remove_source_files' => true));

                    $params['result']  = $params['local']['batch'];
                    return $params;
                }

            }else{
                log_console(tr('Scanning to file ":file"', array(':file' => $params['file'])), 'cyan');

                /*
                 * Scan a single file
                 */
                if($params['domain'] === ''){
                    /*
                     * This is the own machine. Scan to the TMP file
                     */
                    $file = TMP.str_random(16);
                    file_ensure_path(dirname($file));

                    $params['options']['redirect'] = ' > '.$file;
                    $results                       = servers_exec($server, array('timeout'  => $params['timeout'],
                                                                                 'commands' => array('scanimage', array_merge(array('sudo' => $params['sudo'], '--format', 'tiff'), $params['options']))));

                }else{
                    /*
                     * This is a remote server. Scan and rsync the file to TMP
                     */
                    $file   = file_temp(false);
                    $remote = '/tmp/'.str_random(16);

                    $params['options']['redirect'] = ' > '.$remote;
                    $results                       = servers_exec($server, array('timeout'  => $params['timeout'],
                                                                                 'commands' => array('scanimage', array_merge(array('sudo' => $params['sudo'], '--format', 'tiff'), $params['options']))));

                    rsync(array('source'              => $params['domain'].':'.$remote,
                                'target'              => $file,
                                'remove_source_files' => true));
                }

                /*
                 * Change file format?
                 */
                file_delete($params['file'], ROOT);

                switch($params['format']){
                    case 'tiff':
                        /*
                         * File should already be in TIFF, so we only have to rename
                         * it to the target file
                         */
                        rename($file, $params['file']);
                        break;

                    case 'jpg':
                        // FALLTHROUGH
                    case 'jpeg':
                        /*
                         * We have to convert it to a JPG file
                         */
                        image_convert(array('source' => $file,
                                            'target' => $params['file'],
                                            'method' => 'custom',
                                            'format' => 'jpg'));

                        break;
                }
            }

            $params['results'] = $results;
            $params['result']  = $params['file'];

            return $params;

        }catch(Exception $e){
            if(!is_numeric($e->getRealCode())){
                /*
                 *  This is some exception in the processing code, not an
                 *  exception from the command line, apparently
                 */
                throw $e;
            }

            /*
             * Try and parse output for error information to give understandable
             * error messages
             */
            $data = $e->getData();
            $line = '';

            if(is_array($data)){
                while($data){
                    $line = array_shift($data);
                    $line = strtolower($line);
                    $line = trim($line);

                    switch($line){
                        case 'terminate called after throwing an instance of \'std::bad_alloc\'':
                            // FALLTROUGH
                        case 'scanimage: sane_start: Error during device I/O':
                            // FALLTROUGH
                        case 'scanimage: sane_start: Operation was cancelled':
                            /*
                             * Scanner is having issues
                             */
// :TODO: Rescan device?
                            throw new BException(tr('scanimage(): Scanner failed, please check if scanner has documents. If all is okay, please restart scanner'), 'failed');

                        case 'scanimage: sane_start: Document feeder out of documents':
                            throw new BException(tr('scanimage(): Scanner document feeder has no documents'), 'empty');
                    }

                    if(str_exists($line, 'sane_start')){
                        break;
                    }

                    if(str_exists($line, 'scanimage:')){
                        break;
                    }

                    $line = '';
                }

            }else{
                $line = '';
            }

            switch(substr($line, 0, 25)){
                case 'scanimage: no SANE device':
                    /*
                     * No scanner found
                     */
                    throw new BException(tr('scanimage(): No scanner found'), 'not-exists');

                case 'scanimage: open of device':
                    /*
                     * Failed to open the device, it might be busy or not
                     * responding
                     */
                    $server  = servers_get($params['domain']);
                    $process = linux_pgrep($server, 'scanimage');

                    if(substr($line, -24, 24) === 'failed: invalid argument'){
                        throw new BException(tr('scanimage(): The scanner ":scanner" on server ":server" is not responding. Please start or restart the scanner', array(':scanner' => $params['device'], ':server' => $server['domain'])), 'stuck');

                    }else{
                        if($process){
                            throw new BException(tr('scanimage(): The scanner ":scanner" on server ":server" is already in operation. Please wait for the process to finish, or kill the process', array(':scanner' => $params['device'], ':server' => $server['domain'])), 'busy');
                        }
                    }

                default:
                    throw new BException(tr('scanimage(): Unknown scanner process error ":e"', array(':e' => $e->getData())), $e);
            }
        }

        return $params;

    }catch(Exception $e){
        throw new BException('scanimage(): Failed', $e);
    }
}



/*
 * Validate the specified scanimage parameters
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package scanimage
 *
 * @param params $params
 * @params string device
 * @params string jpeg_quality
 * @params string buffer_size
 * @params string options
 * @params string format
 * @params string file
 * @return params The specified scanimage parameters $params validated
 */
function scanimage_validate($params){
    global $_CONFIG;

    try{
        load_libs('validate');

        $v       = new ValidateForm($params, 'sudo,source,domain,device,batch,jpeg_quality,format,file,timeout,buffer_size,options');
        $options = array();
        $local   = array();

        $params['sudo'] = ($params['sudo'] or $_CONFIG['devices']['sudo']);

        /*
         * Ensure we have an absolute target file
         */
        $params['file'] = file_absolute($params['file']);

        /*
         * Check source options
         */
        if($params['source']){
            $params['options']['source'] = $params['source'];

            if(preg_match('/(?:bed)|(?:flat)|(?:table)/i', $params['source'])){
                /*
                 * Flatbed table
                 */


            }elseif(preg_match('/(?:auto)|(?:feeder)|(?:adf)/i', $params['source'])){
                /*
                 * ADF Auto Document Feeder
                 *
                 * Assume batch job
                 */
                $params['batch'] = true;

            }else{
                /*
                 * Unknown source type, assume flatbed
                 */

            }
        }

        /*
         * Get the device with the device options list
         */
        if($params['device']){
            $device = scanimage_get($params['device'], $params['domain']);

        }else{
            $device = scanimage_get_default();

            if(!$device){
                $v->setError(tr('No scanner specified and no default scanner found'));
            }
        }

        $params['device'] = $device['string'];

        /*
         * Ensure this is a document scanner device
         */
        if($device['type'] !== 'document-scanner'){
            $v->setError(tr('scanimage_validate(): The specified device ":device" is not a document scanner device', array(':device' => $device['id'].' / '.$device['string'])));
        }

        $options[] = '--device';
        $options[] = $device['string'];

        /*
         * Validate target file
         */
        $params['file'] = strtolower(trim($params['file']));

        if(!$params['file']){
            /*
             * Target file has not been specified
             */
            if(empty($params['batch'])){
                $v->setError(tr('No file specified'));

            }else{
                $v->setError(tr('No batch scan target path specified'));
            }

            $params['path'] = '';

        }elseif($params['batch']){
            /*
             * Ensure the target path exists as a directory
             */
            $params['path'] = file_absolute_path($params['file']);
            $params['path'] = file_ensure_path($params['path']);

        }else{
            /*
             * Single file scan, ensure that the target file does not exist
             */
            $params['path'] = slash(dirname($params['file']));

            if(file_exists($params['file'])){
                if(!FORCE){
                    $v->setError(tr('Specified file ":file" already exists', array(':file' => $params['file'])));

                }elseif(is_file($params['file'])){
                    file_delete($params['file'], ROOT);

                }else{
                    if(is_dir($params['file'])){
                        $v->setError(tr('Specified file ":file" already exists but is a directory', array(':file' => $params['file'])));

                    }else{
                        $v->setError(tr('Specified file ":file" already exists and is not a normal file (maybe a socket or device file?)', array(':file' => $params['file'])));
                    }
                }

            }else{
                file_ensure_path($params['path']);
            }
        }

        /*
         * Validate scanner buffer size
         */
        if($params['buffer_size']){
            $v->isNatural($params['buffer_size'], tr('Please specify a valid natural numbered buffer size'));
            $v->isBetween($params['buffer_size'], 1, 1024, tr('Please specify a valid buffer size between 1 and 1024'));
        }

        $v->isValid();

        /*
         * Ensure requested format is known and file ends with correct extension
         */
        switch($params['format']){
            case 'jpg':
                // FALLTHROUGH
            case 'jpeg':
                $extension = 'jpg';
                break;

            case 'tiff':
                $extension = 'tiff';
                break;

            case '':
                $v->setError(tr('No format specified'));
                break;
            default:
                $v->setError(tr('Unknown format ":format" specified', array(':format' => $params['format'])));
        }

        $v->isValid();

        if($params['batch']){
            array_default($params, 'timeout', $_CONFIG['devices']['timeout']['scanners_adf']);

            if($params['format'] != 'tiff'){
                $v->setError(tr('Specified batch file pattern ":file" has an incorrect file name extension for the requested format ":format", it should have the extension ":extension"', array(':file' => $params['file'], ':format' => $params['format'], ':extension' => $extension)));
            }

            if($params['domain']){
                $params['local']['batch'] = $params['path'];
                $params['path']           = '/tmp/'.str_random(16).'/';

                linux_ensure_path($params['domain'], $params['path']);
            }

            $params['file']             = 'image%d.'.$params['format'];
            $params['options']['batch'] = $params['path'].$params['file'];

        }else{
            array_default($params, 'timeout', $_CONFIG['devices']['timeout']['scanners']);

            if(str_rfrom($params['file'], '.') != $extension){
                if(($extension !== 'jpg') and (str_rfrom($params['file'], '.') !== 'jpeg')){
                    $v->setError(tr('Specified file ":file" has an incorrect file name extension for the requested format ":format", it should have the extension ":extension"', array(':file' => $params['file'], ':format' => $params['format'], ':extension' => $extension)));
                }

                /*
                 * User specified .jpeg, make it .jpg
                 */
                $params['file'] = str_runtil($params['file'], '.').',jpg';
            }
        }

        /*
         * Validate parameters against the device
         */
        if(!$params['options']){
            $params['options'] = array();
        }

        if(!is_array($params['options'])){
            $v->setError(tr('Please ensure options are specified as an array'));

        }else{
            foreach($params['options'] as $key => $value){
                if(!isset($device['options'][$key])){
                    /*
                     * This may be a system driver option
                     */
                    switch($key){
                        case 'batch':
// :TODO: Implement validations!
                            break;
                        case 'batch-start':
// :TODO: Implement validations!
                            break;
                        case 'batch-count':
// :TODO: Implement validations!
                            break;
                        case 'batch-increment':
// :TODO: Implement validations!
                            break;
                        case 'batch--double':
// :TODO: Implement validations!
                            break;

                        default:
                            $v->setError(tr('Driver option ":key" is not supported by device ":device"', array(':key' => $key, ':device' => $params['device'])));
                            goto continue_validation;
                    }

                    $options[] = '--'.$key.'='.$value;
                    $params['options'] = implode(' ', $options);
                    goto continue_validation;
                }

                if(!$value){
                    unset($params['option']);
                    continue;
                }

                if(is_string($device['options'][$key])){
                    /*
                     * This is a value range
                     */
                    $device['options'][$key] = array('min' => str_until($key, '..'),
                                                     'max' => str_from($key, '..'));

                    $v->isNatural($value, tr('Please specify a numeric contrast value'));
                    $v->isBetween($value, $device['options'][$key]['min'], $device['options'][$key]['max'], tr('Please ensure that ":key" is in between ":min" and ":max"', array(':key' => $key, ':min' => $device['options'][$key]['min'], ':max' => $device['options'][$key]['max'])));

                }else{
                    $v->inArray($value, $device['options'][$key], tr('Please select a valid ":key" value', array(':key' => $key)));
                }

                if(strlen($key) == 1){
                    $options[] = '-'.$key.'='.$value;

                }else{
                    $options[] = '--'.$key.'='.$value;
                }

                continue_validation:
            }
        }

        $v->isValid();

        $params['options'] = $options;

        return $params;

    }catch(Exception $e){
        throw new BException('scanimage_validate(): Failed', $e);
    }
}



/*
 * List the available scanner devices from the driver database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package scanimage
 *
 * @return array All found scanner devices
 */
function scanimage_list(){
    try{
        /*
         * Get device data from cache
         */
        $devices = devices_list('document-scanner');
        return $devices;

    }catch(Exception $e){
        throw new BException('scanimage_list(): Failed', $e);
    }
}




/*
 * Search devices from the scanner devices. This might take a while, easily up to 30 seconds or more
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package scanimage
 * @example scanimage -L outputs would be
 * device `brother4:bus4;dev1' is a Brother MFC-L8900CDW USB scanner
 * device `imagescan:esci:usb:/sys/devices/pci0000:00/0000:00:1c.0/0000:03:00.0/usb4/4-2/4-2:1.0' is a EPSON DS-1630
 *
 * @return array All found scanner devices
 */
function scanimage_detect_devices($server = null, $sudo = false){
    try{
        $scanners = servers_exec($server, array('timeout'  => 90,
                                                'commands' => array('scanimage', array('sudo' => $sudo, '-L', '-q'))));
        $devices  = array();

        foreach($scanners as $scanner){
            if(substr($scanner, 0, 6) != 'device') continue;

            $device = null;
            $found  = preg_match_all('/device `(.+?):bus(\d+);dev(\d+)\' is a (.+)/i', $scanner, $matches);

            if($found){
                /*
                 * Found a scanner
                 */
                $device = array('product'        => null,
                                'product_string' => null,
                                'vendor'         => null,
                                'vendor_string'  => null,
                                'bus'            => $matches[2][0],
                                'device'         => $matches[3][0],
                                'raw'            => $matches[0][0],
                                'driver'         => $matches[1][0],
                                'string'         => $matches[1][0].':bus'.$matches[2][0].';dev'.$matches[3][0],
                                'description'    => $matches[4][0]);
            }else{
                $found = preg_match_all('/device `((.+?):.+?)\' is a (.+)/i', $scanner, $matches);

                if($found){
                    /*
                     * Found a scanner
                     */
                    $device = array('product'        => null,
                                    'product_string' => null,
                                    'vendor'         => null,
                                    'vendor_string'  => null,
                                    'bus'            => null,
                                    'device'         => null,
                                    'raw'            => $matches[0][0],
                                    'driver'         => $matches[2][0],
                                    'string'         => $matches[1][0],
                                    'description'    => $matches[3][0]);
                }
            }

            if($device){
                $device['manufacturer'] = trim(str_until($device['description'], ' '));
                $device['model']        = trim(str_until(str_from($device['description'], ' '), ' '));
                $device['type']         = 'document-scanner';

                /*
                 * Get device options
                 */
                try{
                    $device['options'] = scanimage_get_options($device['string'], $server, $sudo);

                }catch(Exception $e){
                    devices_set_status('failed', $device['string']);

                    /*
                     * Options for one device failed to add, continue adding the rest
                     */
                    if(empty($device['options'])){
                        /*
                         * HP device? Give information on how to solve this issue
                         */
                        log_console(tr('Failed to retrieve options for device ":device" with device string ":string", scanner device has been disabled', array(':device' => $device['description'], ':string' => $device['string'])), 'yellow');
                        log_console(tr('Scanner options exception:'), 'yellow');
                        log_console($e, 'yellow');

                        if(strstr($device['string'], 'hpaio') or strstr($device['string'], 'Hewlett-Packard') or strstr($device['string'], ' HP')){
                            if(strstr($e->getData(), 'Error during device I/O')){
                                log_console(tr('*** INFO *** Device ":string" appears to be an Hewlett Packard scanner with an "Error during device I/O" error. This possibly is a known issue with a solution', array(':string' => $device['string'])), 'yellow');
                                log_console(tr('Uninstall the current "hplip" package from your installation, and install the official HP version'), 'yellow');
                                log_console(tr('For more information, see ":url"', array(':url' => 'https://unix.stackexchange.com/questions/272951/hplip-hpaio-error-during-device-i-o')), 'yellow');
                                log_console(tr('For more information, see ":url"', array(':url' => '//https://developers.hp.com/hp-linux-imaging-and-printing')), 'yellow');
                            }
                        }

                    }else{
                        log_console(tr('Failed to store options for device ":device" with device string ":string", scanner device has been disabled', array(':device' => $device['description'], ':string' => $device['string'])), 'yellow');
                        log_console(tr('Options data:'), 'yellow');
                        log_console($device['options'], 'yellow');
                        log_console(tr('Scanner exception:'), 'yellow');
                        log_console($e, 'yellow');
                    }

                    continue;
                }

                $devices[] = $device;
            }
        }

        return $devices;

    }catch(Exception $e){
        throw new BException('scanimage_detect_devices(): Failed', $e);
    }
}



// :DELETE: After 2019/04
///*
// *
// *
// * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
// * @copyright Copyright (c) 2018 Capmega
// * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
// * @category Function reference
// * @package scanimage
// * @example scanimage -L outputs would be
// * device `brother4:bus4;dev1' is a Brother MFC-L8900CDW USB scanner
// * device `imagescan:esci:usb:/sys/devices/pci0000:00/0000:00:1c.0/0000:03:00.0/usb4/4-2/4-2:1.0' is a EPSON DS-1630
// *
// * @return array All found scanner devices
// */
//function scanimage_update_devices(){
//    try{
//        load_libs('devices');
//        devices_clear('scanner');
//
//        $scanners = scanimage_detect_devices();
//        $failed   = 0;
//
//        foreach($scanners as $scanner){
//            unset($options);
//
//            try{
//                $scanner = devices_insert($scanner, 'scanner');
//                log_console(tr('Added device ":device" with device string ":string"', array(':device' => $scanner['description'], ':string' => $scanner['string'])), 'green');
//
//            }catch(Exception $e){
//                $failed++;
//
//                /*
//                 * One device failed to add, continue adding the rest
//                 */
//                log_console(tr('Failed to add device ":device" with device string ":string"', array(':device' => $scanner['description'], ':string' => $scanner['string'])), 'yellow');
//                log_console(tr('Scanner data:'), 'yellow');
//                log_console($scanner, 'yellow');
//                log_console(tr('Scanner exception:'), 'yellow');
//                log_console($e, 'yellow');
//                continue;
//            }
//
//            try{
//                $options = scanimage_get_options($scanner['string']);
//                $count   = devices_insert_options($scanner['id'], $options);
//
//                log_console(tr('Added ":count" options for device string ":string"', array(':string' => $scanner['string'], ':count' => $count)), 'VERBOSE/green');
//
//            }catch(Exception $e){
//                $failed++;
//                devices_set_status($scanner['string'], 'failed');
//
//                /*
//                 * Options for one device failed to add, continue adding the rest
//                 */
//                if(empty($options)){
//                    /*
//                     * HP device? Give information on how to solve this issue
//                     */
//                    log_console(tr('Failed to retrieve options for device ":device" with device string ":string", scanner device has been disabled', array(':device' => $scanner['description'], ':string' => $scanner['string'])), 'yellow');
//                    log_console(tr('Scanner options exception:'), 'yellow');
//                    log_console($e, 'yellow');
//
//                    if(strstr($scanner['string'], 'hpaio') or strstr($scanner['string'], 'Hewlett-Packard') or strstr($scanner['string'], ' HP')){
//                        if(strstr($e->getData(), 'Error during device I/O')){
//                            log_console(tr('*** INFO *** Device ":string" appears to be an Hewlett Packard scanner with an "Error during device I/O" error. This possibly is a known issue with a solution', array(':string' => $scanner['string'])), 'yellow');
//                            log_console(tr('Uninstall the current "hplip" package from your installation, and install the official HP version'), 'yellow');
//                            log_console(tr('For more information, see ":url"', array(':url' => 'https://unix.stackexchange.com/questions/272951/hplip-hpaio-error-during-device-i-o')), 'yellow');
//                            log_console(tr('For more information, see ":url"', array(':url' => '//https://developers.hp.com/hp-linux-imaging-and-printing')), 'yellow');
//                        }
//                    }
//
//                }else{
//                    log_console(tr('Failed to store options for device ":device" with device string ":string", scanner device has been disabled', array(':device' => $scanner['description'], ':string' => $scanner['string'])), 'yellow');
//                    log_console(tr('Options data:'), 'yellow');
//                    log_console($options, 'yellow');
//                    log_console(tr('Scanner exception:'), 'yellow');
//                    log_console($e, 'yellow');
//                }
//
//                continue;
//            }
//        }
//
//        if(empty($failed)){
//            return $scanners;
//        }
//
//        throw new BException(tr('scanimage_update_devices(): Failed to add ":count" scanners or driver options, see file log for more information', array(':count' => $failed)), 'warning/failed');
//
//    }catch(Exception $e){
//        throw new BException('scanimage_update_devices(): Failed', $e);
//    }
//}



/*
 * Get driver options for the specified scanner device from the devices
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package scanimage
 * @note Devices confirmed to be working:
 * device `brother4:bus4;dev1' is a Brother MFC-L8900CDW USB scanner
 * device `imagescan:esci:usb:/sys/devices/pci0000:00/0000:00:1c.0/0000:03:00.0/usb4/4-2/4-2:1.0' is a EPSON DS-1630
 * device `hpaio:/usb/HP_LaserJet_CM1415fnw?serial=00CNF8BC4K04' is a Hewlett-Packard HP_LaserJet_CM1415fnw all-in-one
 *
 * @param string $device
 * @return
 */
function scanimage_get_options($device, $server = null, $sudo = false){
    try{
        $results = servers_exec($server, array('commands' => array('scanimage', array('sudo' => $sudo, '-A', '-d', $device))));
        $retval  = array();

        foreach($results as $result){
            if(strstr($result, 'failed:')){
                if(strtolower(trim(str_from($result, 'failed:'))) == 'invalid argument'){
                    throw new BException(tr('scanimage_get_options(): Options scan for device ":device" failed with ":e". This could possibly be a permission issue; does the current process user has the required access to scanner devices? Please check this user\'s groups!', array(':device' => $device, ':e' => trim(str_from($result, 'failed:')))), 'failed', $result);
                }

                throw new BException(tr('scanimage_get_options(): Options scan for device ":device" failed with ":e"', array(':device' => $device, ':e' => trim(str_from($result, 'failed:')))), 'failed', $result);
            }

            $result = trim($result);
            $status = null;

            if(substr($result, 0, 1) != '-'){
                /*
                 * Doesn't contain driver info
                 */
                continue;
            }

            /*
             * These are driver keys
             */
            if(substr($result, 0, 2) == '--'){
                /*
                 * These are double dash options
                 */
                if(!preg_match_all('/--([a-zA-Z-]+)(.+)/', $result, $matches)){
                    throw new BException(tr('scanimage_get_options(): Unknown driver line format encountered for key "resolution"'), 'unknown');
                }
// :DEBUG: Do not remove the folowing commented line(s), its for debugging purposes
//show($matches);

                $key     = $matches[1][0];
                $data    = $matches[2][0];
                $default = str_rfrom($data, ' [');
                $default = trim(str_runtil($default, ']'));
                $data    = trim(str_runtil($data, ' ['));

                if($default === 'inactive'){
                    $status  =  $default;
                    $default = null;
                }

// :DEBUG: Do not remove the folowing commented line(s), its for debugging purposes
//show($key);
//show($data);
//show($default);
                if($data == '[=(yes|no)]'){
                    /*
                     * Options are yes or no
                     */
                    $data = array('yes', 'no');

                }else{
                    switch($key){
                        case 'mode':
                            // FALLTHROUGH
                        case 'scan-area':
                            // FALLTHROUGH
                        case 'source':
                            $data = explode('|', $data);
                            break;

                        case 'resolution':
                            $data = str_replace('dpi', '', $data);

                            if(strstr($data, '..')){
                                /*
                                 * Resolutions given as a range instead of discrete values
                                 */
                                $data = array(trim($data));

                            }else{
                                $data = explode('|', $data);
                            }

                            break;

                        case 'brightness':
                            $data = str_until($data, '(');
                            $data = str_replace('%', '', $data);
                            $data = array(trim($data));
                            break;

                        case 'contrast':
                            $data = str_until($data, '(');
                            $data = str_replace('%', '', $data);
                            $data = array(trim($data));
                            break;

                        default:
                            if(!strstr($data, '|')){
                                if(!strstr($data, '..')){
                                    throw new BException(tr('scanimage_get_options(): Unknown driver line ":result" found', array(':result' => $result)), 'unknown');
                                }

                                /*
                                 * Unknown entry, but treat it as a range
                                 */
                                $data = str_until($data, '(');
                                $data = str_replace('%', '', $data);
                                $data = array(trim($data));

                            }else{
                                /*
                                 * Unknown entry, but treat it as a distinct list
                                 */
                                $data = str_until($data, '(');
                                $data = str_replace('%', '', $data);
                                $data = explode('|', $data);
                            }
                    }
                }

            }else{
                /*
                 * These are single dash options
                 */
                if(!preg_match_all('/-([a-zA-Z-]+)(.+)/', $result, $matches)){
                    throw new BException(tr('scanimage_get_options(): Unknown driver line format encountered for key "resolution"'), 'unknown');
                }
// :DEBUG: Do not remove the folowing commented line(s), its for debugging purposes
//show($matches);

                $key     = $matches[1][0];
                $data    = $matches[2][0];
                $default = str_rfrom($data, ' [');
                $default = trim(str_runtil($default, ']'));
                $data    = str_runtil($data, ' [');
                $data    = trim(str_replace('mm', '', $data));

                if($default === 'inactive'){
                    $status  =  $default;
                    $default = null;
                }

// :DEBUG: Do not remove the folowing commented line(s), its for debugging purposes
//show($key);
//show($data);
//show($default);
                switch($key){
                    case 'l':
                        $data = str_until($data, '(');
                        $data = str_replace('%', '', $data);
                        $data = array(trim($data));
                        break;

                    case 't':
                        $data = str_until($data, '(');
                        $data = str_replace('%', '', $data);
                        $data = array(trim($data));
                        break;

                    case 'x':
                        $data = str_until($data, '(');
                        $data = str_replace('%', '', $data);
                        $data = array(trim($data));
                        break;

                    case 'y':
                        $data = str_until($data, '(');
                        $data = str_replace('%', '', $data);
                        $data = array(trim($data));
                        break;

                    default:
                        throw new BException(tr('scanimage_get_options(): Unknown driver key ":key" found', array(':key' => $key)), 'unknown');
                }
            }

            $retval[$key] = array('data'    => $data,
                                  'status'  => $status,
                                  'default' => $default);
        }

        return $retval;

    }catch(Exception $e){
        throw new BException(tr('scanimage_get_options(): Failed for device ":device"', array(':device' => $device)), $e);
    }
}



/*
 * Return the data on the default scanner
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package scanimage
 *
 * @return
 */
function scanimage_get_default(){
    try{
        $scanners = scanimage_list();

        while($scanner = sql_fetch($scanners)){
            if($scanner['default']){
                $scanner = scanimage_get($scanner['seostring'], $scanner['servers_id']);
                return $scanner;
            }
        }

        return null;

    }catch(Exception $e){
        throw new BException('scanimage_get_default(): Failed', $e);
    }
}



/*
 * Return the data on the default scanner
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package scanimage
 *
 * @param string $device_string The device to get and return data from
 * @return array All found data for the specified device
 */
function scanimage_get($device, $server = null){
    try{
        if(!$device){
            /*
             * No specific device specified, use the default
             */
            return scanimage_get_default();
        }

        $scanner = devices_get($device, $server);

        if(!$scanner){
            if(is_numeric($device)){
                throw new BException(tr('scanimage_get(): Specified scanner ":device" does not exist', array(':device' => $device)), 'not-exists');
            }

            throw new BException(tr('scanimage_get(): Specified scanner ":device" does not exist on server ":server"', array(':device' => $device, ':server' => $server)), 'not-exists');
        }

        $scanner['options'] = devices_list_options($scanner['id']);
        return $scanner;

    }catch(Exception $e){
        throw new BException('scanimage_get(): Failed', $e);
    }
}



/*
 * Create and return HTML for a select component showing available scanners
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package scanimage
 *
 * @param params $params
 * @return string The HTML for the select box
 */
function scanimage_select($params){
    try{
        array_ensure($params);
        array_default($params, 'name'      , 'scanner');
        array_default($params, 'autosubmit', true);
        array_default($params, 'none'      , false);
        array_default($params, 'empty'     , tr('No scanners available'));

        $scanners = scanimage_list();

        foreach($scanners as $scanner){
            if(empty($scanner['name'])){
                $params['resource'][$scanner['seodomain'].'/'.$scanner['seostring']] = $scanner['description'];

            }else{
                $params['resource'][$scanner['seodomain'].'/'.$scanner['seostring']] = $scanner['name'];
            }
        }

        $html = html_select($params);

        return $html;

    }catch(Exception $e){
        throw new BException('scanimage_select(): Failed', $e);
    }
}



/*
 * Create and return HTML for a select component showing available resolutions
 * for the specified scanner device
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package scanimage
 *
 * @param params $params
 * @return string The HTML for the resolution select box
 */
function scanimage_select_resolution($params){
    try{
        array_ensure($params, 'string');
        array_default($params, 'name'      , 'scanner');
        array_default($params, 'autosubmit', true);
        array_default($params, 'none'      , false);
        array_default($params, 'empty'     , tr('No scanners available'));

        $params['resource'] = sql_query('SELECT    `drivers_options`.`value` AS `id`,
                                                   `drivers_options`.`value`

                                         FROM      `devices`

                                         LEFT JOIN `drivers_options`
                                         ON        `drivers_options`.`devices_id` = `devices`.`id`
                                         AND       `drivers_options`.`key`        = "resolution"

                                         WHERE     `devices`.`string`     = :string',

                                         array(':string' => $params['string']));

        $html = html_select($params);

        return $html;

    }catch(Exception $e){
        throw new BException('scanimage_select_resolution(): Failed', $e);
    }
}



/*
 * Returns true if the scanimage process is running for the server for the
 * specified device
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package empty
 * @see empty_install()
 * @see date_convert() Used to convert the sitemap entry dates
 * @version 2.4.9: Added function and documentation
 *
 * @params mixed $device
 * @params null mixed $server
 * @return natural The amount of processes found
 */
function scanimage_runs($device, $server = null){
    try{
        if(!$device){
            throw new BException(tr('scanimage_runs(): No device specified'), 'not-specified');
        }

        $dbdevice = scanimage_get($device, $server);

        if(!$dbdevice){
            throw new BException(tr('scanimage_runs(): The specified scanner ":id" does not exist', array(':id' => $device)), 'warning/not-exist');
        }

        $count   = 0;
        $server  = servers_get($dbdevice['servers_id']);
        $results = linux_pgrep($server, 'scanimage');

        foreach($results as $result){
            $processes = linux_list_processes($server, array($result, 'scanimage'));

            if($processes){
                foreach($processes as $id => $process){
                    if(!str_exists($process, $dbdevice['string'])){
                        unset($processes[$id]);
                    }
                }

                $count = count($processes);
            }
        }

        return $count;

    }catch(Exception $e){
        throw new BException('scanimage_runs(): Failed', $e);
    }
}



/*
 * Kill the scanimage process for the specified scanner
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package empty
 * @see empty_install()
 * @see date_convert() Used to convert the sitemap entry dates
 * @version 2.4.9: Added function and documentation
 *
 * @params mixed $device
 * @params null mixed $server
 * @return void()
 */
function scanimage_kill($device, $server = null, $hard = false){
    try{
        if(!$device){
            throw new BException(tr('scanimage_kill(): No device specified'), 'not-specified');
        }

        $dbdevice = scanimage_get($device, $server);

        if(!$dbdevice){
            throw new BException(tr('scanimage_kill(): The specified scanner ":id" does not exist', array(':id' => $device)), 'warning/not-exist');
        }

        $server  = servers_get($dbdevice['servers_id']);
        $results = linux_pkill($server, 'scanimage', ($hard ? 9 : 15), true);

        log_console(tr('Successfully killed the scanimage process for scanner device ":device" on server ":server"', array(':device' => $dbdevice['string'], ':server' => $server['domain'])), 'green');

    }catch(Exception $e){
        throw new BException('scanimage_kill(): Failed', $e);
    }
}
?>
