<?php
/*
 * DEVICES CONFIGURATION FILE
 *
 * @author Sven Oostenbrink <support@capmega.com>,
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Configuration
 * @package devices
 */
$_CONFIG['devices']                                                             = array('sudo'    => false,                         // If set to true, all device actions will always be executed with sudo
                                                                                        'timeout' => array('scanners'     => 30,    // Default timeout value for scanners when doing single page scans
                                                                                                           'scanners_adf' => 120)); // Default timeout value for scanners when doing Auto Document Feeder scans
?>
