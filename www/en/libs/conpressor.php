<?php
/*
 * Pack library
 *
 * This library contains functions to manage compressored files like zip, bzip2, rar, etc.
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
 * This function will compressor the specified path $params[source] into the file $params[target] using bzip2
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package compressor
 * @see compressor_unbzip2()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @param string $params[target] The compressored target file
 * @return void()
 */
function compressor_bzip2($params){
    try{
        compressor_validate($params);
        safe_exec(array('commands' => array('bzip2', array($source['source'], $source['target']))));

    }catch(Exception $e){
        throw new BException('compressor_bzip2(): Failed', $e);
    }
}



/*
 * Uncompressor a file with bzip2
 *
 * This function will uncompressor the specified file $params[source] into the path $params[target] using bzip2
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package compressor
 * @see compressor_bzip2()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @param string $params[target] The compressored target file
 * @return void()
 */
function compressor_unbzip2($params){
    try{
        compressor_validate($params);
        safe_exec(array('commands' => array('unbzip2', array($source['source'], $source['target']))));

    }catch(Exception $e){
        throw new BException('compressor_unbzip2(): Failed', $e);
    }
}



/*
 * Compress a path with rar
 *
 * This function will compressor the specified path $params[source] into the file $params[target] using rar
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package compressor
 * @see compressor_unrar()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @param string $params[target] The compressored target file
 * @return void()
 */
function compressor_rar($params){
    try{
        compressor_validate($params);

        safe_exec(array('domain'     => $domain['domain'],
                        'background' => $domain['background'],
                        'commands'   => array('rar', array('a', $source['source'], $source['target']))));

    }catch(Exception $e){
        throw new BException('compressor_rar(): Failed', $e);
    }
}



/*
 * Uncompressor a file with rar
 *
 * This function will uncompressor the specified file $params[source] into the path $params[target] using rar
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package compressor
 * @see compressor_rar()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @param string $params[target] The compressored target file
 * @return void()
 */
function compressor_unrar($params){
    try{
        compressor_validate($params);
        safe_exec(array('commands' => array('unrar', array('x', $source['source'], $source['target']))));

    }catch(Exception $e){
        throw new BException('compressor_unrar(): Failed', $e);
    }
}



/*
 * Compress a path with zip
 *
 * This function will compressor the specified path $params[source] into the file $params[target] using zip
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package compressor
 * @see compressor_unzip()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @param string $params[target] The compressored target file
 * @return void()
 */
function compressor_zip($params){
    try{
        compressor_validate($params);
        safe_exec(array('commands' => array('zip', array($source['source'], $source['target']))));

        return $source['source'].'.gz';

    }catch(Exception $e){
        throw new BException('compressor_zip(): Failed', $e);
    }
}



/*
 * Uncompressor a file with zip
 *
 * This function will uncompressor the specified file $params[source] into the path $params[target] using zip
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package compressor
 * @see compressor_zip()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @param string $params[target] The compressored target file
 * @return void()
 */
function compressor_unzip($params){
    try{
        compressor_validate($params);
        safe_exec(array('commands' => array('unzip', array($source['source']))));

        return substr($source['source'], 0, -3);

    }catch(Exception $e){
        throw new BException('compressor_ungip(): Failed', $e);
    }
}



/*
 * Validate the specified compressorion parameters
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package compressor
 * @version 2.4.24: Added function and documentation
 * @see file_restrict_path()
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @param string $params[target] The compressored target file
 * @return void()
 */
function compressor_validate(&$params){
    try{
        array_ensure($params);

        if(empty($params['source'])){
            throw new BException(tr('compressor_validate(): No source specified'), 'not-specified');
        }

        if(empty($params['target'])){
            throw new BException(tr('compressor_validate(): No target specified'), 'not-specified');
        }

        if(!file_exists($params['source'])){
            throw new BException(tr('compressor_validate(): Specified source ":source" does not exist', array(':source' => $params['source'])), 'not-exists');
        }

        if(!file_exists($params['source'])){
            throw new BException(tr('compressor_validate(): Specified target ":target" already exist', array(':target' => $params['target'])), 'exists');
        }

        /*
         * Ensure that the specified source and target are not outside of the
         * restricted paths
         */
        file_restrict_path($params);

    }catch(Exception $e){
        throw new BException('compressor_validate(): Failed', $e);
    }
}
?>