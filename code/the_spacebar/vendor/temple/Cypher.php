<?php
namespace temple;

/**
 * class for encrypting
 * encrypting methode my_encrypt() & my_decrypt by :
 * https://bhoover.com/using-php-openssl_encrypt-openssl_decrypt-encrypt-decrypt-data/
 * $encryption_key_256bit = base64_encode(openssl_random_pseudo_bytes(32))
 */
class Cypher
{
    static public function my_encrypt($data, $format = 'aes-256-cbc') {
        // Remove the base64 encoding from our key
        $encryption_key = base64_decode(\CRYPT_KEY);
        // Generate an initialization vector
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        // Encrypt the data using AES 256 encryption in CBC mode using our encryption key and initialization vector.
        $encrypted = openssl_encrypt($data, $format, $encryption_key, 0, $iv);
        // The $iv is just as important as the key for decrypting, so save it with our encrypted data using a unique separator (::)
        return base64_encode($encrypted . '::' . $iv);
    }

    static public function my_decrypt($data, $format = 'aes-256-cbc') {
        // Remove the base64 encoding from our key
        $encryption_key = base64_decode(\CRYPT_KEY);
        // To decrypt, split the encrypted data from our IV - our unique separator used was "::"
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return @openssl_decrypt($encrypted_data, $format, $encryption_key, 0, $iv);
    }    
}   
