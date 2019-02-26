<?php
/*
 * Viewer library
 *
 * This library contains functions to view media files like images, videos, pdf files, etc
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package viewer
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package empty
 *
 * @return void
 */
function view_library_init(){
    try{
        load_libs('cli');
        load_config('view');

    }catch(Exception $e){
        throw new BException(tr('view_library_init(): Failed'), $e);
    }
}



 /*
 * View the specified file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package viewer
 * @see view_image()
 * @version 2.4.11: Added function and documentation

 * @param string $file The file to view
 * @return void
 */
function view($file){
    try{
        $mimetype = file_mimetype($file);
        $mimetype = str_until($mimetype, '/');

        switch($mimetype){
            case 'image':
                return view_image($file);

            case 'video':
                return view_video($file);

            case 'pdf':
                return view_pdf($file);

            default:
                throw new BException(tr('view_image(): Unknown default image viewer ":viewer" specified', array(':viewer' => $_CONFIG['view']['images']['default'])), 'unknown');
        }

    }catch(Exception $e){
        throw new BException(tr('view(): Failed'), $e);
    }
}



 /*
 * View the specified image
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package viewer
 * @see view()
 * @version 2.4.11: Added function and documentation

 * @param string $file The image to view
 * @return void
 */
function view_image($file){
    global $_CONFIG;

    try{
        switch($_CONFIG['view']['images']['default']){
            case 'feh':
                return view_image_feh($file);

            default:
                throw new BException(tr('view_image(): Unknown default image viewer ":viewer" specified', array(':viewer' => $_CONFIG['view']['images']['default'])), 'unknown');
        }

    }catch(Exception $e){
        throw new BException(tr('view_image(): Failed'), $e);
    }
}



 /*
 * View the specified image using the feh image viewer
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package viewer
 * @see view()
 * @version 2.4.11: Added function and documentation

 * @param string $file The image to view using feh
 * @return void
 */
function view_image_feh($file){
    try{
        if(!cli_which('feh')){
            /*
             * feh isn't installed yet, try to install it
             */
            load_libs('linux');
            linux_install_package(null, 'feh');
        }

        safe_exec('feh "'.$file.'"');

    }catch(Exception $e){
        throw new BException(tr('view_image_feh(): Failed'), $e);
    }
}
?>
