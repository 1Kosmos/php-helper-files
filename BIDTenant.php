<!--
 * Copyright (c) 2018, 1Kosmos Inc. All rights reserved.
 * Licensed under 1Kosmos Open Source Public License version 1.0 (the "License");
 * You may not use this file except in compliance with the License. 
 * You may obtain a copy of this license at 
 *    https://github.com/1Kosmos/1Kosmos_License/blob/main/LICENSE.txt
 *-->
<?php
require_once("./BIDECDSA.php");
require_once("./WTM.php");
require_once("./InMemCache.php");

class BIDTenant
{
    private static $instance = null;
    private function __construct()
    {
        error_log(print_r("bidtenant constructor", TRUE));
    }
    
    public static function getInstance()
    {

        if (!self::$instance) {
            self::$instance = new BIDTenant();
        }

        return self::$instance;
    }

    private $keySet = null;
    private $sdk_cache_ttl = 60;

    private function loadFromCache($cache_key)
    {
        $json = null;

        if(isset($_SESSION[$cache_key])){
            
            $json_str = InMemCache::get($cache_key);
            if (isset($json_str)) {
                
                $json = json_decode($json_str, true);
            }
        }
            
        return $json;
    }

    private function cacheMe($cache_key, $cache_data)
    {
        $json_str = json_encode($cache_data);
        InMemCache::set($json_str, $cache_key, $this->sdk_cache_ttl);
    }

    public function getKeySet()
    {
        $cache_key = "bidsdk::keyset";
        if (!isset($this->keySet) && isset($_SESSION[$cache_key])) {
            $this->keySet = json_decode($_SESSION[$cache_key], TRUE);
            return $this->keySet;
        }

        $obj = BIDECDSA::generateKeyPair();
        $this->keySet = $this->setKeySet($obj["publicKey"], $obj["privateKey"]);
        return $this->keySet;
    }

    public function setKeySet($publicKey, $privateKey)
    {
        $obj = array(
            "publicKey" => $publicKey,
            "privateKey" => $privateKey
        );

        $cache_key = "bidsdk::keyset";
        $json_str = null;

        if (!empty($publicKey) && !empty($privateKey)) {
            $json_str = json_encode($obj);
        }

        $_SESSION[$cache_key] = $json_str;
        return $obj;
    }

    public function getCommunityInfo($tenantInfo)
    {
        $url = "https://" . $tenantInfo["dns"] . "/api/r1/system/community_info/fetch";
        $communityCacheKey = "communityCache_" . $tenantInfo["dns"];

        $req_body = array();
        if (isset($tenantInfo["tenantId"])) {
            $req_body["tenantId"] = $tenantInfo["tenantId"];
            $communityCacheKey = $communityCacheKey . "_" . $tenantInfo["tenantId"];
        } else {
            $req_body["dns"] = $tenantInfo["dns"];
        }

        if (isset($tenantInfo["communityId"])) {
            $req_body["communityId"] = $tenantInfo["communityId"];
            $communityCacheKey = $communityCacheKey . "_" . $tenantInfo["communityId"];
        } else {
            $req_body["communityName"] = $tenantInfo["communityName"];
            $communityCacheKey = $communityCacheKey . "_" . $tenantInfo["communityName"];
        }

        //check cache
        $communityInfoCache = $this->loadFromCache($communityCacheKey);

        if (isset($communityInfoCache)) {
            return $communityInfoCache;
        }

        $headers = array(
            "Content-Type" => "application/json",
            "charset" => "utf-8"
        );

        $communityInfo =  WTM::executeRequest("POST", $url, $headers, $req_body, false);

        $this->cacheMe($communityCacheKey, $communityInfo);

        return $communityInfo;
    }

    public function getSD($tenantInfo)
    {

        $sdUrl = "https://" . $tenantInfo["dns"] . "/caas/sd";
        $sdCacheKey = "sdCache_" . $tenantInfo["dns"];

        if (isset($tenantInfo["tenantId"])) {
            $sdCacheKey = $sdCacheKey . "_" . $tenantInfo["tenantId"];
        }

        if (isset($tenantInfo["communityId"])) {
            $sdCacheKey = $sdCacheKey . "_" . $tenantInfo["communityId"];
        } else {
            $sdCacheKey = $sdCacheKey . "_" . $tenantInfo["communityName"];
        }
        
        //check cache
        $sdCache = $this->loadFromCache($sdCacheKey);

        if (isset($sdCache)) {
            return $sdCache;
        }

        $headers = array(
            "Content-Type" => "application/json",
            "charset" => "utf-8"
        );

        $sd =  WTM::executeRequest("GET", $sdUrl, $headers, null, false);

        $this->cacheMe($sdCacheKey, $sd);

        return $sd;
    }
}
?>
