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

Request Email verification link
```
require_once("./BIDAccessCodes.php");

$tenantInfo = array("dns" => "$dns", "communityName" => "$communityName", "licenseKey" => "$licenseKey");

$requestEmailVerificationResponse = BIDAccessCode::requestEmailVerificationLink("$tenantInfo", "$emailTo", "$emailTemplateB64OrNull", "$emailSubjectOrNull", "$createdBy", "$ttl_seconds_or_null");
```

Verify and Redeem Email verification link\
```
require_once("./BIDAccessCodes.php");

$tenantInfo = array("dns" => "$dns", "communityName" => "$communityName", "licenseKey" => "$licenseKey");

$redeemVerificationCodeResponse = BIDAccessCode::verifyAndRedeemEmailVerificationCode("$tenantInfo", "$code");
```

Create new Driver's License verification session
```
require_once("./BIDVerifyDocument.php");

$tenantInfo = array("dns" => "$dns", "communityName" => "$communityName", "licenseKey" => "$licenseKey");

$createdSessionResponse = BIDVerifyDocument::createDocumentSession($tenantInfo, "$dvcId", "$documentType");
```

Trigger SMS
```
require_once("./BIDMessaging.php");

$tenantInfo = array("dns" => "$dns", "communityName" => "$communityName", "licenseKey" => "$licenseKey");

$smsResponse = BIDMessaging::sendSMS($tenantInfo, "$smsTo", "$smsISDCode", "$smsTemplateB64");
```

Poll for Driver's License session response
```
require_once("./BIDVerifyDocument.php");

$tenantInfo = array("dns" => "$dns", "communityName" => "$communityName", "licenseKey" => "$licenseKey");

$pollSessionResponse = BIDVerifyDocument::pollSessionResult($tenantInfo, "$dvcId", "$sessionId");
```
