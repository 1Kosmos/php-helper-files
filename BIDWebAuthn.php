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

class BIDWebAuthn
{
    public static function fetchAttestationOptions($tenantInfo, $optionsRequest)
    {
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);

        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

        $additionalData = array("communityId" => $communityInfo["community"]["id"], "tenantId" => $communityInfo["tenant"]["id"]);

        $body = array_merge((array) $optionsRequest, (array) $additionalData);

        $headers = array(
            "Content-Type" => "application/json",
            "charset" => "utf-8",
            "licensekey" => $licenseKey,
            "requestid" => uniqid()
        );

        $ret = WTM::executeRequestV2(
            "POST",
            $sd["webauthn"] . "/u1/attestation/options",
            $headers,
            $body,
            false,
            true
        );

        if (isset($ret["response"])) {
            $ret = json_decode($ret["response"], TRUE);
        }

        return $ret;
    }

    public static function submitAttestationResult($tenantInfo, $resultRequest)
    {
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);

        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

        $additionalData = array("communityId" => $communityInfo["community"]["id"], "tenantId" => $communityInfo["tenant"]["id"]);

        $body = array_merge((array) $resultRequest, (array) $additionalData);

        $headers = array(
            "Content-Type" => "application/json",
            "charset" => "utf-8",
            "licensekey" => $licenseKey,
            "requestid" => uniqid()
        );

        $ret = WTM::executeRequestV2(
            "POST",
            $sd["webauthn"] . "/u1/attestation/result",
            $headers,
            $body,
            false,
            true
        );

        if (isset($ret["response"])) {
            $ret = json_decode($ret["response"], TRUE);
        }

        return $ret;
    }

    public static function fetchAssertionOptions($tenantInfo, $optionsRequest)
    {
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);

        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

        $additionalData = array("communityId" => $communityInfo["community"]["id"], "tenantId" => $communityInfo["tenant"]["id"]);

        $body = array_merge((array) $optionsRequest, (array) $additionalData);

        $headers = array(
            "Content-Type" => "application/json",
            "charset" => "utf-8",
            "licensekey" => $licenseKey,
            "requestid" => uniqid()
        );

        $ret = WTM::executeRequestV2(
            "POST",
            $sd["webauthn"] . "/u1/assertion/options",
            $headers,
            $body,
            false,
            true
        );

        if (isset($ret["response"])) {
            $ret = json_decode($ret["response"], TRUE);
        }

        return $ret;
    }

    public static function submitAssertionResult($tenantInfo, $resultRequest)
    {
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);

        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

        $additionalData = array("communityId" => $communityInfo["community"]["id"], "tenantId" => $communityInfo["tenant"]["id"]);

        $body = array_merge((array) $resultRequest, (array) $additionalData);

        $headers = array(
            "Content-Type" => "application/json",
            "charset" => "utf-8",
            "licensekey" => $licenseKey,
            "requestid" => uniqid()
        );

        $ret = WTM::executeRequestV2(
            "POST",
            $sd["webauthn"] . "/u1/assertion/result",
            $headers,
            $body,
            false,
            true
        );

        if (isset($ret["response"])) {
            $ret = json_decode($ret["response"], TRUE);
        }

        return $ret;
    }
}
?>
