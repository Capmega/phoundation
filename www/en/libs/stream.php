<?php
/*
 * Stream library
 *
 * This is a data streaming library
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package stream
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package stream
 * @version 2.5.84: Added function and documentation
 *
 * @return void
 */
function stream_library_init(){
    try{
        load_config('stream');

    }catch(Exception $e){
        throw new CoreException(tr('stream_library_init(): Failed'), $e);
    }
}



/*
 * Stream a video file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package stream
 * @version 2.5.92: Added function and documentation
 * @note Inspiration for this function taken from https://codesamplez.com/programming/php-html5-video-streaming-tutorial
 * @see http://codesamplez.com/programming/php-html5-video-streaming-tutorial
 * @see stream_open()
 * @see stream_close()
 * @see stream_video()
 * @see stream_audio()
 *
 * @param params $params The streaming parameters array
 */
function stream($params){
    global $_CONFIG;

    try{
        array_ensure($params, 'file,mimetype');
        array_default($params, 'strict'       , $_CONFIG['stream']['strict']);
        array_default($params, 'cache_max_age', $_CONFIG['stream']['cache_max_age']);

        /*
         * Open the file to be streamed and determine its mimetype to know what
         * sub functions to use
         */
        stream_open($params);

        $mimetype = mime_content_type($params['file']);

        if($params['mimetype']){
            /*
             * Ensure the specified file maches the mimetype
             */
            if($params['strict']){
                /*
                 * Match the entire mimetype
                 */
                if($mimetype !== $params['mimetype']){
                    throw new CoreException(tr('stream(): Specified file ":file" failed strict mimetype check. If has mimetype ":has" while ":requested" was requested', array(':has' => $mimetype, ':mimetype' => $params['mimetype'])), 'not-authorized');
                }

            }else{
                if(str_until($mimetype, '/') !== str_until($params['mimetype'], '/')){
                    throw new CoreException(tr('stream(): Specified file ":file" failed lax mimetype check. If has mimetype ":has" while ":requested" was requested', array(':has' => str_until($mimetype, '/'), ':mimetype' => str_until($params['mimetype'], '/'))), 'not-authorized');
                }
            }
        }

        /*
         * Set mimetype in parameters, it will be required later
         */
        $params['mimetype'] = $mimetype;

        switch(str_until($mimetype, '/')){
            case 'audio':
                return stream_audio($params);

            case 'video':
                return stream_video($params);

            throw new CoreException(tr('stream(): Unsupported mimetype ":mimetype" encounered', array(':mimetype' => str_until($mimetype, '/'))), 'unsupported');
        }

    }catch(Exception $e){
        throw new CoreException(tr('stream(): Failed'), $e);
    }
}



/*
 * Open the file for streaming
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package stream
 * @version 2.5.86: Added function and documentation
 * @see stream_close()
 *
 * @param params $params A parameters array
 * @return void
 */
function stream_open(&$params){
    try{
        if(empty($params['file'])){
            throw new CoreException(tr('stream_open(): No file specified'), 'not-specified');
        }

        if(!file_exists($params['file'])){
            throw new CoreException(tr('stream_open(): Specified file ":file" does not exist', array(':file' => $params['file'])), 'not-exist');
        }

        if(!is_readable($params['file'])){
            throw new CoreException(tr('stream_open(): Specified file ":file" is not readable', array(':file' => $params['file'])), 'not-readable');
        }

        $params['resource'] = fopen($this->path, 'rb');

        if(!$params['resource']){
            throw new CoreException(tr('stream_open(): Failed to open file ":file" for streaming', array(':file' => $params['file'])), $e);
        }

        $params['start']     = 0;
        $params['size']      = filesize($params['file']);
        $params['end']       = $params['size'];
        $params['filemtime'] = filemtime($params['file']);

    }catch(Exception $e){
        throw new CoreException(tr('stream_open(): Failed'), $e);
    }
}



/*
 * Closes the stream file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package stream
 * @version 2.5.92: Added function and documentation
 * @note Inspiration for this function taken from https://codesamplez.com/programming/php-html5-video-streaming-tutorial
 * @see http://codesamplez.com/programming/php-html5-video-streaming-tutorial
 * @see stream_open()
 *
 * @param params $params The streaming parameters array
 */
function stream_close($params){
    try{
        array_ensure($params);

        array_default($params, 'die', true);

        if(empty($params['resource'])){
            throw new CoreException(tr('stream_close(): No video file resource opened. Please open one first using stream_open(), or just use stream()'), 'not-specified');
        }

        fclose($params['resource']);

        if(empty($params['die'])){
            die();
        }

    }catch(Exception $e){
        throw new CoreException(tr('stream_close(): Failed'), $e);
    }
}



/*
 * Stream a audio file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package stream
 * @version 2.5.92: Added function and documentation
 * @note Inspiration for this function taken from https://codesamplez.com/programming/php-html5-audio-streaming-tutorial
 * @see http://codesamplez.com/programming/php-html5-audio-streaming-tutorial
 *
 * @param params $params The streaming parameters array
 */
function stream_audio($params){
    try{
under_construction('Audio streaming is still under construction');
        stream_audio_send_headers($params);
        stream_audio_send($params);
        stream_end($params);

    }catch(Exception $e){
        throw new CoreException(tr('stream_audio(): Failed'), $e);
    }
}



/*
 * Stream a video file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package stream
 * @version 2.5.92: Added function and documentation
 * @note Inspiration for this function taken from https://codesamplez.com/programming/php-html5-video-streaming-tutorial
 * @see http://codesamplez.com/programming/php-html5-video-streaming-tutorial
 *
 * @param params $params The streaming parameters array
 */
function stream_video($params){
    try{
        stream_video_data_headers($params);
        stream_video_data($params);
        stream_end($params);

    }catch(Exception $e){
        throw new CoreException(tr('stream_video(): Failed'), $e);
    }
}



/*
 * Send the video stream HTTP headers
 *
 * @author Rana
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package stream
 * @version 2.5.92: Added function and documentation
 * @note Inspiration for this function taken from https://codesamplez.com/programming/php-html5-video-streaming-tutorial
 * @see http://codesamplez.com/programming/php-html5-video-streaming-tutorial
 *
 * @param params $params The streaming parameters array
 * return void
 */
function stream_video_data_headers($params){
    try{
        array_ensure($params, 'mimetype,cache_max_age,start,end,size');

        if(empty($params['resource'])){
            throw new CoreException(tr('stream_video_data_headers(): No video file resource opened. Please open one first using stream_open(), or just use stream()'), 'not-specified');
        }

        if(empty($params['file'])){
            throw new CoreException(tr('stream_video_data_headers(): No video file specified'), 'not-specified');
        }

        /*
         * Clean the output buffer and start sending HTTP headers
         */
        ob_get_clean();

        header('Content-Type: '.$params['mimetype']);
        header('Cache-Control: max-age='.$params['cache_max_age'].', public');
        header('Expires: '.gmdate('D, d M Y H:i:s', time() + $params['cache_max_age']).' GMT');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $params['filemtime']).' GMT');

        header('Accept-Ranges: 0-'.$params['end']);

        if(empty($_SERVER['HTTP_RANGE'])){
            header('Content-Length: '.$params['size']);
            return;
        }

        $c_start = $params['start'];
        $c_end   = $params['end'];

        list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);

        if(strpos($range, ',') !== false){
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header('Content-Range: bytes '.$params['start'].'-'.$params['end'].'/'.$params['size']);
            die();
        }

        if($range == '-'){
            $c_start = $params['size'] - substr($range, 1);

        }else{
            $range   = explode('-', $range);
            $c_start = $range[0];
            $c_end   = (isset($range[1]) and (is_numeric($range[1])) ? $range[1] : $c_end);
        }

        $c_end = (($c_end > $params['end']) ? $params['end'] : $c_end);

        if(($c_start > $c_end) || ($c_start > $params['size'] - 1) || ($c_end >= $params['size'])){
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header('Content-Range: bytes '.$params['start'].'-'.$params['end'].'/'.$params['size']);
            die();
        }

        $params['start'] = $c_start;
        $params['end']   = $c_end;

        $length = $params['end'] - $params['start'] + 1;
        fseek($this->stream, $params['start']);

        header('HTTP/1.1 206 Partial Content');
        header('Content-Length: '.$length);
        header('Content-Range: bytes '.$params['start'].'-'.$params['end'].'/'.$params['size']);

    }catch(Exception $e){
        throw new CoreException(tr('stream_video_data_headers(): Failed'), $e);
    }
}



/*
 * Stream the video file data
 *
 * @author Rana
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package stream
 * @version 2.5.92: Added function and documentation
 * @note Inspiration for this function taken from https://codesamplez.com/programming/php-html5-video-streaming-tutorial
 * @see http://codesamplez.com/programming/php-html5-video-streaming-tutorial
 *
 * @param params $params The streaming parameters array
 */
function stream_video_data($params){
    global $_CONFIG;

    try{
        array_ensure($params);
        array_default($params, 'buffer', $_CONFIG['stream']['buffer']);


        if(empty($params['resource'])){
            throw new CoreException(tr('stream_video_data_headers(): No video file resource opened. Please open one first using stream_open(), or just use stream()'), 'not-specified');
        }

        if(empty($params['file'])){
            throw new CoreException(tr('stream_video_data_headers(): No video file specified'), 'not-specified');
        }

        /*
         * Disable timelimit because we might be streaming for a while
         */
        set_time_limit(0);
        $i = $params['start'];

        while(!feof($params['resource']) && $i <= $params['end']){
            $bytesToRead = $params['buffer'];

            if(($i + $bytesToRead) > $params['end']){
                $bytesToRead = $params['end'] - $i + 1;
            }

            echo fread($params['resource'], $bytesToRead);
            flush();

            $i += $bytesToRead;
        }

    }catch(Exception $e){
        throw new CoreException(tr('stream_video_data(): Failed'), $e);
    }
}
?>
