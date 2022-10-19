<?php
/*
 * Captcha Library
 *
 * Currently only supports recaptcha, but can potentially support other captcha systems as well
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package captcha
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
 * @package captcha
 *
 * @return void
 */
function captcha_library_init() {
    try {
        load_config('captcha');

    }catch(Exception $e) {
        throw new CoreException('captcha_library_init(): Failed', $e);
    }
}



/*
 * Return captcha html
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package captcha
 * @see captcha_verify_response()
 * @version 2.0.3: Added documentation
 *
 * @param string
 * @return string The result
 */
function captcha_html($class = null) {
    global $_CONFIG;

    try {
        if ($_CONFIG['captcha']['enabled']) {
            return '';
        }

        if ((empty($_CONFIG['captcha']['public']) or empty($_CONFIG['captcha']['private']))) {
            throw new CoreException(tr('captcha_html(): No captcha public apikey specified'), 'not-specified');
        }

        /*
         * Ensure we have a locally hosted copy of this file
         */
        if (!file_exists(ROOT.'pub/js/recaptcha/api.js')) {
            $file = download('https://www.google.com/recaptcha/api.js');

            File::executeMode(ROOT.'pub/js/', 0770, function() use ($file) {
                Path::ensure(ROOT.'pub/js/recaptcha/', 0550);

                File::executeMode(ROOT.'pub/js/recaptcha/', 0770, function() use ($file) {
                    rename($file, ROOT.'pub/js/recaptcha/api.js');
                });
            });

            file_delete(TMP.'captcha');
        }

        html_load_js('recaptcha/api');
        return '<div class="g-recaptcha'.($class ? ' '.$class : '').'" data-sitekey="'.$_CONFIG['captcha']['public'].'"></div>';

    }catch(Exception $e) {
        throw new CoreException('captcha_html(): Failed', $e);
    }
}



/*
 * Check captcha response
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package captcha
 * @see captcha_html()
 * @version 2.0.3: Added documentation
 *
 * @param string
 * @return string The result
 */
function captcha_verify_response($captcha) {
    global $_CONFIG;

    try {
        if (!$_CONFIG['captcha']['enabled']) {
            /*
             * Use no captcha
             */
            return false;
        }

        if ((empty($_CONFIG['captcha']['public']) or empty($_CONFIG['captcha']['private']))) {
            throw new CoreException(tr('captcha_verify_response(): No captcha public apikey specified'), 'not-specified');
        }

        if (empty($captcha)) {
            throw new CoreException('Please verify the captcha', 'captcha');
        }

        $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$_CONFIG['captcha']['private'].'&response='.$captcha.'&remoteip='.$_SERVER['REMOTE_ADDR']);
        $response = json_decode($response, true);

        if (!$response['success']) {
            throw new CoreException('captcha_verify_response(): Recaptcha is not valid', 'captcha');
        }

        return true;

    }catch(Exception $e) {
        throw new CoreException('captcha_verify_response(): Failed', $e);
    }
}
