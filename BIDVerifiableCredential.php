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

class BIDVerifiableCredential
{
    private static $ttl = 60;
    private static function getVcsPublicKey($tenantInfo)
    {
        $bidTenant      = BIDTenant::getInstance();
        $sd             = $bidTenant->getSD($tenantInfo);

        $url = $sd["vcs"] . "/publickeys";

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

    public static function requestVCForID($tenantInfo, $type, $document, $userDid, $userPublickey, $userUrn)
    {
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);
        $keySet         = $bidTenant->getKeySet();
        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

        $vcsPublicKey = self::getVcsPublicKey($tenantInfo);

        $sharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $vcsPublicKey);

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
            "document" => $document,
            "did" => $userDid,
            "publicKey" => $userPublickey,
            "userURN" => $userUrn
        );

        $ret = WTM::executeRequestV2(
            "POST",
            $sd["vcs"] . "/tenant/" . $communityInfo["tenant"]["id"] . "/community/" . $communityInfo["community"]["id"] . "/vc/from/document/" . $type,
            $headers,
            $body,
            false
        );

        if (isset($ret["response"])) {
            $ret = json_decode($ret["response"], TRUE);
        }

        return $ret;
    }

    public static function requestVCForPayload($tenantInfo, $type, $issuer, $info, $userDid, $userPublickey, $userUrn)
    {
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);
        $keySet         = $bidTenant->getKeySet();
        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

        $vcsPublicKey = self::getVcsPublicKey($tenantInfo);

        $sharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $vcsPublicKey);

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
            "info" => $info,
            "did" => $userDid,
            "publicKey" => $userPublickey,
            "issuer" => $issuer,
            "userURN" => $userUrn
        );

        $ret = WTM::executeRequestV2(
            "POST",
            $sd["vcs"] . "/tenant/" . $communityInfo["tenant"]["id"] . "/community/" . $communityInfo["community"]["id"] . "/vc/from/payload/" . $type,
            $headers,
            $body,
            false
        );

        if (isset($ret["response"])) {
            $ret = json_decode($ret["response"], TRUE);
        }

        return $ret;
    }

    public static function verifyCredential($tenantInfo, $vc)
    {
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);
        $keySet         = $bidTenant->getKeySet();
        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

        $vcsPublicKey = self::getVcsPublicKey($tenantInfo);

        $sharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $vcsPublicKey);

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
            "vc" => $vc
        );

        $ret = WTM::executeRequestV2(
            "POST",
            $sd["vcs"] . "/tenant/" . $communityInfo["tenant"]["id"] . "/community/" . $communityInfo["community"]["id"] . "/vc/verify",
            $headers,
            $body,
            false
        );

        if (isset($ret["response"])) {
            $ret = json_decode($ret["response"], TRUE);
        }

        return $ret;
    }

    public static function requestVPForCredentials($tenantInfo, $vcs)
    {
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);
        $keySet         = $bidTenant->getKeySet();
        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

        $vcsPublicKey = self::getVcsPublicKey($tenantInfo);

        $sharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $vcsPublicKey);

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
            "vcs" => $vcs
        );

        echo "vcs:::" . $sd["vcs"] . "<br/>";

        $ret = WTM::executeRequestV2(
            "POST",
            $sd["vcs"] . "/tenant/" . $communityInfo["tenant"]["id"] . "/community/" . $communityInfo["community"]["id"] . "/vp/create",
            $headers,
            $body,
            false
        );

        if (isset($ret["response"])) {
            $ret = json_decode($ret["response"], TRUE);
        }

        return $ret;
    }


    public static function verifyPresentation($tenantInfo, $vp)
    {
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);
        $keySet         = $bidTenant->getKeySet();
        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

        $vcsPublicKey = self::getVcsPublicKey($tenantInfo);

        $sharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $vcsPublicKey);

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
            "vp" => $vp
        );

        $ret = WTM::executeRequestV2(
            "POST",
            $sd["vcs"] . "/tenant/" . $communityInfo["tenant"]["id"] . "/community/" . $communityInfo["community"]["id"] . "/vp/verify",
            $headers,
            $body,
            false
        );

        if (isset($ret["response"])) {
            $ret = json_decode($ret["response"], TRUE);
        }

        return $ret;
    }

    public static function getVcStatusById($tenantInfo, $vcId)
    {
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);
        $keySet         = $bidTenant->getKeySet();
        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

        $vcsPublicKey = self::getVcsPublicKey($tenantInfo);

        $sharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $vcsPublicKey);

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

        $ret = WTM::executeRequestV2(
            "GET",
            $sd["vcs"] . "/tenant/" . $communityInfo["tenant"]["id"] . "/community/" . $communityInfo["community"]["id"] . "/vc/" . $vcId . "/status",
            $headers,
            null,
            false
        );

        if (isset($ret["response"])) {
            $ret = json_decode($ret["response"], TRUE);
        }

        return $ret;
    }
}
