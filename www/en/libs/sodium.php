<?php
/*
 * Sodium library
 *
 * This is the PHP Libsodium front-end library
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package sodium
 * @see https://paragonie.com/book/pecl-libsodium/read/00-intro.md
 * @see https://paragonie.com/book/pecl-libsodium/read/05-publickey-crypto.md
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
 * @package sodium
 *
 * @return void
 */
function sodium_library_init(){
    try{
        if(!defined('SODIUM_LIBRARY_MAJOR_VERSION')){
            throw new CoreException(tr('sodium_library_init(): PHP module "sodium" appears is not available, please install the module first. On Ubuntu and alikes, use "sudo apt-get -y install php-libsodium" to install and enable the module. After this, a restart of your webserver or php-fpm server may be needed'), 'not-exists');
        }

        if(!function_exists('sodium_crypto_secretbox')){
            sodium_install();
        }

    }catch(Exception $e){
        throw new CoreException('sodium_library_init(): Failed', $e);
    }
}



/*
 * Install the PHP libsodium library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sodium
 *
 * @param
 * @return
 */
function sodium_install($params){
    try{
        return safe_exec(array('commands' => array('apt-get', array('install', 'php-libsodium', 'sudo' => true))));

    }catch(Exception $e){
        throw new CoreException('sodium_install(): Failed to auto install php-libsodium', $e);
    }
}



/*
 * Returns a cryptographically secure pseudo-random bytes nonce string
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sodium
 *
 * @return a unique and safe nonce for use with sodium
 */
function sodium_nonce(){
    try{
        return random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

    }catch(Exception $e){
        throw new CoreException('sodium_nonce(): Failed', $e);
    }
}



/*
 * Returns a cryptographically secure pseudo-random bytes string
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sodium
 *
 * @param string $type The type of random string to be returned
 * @return string the random string
 */
function sodium_random($type){
    try{
        switch($type){
            case 'nonce':
                return sodium_nonce();

            case 'key':
                return random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

            case 'auth':
                return random_bytes(SODIUM_CRYPTO_AUTH_KEYBYTES);

            default:
                throw new CoreException(tr('sodium_random(: Unknown type ":type" specified', array(':type' => $type)), 'unknown');
        }

    }catch(Exception $e){
        throw new CoreException('sodium_random(): Failed', $e);
    }
}



/*
 * Encrypt the specified string with the specified key using libsodium.
 *
 * This function will return an encrypted string made from the specified source string and secret key. The encrypted string will contain the used nonce appended in front of the ciphertext with the format nonce$cipher_data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sodium
 *
 * @param string $data The text string to be encrypted
 * @param string $key The secret key to encrypt the text string with
 * @return string The encrypted ciphertext
 */
function sodium_encrypt($data, $key){
    try{
        $key         = sodium_pad_key($key);
        $nonce       = sodium_nonce();
        $cipher_data = sodium_crypto_secretbox($data, $nonce, $key);
        $cipher_data = base64_encode($cipher_data);
        $nonce       = base64_encode($nonce);

        $cipher_data = $nonce.'$'.$cipher_data;

        sodium_memzero($key);
        return $cipher_data;

    }catch(Exception $e){
        sodium_memzero($key);
        throw new CoreException('sodium_encrypt(): Failed', $e);
    }
}



/*
 * Decrypt the specified string with the specified key using libsodium.
 *
 * This function will return an decrypted string made from the specified source ciphertext string and secret key. The encrypted string must contain the used nonce appended in front of the ciphertext with the format nonce$cipher_data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sodium
 *
 * @param string $cipher_data The cipher text (containing the nonce prefixed)
 * @param string $key The secret key to decrypt the ciphertext string with
 * @return
 */
function sodium_decrypt($cipher_data, $key){
    try{
        $key   = sodium_pad_key($key);
        $nonce = str_until($cipher_data, '$');
        $nonce = base64_decode($nonce);

        if(!$nonce){
            throw new CoreException(tr('sodium_decrypt(): Specified ciphertext does not contain a nonce prefix'), 'not-exists');
        }

        $cipher_data = str_from($cipher_data, '$');
        $cipher_data = base64_decode($cipher_data);
        $data        = sodium_crypto_secretbox_open($cipher_data, $nonce, $key);

        if($data === false){
            throw new CoreException(tr('sodium_decrypt(): Specified ciphertext does not contain a nonce prefix'), 'not-exists');
        }

        sodium_memzero($key);
        return $data;

    }catch(Exception $e){
        sodium_memzero($key);
        throw new CoreException('sodium_decrypt(): Failed', $e);
    }
}



/*
 * Sign the specified string
 *
 * This function will return an encrypted string made from the specified source string and secret key. The encrypted string will contain the used nonce appended in front of the ciphertext with the format nonce$cipher_data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sodium
 *
 * @param string $data The text string to be encrypted
 * @param string $key The secret key to encrypt the text string with
 * @return string The encrypted ciphertext
 */
function sodium_sign_mac($data, $key){
    try{
        $key  = sodium_pad_key($key);
        $mac  = sodium_crypto_auth($data, $key);
        $data = $mac.'$'.$data;

        sodium_memzero($key);
        return $data;

    }catch(Exception $e){
        sodium_memzero($key);
        throw new CoreException('sodium_sign_mac(): Failed', $e);
    }
}



/*
 * Verify the signature of the specified string
 *
 * This function requires a string with a MAC signature with the format mac$cipher_data. It will return the specified string without the MAC signature if the MAC signature is valid.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sodium
 *
 * @param string $cipher_data The text with MAC signature
 * @param string $key The secret key required to verify the MAC signature
 * @return string The specified string without the MAC signature
 */
function sodium_verify_mac($data, $key){
    try{
        $key = sodium_pad_key($key);
        $mac = str_from($data, '$');

        if(!$mac){
            throw new CoreException(tr('sodium_verify_mac(): Specified string does not contain a mac prefix'), 'not-exists');
        }

        $data = str_from($data, '$');
        $data = sodium_crypto_auth_verify($mac, $data, $key);

        if($data === false){
            throw new CoreException(tr('sodium_verify_mac(): Specified text signature contains an invalid MAC'), 'invalid');
        }

        sodium_memzero($key);
        return $data;

    }catch(Exception $e){
        sodium_memzero($key);
        throw new CoreException('sodium_verify_mac(): Failed', $e);
    }
}



/*
 * Pad the specified crypto key
 *
 * .....
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sodium
 *
 * @param string $key The secret key required to verify the MAC signature
 * @param string $character The character to pad the crypto key with
 * @return string
 */
function sodium_pad_key($key, $character = '*'){
    global $_CONFIG;

    try{
        if(strlen($key) < SODIUM_CRYPTO_SECRETBOX_KEYBYTES){
            $key = $key.str_repeat($character, SODIUM_CRYPTO_SECRETBOX_KEYBYTES - strlen($key));

        }elseif(strlen($key) > SODIUM_CRYPTO_SECRETBOX_KEYBYTES){
            $key = substr($key, 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        }

        return $key;

    }catch(Exception $e){
        throw new CoreException('sodium_pad_key(): Failed', $e);
    }
}
