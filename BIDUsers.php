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

class BIDUsers
{

    public static function fetchUserByDID($tenantInfo, $did, $fetchDevices)
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
            "X-TenantTag" => $communityInfo["tenant"]["tenanttag"],
            "publickey" => $keySet["publicKey"],
            "licensekey" => BIDECDSA::encrypt($licenseKey, $sharedKey),
            "requestid" => BIDECDSA::encrypt(json_encode($requestid), $sharedKey)
        );

        $url = $sd["adminconsole"] . "/api/r1/community/" . $communityInfo["community"]["name"] . "/userdid/" . $did . "/userinfo";
        if ($fetchDevices) {
            $url = $url . "?devicelist=true";
        }

        $ret = null;
        $response = WTM::executeRequest(
            "GET",
            $url,
            $headers,
            null,
            false,
            false
        );


        if (isset($response) && isset($response["data"])) {
            $dec_data = BIDECDSA::decrypt($response["data"], $sharedKey);
            $ret = json_decode($dec_data, true);
        }
        return $ret;
    }
}
?>
