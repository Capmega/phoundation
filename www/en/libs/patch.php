<?php
/*
 * Patch library
 *
 * This library contains functions to assist the phoundation patch script
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Find the location of the phoundation project and return it
 */
function patch_get_phoundation_location(){
    static $path;

    try{
        if($path){
            return $path;
        }

        $path = ROOT;

        while($path = dirname($path)){
            $path = slash($path);

            if(file_exists($path.'phoundation')){
                if(file_exists($path.'phoundation/config/base/default.php')){
                    /*
                     * Found the phoundation default configuration file, we're good
                     */
                    $path .= 'phoundation/';

                    log_console(tr('Using phoundation location ":path"', array(':path' => $path)), 'VERBOSE');
                    return $path;
                }
            }

            if($path == '/'){
                throw new bException(tr('patch_get_phoundation_location(): Failed to find "phoundation" project from ":path" up', array(':path' => ROOT)), 'not-exist');
            }
        }

    }catch(Exception $e){
        $path = null;
        throw new bException('patch_get_phoundation_location(): Failed', $e);
    }
}



/*
 * Find the location of the toolkit project and return it
 */
function patch_get_toolkit_location(){
    static $path;

    try{
        if($path){
            return $path;
        }

        $path = ROOT;

        while($path = dirname($path)){
            $path = slash($path);

            if(file_exists($path.'capmega')){
                if(!file_exists($path.'capmega/toolkit.capmega.com')){
                    /*
                     * Found the capmega dir, but not the toolkit project
                     */
                    throw new bException(tr('patch_get_toolkit_location(): Found the capmega company path in ":path", but toolkit.capmega.com project isn\'t available', array(':path' => $path)), 'not-exist');
                }

                if(!file_exists($path.'capmega/toolkit.capmega.com/config/base/default.php')){
                    /*
                     * Found the phoundation default configuration file, we're good
                     */
                    throw new bException(tr('patch_get_toolkit_location(): Found the toolkit.capmega.com path in ":path", but its default phoundation configuration file does not exist', array(':path' => $path)), 'not-exist');
                }

                $path .= 'capmega/toolkit.capmega.com/';

                log_console(tr('Using toolkit location ":path"', array(':path' => $path)), 'VERBOSE');
                return $path;
            }

            if($path == '/'){
                throw new bException(tr('patch_get_toolkit_location(): Failed to find "phoundation" project from ":path" up', array(':path' => ROOT)), 'not-exist');
            }
        }

    }catch(Exception $e){
        $path = null;
        throw new bException('patch_get_toolkit_location(): Failed', $e);
    }
}



/*
 * Check if the specified file exists in phoundation
 */
function patch_file_exists_in_phoundation($file){
    try{
        $path = patch_get_phoundation_location();

        return file_exists($path.$file);

    }catch(Exception $e){
        throw new bException('patch_file_exists_in_phoundation(): Failed', $e);
    }
}



/*
 * Check if the specified file exists in base
 */
function patch_file_exists_in_toolkit($file){
    try{
        $path = patch_get_toolkit_location();

        return file_exists($path.$file);


    }catch(Exception $e){
        throw new bException('patch_file_exists_in_toolkit(): Failed', $e);
    }
}



/*
 * Perform a diff between the specified file and its version in base
 */
function patch_file_diff_with_base($file){
    try{
        $path = patch_get_toolkit_location();

        return safe_exec('diff '.$file.' '.$path.$file);

    }catch(Exception $e){
        throw new bException('patch_file_diff_with_base(): Failed', $e);
    }
}



/*
 * Perform a diff between the specified file and its version in toolkit
 */
function patch_file_diff_with_toolkit($file){
    try{
        $path = patch_get_toolkit_location();

        return safe_exec('diff '.$file.' '.$path.$file);


    }catch(Exception $e){
        throw new bException('patch_file_diff_with_toolkit(): Failed', $e);
    }
}



/*
 * Get diff for the specified file and try to apply it in base or toolkit version
 */
function patch($file, $path, $method = 'apply', $replaces = null){
    try{
        switch($method){
            case 'diff':
                log_console(tr('Showing diff patch for file ":file"', array(':file' => $file)), 'white');
                echo git_diff($file, !NOCOLOR);
                break;

            case 'create':
                // FALLTHROUGH
            case 'apply':
                // FALLTHROUGH
            case 'patch':
                $patch      = git_diff($file);
                $patch_file = $path.sha1($file).'.patch';

                if(empty($patch)){
                    throw new bException(tr('patch(): git_diff() returned empty patch data for file ":file"', array(':file' => $file)), 'empty');
                }

                if($replaces){
                    /*
                     * Perform a search / replace on the patch data
                     */
                    foreach($replaces as $search => $replace){
                        $patch = str_replace($search, $replace, $patch);
                    }
                }

                file_put_contents($patch_file, $patch);

                if($method == 'create'){
                    /*
                     * Don't actually apply the patch
                     */

                }else{
                    git_apply($patch_file);
                    file_delete($patch_file);
                }

                break;

            default:
                throw new bException(tr('patch(): Unknown method ":method" specified', array(':method' => $method)), 'unknown');
        }

    }catch(Exception $e){
        throw new bException(tr('patch(): Failed for file ":file"', array(':file' => $file)), $e);
    }
}
?>
