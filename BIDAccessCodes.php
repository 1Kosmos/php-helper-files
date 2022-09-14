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

class BIDAccessCode
{

    public static function requestEmailVerificationLink($tenantInfo, $emailTo, $emailTemplateB64OrNull, $emailSubjectOrNull, $createdBy, $ttl_seconds_or_null)
    {
        if (empty($emailTo)) {
            return array(
                "statusCode" => 400,
                "message" => "emailTo is required parameter"
            );
        }

        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);

        $keySet         = $bidTenant->getKeySet();
        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

        $body = array(
            "createdBy" => $createdBy,
            "version" => "v0",
            "type" => "verification_link",
            "emailTo" => $emailTo
        );

        if (isset($ttl_seconds_or_null)) {
            $body["ttl_seconds"] = $ttl_seconds_or_null;
        }

        if (isset($emailTemplateB64OrNull)) {
            $body["emailTemplateB64"] = $emailTemplateB64OrNull;
        }

        if (isset($emailSubjectOrNull)) {
            $body["emailSubject"] = $emailSubjectOrNull;
        }

        $sharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $communityInfo["community"]["publicKey"]);

        $requestid = array(
            "appId" => "blockid.php.sdk",
            "uuid" => uniqid(),
            "ts" => time()
        );

        $encryptedData = array(
            "data" => BIDECDSA::encrypt(json_encode($body), $sharedKey)
        );

        $headers = array(
            "Content-Type" => "application/json",
            "charset" => "utf-8",
            'X-tenantTag' => $communityInfo["tenant"]["tenanttag"],
            "publickey" => $keySet["publicKey"],
            "licensekey" => BIDECDSA::encrypt($licenseKey, $sharedKey),
            "requestid" => BIDECDSA::encrypt(json_encode($requestid), $sharedKey)
        );

        $ret = WTM::executeRequestV2(
            "PUT",
            $sd["adminconsole"] . "/api/r2/acr/community/" . $communityInfo["community"]["name"] . "/code",
            $headers,
            $encryptedData,
            false
        );

        $status = $ret["statusCode"];
        $ret = json_decode($ret["response"], TRUE);
        $ret["statusCode"] = $status;

        return $ret;
    }
}
?>
