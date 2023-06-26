<?php
/*
 * Copyright (c) 2018, 1Kosmos Inc. All rights reserved.
 * Licensed under 1Kosmos Open Source Public License version 1.0 (the "License");
 * You may not use this file except in compliance with the License. 
 * You may obtain a copy of this license at 
 *    https://github.com/1Kosmos/1Kosmos_License/blob/main/LICENSE.txt
 */

require_once("./BIDTenant.php");
require_once("./BIDECDSA.php");
require_once("./BIDUsers.php");
require_once("./WTM.php");
require_once("./InMemCache.php");

class BIDOauth2
{
    public static function requestAuthorizationCode($tenantInfo, $proofOfAuthenticationJwt, $clientId, $responseType, $scope, $redirectUri, $stateOrNull, $nonceOrNull)
    {
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);
        $sd             = $bidTenant->getSD($tenantInfo);

        $headers = array(
            "Content-Type: application/x-www-form-urlencoded",
            "charset: utf-8", "Accept: application/json",
            "Connection: Keep-Alive"
        );

        $req = array(
            "client_id" => $clientId,
            "proof_of_authentication_jwt" => $proofOfAuthenticationJwt,
            "response_type" => $responseType,
            "scope" => $scope,
            "redirect_uri" => $redirectUri
        );

        if ($stateOrNull !== null) {
            $req["state"] = $stateOrNull;
        }

        if ($nonceOrNull !== null) {
            $req["nonce"] = $nonceOrNull;
        }

        $curl = curl_init();

        $options = array(
            CURLOPT_URL => $sd["oauth2"] . "/community/" . $communityInfo["community"]["name"] . "/v1/authorize",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true
        );

        $options[CURLOPT_HEADERFUNCTION] = function ($url, $header) use (&$location) {
            if (strpos($header, "Location: ") === 0) {
                $location = trim(substr($header, 10));
            }
            return strlen($header);
        };

        curl_setopt_array($curl, $options);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($req));

        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        $ret = json_decode($response, TRUE);
        $ret["statusCode"] = $status;

        if ($status !== 200 && $status !== 303) {
            return $ret;
        }

        $locationUrl = parse_url($location, PHP_URL_QUERY);
        parse_str($locationUrl, $queryData);

        if ($queryData["error"] !== null) {
            $ret["statusCode"] = 400;
            $ret["message"] = $queryData["error_description"];
            return $ret;
        }

        $ret["url"] = $location;
        return $ret;
    }

    public static function requestToken($tenantInfo, $clientId, $clientSecret, $grantType, $redirectUri, $codeOrNull, $refreshTokenOrNull)
    {
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);
        $sd             = $bidTenant->getSD($tenantInfo);

        $headers = array(
            "Content-Type: application/x-www-form-urlencoded",
            "charset: utf-8", "Accept: application/json",
            "Connection: Keep-Alive"
        );

        $req = array(
            "grant_type" => $grantType,
            "redirect_uri" => $redirectUri
        );

        if ($codeOrNull !== null) {
            $req["code"] = $codeOrNull;
        }

        if ($refreshTokenOrNull !== null) {
            $req["refresh_token"] = $refreshTokenOrNull;
        }

        $curl = curl_init();

        $options = array(
            CURLOPT_URL => $sd["oauth2"] . "/community/" . $communityInfo["community"]["name"] . "/v1/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_USERPWD => "$clientId:$clientSecret"
        );

        curl_setopt_array($curl, $options);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($req));

        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        $ret = json_decode($response, TRUE);
        $ret["statusCode"] = $status;

        return $ret;
    }
}
