<?php

/**
 * @file
 * Contains \Drupal\pubkey_encrypt\KeysManager.
 */

namespace Drupal\pubkey_encrypt;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\user\UserInterface;

/**
 * Handles users' Public/Private key pairs.
 */
class KeysManager {
  protected $entityTypeManager;
  protected $tempStore;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, PrivateTempStoreFactory $tempStoreFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->tempStore = $tempStoreFactory->get('pubkey_encrypt');
  }

  /*
   * Initialize all users' keys.
   */
  public function initializeAllUserKeys() {
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple();

    foreach ($users as $user) {
      $this->initializeUserKeys($user);
    }
  }

  /*
   * Initialize a specific user's keys.
   */
  public function initializeUserKeys(UserInterface $user) {
    // Generate a Public/Private key pair.
    $config = array(
      "config" => "C:/xampp/apache/bin/openssl.cnf",
      "digest_alg" => "sha512",
      "private_key_bits" => 4096,
      "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );
    // Create the private and public key.
    $res = openssl_pkey_new($config);
    // Extract the private key.
    openssl_pkey_export($res, $privKey, NULL, $config);
    // Extract the public key.
    $pubKey = openssl_pkey_get_details($res);
    $pubKey = $pubKey["key"];

    $privateKey = $privKey;
    $publicKey = $pubKey;

    // Set Public/Private keys.
    $user
      ->set('field_public_key', $publicKey)
      ->set('field_private_key', $privateKey)
      ->set('field_private_key_protected', 0)
      ->save();
  }

  /*
   * Protect a user keys with his credentials.
   */
  public function protectUserKeys(UserInterface $user, $credentials) {
    $isProtected = $user->get('field_private_key_protected')
      ->get(0)
      ->getValue();
    $isProtected = $isProtected['value'];
    // Ensure that the keys have not already been protected.
    if (!$isProtected) {
      // Get original private key.
      $privateKey = $user->get('field_private_key')
        ->get(0)
        ->getValue();
      $privateKey = $privateKey['value'];

      // Protect the original private key.
      // We don't really need the IV to be random here, hence all zeros.
      $protectedPrivateKey = openssl_encrypt($privateKey, 'AES-128-CBC', $credentials, 0, '0000000000000000');

      // Set new values for the fields.
      $user
        ->set('field_private_key', $protectedPrivateKey)
        ->set('field_private_key_protected', 1)
        ->save();
    }
  }

  /*
  * Fetch the private key of a user in its original form
  */
  public function getOriginalPrivateKey(UserInterface $user, $credentials) {
    // Get stored private key.
    $privateKey = $user->get('field_private_key')
      ->get(0)
      ->getValue();
    $privateKey = $privateKey['value'];

    // Get stored keys status.
    $isProtected = $user->get('field_private_key_protected')
      ->get(0)
      ->getValue();
    $isProtected = $isProtected['value'];

    if($isProtected) {
      // Decrypt protected private key using user credentials and return.
      // We don't really need the IV to be random here, hence all zeros.
      $originalPrivateKey  = openssl_decrypt($privateKey, 'AES-128-CBC', $credentials, 0, '0000000000000000');
      return $originalPrivateKey;
    }
    else {
      return $privateKey;
    }
  }

  /*
   * Handle a change in user credentials.
   */
  public function userCredentialsChanged($userId, $currentCredentials, $newCredentials) {
    $user = $this->entityTypeManager->getStorage('user')->load($userId);

    // Grab the original private key.
    $originalPrivateKey = $this->getOriginalPrivateKey($user, $currentCredentials);

    // Store it in original form.
    $user
      ->set('field_private_key', $originalPrivateKey)
      ->set('field_private_key_protected', 0)
      ->save();

    // Protect with new credentials.
    $this->protectUserKeys($user, $newCredentials);
  }

  /*
   * Fetch and temporarily store user's private key upon login.
   */
  public function userLoggedIn(UserInterface $user, $credentials) {
    $isProtected = $user->get('field_private_key_protected')
      ->get(0)
      ->getValue();
    $isProtected = $isProtected['value'];

    // If it was the first-time login of a user, protect his keys first.
    if (!$isProtected) {
      $this->protectUserKeys($user, $credentials);
    }

    $originalPrivateKey = $this->getOriginalPrivateKey($user, $credentials);

    // Store private key in tempstore.
    $this->tempStore->set('private_key', $originalPrivateKey);
  }
}