<!--
 * Copyright (c) 2018, 1Kosmos Inc. All rights reserved.
 * Licensed under 1Kosmos Open Source Public License version 1.0 (the "License");
 * You may not use this file except in compliance with the License.
 * You may obtain a copy of this license at
 *    https://github.com/1Kosmos/1Kosmos_License/blob/main/LICENSE.txt
-->

<?php

class InMemCache
{
    public static function set($value, $key, $ttl)
    {
        if (!isset($key)) {
            return;
        }

        $_SESSION[$key] = $value;

        $tos_key = $key . "::tos"; //time of storage
        $_SESSION[$tos_key] = time();

        $ttl_key = $key . "::ttl";
        $_SESSION[$ttl_key] = $ttl;

        if (!isset($value)) {
            $_SESSION[$tos_key] = null;
            $_SESSION[$ttl_key] = null;
        }
    }

    public static function get($key)
    {
        if (!isset($key)) {
            return null;
        }

        $ret = $_SESSION[$key];

        $tos_key = $key . "::tos"; //time of storage
        $ttl_key = $key . "::ttl";

        if (!isset($ret)) {
            self::set(null, $key, 0);
            return null;
        }

        $tos = $_SESSION[$tos_key];
        $ttl = $_SESSION[$ttl_key];

        $now = time();

        if ($now - $ttl > $tos) {
            self::set(null, $key, 0);
            return null;
        }

        return $ret;
    }
}

?>
