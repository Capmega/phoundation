<?php
/*
 * Wget library
 *
 * This library us a front-end to the wget function
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package wget
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
 * @package wget
 * @version 2.4.11: Added function and documentation
 *
 * @return void
 */
function wget_library_init() {
    try {
        if (!file_which('wget')) {
            linux_install_package('wget');
        }

    }catch(Exception $e) {
        throw new CoreException('wget_library_init(): Failed', $e);
    }
}



/*
 * wget command front-end function
 *
 * At minimum, this function requires $params[url], and $params[file]
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package wget
 * @version 2.4.22: Added function and documentation
 *
 * @param params $params The parameters for wget
 * @return string The result
 */
function wget($params) {
    try {
        array_params($params, 'url');

        if (empty($params['url'])) {
            throw new CoreException(tr('wget(): No url specified'), 'not-specified');
        }

        if (empty($params['file'])) {
            Path::ensure(PATH_TMP);
            $params['file'] = file_temp(false);
        }

        safe_exec(array('commands' => array('wget', array('-O', $params['file'], $params['url'], 'redirect' => ' >> '.PATH_ROOT.'data/log/syslog'))));
    	return $params['file'];

    }catch(Exception $e) {
        switch ($e->getCode()) {
            case '1':
                throw new CoreException('wget(): Failed to download file, wget reported error "1: Generic error code"', $e);

            case '2':
                throw new CoreException('wget(): Failed to download file, wget reported error "2: Parse error---for instance, when parsing command-line options, the .wgetrc or .netrc"', $e);

            case '3':
                throw new CoreException('wget(): Failed to download file, wget reported error "3: File I/O error"', $e);

            case '4':
                throw new CoreException('wget(): Failed to download file, wget reported error "4: Network failure"', $e);

            case '5':
                throw new CoreException('wget(): Failed to download file, wget reported error "5: SSL verification failure"', $e);

            case '6':
                throw new CoreException('wget(): Failed to download file, wget reported error "6: Username/password authentication failure"', $e);

            case '7':
                throw new CoreException('wget(): Failed to download file, wget reported error "7: Protocol errors"', $e);

            case '8':
                throw new CoreException('wget(): Failed to download file, wget reported error "8: Server issued an error response"', $e);

        }

        throw new CoreException('wget(): Failed', $e);
    }
}
?>
