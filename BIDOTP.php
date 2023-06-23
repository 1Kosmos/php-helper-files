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
require_once("./WTM.php");
require_once("./InMemCache.php");

class BIDOTP
{

    public static function requestOTP($tenantInfo, $userName, $emailToOrNull, $smsToOrNull, $smsISDCodeOrNull)
    {
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);

        $keySet         = $bidTenant->getKeySet();
        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

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

        $headers = array(
            "Content-Type" => "application/json",
            "charset" => "utf-8",
            "publickey" => $keySet["publicKey"],
            "licensekey" => BIDECDSA::encrypt($licenseKey, $sharedKey),
            "requestid" => BIDECDSA::encrypt(json_encode($requestid), $sharedKey)
        );

        $ret = WTM::executeRequest(
            "POST",
            $sd["adminconsole"] . "/api/r2/otp/generate",
            $headers,
            $body,
            false,
            false
        );

        if (isset($ret) && isset($ret["data"])) {
            $responseStr = BIDECDSA::decrypt($ret["data"], $sharedKey);
            $ret["response"] = json_decode($responseStr, TRUE);
        }

        return $ret;
    }


    public static function verifyOTP($tenantInfo, $userName, $otpCode)
    {
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);

        $keySet         = $bidTenant->getKeySet();
        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

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

        $headers = array(
            "Content-Type" => "application/json",
            "charset" => "utf-8",
            "publickey" => $keySet["publicKey"],
            "licensekey" => BIDECDSA::encrypt($licenseKey, $sharedKey),
            "requestid" => BIDECDSA::encrypt(json_encode($requestid), $sharedKey)
        );

        $ret = WTM::executeRequest(
            "POST",
            $sd["adminconsole"] . "/api/r2/otp/verify",
            $headers,
            $body,
            false,
            false
        );

        return $ret;
    }
}

?>
