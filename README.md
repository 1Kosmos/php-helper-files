# php-helper-files

sample APIs for tests.php

How to use the SDK

Request OTP code
```
require_once("./BIDOTP.php");

$tenantInfo = array("dns" => "$dns", "communityName" => "$communityName", "licenseKey" => "$licenseKey");

$otpResponse = BIDOTP::requestOTP($tenantInfo, "$userId", "$emailorNull", "$phoneOrNull", "$countryCode");
```

Verify OTP code
```
require_once("./BIDOTP.php");

$tenantInfo = array("dns" => "$dns", "communityName" => "$communityName", "licenseKey" => "$licenseKey");

$verifyOTPResponse = BIDOTP::verifyOTP("$tenantInfo", "$userId", "$code");
```

Create UWL2.0 session
```
require_once("./BIDSession.php");

$tenantInfo = array("dns" => "$dns", "communityName" => "$communityName", "licenseKey" => "$licenseKey");

$createdSessionResponse = BIDSession::createNewSession(null, null);
```

Poll for UWL2.0 session
```
require_once("./BIDSession.php");

$tenantInfo = array("dns" => "$dns", "communityName" => "$communityName", "licenseKey" => "$licenseKey");

$pollSessionResponse = BIDSession::pollSession("$tenantInfo", "$sessionId", TRUE, TRUE);
```
