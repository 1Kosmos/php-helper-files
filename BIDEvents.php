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
class BIDEvents
{
    private static $ttl = 86400; // Cashing public key for 24 hours

    private static function getPublicKey($baseUrl)
    {
        $url = $baseUrl . "/publickeys";

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
                false,
                true
            );

            InMemCache::set(json_encode($response), $url, self::$ttl);
        }

        return $response["publicKey"];
    }

    public static function logEvent($tenantInfo, $eventName, $data, $requestId)
    {
        $bidTenant = BIDTenant::getInstance();
        $communityInfo = $bidTenant->getCommunityInfo($tenantInfo);

        $keySet = $bidTenant->getKeySet();
        $licenseKey = $tenantInfo["licenseKey"];
        $sd = $bidTenant->getSD($tenantInfo);

        $eventsPublicKey = self::getPublicKey($sd["events"]);

        $sharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $eventsPublicKey);

        $headers = array(
            "Content-Type" => "application/json",
            "charset" => "utf-8",
            "publickey" => $keySet["publicKey"],
            "licensekey" => BIDECDSA::encrypt($licenseKey, $sharedKey),
            "requestid" => BIDECDSA::encrypt(json_encode($requestId), $sharedKey)
        );

        $encryptedData = array(
            "data" => BIDECDSA::encrypt(json_encode($data), $sharedKey)
        );

        $ret = WTM::executeRequestV2(
            "PUT",
            $sd["events"] . "/tenant/" . $communityInfo["tenant"]["id"] . "/community/" . $communityInfo["community"]["id"] . "/event/" . $eventName,
            $headers,
            $encryptedData,
            false,
            true
        );

        return $ret;
    }
}

?>

