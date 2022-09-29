<?php
/*
 * mbox library
 *
 * This library contains functions to manage mbox type email systems
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the mbox library configuration
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mbox
 *
 * @return void
 */
function mbox_library_init() {
    try{
        load_config('mbox');

    }catch(Exception $e) {
        throw new CoreException('mbox_library_init(): Failed', $e);
    }
}



/*
 * Import an mbox file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mbox
 *
 * @param
 * @return
 */
function mbox_import_file($domain, $user, $file, $box = 'Archives', $mail_path = '') {
    try{
        $path  = mbox_test_access($mail_path);
        $path .= $path.'vhosts/'.$domain.'/'.$user.'/mail/';

        file_ensure_path($path);

        if (file_exists($path.$box)) {
            /*
             * We need to concat these files together
             */
            safe_exec(array('commands' => array('cat', array($file, $path.$box, 'redirect' => ' > '.$path.$box.'~'))));
            file_delete($path.$box, false);
            rename($path.$box.'~ ', $path.$box);

        } else {
            /*
             * Just drop the file in place
             */
            rename($file, $path.$box);
        }

    }catch(Exception $e) {
        throw new CoreException('mbox_import_file(): Failed', $e);
    }
}



/*
 * Convert a maildir path to an mbox file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mbox
 *
 * @param string $path
 * @return string The converted mbox file
 */
function mbox_convert_maildir($maildir_path, $box, $mail_path) {
    try{
        $path  = mbox_test_access($mail_path);
        $path .= $path.'vhosts/'.$domain.'/'.$user.'/mail/';
        safe_exec(array('commands' => array(ROOT.'scripts/md2mb.py', array($path))));

    }catch(Exception $e) {
        throw new CoreException('mbox_convert_maildir(): Failed', $e);
    }
}



/*
 * Tests access to the configured or specified mail directory
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mbox
 *
 * @param string $path
 * @return string The converted mbox file
 */
function mbox_test_access($path) {
    global $_CONFIG;

    try{
        if (!$path) {
            $path = $_CONFIG['mbox']['path'];
        }

        $path = Strings::slash($path);

        if (!file_exists($path)) {
            throw new CoreException(tr('mbox_test_access(): The configured (or specified) mail directory ":path" does not exist. Please check the configuration option $_CONFIG[mbox][path]', array(':path' => $path)), 'not-exists');
        }

        if (file_exists($path.'base-test')) {
            file_delete($path.'base-test', false);
        }

        touch($path.'base-test');
        file_delete($path.'base-test', false);

        return $path;

    }catch(Exception $e) {
        throw new CoreException('mbox_test_access(): Failed', $e);
    }
}
?>
