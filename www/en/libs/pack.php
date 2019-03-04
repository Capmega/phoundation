<?php
/*
 * Pack library
 *
 * This library contains functions to manage compressed files like zip, bzip2, rar, etc.
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package template
 */
under_construction();



/*
 * Compress a path with bzip2
 *
 * This function will compress the specified path $params[source] into the file $params[target] using bzip2
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package compress
 * @see compress_unbzip2()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressed
 * @param string $params[target] The compressed target file
 * @return void()
 */
function compress_bzip2($params){
    try{
        compress_validate($params);
        safe_exec(array('commands' => array('bzip2', array($source['source'], $source['target']))));

    }catch(Exception $e){
        throw new BException('compress_bzip2(): Failed', $e);
    }
}



/*
 * Uncompress a file with bzip2
 *
 * This function will uncompress the specified file $params[source] into the path $params[target] using bzip2
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package compress
 * @see compress_bzip2()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressed
 * @param string $params[target] The compressed target file
 * @return void()
 */
function compress_unbzip2($params){
    try{
        compress_validate($params);
        safe_exec(array('commands' => array('unbzip2', array($source['source'], $source['target']))));

    }catch(Exception $e){
        throw new BException('compress_unbzip2(): Failed', $e);
    }
}



/*
 * Compress a path with rar
 *
 * This function will compress the specified path $params[source] into the file $params[target] using rar
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package compress
 * @see compress_unrar()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressed
 * @param string $params[target] The compressed target file
 * @return void()
 */
function compress_rar($params){
    try{
        compress_validate($params);

        safe_exec(array('domain'     => $domain['domain'],
                        'background' => $domain['background'],
                        'commands'   => array('rar', array('a', $source['source'], $source['target']))));

    }catch(Exception $e){
        throw new BException('compress_rar(): Failed', $e);
    }
}



/*
 * Uncompress a file with rar
 *
 * This function will uncompress the specified file $params[source] into the path $params[target] using rar
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package compress
 * @see compress_rar()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressed
 * @param string $params[target] The compressed target file
 * @return void()
 */
function compress_unrar($params){
    try{
        compress_validate($params);
        safe_exec(array('commands' => array('unrar', array('x', $source['source'], $source['target']))));

    }catch(Exception $e){
        throw new BException('compress_unrar(): Failed', $e);
    }
}



/*
 * Compress a path with zip
 *
 * This function will compress the specified path $params[source] into the file $params[target] using zip
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package compress
 * @see compress_unzip()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressed
 * @param string $params[target] The compressed target file
 * @return void()
 */
function compress_zip($params){
    try{
        compress_validate($params);
        safe_exec(array('commands' => array('zip', array($source['source'], $source['target']))));

        return $source['source'].'.gz';

    }catch(Exception $e){
        throw new BException('compress_zip(): Failed', $e);
    }
}



/*
 * Uncompress a file with zip
 *
 * This function will uncompress the specified file $params[source] into the path $params[target] using zip
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package compress
 * @see compress_zip()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressed
 * @param string $params[target] The compressed target file
 * @return void()
 */
function compress_unzip($params){
    try{
        compress_validate($params);
        safe_exec(array('commands' => array('unzip', array($source['source']))));

        return substr($source['source'], 0, -3);

    }catch(Exception $e){
        throw new BException('compress_ungip(): Failed', $e);
    }
}



/*
 * Validate the specified compression parameters
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package compress
 * @version 2.4.24: Added function and documentation
 * @see file_restrict_path()
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressed
 * @param string $params[target] The compressed target file
 * @return void()
 */
function compress_validate(&$params){
    try{
        array_ensure($params);

        if(empty($params['source'])){
            throw new BException(tr('compress_validate(): No source specified'), 'not-specified');
        }

        if(empty($params['target'])){
            throw new BException(tr('compress_validate(): No target specified'), 'not-specified');
        }

        if(!file_exists($params['source'])){
            throw new BException(tr('compress_validate(): Specified source ":source" does not exist', array(':source' => $params['source'])), 'not-exists');
        }

        if(!file_exists($params['source'])){
            throw new BException(tr('compress_validate(): Specified target ":target" already exist', array(':target' => $params['target'])), 'exists');
        }

        /*
         * Ensure that the specified source and target are not outside of the
         * restricted paths
         */
        file_restrict_path($params);

    }catch(Exception $e){
        throw new BException('compress_validate(): Failed', $e);
    }
}
?>