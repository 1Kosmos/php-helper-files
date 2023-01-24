<?php
/*
 * Copyright (c) 2018, 1Kosmos Inc. All rights reserved.
 * Licensed under 1Kosmos Open Source Public License version 1.0 (the "License");
 * You may not use this file except in compliance with the License. 
 * You may obtain a copy of this license at 
 *    https://github.com/1Kosmos/1Kosmos_License/blob/main/LICENSE.txt
 */

class WTM
{

    public static function executeRequest($method, $url, $headers, $body, $debug) {

        $debug_curl = false;
        if (isset($debug)) {
            $debug_curl = $debug;
        }
    
        $curl = curl_init();
    
        $curl_headers = array("Content-Type: application/json", "Content-Type: text/plain");
    
        if (isset($headers)) {
            foreach ($headers as $key => $value){
                array_push($curl_headers, $key . ": " . $value);
            }
        }
    
        $options = array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => $method,
          CURLOPT_HTTPHEADER => $curl_headers,
        );
        
        if (isset($body)) {
            $bodyStr = json_encode($body);
            $options[CURLOPT_POSTFIELDS] = $bodyStr;
        }
    
        curl_setopt_array($curl, $options);
        if ($debug) {
            curl_setopt($curl, CURLOPT_VERBOSE, true);
        }
        
        $response = curl_exec($curl);
        
        if ($debug) {
            $info = curl_getinfo($curl);
            error_log(print_r("curl info: " . json_encode($info), TRUE));
        }
        
        curl_close($curl);
    
        if ($debug) {
            error_log(print_r($method . " url: " . $url . " returned: " . $response, TRUE));
        }
    
        return json_decode($response, TRUE);	
    }

    public static function executeRequestV2($method, $url, $headers, $body, $debug) {

        $debug_curl = false;
        if (isset($debug)) {
            $debug_curl = $debug;
        }
    
        $curl = curl_init();
    
        $curl_headers = array("Content-Type: application/json", "Content-Type: text/plain");
    
        if (isset($headers)) {
            foreach ($headers as $key => $value){
                array_push($curl_headers, $key . ": " . $value);
            }
        }
    
        $options = array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => $method,
          CURLOPT_HTTPHEADER => $curl_headers,
        );
        
        if (isset($body)) {
            $bodyStr = json_encode($body);
            $options[CURLOPT_POSTFIELDS] = $bodyStr;
        }
    
        curl_setopt_array($curl, $options);
        if ($debug) {
            curl_setopt($curl, CURLOPT_VERBOSE, true);
        }
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if ($debug) {
            $info = curl_getinfo($curl);
            error_log(print_r("curl info: " . json_encode($info), TRUE));
        }
        
        curl_close($curl);
    
        if ($debug) {
            error_log(print_r($method . " url: " . $url . " returned: " . $response, TRUE));
        }
    
        return array(
            "response" => $response,
            "statusCode" => $http_code
        );
    }
}

?>
