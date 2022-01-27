/**
 * Copyright (c) 2018, 1Kosmos Inc. All rights reserved.
 * Licensed under 1Kosmos Open Source Public License version 1.0 (the "License");
 * You may not use this file except in compliance with the License. 
 * You may obtain a copy of this license at 
 *    https://github.com/1Kosmos/1Kosmos_License/blob/main/LICENSE.txt
 */
 <?php
require_once("./BIDSDK.php");
require_once("./BIDECDSA.php");
require_once("./BIDUsers.php");
require_once("./WTM.php");
require_once("./InMemCache.php");

class BIDSession
{

    private static $ttl = 60;

    private static function getSessionPublicKey() {
        $bidsdk         = BIDSDK::getInstance();
        $communityInfo  = $bidsdk->getCommunityInfo();
        
        $keySet         = $bidsdk->getKeySet();
        $licenseKey     = $bidsdk->getLicense();
        $sd             = $bidsdk->getSD();


        $url = $sd["sessions"] . "/publickeys";

        $response = null;
        $responseStr = InMemCache::get($url);
        if (isset($responseStr)) {
            $response = json_decode($responseStr, TRUE);
        }

        if (!isset($responseStr)) {//no cache available.. fetch again
            $response = WTM::executeRequest("GET"
                        , $url
                        , array ("Content-Type" => "application/json","charset" => "utf-8")
                        , null
                        , false);

            InMemCache::set(json_encode($response), $url, self::$ttl);
        }

        return $response["publicKey"];

    }

    public static function createNewSession($authType, $scopes) {
        $bidsdk         = BIDSDK::getInstance();
        $communityInfo  = $bidsdk->getCommunityInfo();
        
        $keySet         = $bidsdk->getKeySet();
        $licenseKey     = $bidsdk->getLicense();
        $sd             = $bidsdk->getSD();
        $sessionsPublicKey = self::getSessionPublicKey();

        $sharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $sessionsPublicKey);

        $body = array(
            "origin" => array(
                "tag" => $communityInfo["tenant"]["tenanttag"],
                "url" => $sd["adminconsole"],
                "communityName" => $communityInfo["community"]["name"],
                "communityId" => $communityInfo["community"]["id"],
                "authPage" => "blockid://authenticate"
            ),
            "scopes" => (isset($scopes) ? scopes : ""),
            "authtype" => (isset($authType) ? authType : "none")
        );


        
        $requestid = array(
            "appId" => "blockid.php.sdk",
            "uuid" => uniqid(),
            "ts" => time()
        );
        
        $headers = array (
            "Content-Type" => "application/json",
            "charset" => "utf-8",
            "publickey" => $keySet["publicKey"],
            "licensekey" => BIDECDSA::encrypt($licenseKey, $sharedKey),
            "requestid" => BIDECDSA::encrypt(json_encode($requestid), $sharedKey)
        );

        $ret = WTM::executeRequest("PUT"
                            , $sd["sessions"] . "/session/new"
                            , $headers
                            , $body
                            , false);

        if (isset($ret) && isset($ret["sessionId"])) {
            $ret["url"] = $sd["sessions"];
        }
        return $ret;

    }
    
    public static function pollSession($sessionId, $fetchProfile, $fetchDevices) {
        $bidsdk         = BIDSDK::getInstance();
        $communityInfo  = $bidsdk->getCommunityInfo();
        
        $keySet         = $bidsdk->getKeySet();
        $licenseKey     = $bidsdk->getLicense();
        $sd             = $bidsdk->getSD();
        $sessionsPublicKey = self::getSessionPublicKey();

        $sharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $sessionsPublicKey);

        
        $requestid = array(
            "appId" => "blockid.php.sdk",
            "uuid" => uniqid(),
            "ts" => time()
        );
        
        $headers = array (
            "Content-Type" => "application/json",
            "charset" => "utf-8",
            "publickey" => $keySet["publicKey"],
            "licensekey" => BIDECDSA::encrypt($licenseKey, $sharedKey),
            "requestid" => BIDECDSA::encrypt(json_encode($requestid), $sharedKey)
        );

        $response = WTM::executeRequestV2("GET"
                            , $sd["sessions"] . "/session/" . $sessionId . "/response"
                            , $headers
                            , $body
                            , false);

        $ret = null;
        if (isset($response)) {
            $status = $response["statusCode"];
            if ($status != 200) {
                $ret = array(
                    "status" => $status,
                    "message" => $response["response"]
                );

                return $ret;
            }

            $ret = json_decode($response["response"], TRUE);
            $ret["status"] = $status;

            if (isset($ret["data"])) {
                $clientSharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $ret["publicKey"]);
                $dec_data = BIDECDSA::decrypt($ret["data"], $clientSharedKey);
                $ret["user_data"] = json_decode($dec_data, TRUE);
        
            }
        }

        if (isset($ret) && isset($ret["user_data"]) && isset($ret["user_data"]["did"]) && $fetchProfile) {
            $ret["account_data"] = BIDUsers::fetchUserByDID($ret["user_data"]["did"], $fetchDevices);
          }
      

        return $ret;

    }    
    
}

?>