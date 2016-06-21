<?php

/**
 * @file
 * Provides \Drupal\pubkey_encrypt\Plugin\AsymmetricKeysGeneratorBase.
 */

namespace Drupal\pubkey_encrypt\Plugin;

use Drupal\Component\Plugin\PluginBase;

abstract class AsymmetricKeysGeneratorBase extends PluginBase implements AsymmetricKeysGeneratorInterface {

  public function getName() {
    return $this->pluginDefinition['name'];
  }

  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

}