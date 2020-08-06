# Automation Bundle

Add custom features from core to bundle.

## SSO keycloak features
                                                                       
+ UX changes
 
 /s/users
  
 - Remove Add User button
 - Remove two user from begining
 
 s/users/edit/{id}
 s/account
 - disable inputs - firstname, lastname, username, email
 - remove password/password repeat
 
  + core changes: composer.json, bundles_local.php, config_local.php, security_local.php  https://github.com/Webmecanik/Automation_dev/commit/c940a2fa13ed725a65c47df137892abed854b2ee
 