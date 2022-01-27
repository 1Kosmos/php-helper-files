# php-helper-files

sample APIs for tests.php

How to use the SDK

Request OTP code
```
require_once("./BIDSDK.php");
require_once("./BIDOTP.php");


BIDSDK::getInstance()->setupTenant($tenant, $license);
BIDOTP::requestOTP("$userId", "$emailorNull", "$phoneOrNull", "$countryCode");
```

Verify OTP code
```
require_once("./BIDSDK.php");
require_once("./BIDOTP.php");

BIDSDK::getInstance()->setupTenant($tenant, $license);
BIDOTP::verifyOTP("$userId", "$code");
```

Create UWL2.0 session
```
require_once("./BIDSDK.php");
require_once("./BIDSession.php");

    BIDSDK::getInstance()->setupTenant($tenant, $license);
    $ret = BIDSession::createNewSession(null, null);
```

Poll for UWL2.0 session
```
require_once("./BIDSDK.php");
require_once("./BIDSession.php");

    BIDSDK::getInstance()->setupTenant($tenant, $license);
    $ret = BIDSession::pollSession($sessionId, TRUE, TRUE);
```
