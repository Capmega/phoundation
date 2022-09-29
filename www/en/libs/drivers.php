<?php
/*
 * Drivers library
 *
 * This library can manage hardware device drivers
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package drivers
 */



/*
 * Get and setup the driver for the specified device type, brand and model
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package drivers
 * @version 2.4.10: Added function and documentation
 *
 * @param string $type
 * @param string $brand
 * @param string $model
 * @return void
 */
function drivers_setup($type, $brand, $model, $server = null){
    try{
        cli_only();
        load_libs('linux');

        $brand = strtolower($brand);
        $model = strtolower($model);

        switch($type){
            case 'printer':
                // FALLTHROUGH
            case 'scanner':
                switch($brand){
                    case 'brother':
                        /*
                         * Brother MFC L-8900 CDW scanner / printer combo
                         * Download the generic driver file
                         */
                        $file  = linux_download($server, 'https://download.brother.com/welcome/dlf006893/linux-brprinter-installer-2.2.1-1.gz');
                        $file  = linux_unzip($server, $file).'linux-brprinter-installer-2.2.1-1';

                        /*
                         * Sanitize the model
                         */
                        $model = strtoupper($model);
                        $model = str_replace(' ', '', $model);

                        chmod($file, 0750);
                        servers_exec($server, $file.' -f '.$model, null, true, 'passthru');
                        break;

                    default:
                        throw new CoreException(tr('drivers_setup(): Unknown brand ":brand" for device type ":type" specified', array(':type' => $type, ':brand' => $brand)), 'unknown');
                }

            default:
                throw new CoreException(tr('drivers_setup(): Unknown device type ":type" specified', array(':type' => $type)), 'unknown');
        }

    }catch(Exception $e){
        throw new CoreException(tr('drivers_setup(): Failed'), $e);
    }
}
?>
