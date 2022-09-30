<?php
/*
 * Audio Library
 *
 * This library contains functions to play audio on the command line
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package audio
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the audio library configuration
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package audio
 *
 * @return void
 */
function audio_library_init() {
    try {
        load_config('audio');

    }catch(Exception $e) {
        throw new CoreException('audio_library_init(): Failed', $e);
    }
}



/*
 * Play the specified audio file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package audio
 * @CoreException not-exists Thrown when the specified audio class does not exist
 *
 * @param string $class
 * @return boolean True if the audio file was played, false if the audio file was not played
 */
function audio_play($class = null) {
    global $_CONFIG;

    try {
        if ($_CONFIG['audio']['quiet']) {
            /*
             * We're running quiet mode, do not play any audio!
             */
            return false;
        }

        /*
         * Check if there is no given class
         */
        if (!$class) {
            $class = $_CONFIG['audio']['default'];
        }

        /*
         * Check if given class is in CONFIG[audio]
         */
        if (empty($_CONFIG['audio']['classes'][$class])) {
            throw new CoreException(tr('audio_play(): This audio class does not exist ":class"', array(':class' => $class)), 'not-exists');
        }

        $file = ROOT.'data/audio/'.$_CONFIG['audio']['classes'][$class];

        /*
         * Check if audio file exists
         */
        if (!file_exists($file)) {
            throw new CoreException(tr('audio_play(): This audio file does not exist ":file"', array(':file' => $file)), 'audio');
        }

        /*
         * Detect if the audio is gonna be played local or remote
         */
        if (!getenv('SSH_CLIENT')) {
            /*
             * Play the audio local
             */
            try {
                log_console(tr('Playing audio file ":file"', array(':file' => $file)), 'cyan');
                safe_exec(array('background' => true,
                                'commands'   => array($_CONFIG['audio']['command'], array($file))));

            }catch(Exception $e) {
                throw new CoreException(tr('audio_play(): Can not play audio file ":file", commando ":command" returned error: ":error"', array(':file' => $file, ':command' => $_CONFIG['audio']['command'], ':error' => $e)), 'audio');
            }

        } else {
// :INVESTIGATE: To do later how to do this easy without losing connection
//            /*
//             * Play the audio on remote server
//             */
///*
// *  Need 3 things for connecting again:
// *  PORT
// *  IP (Server Name)
// *  Log user name
// */
//            $log_user_name = getenv('LOGNAME');
//            $port = Strings::fromReverse('', '')
//
//            safe_exec('exit', $exitcode);
//
//            passthru('ssh -p port -t -R 24713:localhost:4713 user_name@server_name', $exitcode);
//
//            safe_exec('export PULSE_SERVER="tcp:localhost:24713"', $exitcode);
//
//            safe_exec($_CONFIG['audio']['command'].' '.$file, $exitcode);

        }

        return true;

    }catch(Exception $e) {
        throw new CoreException('audio_play(): Failed', $e);
    }
}
?>
