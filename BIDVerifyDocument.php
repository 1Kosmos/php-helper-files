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

class BIDVerifyDocument
{
    private static $ttl = 60;

    private static function getDocVerifyPublicKey($tenantInfo)
    {
        $bidTenant      = BIDTenant::getInstance();
        $sd             = $bidTenant->getSD($tenantInfo);

        $url = $sd["docuverify"] . "/publickeys";

        $response = null;
        $responseStr = InMemCache::get($url);
        if (isset($responseStr)) {
            $response = json_decode($responseStr, TRUE);
        }

        if (!isset($responseStr)) { //no cache available.. fetch again
            $response = WTM::executeRequest(
                "GET",
                $url,
                array("Content-Type" => "application/json", "charset" => "utf-8"),
                null,
                false
            );

            InMemCache::set(json_encode($response), $url, self::$ttl);
        }

        return $response["publicKey"];
    }

    public static function verifyDocument($tenantInfo, $dvcId, $verifications, $document)
    {
        $bidTenant      = BIDTenant::getInstance();
        $keySet         = $bidTenant->getKeySet();
        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

        $sessionsPublicKey = self::getDocVerifyPublicKey($tenantInfo);

        $sharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $sessionsPublicKey);

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
            "dvcID" => $dvcId,
            "verifications" => $verifications,
            "document" => $document
        );

        $encryptedData = array(
            "data" => BIDECDSA::encrypt(json_encode($body), $sharedKey)
        );

        $ret = WTM::executeRequestV2(
            "POST",
            $sd["docuverify"] . "/verify",
            $headers,
            $encryptedData,
            false
        );

        if (isset($ret["response"])) {
            $ret = json_decode($ret["response"], TRUE);

            if (isset($ret["data"])) {
                $dec_data = BIDECDSA::decrypt($ret["data"], $sharedKey);
                $ret = json_decode($dec_data, TRUE);
            }
        }

        return $ret;
    }

    public static function createDocumentSession($tenantInfo, $dvcId, $documentType)
    {
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);
        $keySet         = $bidTenant->getKeySet();
        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

        $sessionsPublicKey = self::getDocVerifyPublicKey($tenantInfo);

        $sharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $sessionsPublicKey);
        $userUIDAndDid = uniqid();

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
            "dvcID" => $dvcId,
            "sessionRequest" => array(
                "tenantDNS" => $tenantInfo["dns"],
                "communityName" => $communityInfo["community"]["name"],
                "documentType" => $documentType,
                "userUID" => $userUIDAndDid,
                "did" => $userUIDAndDid
            )
        );

        $encryptedData = array(
            "data" => BIDECDSA::encrypt(json_encode($body), $sharedKey)
        );

        $ret = WTM::executeRequestV2(
            "POST",
            $sd["docuverify"] . "/document_share_session/create",
            $headers,
            $encryptedData,
            false
        );

        if (isset($ret["response"])) {
            $ret = json_decode($ret["response"], TRUE);
        }

        return $ret;
    }

    public static function pollSessionResult($tenantInfo, $dvcId, $sessionId)
    {
        $bidTenant      = BIDTenant::getInstance();
        $keySet         = $bidTenant->getKeySet();
        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

        $sessionsPublicKey = self::getDocVerifyPublicKey($tenantInfo);

        $sharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $sessionsPublicKey);

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
            "dvcID" => $dvcId,
            "sessionId" => $sessionId
        );

        $encryptedData = array(
            "data" => BIDECDSA::encrypt(json_encode($body), $sharedKey)
        );

        $ret = WTM::executeRequestV2(
            "POST",
            $sd["docuverify"] . "/document_share_session/result",
            $headers,
            $encryptedData,
            false
        );

        if (isset($ret["response"])) {
            $ret = json_decode($ret["response"], TRUE);

            if (isset($ret["data"])) {
                $dec_data = BIDECDSA::decrypt($ret["data"], $sharedKey);
                $ret = json_decode($dec_data, TRUE);
            }
        }

        return $ret;
    }
}
