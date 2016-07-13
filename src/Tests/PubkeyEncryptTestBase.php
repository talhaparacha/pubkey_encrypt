<?php

namespace Drupal\pubkey_encrypt\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Base class for Pubkey Encrypt tests.
 */
abstract class PubkeyEncryptTestBase extends WebTestBase {

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
   * Initialize the module manually with default plugins.
   */
  protected function initializePubkeyEncrypt() {
    $config = \Drupal::service('config.factory')
      ->getEditable('pubkey_encrypt.initialization_settings');
    $config->set('module_initialized', 1)
      ->set('asymmetric_keys_generator', 'openssl_default')
      ->set('asymmetric_keys_generator_configuration', array('key_size' => '2048'))
      ->set('login_credentials_provider', 'user_passwords')
      ->save();
    // During testing, we cannot call the module initialization function
    // directly because it uses the Batch API. Hence doing the vital steps
    // manually.
    \Drupal::service('pubkey_encrypt.pubkey_encrypt_manager')
      ->refreshReferenceVariables();
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple();
    foreach ($users as $user) {
      \Drupal::service('pubkey_encrypt.pubkey_encrypt_manager')
        ->initializeUserKeys($user);
    }
  }

}
