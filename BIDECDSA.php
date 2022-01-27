/**
 * Copyright (c) 2018, 1Kosmos Inc. All rights reserved.
 * Licensed under 1Kosmos Open Source Public License version 1.0 (the "License");
 * You may not use this file except in compliance with the License. 
 * You may obtain a copy of this license at 
 *    https://github.com/1Kosmos/1Kosmos_License/blob/main/LICENSE.txt
 */
 
<?php
require_once('./vendor/autoload.php');
use Elliptic\EC;

class BIDECDSA
{

    public static function createSharedKey($pr64, $pk64) {
        $prKey = base64_decode($pr64);
        $prHex = bin2hex($prKey);
        $shared1_base64 = "";

        try {
            $ec                         = new EC('secp256k1');
            $keypair					= $ec->keyFromPrivate($prHex, "hex");

            $pKey = base64_decode($pk64);
            $pHex = "04" . bin2hex($pKey);
            $keypair_other 				= $ec->keyFromPublic($pHex, 'hex');
    
    
            $shared1 				= $keypair->derive($keypair_other->getPublic());
            $shared1_base64			= base64_encode(hex2bin($shared1->toString(16)));	

            return $shared1_base64;
        }
        catch (Exception $ex) {
            echo "\nCaught exception: ",  $ex->getMessage();
            echo "\nCurrent PHP version: " . phpversion();
            return "";
        }
        
        
    }

    public static function generateKeyPair() {
        $ec                         = new EC('secp256k1');
        $keypair					= $ec->genKeyPair();

        //generate public key
        $pub_hex 					= $keypair->getPublic("hex");
        $pub_hex 					= substr($pub_hex, 2, strlen($pub_hex));
        $pubenc   					= hex2bin($pub_hex);
        $my_public_key 				= base64_encode($pubenc);


        $priv_hex 					= $keypair->getPrivate("hex");
        //don't think private key needs to loose the two bytes. might need this in case we generate keys using mnemonic phrases
        // $priv_hex 					= substr($priv_hex, 2, strlen($priv_hex)); 
        $privenc   					= hex2bin($priv_hex);
        $my_private_key 			= base64_encode($privenc);


        $ret = array(
            "publicKey" => $my_public_key,
            "privateKey" => $my_private_key
        );

        return $ret;
    }

    public static function testKeyPair($pr64) {
        // $pr64 = "TF6Z+A6CDD/EKCxytqwgVL19Ny42CXAnoJMHDQFZyDs=";
        // $pr64 = "sOz2fEPf+ps34dKsqujZyuawVT4yEJerLbFl6UFsvA==";

        $prKey = base64_decode($pr64);
        $prHex = bin2hex($prKey);

        $ec                         = new EC('secp256k1');
        $keypair					= $ec->keyFromPrivate($prHex, "hex");

        //generate public key
        $pub_hex 					= $keypair->getPublic("hex");
        $pub_hex 					= substr($pub_hex, 2, strlen($pub_hex));
        $pubenc   					= hex2bin($pub_hex);
        $my_public_key 				= base64_encode($pubenc);


        $ret = array(
            "publicKey" => $my_public_key,
            "privateKey" => $pr64
        );

        return $ret;
    }


    public static function encrypt($str, $key64) {

        $key = base64_decode($key64);
        $cipher = 'aes-256-gcm';
        $iv_len = 16;
        $tag_length = 16;

        $iv = openssl_random_pseudo_bytes($iv_len);
        $tag = ""; 
        $ciphertext = openssl_encrypt($str, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag, "", $tag_length);
        $encrypted = base64_encode($iv . $ciphertext . $tag);    

        return $encrypted;
    }

    public static function decrypt($str, $key64) {
        
        $key = base64_decode($key64);
        $cipher = 'aes-256-gcm';
        $iv_len = 16;
        $tag_length = 16;

        $encrypted = base64_decode($str);    
        $iv = substr($encrypted, 0, $iv_len);
        $tag = substr($encrypted, strlen($encrypted) - $tag_length, $tag_length);
    
        $ciphertext = substr($encrypted, $iv_len, strlen($encrypted) - ($iv_len + $tag_length));
        
    
        $decrypted = openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);    
    
        return $decrypted;
    

    }
}

?>