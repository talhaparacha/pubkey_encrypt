<?php

/**
 * @file
 * Provides \Drupal\pubkey_encrypt\Plugin\LoginCredentialsProviderInterface
 */

namespace Drupal\pubkey_encrypt\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for ice cream flavor plugins.
 */
interface LoginCredentialsProviderInterface extends PluginInspectionInterface {

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
   * Fetch relevant credentials from the user login form which Pubkey Encrypt
   * should use during Encryption/Decryption phases.
   *
   * @param $form
   *   Nested array of form elements that comprise the user login form.
   * @param $form_state
   *   Current state of user login form.
   *
   * @return string
   */
  public function fetchLoginCredentials($form, FormStateInterface &$form_state);

}
