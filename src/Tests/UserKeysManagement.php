<?php

/**
 * @file
 * Definition of Drupal\password_policy\Tests\PasswordManualReset.
 */

namespace Drupal\pubkey_encrypt\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the management of users asymmetric keys.
 *
 * @group pubkey_encrypt
 */
class UserKeysManagement extends WebTestBase {

  public static $modules = array(
    'key',
    'encrypt',
    'pubkey_encrypt',

  );

  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Have the module initialized.
    $this->initializePubkeyEncrypt();
  }

  /**
   * Test initialization and protection of fields upon new user registration.
   */
  public function testNewUserRegistration() {
    // Create a user.
    $user = $this->drupalCreateUser(array());

    // Verify the initialization of fields.
    $this->assertEqual($user->get('field_private_key_protected')->getString(), "0", "User keys have not been protected initially");
    $this->assertFalse($user->get('field_private_key')->isEmpty(), "Private key has been initialized");
    $this->assertFalse($user->get('field_public_key')->isEmpty(), "Public key has been initialized");

    // First time user login.
    $this->drupalLogin($user);

    // Reload the user entity again.
    \Drupal::entityTypeManager()->getStorage('user')->resetCache();
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($user->id());

    // Verify the protection of fields.
    $this->assertEqual($user->get('field_private_key_protected')->getString(), "1", "User keys have been protected after first time login");
  }

  /**
   * Test the temporary storage of a user Private key upon login.
   */
  public function testPrivateKeyTemporaryStorage() {
    // Create a user.
    $user = $this->drupalCreateUser(array());

    // Store the original Private key of user.
    $privateKey = $user->get('field_private_key')->getString();

    // First time user login.
    $this->drupalLogin($user);

    // Fetch the temporarily stored Private key.
    $tempstore = \Drupal::service('user.private_tempstore')
      ->get('pubkey_encrypt');
    $storedPrivateKey = $tempstore->get('private_key');

    $this->assertEqual($storedPrivateKey, $privateKey, "Private key is temporarily stored upon a user login.");
  }

  /**
   * Test re-protection of a user Private key upon credentials change.
   */
  public function testCredentialsChange() {
    $user = $this->drupalCreateUser(array('change own username'));
    $this->drupalLogin($user);

    $oldPassword = $user->pass_raw;

    // Reload the user entity.
    $user = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadUnchanged($user->id());

    // Fetch the Private key of user, protected with old credentials atm.
    $protectedPrivateKeyOld = $user
      ->get('field_private_key')
      ->getString();

    // Fetch the temporarily stored original Private key.
    $tempstore = \Drupal::service('user.private_tempstore')
      ->get('pubkey_encrypt');
    $storedPrivateKeyOld = $tempstore->get('private_key');

    // Change user credentials.
    $edit = array();
    $edit['pass[pass1]'] = $newPassword = $this->randomMachineName();
    $edit['pass[pass2]'] = $newPassword;
    $edit['current_pass'] = $oldPassword;
    $this->drupalPostForm("user/" . $user->id() . "/edit", $edit, t('Save'));
    $this->assertRaw(t("The changes have been saved."));

    // Login the user again, this time with his new credentials.
    $this->drupalLogout();
    $user->pass_raw = $newPassword;
    $this->drupalLogin($user);

    // Reload the user entity again.
    $user = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadUnchanged($user->id());

    // Fetch the Private key of user, now protected with new credentials.
    $protectedPrivateKeyNew = $user
      ->get('field_private_key')
      ->getString();

    // Fetch the temporarily stored original Private key.
    $storedPrivateKey = $tempstore->get('private_key');

    $this->assertNotEqual($protectedPrivateKeyNew, $protectedPrivateKeyOld, "Credentials change re-protects the Private key of a user.");
    $this->assertEqual($storedPrivateKey, $storedPrivateKeyOld, "Credentials change does not modify the Private key of a user.");
  }

  protected function initializePubkeyEncrypt() {
    $config = \Drupal::service('config.factory')
      ->getEditable('pubkey_encrypt.initialization_settings');
    $config->set('module_initialized', 1)
      // Use default plugins provided by the module during initialization.
      ->set('asymmetric_keys_generator', 'openssl_default')
      ->set('asymmetric_keys_generator_configuration', array('key_size' => '2048'))
      ->set('login_credentials_provider', 'user_passwords')
      ->save();
    \Drupal::service('pubkey_encrypt.pubkey_encrypt_manager')
      ->initializeModule();
  }

}
