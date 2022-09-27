<?php
/*
 * sane library
 *
 * This library allows access to the SANE commands
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package sane
 * @see http://download.ebz.epson.net/dsc/du/02/DriverDownloadInfo.do?LG2=EN&CN2=&DSCMI=89050&DSCCHK=a6987299eeb16ea098c06b7b66f332a53c7c34de For Epson DS-1630 scanner driver for Linux
 * @see http://download.ebz.epson.net/man/linux/imagescanv3_e.html For Epson Image Scan v3 manualfor Linux
 * @see https://support.brother.com/g/b/downloadlist.aspx?c=us&lang=en&prod=mfcl8900cdw_all&os=128 for various Brother MFC L8900 CDW driver files for Linux
 * @see https://support.brother.com/g/b/downloadend.aspx?c=us&lang=en&prod=mfcl8900cdw_all&os=128&dlid=dlf006645_000&flang=4&type3=566 for Brother MFC L8900 CDW scanner driver for Linux
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the ssh library configuration
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sane
 *
 * @return void
 */
function sane_library_init(){
    try{
        load_config('sane');

    }catch(Exception $e){
        throw new BException('sane_library_init(): Failed', $e);
    }
}



/*
 * Find available scanners
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package scanimage
 *
 * @param string $libusb The libusb identifier string of a specific device. If specified, only this device will be returned, if found
 * @return array All scanners found by SANE
 */
function sane_find_scanners($libusb = false){
    global $_CONFIG;

    try{
        $results = safe_exec(array('ok_exitcodes' => '1',
                                   'commands'     => array('sane-find-scanner', array('sudo' => true, '-q', 'connector' => '|'),
                                                           'grep'             , array('-v', '"Could not find"', 'connector' => '|'),
                                                            'grep'            , array('-v', '"Pipe error"'))));
        $retval  = array('count'     => 0,
                         'usb'       => array(),
                         'scsi'      => array(),
                         'parrallel' => array(),
                         'unknown'   => array());

        foreach($results as $result){
            if(substr($result, 0, 17) == 'found USB scanner'){
                /*
                 * Found a USB scanner
                 */
                if(preg_match_all('/found USB scanner \(vendor=0x([0-9a-f]{4}) \[([A-Za-z0-9-_ ]+)\], product=0x([0-9a-f]{4}) \[([A-Za-z0-9-_ ]+)\]\) at libusb:([0-9]{3}:[0-9]{3})/i', $result, $matches)){
                    $device = array('raw'          => $matches[0][0],
                                    'vendor'       => $matches[1][0],
                                    'product'      => $matches[3][0],
                                    'manufacturer' => $matches[2][0],
                                    'model'        => $matches[4][0],
                                    'libusb'       => $matches[5][0]);

                    if($libusb){
                        if($libusb == $device['libusb']){
                            /*
                             * Return only the requested device
                             */
                            return $device;
                        }

                        /*
                         * Only show the requested libusb device
                         */
                        continue;
                    }

                    $retval['count']++;
                    $retval['usb'][] = $device;

                }else{
                    $retval['unknown'][] = $result;
                }

            }elseif(substr($result, 0, 18) == 'found SCSI scanner'){
under_construction();
// :TEST: This has not been tested due to a lack of parrallel scanners. Do these still exist?
                /*
                 * Found a SCSI scanner
                 */
                if(preg_match_all('/found SCSI scanner (vendor=0x([0-9a-f]{4}) \[([A-Z0-9-_])\], product=0x([0-9a-f]{4}) \[([A-Z0-9-_])\]) at libusb:([0-9{3}]:[0-9]{3})/i', $result, $matches)){
                    $retval['count']++;
                    $retval['scsi'][] = array('vendor'       => $matches[0][0],
                                              'product'      => $matches[2][0],
                                              'manufacturer' => $matches[1][0],
                                              'libusb'       => $matches[4][0]);

                }else{
                    $retval['unknown'][] = $result;
                }

            }elseif(substr($result, 0, 23) == 'found parrallel scanner'){
under_construction();
// :TEST: This has not been tested due to a lack of parrallel scanners. Do these still exist?
                /*
                 * Found a parrallel scanner
                 */
                if(preg_match_all('/found parrallel scanner (vendor=0x([0-9a-f]{4}) \[([A-Z0-9-_])\], product=0x([0-9a-f]{4}) \[([A-Z0-9-_])\]) at libusb:([0-9{3}]:[0-9]{3})/i', $result, $matches)){
                    $retval['count']++;
                    $retval['parrallel'][] = array('vendor'       => $matches[0][0],
                                                   'product'      => $matches[2][0],
                                                   'manufacturer' => $matches[1][0],
                                                   'libusb'       => $matches[4][0]);

                }else{
                    $retval['unknown'][] = $result;
                }

            }elseif(substr($result, 0, 25) == 'could not open USB device'){
                /*
                 * Skip, this is not a scanner
                 */

            }else{
                $retval['unknown'][] = $result;
            }

        }

        return $retval;

    }catch(Exception $e){
        throw new BException('sane_find_scanners(): Failed', $e);
    }
}
?>
