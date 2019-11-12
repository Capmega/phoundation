<?php
/*
 * Crypt library
 *
 * This lirary contains easy to use encrypt / decrypt functions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package crypt
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
 * @package crypt
 *
 * @return void
 */
function crypt_library_init(){
    global $core;

    try{
        load_config('crypt');
        load_libs('sodium');

    }catch(Exception $e){
        throw new BException('crypt_library_init(): Failed', $e);
    }
}



/*
 * Encrypt the specified string using the specified key.
 *
 * This function will return an encrypted string made from the specified source string and secret key. The encrypted string will contain the used nonce appended in front of the ciphertext with the format library^nonce$ciphertext
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package crypt
 * @see decrypt
 * @see sodium_encrypt()
 * @note JSON encodes the data before encrypting it
 * @note Uses the backend configured in $_CONFIG[crypt][backend]
 *
 * @param string $string The text string to be encrypted
 * @param string $key The secret key to encrypt the text string with
 * @return string The data json encoded and encrypted with the specified key using the configured backend
 */
function encrypt($data, $key, $method = null){
    global $core;

    try{
        switch($_CONFIG['crypt']['backend']){
            case 'sodium':
                $key  = crypt_pad_key($key);
                $data = json_encode_custom($data);
                $data = 'sodium^'.sodium_encrypt($data, $key);
                break;

            default:
                throw new BException(tr('encrypt(): Unknown backend ":backend" specified $_CONFIG[crypt][backend]', array(':backend' => $_CONFIG['crypt']['backend'])), 'unknown');
        }

        return $data;

    }catch(Exception $e){
        throw new BException('encrypt(): Failed', $e);
    }
}



/*
 * Decrypt the specified string using the specified key.
 *
 * This function will return an encrypted string made from the specified source string and secret key. The encrypted string will contain the used nonce appended in front of the ciphertext with the format library^nonce$ciphertext
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package crypt
 * @see encrypt
 * @see sodium_decrypt()
 * @note JSON decodes the data after decrypting it
 * @note Requires that the encrypted string is of the format BACKEND^DATA where BACKEND is the backend used for encryption, and DATA is the actual encrypted data. BACKEND can currently be only "sodium" for php-libsodium
 *
 * @param string $string The text string to be encrypted
 * @param string $key The secret key to encrypt the text string with
 * @return string The encrypted ciphertext
 */
function decrypt($data, $key, $method = null){
    global $core;

    try{
        if($data === false){
            throw new BException(tr('decrypt(): base64_decode() asppears to have failed to decode data, probably invalid base64 string'), 'invalid');
        }

        $key     = crypt_pad_key($key);
        $backend = str_until($data, '^');
        $data    = str_from ($data, '^');

        switch($core->register('crypt_backend')){
            case 'sodium':
                $data = sodium_decrypt($data, $key);
                break;

            case '':
                throw new BException(tr('decrypt(): Data has no backend specified'), 'invalid');

            default:
                throw new BException(tr('decrypt(): Unknown backend ":backend" specified by data', array(':backend' => $backend)), 'unknown');
        }

        $data = trim($data);
        $data = json_decode_custom($data);

        return $data;

    }catch(Exception $e){
        throw new BException('decrypt(): Failed', $e);
    }
}



/*
 * Pad the specified crypto key
 *
 * .....
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package crypt
 *
 * @param string $key The secret key required to verify the MAC signature
 * @param string $character The character to pad the crypto key with
 * @return string
 */
function crypt_pad_key($key, $character = '*'){
    global $_CONFIG;

    try{
        if($_CONFIG['crypt']['min_key_size'] and (strlen($key) < $_CONFIG['crypt']['min_key_size'])){
            $key = $key.str_repeat($character, $_CONFIG['crypt']['min_key_size'] - strlen($key));
        }

        return $key;

    }catch(Exception $e){
        throw new BException('crypt_pad_key(): Failed', $e);
    }
}
