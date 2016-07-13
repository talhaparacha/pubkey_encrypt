<?php

/**
 * @file
 * Provides \Drupal\pubkey_encrypt\Plugin\AsymmetricKeysGeneratorInterface
 */

namespace Drupal\pubkey_encrypt\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines an interface for ice cream flavor plugins.
 */
interface AsymmetricKeysGeneratorInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Return name of the asymmetric keys generator plugin.
   *
   * @return string
   */
  public function getName();

  /**
   * Return description of the asymmetric keys generator plugin.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Generate and return asymmetric keys in the form of an array indexed with
   * "public_key" and "private_key" or NULL upon failure.
   *
   * @return string[]|NULL
   */
  public function generateAsymmetricKeys();


  /**
   * Return encrypted data.
   *
   * @return string
   */
  public function encryptWithPublicKey($original_data, $public_key);

  /**
   * Return decrypted data.
   *
   * @return string
   */
  public function decryptWithPrivateKey($encrypted_data, $private_key);

}