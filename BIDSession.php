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
require_once("./BIDUsers.php");
require_once("./BIDEvents.php");
require_once("./WTM.php");
require_once("./InMemCache.php");

class BIDSession
{

    private static $ttl = 86400; // Cashing public key for 24 hours

    private static function getSessionPublicKey($baseUrl)
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

    public static function createNewSession($tenantInfo, $authType, $scopes, $metadata, $requestIdOrNull)
    {
        $requestId      = isset($requestIdOrNull) ? $requestIdOrNull : WTM::createRequestID();
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);

        $keySet         = $bidTenant->getKeySet();
        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

        $sessionsPublicKey = self::getSessionPublicKey($sd["sessions"]);

        $sharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $sessionsPublicKey);

        $body = array(
            "origin" => array(
                "tag" => $communityInfo["tenant"]["tenanttag"],
                "url" => $sd["adminconsole"],
                "communityName" => $communityInfo["community"]["name"],
                "communityId" => $communityInfo["community"]["id"],
                "authPage" => "blockid://authenticate"
            ),
            "scopes" => (isset($scopes) ? $scopes : ""),
            "authtype" => (isset($authType) ? $authType : "none"),
            "metadata" => $metadata
        );

        $headers = array(
            "Content-Type" => "application/json",
            "charset" => "utf-8",
            "publickey" => $keySet["publicKey"],
            "licensekey" => BIDECDSA::encrypt($licenseKey, $sharedKey),
            "requestid" => BIDECDSA::encrypt(json_encode($requestId), $sharedKey)
        );

        $ret = WTM::executeRequest(
            "PUT",
            $sd["sessions"] . "/session/new",
            $headers,
            $body,
            false,
            true
        );

        if (isset($ret) && isset($ret["sessionId"])) {
            $ret["url"] = $sd["sessions"];
        }
        return $ret;
    }

    public static function pollSession($tenantInfo, $sessionId, $fetchProfile, $fetchDevices, $eventDataOrNull, $requestIdOrNull)
    {
        $requestId      = isset($requestIdOrNull) ? $requestIdOrNull : WTM::createRequestID();
        $bidTenant      = BIDTenant::getInstance();
        $communityInfo  = $bidTenant->getCommunityInfo($tenantInfo);
        $keySet         = $bidTenant->getKeySet();
        $licenseKey     = $tenantInfo["licenseKey"];
        $sd             = $bidTenant->getSD($tenantInfo);

        $sessionsPublicKey = self::getSessionPublicKey($sd["sessions"]);
        $sharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $sessionsPublicKey);

        $headers = array(
            "Content-Type" => "application/json",
            "charset" => "utf-8",
            "publickey" => $keySet["publicKey"],
            "licensekey" => BIDECDSA::encrypt($licenseKey, $sharedKey),
            "requestid" => BIDECDSA::encrypt(json_encode($requestId), $sharedKey),
            "addsessioninfo" => 1
        );

        $response = WTM::executeRequestV2(
            "GET",
            $sd["sessions"] . "/session/" . $sessionId . "/response",
            $headers,
            null,
            false,
            true
        );

        $ret = null;
        if (isset($response)) {
            $status = $response["statusCode"];
            if ($status != 200) {
                
                return array(
                    "status" => $status,
                    "message" => $response["response"]
                );
            }

            $ret = json_decode($response["response"], TRUE);
            $ret["status"] = $status;

            if (!isset($ret["data"])) {
                
                return array(
                    "status" => 401,
                    "message" => "Session data not found"
                );
            }
            
            $clientSharedKey = BIDECDSA::createSharedKey($keySet["privateKey"], $ret["publicKey"]);
            $dec_data = BIDECDSA::decrypt($ret["data"], $clientSharedKey);
            $ret["user_data"] = json_decode($dec_data, TRUE);

            if (!array_key_exists("did", $ret["user_data"])) {
                
                return array(
                    "status" => 401,
                    "message" => "Unauthorized user"
                );
            }
        }

        if (isset($ret) && isset($ret["user_data"]) && isset($ret["user_data"]["did"]) && $fetchProfile) {
            $ret["account_data"] = BIDUsers::fetchUserByDID($tenantInfo, $ret["user_data"]["did"], $fetchDevices);
        }

        // check if authenticator response is authorized.
        $userIdList = isset($ret["account_data"]) ? $ret["account_data"]["userIdList"] : [];
        if (!empty($userIdList) && isset($ret["user_data"]["userid"])) {
            $ret["user_data"]["userid"] = $userIdList[0];
        }

        if (in_array($ret["user_data"]["userid"], $userIdList)) {
            $ret["isValid"] = true;
        } else { // This covers when the userid is not found in userIdList
            // This covers when userIdList is empty or does not contain the user ID
            $ret["status"] = 401;
            $ret["isValid"] = false;
            $ret["message"] = "Unauthorized user";
        }

        $session_purpose = isset($ret['sessionInfo']['metadata']['purpose']) ? $ret['sessionInfo']['metadata']['purpose'] : null;
        
        // Report Event
        if ($session_purpose === "authentication") {
            $eventData = array(
                "tenant_dns" => $tenantInfo['dns'],
                "tenant_tag" => $communityInfo['tenant']['tenanttag'],
                "service_name" => "NodeJS Helper",
                "auth_method" => "qr",
                "type" => "event",
                "event_id" => uniqid(),
                "version" => "v1",
                "session_id" => $sessionId,
                "did" => $ret['user_data']['did'],
                "auth_public_key" => $ret['publicKey'],
                "user_id" => $ret['user_data']['userid'],
                "login_state" => "SUCCESS",
            );

            if (is_array($eventDataOrNull)) {
                $eventData = array_merge($eventData, $eventDataOrNull);
            }

            $eventName = $ret['isValid'] ? "E_LOGIN_SUCCEEDED" : "E_LOGIN_FAILED";
            
            if (!$ret['isValid']) {
                $eventData['reason'] = array(
                    "reason" => "User not found in PON data"
                );
                $eventData['login_state'] = "FAILED";
            }
            
            BIDEvents::logEvent($tenantInfo, $eventName, $eventData, $requestId);
        }
        
        return $ret;
    }
}

?>
