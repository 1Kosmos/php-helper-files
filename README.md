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

$verifyOTPResponse = BIDOTP::verifyOTP($tenantInfo, "$userId", "$code");
```

Create UWL2.0 session
```
require_once("./BIDSession.php");

$tenantInfo = array("dns" => "$dns", "communityName" => "$communityName", "licenseKey" => "$licenseKey");

$createdSessionResponse = BIDSession::createNewSession($tenantInfo, null, null);
```

Poll for UWL2.0 session
```
require_once("./BIDSession.php");

$tenantInfo = array("dns" => "$dns", "communityName" => "$communityName", "licenseKey" => "$licenseKey");

$pollSessionResponse = BIDSession::pollSession($tenantInfo, "$sessionId", TRUE, TRUE);
```

FIDO device registration options
```
require_once("./BIDWebAuthn.php");

$tenantInfo = array("dns" => "$dns", "communityName" => "$communityName", "licenseKey" => "$licenseKey");

//authenticatorSelection

// if your device is a security key, such as a YubiKey:
"attestation" => "direct",
    "authenticatorSelection" => array(
        "requireResidentKey" => true
    )

// if your device is a platform authenticator, such as TouchID
"attestation" => "direct",
    "authenticatorSelection" => array(
        "authenticatorAttachment" => "platform"
    )

// if your device is a MacBook
"attestation" => "none"

// sample options request object for YubiKey
$optionsRequest = array(
    "displayName" => "<displayname>",
    "username" => "<username>",
    "dns" => "<current domain>",
    "attestation" => "<attestation>",
    "authenticatorSelection" => array(
        "requireResidentKey" => true
    )
);
$attestationOptionsResponse = BIDWebAuthn::fetchAttestationOptions($tenantInfo, $optionsRequest);
```

FIDO device registration result
```
require_once("./BIDWebAuthn.php");

$tenantInfo = array("dns" => "$dns", "communityName" => "$communityName", "licenseKey" => "$licenseKey");

$optionsRequest = array(
    "rawId" => "<rawId>",
    "response" => array(
        "attestationObject" => "<attestationObject>",
        "getAuthenticatorData" => "<getAuthenticatorData>",
        "getPublicKey" => "<getPublicKey>",
        "getPublicKeyAlgorithm" => "<getPublicKeyAlgorithm>",
        "getTransports" => "<getTransports>",
        "clientDataJSON" => "<clientDataJSON>"
    ),
    "authenticatorAttachment" => "<authenticatorAttachment>",
    "getClientExtensionResults" => "<getClientExtensionResults>",
    "id" => "<id>",
    "type" => "<type>",
    "dns" => "<current domain>"
);

$attestationResultResponse = BIDWebAuthn::submitAttestationResult($tenantInfo, $optionsRequest);
```

Request Email verification link
```
require_once("./BIDAccessCodes.php");

$tenantInfo = array("dns" => "$dns", "communityName" => "$communityName", "licenseKey" => "$licenseKey");

$requestEmailVerificationResponse = BIDAccessCode::requestEmailVerificationLink($tenantInfo, "$emailTo", "$emailTemplateB64OrNull", "$emailSubjectOrNull", "$createdBy", "$ttl_seconds_or_null");
```

Verify and Redeem Email verification link
```
require_once("./BIDAccessCodes.php");

$tenantInfo = array("dns" => "$dns", "communityName" => "$communityName", "licenseKey" => "$licenseKey");

$redeemVerificationCodeResponse = BIDAccessCode::verifyAndRedeemEmailVerificationCode($tenantInfo, "$code");
```
