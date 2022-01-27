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
require_once("./WTM.php");
require_once("./InMemCache.php");

class BIDOTP
{

    public static function requestOTP($userName, $emailToOrNull, $smsToOrNull, $smsISDCodeOrNull) {
        $bidsdk         = BIDSDK::getInstance();
        $communityInfo  = $bidsdk->getCommunityInfo();
        
        $keySet         = $bidsdk->getKeySet();
        $licenseKey     = $bidsdk->getLicense();
        $sd             = $bidsdk->getSD();

        $body = array(
            "userId" => $userName,
            "communityId" => $communityInfo["community"]["id"],
            "tenantId" => $communityInfo["tenant"]["id"]
        );

        if (isset($emailToOrNull)) {
            $body["emailTo"] = $emailToOrNull;
        }

        if (isset($smsToOrNull) && isset($smsISDCodeOrNull)) {
            $body["smsTo"] = $smsToOrNull;
            $body["smsISDCode"] = $smsISDCodeOrNull;
        }


        $sharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $communityInfo["community"]["publicKey"]);
        
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

        $ret = WTM::executeRequest("POST"
                            , $sd["adminconsole"] . "/api/r2/otp/generate"
                            , $headers
                            , $body
                            , false);

        if (isset($ret) && isset($ret["data"])) {
            $responseStr = BIDECDSA::decrypt($ret["data"], $sharedKey);
            $ret["response"] = json_decode($responseStr, TRUE);
        }
        

        return $ret;

    }


    public static function verifyOTP($userName, $otpCode) {
        $bidsdk         = BIDSDK::getInstance();
        $communityInfo  = $bidsdk->getCommunityInfo();
        
        $keySet         = $bidsdk->getKeySet();
        $licenseKey     = $bidsdk->getLicense();
        $sd             = $bidsdk->getSD();

        $body = array(
            "userId" => $userName,
            "communityId" => $communityInfo["community"]["id"],
            "tenantId" => $communityInfo["tenant"]["id"],
            "code" => $otpCode
        );


        $sharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $communityInfo["community"]["publicKey"]);
        
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

        $ret = WTM::executeRequest("POST"
                            , $sd["adminconsole"] . "/api/r2/otp/verify"
                            , $headers
                            , $body
                            , false);

        return $ret;

    }    
    
}

?>