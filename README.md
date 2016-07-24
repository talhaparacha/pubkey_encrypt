# Pubkey Encrypt for Drupal 8

[![Build Status](https://travis-ci.org/d8-contrib-modules/pubkey_encrypt.svg?branch=8.x)](https://travis-ci.org/d8-contrib-modules/pubkey_encrypt)

Provides support for encrypting data with users login-credentials. The mechanism is based on ownCloud's Data Encryption Model.

Pubkey Encrypt leverages the Key & Encrypt modules for integrating it's encryption mechanism into a Drupal website. Accordingly, **the module generates an Encryption Profile for each role in the website.** This allows an administrator to select the set of users whose login-credentials should be used during the encryption/decryption processes. For example, if an administrator choses the "Premium User Encryption Profile" while encrypting the data, then the login-credentials of all users from the "Premium User" role would get used. Consequently, only these users would then be able to decrypt the encrypted data.

* For encrypting field data using this mechanism, Field Encrypt module should be used along with this one.
* For encrypting files using this mechanism, Encrypted Files module should be used along with this one.

## Module Documentation
1) [See the video demonstrating a sample use-case for Pubkey Encrypt.](https://vimeo.com/174876122)

2) [Read the user stories for step-by-step instructions on how to use this module](documentation/UserStories.pdf).

3) [Head over to the architecture document for a detailed technical discussion on how this module works](documentation/ArchitectureDoc.pdf).


## Pluggable Systems
Pubkey Encrypt leverages the Drupal 8 Plugin API to allow for extensibility for customized needs. Accordingly, it provides two plugin types:

### Login Credentials Provider:
The module encrypts data with users login-credentials, but the type of credentials to use (i.e. Password, PIN etc.) depends upon the "Login Credentials Provider" plugin selected when initializing the module.

For default use, Pubkey Encrypt provides a user-passwords based Login Credentials Provider as a submodule within the module.

### Asymmetric Keys Generator:
The module generates Public/Private keys for all users in the webite. But the library to use for generating these keys (i.e. OpeSSL, PHPSecLib etc.) depends upon the "Asymmetric Keys Generator" plugin selected when initializing the module.

For default use, Pubkey Encrypt provides an OpenSSL-based Asymmetric Keys Generator and a PHPSecLib-based Asymmetric Keys Generator as submodules within the module.

## Caution
With the module enabled, users won't be able to change their login-credentials without providing the exisiting credentials. This means that password-reset functionality and any related features won't work.

**Furthermore, Pubkey Encrypt requires that only users with "administer permissions" permission should be allowed to add/remove other users to/from any role.**

## FAQs

### What if a user forgets his login-credentials?
Such a user won't be able to decrypt the encrypted data. In fact that's why Pubkey Encrypt does not allow any user to change his credentials without providing the existing credentials.

### How secure is Pubkey Encrypt?
Theoretically speaking, pretty secure since the data gets encrypted with users login-credentials. And the users login-credentials never get stored anywhere. So even if a hacker gets complete access to the database, still he won't be able to decrypt any encrypted data stored there because he won't know the users login-credentials.

But Pubkey Encrypt directly depends on the strength of users login-credentials. So if a user has chosen a weak credential i.e a password like "12345", then the Pubkey Encrypt mechanism can be easily broken.


