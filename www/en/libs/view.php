<?php
/*
 * Viewer library
 *
 * This library contains functions to view media files like images, videos, pdf files, etc
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <license@capmega.com>
 * @category Function reference
 * @package viewer
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
function view_library_init(){
    try{
        load_libs('cli');
        load_config('view');

    }catch(Exception $e){
        throw new CoreException(tr('view_library_init(): Failed'), $e);
    }
}



 /*
 * View the specified file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
        /*
         * Validate argument
         */
        if(!$file){
            /*
             * A directory was specified instead of a file.
             */
            throw new CoreException(tr('view(): No file specified'), 'invalid');
        }

        if(!file_exists($file)){
            /*
             * A directory was specified instead of a file.
             */
            throw new CoreException(tr('view(): The specified file ":file" does not exist', array(':file' => $file)), 'invalid');
        }

        if(!is_file($file)){
            if(is_dir($file)){
                /*
                 * A directory was specified instead of a file.
                 */
                throw new CoreException(tr('view(): The specified file ":file" is not a normal file but a directory', array(':file' => $file)), 'invalid');
            }

            throw new CoreException(tr('view(): The specified file ":file" is not a normal viewable file', array(':file' => $file)), 'invalid');
        }

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
                throw new CoreException(tr('view_image(): Unknown default image viewer ":viewer" specified', array(':viewer' => $_CONFIG['view']['images']['default'])), 'unknown');
        }

    }catch(Exception $e){
        throw new CoreException(tr('view(): Failed'), $e);
    }
}



 /*
 * View the specified image
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
                throw new CoreException(tr('view_image(): Unknown default image viewer ":viewer" specified', array(':viewer' => $_CONFIG['view']['images']['default'])), 'unknown');
        }

    }catch(Exception $e){
        throw new CoreException(tr('view_image(): Failed'), $e);
    }
}



 /*
 * View the specified image using the feh image viewer
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
        if(!file_which('feh')){
            /*
             * feh isn't installed yet, try to install it
             */
            load_libs('linux');
            linux_install_package(null, 'feh');
        }

        safe_exec(array('background' => true,
                        'commands'   => array('feh', array($file))));

    }catch(Exception $e){
        throw new CoreException(tr('view_image_feh(): Failed'), $e);
    }
}
?>
