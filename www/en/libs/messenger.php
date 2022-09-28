<?php
/*
 * Messengers library
 *
 * This library contains various functions to interface with messengers like
 *
 * irc
 * google hangouts
 * jabber
 * matrix
 * whatsapp
 * signal
 * slack
 * skype
 * telegram
 * twitter
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <license@capmega.com>
 * @category Function reference
 * @package messenger
 */



/*
 * Send a message over the specified messenger platform
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package messenger
 * @version 2.5.12: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $platform The platform over which the message to send
 * @param string $message
 * @return string The result
 */
function messenger_send($platform, $message){
    try{

    }catch(Exception $e){
        throw new CoreException('messenger_send(): Failed', $e);
    }
}



/*
 * Returns an array with a list of supported platforms and for each platform a boolean with true if they are enabled, false if they are not
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package messenger
 * @version 2.5.12: Added function and documentation
 *
 * @return string An array containing the supported platforms, and if they are enabled
 */
function messenger_list_supported(){
    try{

    }catch(Exception $e){
        throw new CoreException('messenger_list_supported(): Failed', $e);
    }
}



/*
 * Show icon for the specified name / messenger
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package messenger
 * @see http://www.skype.com/go/skypebuttons
 * @version 2.5.12: Added function and documentation
 *
 * @param string $platform
 * @param string $size
 * @return string A url to the requested messenger icon
 */
function messenger_icon($platform, $size){
    try{

    }catch(Exception $e){
        throw new CoreException('messenger_icon(): Failed', $e);
    }
}
?>
