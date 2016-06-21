<?php

/**
 * @file
 * Provides \Drupal\pubkey_encrypt\Plugin\LoginCredentialsProviderBase.
 */

namespace Drupal\pubkey_encrypt\Plugin;

use Drupal\Component\Plugin\PluginBase;

abstract class LoginCredentialsProviderBase extends PluginBase implements LoginCredentialsProviderInterface {

  public function getName() {
    return $this->pluginDefinition['name'];
  }

  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

}
