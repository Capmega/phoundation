<?php
/*
 * Social library
 *
 * This library contains social media functionalities
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package template
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
 * @package template
 * @version 2.2.0: Added function and documentation
 *
 * @return void
 */
function social_library_init() {
    try {
        load_config('social');

    }catch(Exception $e) {
        throw new CoreException('social_library_init(): Failed', $e);
    }
}



/*
 *
 */
function social_links($params = false, $returnas = 'string', $separator = ' | ') {
    global $_CONFIG;

    try {
        $retval = array();

        if (!$params) {
            $params = $_CONFIG['social']['links'];
        }

        foreach ($params as $key => $value) {
            switch ($key) {
                case 'youtube';
                    if ($value) {
                        $retval[] = '<a href="http://www.youtube.com/user/'.$value.'" class="social youtube"'.(empty($params['target']) ? '' : ' target="'.$params['target'].'"').'>Youtube</a>';
                    }

                    break;

                case 'facebook';
                    if ($value) {
                        $retval[] = '<a href="https://www.facebook.com/'.$value.'" class="social facebook"'.(empty($params['target']) ? '' : ' target="'.$params['target'].'"').'>Facebook</a>';
                    }

                    break;

                case 'twitter';
                    if ($value) {
                        $retval[] = '<a href="https://twitter.com/'.$value.'" class="social twitter"'.(empty($params['target']) ? '' : ' target="'.$params['target'].'"').'>Twitter</a>';
                    }

                    break;
            }
        }

        if ($retval) {
            html_load_css('social');
        }

        switch ($returnas) {
            case 'array':
                return $retval;

            case 'string':
                return implode($separator, $retval);

            default:
                throw new CoreException('social_links(): Unknown returnas "'.Strings::Log($returnas).'" specified', 'unknown');
        }

    }catch(Exception $e) {
        throw new CoreException('social_links(): Failed', $e);
    }
}
?>
