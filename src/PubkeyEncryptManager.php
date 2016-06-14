<?php

/**
 * @file
 * Contains \Drupal\pubkey_encrypt\PubkeyEncryptManager.
 */

namespace Drupal\pubkey_encrypt;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\user\UserInterface;
use Drupal\user\Entity\Role;
use Drupal\Core\Session\AccountInterface;

/**
 * Handles users' Public/Private key pairs.
 */
class PubkeyEncryptManager {
  protected $entityTypeManager;
  protected $tempStore;

  /*
   * Constructor with dependencies injected to it.
   */
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
    // Get stored keys status.
    $isProtected = $user->get('field_private_key_protected')->getString();

    // Ensure that the keys have not already been protected.
    if (!$isProtected) {
      // Get original private key.
      $privateKey = $user->get('field_private_key')->getString();

      // Protect the original private key.
      // Since we're encrypting keys which are themselves pretty random, we
      // don't really need the IV to be random here too. Hence using all zeros.
      $protectedPrivateKey = openssl_encrypt($privateKey, 'AES-128-CBC', $credentials, 0, '0000000000000000');

      // Set new values for the fields.
      $user
        ->set('field_private_key', $protectedPrivateKey)
        ->set('field_private_key_protected', 1)
        ->save();
    }
  }

  /*
   * Fetch the private key of a user in its original form.
   */
  public function getOriginalPrivateKey(UserInterface $user, $credentials) {
    // Get stored private key.
    $privateKey = $user->get('field_private_key')->getString();

    // Get stored keys status.
    $isProtected = $user->get('field_private_key_protected')->getString();

    if ($isProtected) {
      // Decrypt protected private key using user credentials and return.
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
    $isProtected = $user->get('field_private_key_protected')->getString();

    // If it was the first-time login of a user, protect his keys first.
    if (!$isProtected) {
      $this->protectUserKeys($user, $credentials);
    }

    $originalPrivateKey = $this->getOriginalPrivateKey($user, $credentials);

    // Store private key in tempstore.
    $this->tempStore->set('private_key', $originalPrivateKey);
  }

  /*
   * Generate a Role key.
  */
  public function generateRoleKey(Role $role) {
    $role_id = $role->id();
    $role_label = $role->label();

    // Generate a key; at this stage the key hasn't been configured completely.
    $values = [];
    $values["id"] = $role_id . "_role_key";
    $values["label"] = $role_label . " Role key";
    $values["description"] = $role_label . " Role key used by Pubkey Encrypt";
    $values["key_type"] = "encryption";
    $values["key_type_settings"]["key_size"] = "128";
    $values["key_input"] = "none";
    $values["key_provider"] = "pubkey_encrypt";
    $values["key_provider_settings"]["role"] = $role_id;
    \Drupal::entityTypeManager()
      ->getStorage('key')
      ->create($values)
      ->save();

    // Fetch the newly generated key from key repository.
    $new_key = \Drupal::service('key.repository')
      ->getKey($role_id . "_role_key");

    // Generate a value for the key.
    $new_key_value = $new_key
      ->getKeyType()
      ->generateKeyValue(array("key_size" => "128"));

    // Save the key with new value.
    // This would cause our Key Provider to save it as per the business logic.
    $new_key->setKeyValue($new_key_value);
    $new_key->save(\Drupal::entityTypeManager()->getStorage('key'));
  }

  /*
   * Initialize Role keys upon module installation.
   */
  public function initializeRoleKeys() {
    // Generate a Role key per role.
    foreach (Role::loadMultiple() as $role) {
      if ($role->id() != AccountInterface::ANONYMOUS_ROLE && $role->id() != AccountInterface::AUTHENTICATED_ROLE) {
        $this->generateRoleKey($role);
      }
    }
  }

  /*
   * Update a Role key.
   */
  public function updateRoleKey(Role $role) {
    // Since only root user has complete control over all keys, so allow for
    // Role key updates only if the root user is logged in.
    if (\Drupal::currentUser()->id() == '1') {
      // Since we don't have a Role key for "authenticated" role.
      if ($role->id() != AccountInterface::AUTHENTICATED_ROLE) {
        // Fetch the Role key.
        $key = \Drupal::service('key.repository')
          ->getKey($role->id() . "_role_key");

        // Re-save the key with same value.
        // This would case our Key Provider to cater for the update.
        $key->setKeyValue($key->getKeyValue());
        $key->save(\Drupal::entityTypeManager()->getStorage('key'));
      }
    }
  }

  /*
   * Delete a Role key upon role removal.
   */
  public function deleteRoleKey(Role $role) {
    \Drupal::service('key.repository')
      ->getKey($role->id() . "_role_key")
      ->delete();
  }

}
