<?php
/*
 * Video library
 *
 * This library contains various video management functions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package video
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
 * @package video
 *
 * @return void
 */
function video_library_init() {
    try{
        if (!file_which('ffmpeg')) {
            throw new CoreException(tr('video_library_init(): ffmpeg module not installed, run this command on your server: sudo apt update && sudo apt install ffmpeg libav-tools x264 x265;'), 'not_available');
        }

    }catch(Exception $e) {
        throw new CoreException('video_library_init(): Failed', $e);
    }
}



/*
 * Generates a thumbnail for the specified video file and returns the filename
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package video
 *
 * @params string $file The video file from which a thumbnail must be made
 * @params string $size The required size of the thumbnail in XxY format
 * @return string The generated thumbnail file
 */
function video_get_thumbnail($file, $size = '50x50') {
    try{
        $retval = file_temp(false);
        safe_exec(array('commands' => array('ffmpeg', array('-i', '{'.$file.'}', '-deinterlace', '-an', '-ss', '00:00:01', '-t', '00:00:02', '-s', '{'.$size.'}', '-r', '1', '-y', '-vcodec', 'mjpeg', '-f', 'mjpeg', '{'.$retval.'}'))));

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('video_get_thumbnail(): Failed', $e);
    }
}
