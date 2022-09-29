<?php
/*
 * USB library
 *
 * This library is a frontend to lsusb
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package empty
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
 * @package empty
 *
 * @return void
 */
function usb_library_init() {
    try{
        load_libs('servers');

    }catch(Exception $e) {
        throw new CoreException('usb_library_init(): Failed', $e);
    }
}



/*
 * List all available USB devices
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package usb
 * @see usb_scan()
 * @version 2.4.10: Added documentation, added multi server support
 *
 * @params null string $libusb
 * @params null mixed $server
 * @return array
 */
function usb_list($libusb = null, $server = null) {
    try{
        $results = servers_exec(array('commands' => array('lsusb')));
        $devices = array();

        foreach($results as $result) {
            //Bus 004 Device 001: ID 1d6b:0003 Linux Foundation 3.0 root hub
            preg_match('/Bus (\d{3}) Device (\d{3}): ID ([0-9a-f]{4}):([0-9a-f]{4}) (.+)/', $result, $matches);

            $device = array('raw'     => $matches[0],
                            'bus'     => $matches[1],
                            'device'  => $matches[2],
                            'vendor'  => $matches[3],
                            'product' => $matches[4],
                            'name'    => $matches[5]);

            if($libusb) {
                if($libusb == $device['bus'].':'.$device['device']) {
                    /*
                     *
                     */
                    return $device;
                }

                /*
                 *
                 */
                continue;
            }

            $devices[] = $device;

        }

        return $devices;

    }catch(Exception $e) {
        throw new CoreException('usb_list(): Failed', $e);
    }
}





/*
 * Scan the USB bus for the specified filter
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package usb
 * @see usb_list()
 * @see devices_scan()
 * @version 2.4.10: Added function and documentation
 *
 * @param string $filter The regular expression filter with which the device will be found
 * @params null mixed $server
 * @return array
 */
function usb_scan($regex_filter, $server = null) {
    try{
        $results = safe_exec(array('commands' => array('lsusb', array('-v'))));
        $devices = array();
        $retval  = array();
        $device  = '';

        /*
         * Divide the result lines into USB devices
         */
        foreach($results as $result) {
            $result = trim($result);

            if($result) {
                $device .= $result."\n";

            } else {
                if(!$device) {
                    /*
                     * We have no device data yet, probably an empty line at the
                     * top of the file or a double empty line. Ignore and
                     * continue;
                     */
                    continue;
                }

                $devices[] = $device;
                $device    = '';
            }
        }

        foreach($devices as $devices) {
            //Bus 004 Device 001: ID 1d6b:0003 Linux Foundation 3.0 root hub
            $found = preg_match($regex_filter, $devices, $matches);

            if($found) {
                $retval[] = $device;
            }
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('usb_scan(): Failed', $e);
    }
}
?>
