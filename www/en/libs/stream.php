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
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
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

    }catch(Exception $e){
        throw new BException(tr('stream_library_init(): Failed'), $e);
    }
}



/*
 * SUB HEADER TEXT
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package stream
 * @see stream_install()
 * @see date_convert() Used to convert the sitemap entry dates
 * @table: `template`
 * @note: This is a note
 * @version 2.5.92: Added function and documentation
 *
 * @param params $params The streaming parameters array
 */
function stream_download($params){
    try{
under_construction();
        /*
         * set appropriate headers for attachment or streamed file
         */
// :BUG: Possible bug, the Content-Disposition: attachment header is already specified in the last line, while with $stream it would be inline?
        if($stream){
            header('Content-Disposition: inline;');

        }else{
            header('Content-Disposition: attachment; filename="'.$file_name.'"');
        }

        // set the mime type based on extension, add yours if needed.
        $ctype_default = 'application/octet-stream';
        $content_types = array('exe' => 'application/octet-stream',
                               'zip' => 'application/zip',
                               'mp3' => 'audio/mpeg',
                               'mpg' => 'video/mpeg',
                               'avi' => 'video/x-msvideo');

        $ctype = isset($content_types[$file_ext]) ? $content_types[$file_ext] : $ctype_default;
        header("Content-Type: ".$ctype);

        //check if http_range is sent by browser (or download manager)
        if(isset($_SERVER['HTTP_RANGE'])){
            list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);

            if($size_unit == 'bytes'){
                /*
                 * multiple ranges could be specified at the same time, but for simplicity only serve the first range
                 * http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
                 */
                list($range, $extra_ranges) = explode(',', $range_orig, 2);

            }else{
                $range = '';
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                throw new BException(tr('file_http_download(): Unknown size_unit ":size_unit" specified, please ensure its "bytes"', array(':size_unit' => $size_unit)), 'not-exist');
            }

        }else{
            $range = '';
        }

        //figure out download piece from range (if set)
        list($seek_start, $seek_end) = explode('-', $range, 2);

        //set start and end based on range (if set), else set defaults
        //also check for invalid ranges.
        $seek_end   = (empty($seek_end)) ? ($file_size - 1) : min(abs(intval($seek_end)), ($file_size - 1));
        $seek_start = (empty($seek_start) or ($seek_end < abs(intval($seek_start)))) ? 0 : max(abs(intval($seek_start)), 0);

        //Only send partial content header if downloading a piece of the file (IE workaround)
        if($seek_start or ($seek_end < ($file_size - 1))){
            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$file_size);
            header('Content-Length: '.($seek_end - $seek_start + 1));

        }else{
            header('Content-Length: '.$file_size);
        }

        header('Accept-Ranges: bytes');

        set_time_limit(0);
        fseek($file, $seek_start);

        /*
         * Download file to client
         */
        while(!feof($file)){
            print(fread($file, 8912));
            ob_flush();
            flush();

            if(connection_status()){
                fclose($file);
                exit;
            }
        }

        /*
         * file download was a success
         */
        fclose($file);

    }catch(Exception $e){
        throw new BException(tr('stream_function(): Failed'), $e);
    }
}
?>
