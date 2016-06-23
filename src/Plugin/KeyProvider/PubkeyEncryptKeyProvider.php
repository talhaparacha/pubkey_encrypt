<?php

/**
 * @file
 * Contains \Drupal\pubkey_encrypt\Plugin\KeyProvider\PubkeyEncryptKeyProvider.
 */

namespace Drupal\pubkey_encrypt\Plugin\KeyProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyProviderBase;
use Drupal\key\Plugin\KeyPluginFormInterface;
use Drupal\key\Plugin\KeyProviderSettableValueInterface;
use Drupal\key\Exception\KeyValueNotSetException;
use Drupal\key\KeyInterface;
use Drupal\user\Entity\Role;
use Drupal\Core\Session\AccountInterface;

/**
 * Adds a key provider as per the requirements of Pubkey Encrypt module.
 *
 * @KeyProvider(
 *   id = "pubkey_encrypt",
 *   label = @Translation("Pubkey Encrypt"),
 *   description = @Translation("Stores and Retrieves the key as per the requirements of Pubkey Encrypt module."),
 *   storage_method = "pubkey_encrypt",
 *   key_value = {
 *     "accepted" = TRUE,
 *     "required" = FALSE
 *   }
 * )
 */
class PubkeyEncryptKeyProvider extends KeyProviderBase implements KeyPluginFormInterface, KeyProviderSettableValueInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $roleOptions = [];
    foreach (Role::loadMultiple() as $role) {
      $roleOptions[$role->id()] = $role->label();
    }
    unset($roleOptions[AccountInterface::ANONYMOUS_ROLE]);
    unset($roleOptions[AccountInterface::AUTHENTICATED_ROLE]);

    $form['role'] = [
      '#type' => 'select',
      '#title' => $this->t('Role'),
      '#description' => $this->t('Share keys would be generated and stored for all the users in this Role.'),
      '#options' => $roleOptions,
      '#default_value' => $this->getConfiguration()['role'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValue(KeyInterface $key) {
    $key_value = '';

    $currentUserId = \Drupal::currentUser()->id();

    // Retrieve the actual key value from the Share key of user.
    $shareKeys = $this->configuration['share_keys'];
    if (isset($shareKeys[$currentUserId])) {
      $shareKey = $shareKeys[$currentUserId];

      // The Private key of the user should be here, if the user is logged in.
      $tempstore = \Drupal::service('user.private_tempstore')
        ->get('pubkey_encrypt');
      $privateKey = $tempstore->get('private_key');


      // Delegate the task of encryption to perspective plugin.
      $config = \Drupal::config('pubkey_encrypt.initialization_settings');
      $manager = \Drupal::service('plugin.manager.pubkey_encrypt.asymmetric_keys');
      $plugin = $manager
        ->createInstance($config->get('asymmetric_keys_generator'));
      $key_value = $plugin
        ->DecryptWithPrivateKey($shareKey, $privateKey);
    }

    return $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public function setKeyValue(KeyInterface $key, $key_value) {
    $role = $this->configuration['role'];
    $shareKeys = [];
    $users = \Drupal::service('entity_type.manager')
      ->getStorage('user')
      ->loadMultiple();
    // Each user will have a Share key.
    foreach ($users as $user) {
      // Generate Share keys for all users from the specified role. Also
      // generate a Share key for any user with "administer_permissions"
      // permission since he should be given complete complete control over
      // all keys..
      if ($user->hasRole($role) || $user->hasPermission('administer permissions')) {
        $userId = $user->get('uid')->getString();
        $publicKey = $user->get('field_public_key')->getString();

        // Delegate the task of encryption to perspective plugin.
        $config = \Drupal::config('pubkey_encrypt.initialization_settings');
        $manager = \Drupal::service('plugin.manager.pubkey_encrypt.asymmetric_keys');
        $plugin = $manager
          ->createInstance($config->get('asymmetric_keys_generator'));
        $shareKey = $plugin
          ->encryptWithPublicKey($key_value, $publicKey);
        $shareKeys[$userId] = $shareKey;
      }
    }

    // Store the Share keys.
    if ($this->configuration['share_keys'] = $shareKeys) {
      return TRUE;
    }
    else {
      throw new KeyValueNotSetException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteKeyValue(KeyInterface $key) {
    // Nothing needs to be done, since the value will have been deleted
    // with the Key entity.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function obscureKeyValue($key_value, array $options = []) {
    // Key values are not obscured when this provider is used.
    return $key_value;
  }

}
