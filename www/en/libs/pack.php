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
 * @package pack
 */



/*
 * Compress a path with bzip2
 *
 * This function will compressor the specified path $params[source] into the file $params[target] using bzip2
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package pack
 * @see pack_unbzip2()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @return void()
 */
function pack_bzip2($params){
    try{
        $params = pack_validate($params);
        $target = $params['source'].'.bz2';

        safe_exec(array('domain'     => $params['domain'],
                        'background' => $params['background'],
                        'commands'   => array('cd'   , array(dirname($params['source'])),
                                              'bzip2', array($params['source'], $target))));

        return $target;

    }catch(Exception $e){
        throw new BException('pack_bzip2(): Failed', $e);
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
 * @package pack
 * @see pack_bzip2()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @return void()
 */
function pack_unbzip2($params){
    try{
        $params = pack_validate($params);
        $target = str_until($params['source'], '.bz2');

        safe_exec(array('domain'     => $params['domain'],
                        'background' => $params['background'],
                        'commands'   => array('cd'     , array(dirname($params['source'])),
                                              'unbzip2', array($params['source']))));

        return $target;

    }catch(Exception $e){
        throw new BException('pack_unbzip2(): Failed', $e);
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
 * @package pack
 * @see pack_unrar()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @return void()
 */
function pack_rar($params){
    try{
        $params = pack_validate($params);
        $target = $params['source'].'.rar';

        safe_exec(array('domain'     => $params['domain'],
                        'background' => $params['background'],
                        'commands'   => array('rar', array('a', $target, $params['source']))));

        return $target;

    }catch(Exception $e){
        throw new BException('pack_rar(): Failed', $e);
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
 * @package pack
 * @see pack_rar()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @return void()
 */
function pack_unrar($params){
    try{
        $params = pack_validate($params);
        $target = str_until($params['source'], '.rar');

        safe_exec(array('domain'     => $params['domain'],
                        'background' => $params['background'],
                        'commands'   => array('unrar', array('x', $params['source']))));

        return $target;

    }catch(Exception $e){
        throw new BException('pack_unrar(): Failed', $e);
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
 * @package pack
 * @see pack_unzip()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @return void()
 */
function pack_zip($params){
    try{
        $params = pack_validate($params);
        $target = $params['source'].'zip';

        safe_exec(array('domain'     => $params['domain'],
                        'background' => $params['background'],
                        'commands'   => array('cd' , array(dirname($params['source'])),
                                              'zip', array($target, $params['source']))));

        return $target;

    }catch(Exception $e){
        throw new BException('pack_zip(): Failed', $e);
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
 * @package pack
 * @see pack_zip()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @return void()
 */
function pack_unzip($params){
    try{
        $params = pack_validate($params);
        $target = str_until($params['source'], '.zip');

        safe_exec(array('domain'     => $params['domain'],
                        'background' => $params['background'],
                        'commands'   => array('cd'   , array(dirname($params['source'])),
                                              'unzip', array($params['source']))));

        return $target;

    }catch(Exception $e){
        throw new BException('pack_ungip(): Failed', $e);
    }
}



/*
 * Compress a path with gzip
 *
 * This function will compressor the specified path $params[source] into the file $params[target] using gzip
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package pack
 * @see pack_ungzip()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @return void()
 */
function pack_gzip($params){
    try{
        $params = pack_validate($params);
        $target = $params['source'].'.gz';

        safe_exec(array('domain'     => $params['domain'],
                        'background' => $params['background'],
                        'commands'   => array('gzip', array($params['source'], $target))));

        return $target;

    }catch(Exception $e){
        throw new BException('pack_gzip(): Failed', $e);
    }
}



/*
 * Uncompressor a file with gzip
 *
 * This function will uncompressor the specified file $params[source] into the path $params[target] using gzip
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package pack
 * @see pack_gzip()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @return void()
 */
function pack_ungzip($params){
    try{
        $params = pack_validate($params);
        $target = str_until($params['source'], '.gz');

        safe_exec(array('domain'     => $params['domain'],
                        'background' => $params['background'],
                        'commands'   => array('ungzip', array($params['source']))));

        return $target;

    }catch(Exception $e){
        throw new BException('pack_ungip(): Failed', $e);
    }
}



/*
 * Compress a path with tar
 *
 * This function will compressor the specified path $params[source] into the file $params[target] using tar
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package pack
 * @see pack_untar()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @return void()
 */
function pack_tar($params){
    try{
        $params = pack_validate($params);
        $target = $params['source'].'.tgz';

        safe_exec(array('domain'     => $params['domain'],
                        'background' => $params['background'],
                        'commands'   => array('cd' , array(dirname($params['source'])),
                                              'tar', array('-cjf', $params['source'], $params['target']))));

        return $params['target'];

    }catch(Exception $e){
        throw new BException('pack_tar(): Failed', $e);
    }
}



/*
 * Uncompressor a file with tar
 *
 * This function will uncompressor the specified file $params[source] into the path $params[target] using tar
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package pack
 * @see pack_tar()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @return void()
 */
function pack_untar($params){
    try{
        $params = pack_validate($params);
        $target = str_until(str_until($params['source'], '.tar.gz'), '.tgz');

        safe_exec(array('domain'     => $params['domain'],
                        'background' => $params['background'],
                        'commands'   => array('cd' , array(dirname($params['source'])),
                                              'tar', array('-xf', $params['source']))));

        return $target;

    }catch(Exception $e){
        throw new BException('pack_ungip(): Failed', $e);
    }
}



/*
 * Validate pack parameters
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package pack
 * @see pack_tar()
 * @see safe_exec()
 * @version 2.4.24: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $params[source] The source directory to be compressored
 * @return void()
 */
function pack_validate($params){
    try{
        array_params($params, 'source');
        array_ensure($params, 'domain,background');

        return $params;

    }catch(Exception $e){
        throw new BException('pack_validate(): Failed', $e);
    }
}
