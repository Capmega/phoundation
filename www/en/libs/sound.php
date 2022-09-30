<?php
/*
 * Sound library
 *
 * This library contains sound functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */


/*
 * Play the specified audio file
 */
function sound_play($file) {
    try {
        shell_exec('sudo nohup /usr/bin/aplay '.$file.' 2>/dev/null >/dev/null &');

    }catch(Exception $e) {
        throw new CoreException('sound_play(): Failed', $e);
    }
}
?>
