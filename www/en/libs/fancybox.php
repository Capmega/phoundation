<?php
/*
 * fancybox library
 *
 * This is an fancybox library
 *
 * @author Camilo Rodriguez <crodriguez@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package fancybox
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
 * @package fancybox
 *
 * @return void
 */
function fancybox_library_init() {
    try {
        ensure_installed(array('name'      => 'fancybox',
                               'project'   => '2019',
                               'callback'  => 'fancybox_install',
                               'checks'    => array(ROOT.'pub/css/jquery.fancybox.css',
                                                    ROOT.'pub/js/jquery.fancybox.js')));

        // html_load_js('jquery-3.3.1,jquery.fancybox');

        html_load_js('jquery.fancybox');
        html_load_css('jquery.fancybox');

    }catch(Exception $e) {
        throw new CoreException('fancybox_library_init(): Failed', $e);
    }
}



/*
 * Install the external fancybox library
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package fancybox
 *
 * @param
 * @return
 */
function fancybox_install($params) {
    try {
        /*
         * Download the fancybox library, and install it in the pub directory
         */
        $js  = download('https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.6/dist/jquery.fancybox.min.js');
        $css = download('https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.6/dist/jquery.fancybox.min.css');

        File::executeMode(ROOT.'www/en/pub/js', 0770, function() {
            file_delete(array(ROOT.'www/en/pub/js/jquery.fancybox.js',
                              ROOT.'www/en/pub/css/jquery.fancybox.css'), ROOT.'www/en/pub');

            rename($js , ROOT.'www/en/pub/js/jquery.fancybox.js');
            rename($css, ROOT.'www/en/pub/css/jquery.fancybox.css');
        });

    }catch(Exception $e) {
        throw new CoreException('fancybox_install(): Failed', $e);
    }
}



/*
 * Generate and return Html code for fancybox
 *
 * @author Camilo Antonio Rodriguez <crodriguez@capmega.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package fancybox
 * @see html_img()
 * @version 1.27.0: Added documentation
 * @example
 * code
 * fancybox_image('path/to/big_img.jpg', html_img('path/to/small_img.jpg', 'alt text'));
 * /code
 * This would return
 * code
 * <a data-fancybox="gallery" href="path/to/big_img.jpg">
 *     <img src="path/to/small_img.jpg" alt="alt text" width="28" height="28">
 * </a>
 * /code
 *
 * @param $img_big URL for the orignal image
 * @params html_img $img_small
 * @return string with html code for fancybox
 */
function fancybox_image($img_big, $img_small) {
    try {
        return '<a data-fancybox="gallery" href="'.cdn_domain($img_big).'">'.$img_small.'</a>';

    }catch(Exception $e) {
        throw new CoreException('fancybox_image(): Failed', $e);
    }
}
?>
