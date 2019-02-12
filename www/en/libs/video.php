<?php
/*
 * Video library
 *
 * This library contains various video management functions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package video
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
 * @package video
 *
 * @return void
 */
function video_library_init(){
    try{
        if(!safe_exec('which ffmpeg')){
            throw new BException(tr('video_library_init(): ffmpeg module not installed, run this command on your server: sudo apt update && sudo apt install ffmpeg libav-tools x264 x265;'), 'not_available');
        }

    }catch(Exception $e){
        throw new BException('video_library_init(): Failed', $e);
    }
}



/*
 * Generates a thumbnail for the specified video file and returns the filename
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package video
 *
 * @params string $video_file The video file from which a thumbnail must be made
 * @params string $size The required size of the thumbnail in XxY format
 * @return string The generated thumbnail file
 */
function video_get_thumbnail($video_file, $size = '50x50'){
    try{
        $output_file = file_temp(false);
        $command     = 'ffmpeg -i {'.$video_file.'} -deinterlace -an -ss 00:00:01 -t 00:00:02 -s {'.$size.'} -r 1 -y -vcodec mjpeg -f mjpeg {'.$output_file.'} 2>&1 >> '.ROOT.'data/log/video_thumbnail';

        safe_exec($command);
        return $output_file;

    }catch(Exception $e){
        throw new BException('video_get_thumbnail(): Failed', $e);
    }
}
