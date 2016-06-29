<?php

/**
 * @file
 * Provides \Drupal\pubkey_encrypt\Plugin\AsymmetricKeysGeneratorBase.
 */

namespace Drupal\pubkey_encrypt\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Provides a base class for AsymmetricKeysGenerator plugins.
 */
abstract class AsymmetricKeysGeneratorBase extends PluginBase implements AsymmetricKeysGeneratorInterface {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

}