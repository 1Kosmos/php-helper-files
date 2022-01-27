/**
 * Copyright (c) 2018, 1Kosmos Inc. All rights reserved.
 * Licensed under 1Kosmos Open Source Public License version 1.0 (the "License");
 * You may not use this file except in compliance with the License. 
 * You may obtain a copy of this license at 
 *    https://github.com/1Kosmos/1Kosmos_License/blob/main/LICENSE.txt
 */
 <?php
require_once("./BIDECDSA.php");
require_once("./WTM.php");
require_once("./InMemCache.php");

class BIDSDK
{
    private static $instance = null;
    private function __construct()
    {
        error_log(print_r("bidsdk constructor", TRUE));
     //noop
    }

    public static function getInstance()
    {
        
        if (!self::$instance)
        {
            self::$instance = new BIDSDK();
        }

        return self::$instance;
    }

    private $loaded = false;
    private $keySet = null;
    private $tenant = null;
    private $licenseKey = null;
    private $communityInfo = null;
    private $sd = null;
    private $sdk_cache_ttl = 60;
    
    public function isLoaded() {
        return $this->loaded;
    }

    private function loadFromCache() {

        $bid_sdk_cache_key = $_SESSION["bid_sdk_cache_key"];

        $json_str = InMemCache::get($bid_sdk_cache_key);

        if (isset($json_str)) {
           // error_log(print_r("found sdk cache.. load it up" . "\n" . $json_str . "\n", TRUE));
            

            $json = json_decode($json_str, true);

            $this->communityInfo = $json["communityInfo"];
            $this->sd = $json["sd"];
        }
    }

    private function cacheMe() {
        $bid_sdk_cache_key = $_SESSION["bid_sdk_cache_key"];

        $json = array(
            "communityInfo" => $this->communityInfo,
            "sd" => $this->sd
        );

        $json_str = json_encode($json);

        InMemCache::set($json_str, $bid_sdk_cache_key, $this->sdk_cache_ttl);
        
    }


    public function setupTenant($obj, $license) {
        $this->loaded = false;

        //has tenant changed from before?
        $bid_sdk_cache_key = $_SESSION["bid_sdk_cache_key"];
        $bid_sdk_new_cache_key = $obj["dns"] . "::" . $obj["communityName"] . "::" . $license;

        if (strcasecmp($bid_sdk_cache_key, $bid_sdk_new_cache_key) !== 0) {//changed
            error_log(print_r("tenant or license changed.. clearing cache", TRUE));
            $_SESSION["bid_sdk_cache_key"] = null;
            $_SESSION[$bid_sdk_cache_key] = null;
        }

        $_SESSION["bid_sdk_cache_key"] = $bid_sdk_new_cache_key;

        $this->tenant = $obj;
        $this->licenseKey = $license;


        $this->keySet = $this->getKeySet();
        if ($this->keySet == null) {
            error_log(print_r("generate sdk keyset ", TRUE));
            $this->setKeySet(BIDECDSA::generateKeyPair());
        }

        $this->loadFromCache();

        if ($this->communityInfo == null) {
            error_log(print_r("load sdk communityInfo ", TRUE));
            $this->loadCommunityInfo();
        }

        if ($this->sd == null) {
            error_log(print_r("load sdk sd ", TRUE));
            $sdUrl = "https://" . $this->tenant["dns"] . "/caas/sd";
            $this->loadSD($sdUrl);    
        }

        
        $this->loaded = true;

        $this->cacheMe();

        return true;
    }

    public function getTenant() {
        return $this->tenant;
    }

    public function getSD() {
        return $this->sd;
    }

    public function getKeySet() {
        $cache_key = "bidsdk::keyset";
        if (!isset($this->keySet) && isset($_SESSION[$cache_key])) {
            $this->keySet = json_decode($_SESSION[$cache_key], TRUE);
        }
        return $this->keySet;
    }

    public function setKeySet($obj) {
        $this->keySet = $obj;
        $cache_key = "bidsdk::keyset";
        $json_str = null;
        
        if (isset($obj)) {
            $json_str = json_encode($obj);
        }
        
        $_SESSION[$cache_key] = $json_str;

    }

    public function getCommunityInfo() {
        return $this->communityInfo;
    }


    public function getLicense() {
        return $this->licenseKey;
    }
    

    private function loadCommunityInfo() {
        //TODO: pending
        
        $url = "https://" . $this->tenant["dns"] . "/api/r1/system/community_info/fetch";
        

        $req_body = array();
        if (isset($this->tenant["tenantId"])) {
            $req_body["tenantId"] = $this->tenant["tenantId"];
        }
        else {
            $req_body["dns"] = $this->tenant["dns"];
        }

        if (isset($this->tenant["communityId"])) {
            $req_body["communityId"] = $this->tenant["communityId"];
        }
        else {
            $req_body["communityName"] = $this->tenant["communityName"];
        }


        $headers = array (
            "Content-Type" => "application/json",
            "charset" => "utf-8"
        );

        $this->communityInfo =  WTM::executeRequest("POST", $url, $headers, $req_body, false);
    }

    private function loadSD($sdUrl) {
        $headers = array (
            "Content-Type" => "application/json",
            "charset" => "utf-8"
        );

        $this->sd =  WTM::executeRequest("GET", $sdUrl, $headers, null, false);

    }


    

    
}

?>