<!--
 * Copyright (c) 2018, 1Kosmos Inc. All rights reserved.
 * Licensed under 1Kosmos Open Source Public License version 1.0 (the "License");
 * You may not use this file except in compliance with the License. 
 * You may obtain a copy of this license at 
 *    https://github.com/1Kosmos/1Kosmos_License/blob/main/LICENSE.txt
 * -->

<?php
require_once("./BIDTenant.php");
require_once("./BIDECDSA.php");
require_once("./WTM.php");
require_once("./InMemCache.php");

class BIDMessaging
{
    public static function sendSMS($tenantInfo, $smsTo, $smsISDCode, $smsTemplateB64)
    {
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);
        $keySet         = $bidTenant->getKeySet();
        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

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

        $body = array(
            "tenantId" => $communityInfo["community"]["tenantid"],
            "communityId" => $communityInfo["community"]["id"],
            "smsTo" => $smsTo,
            "smsISDCode" => $smsISDCode,
            "smsTemplateB64" => $smsTemplateB64
        );

        $ret = WTM::executeRequestV2(
            "POST",
            $sd["adminconsole"] . "/api/r2/messaging/schedule",
            $headers,
            $body,
            false
        );

        if (isset($ret["response"])) {
            $ret = json_decode($ret["response"], TRUE);
        }

        return $ret;
    }
}
