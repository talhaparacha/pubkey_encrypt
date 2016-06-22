<?php

/**
 * @file
 * Contains \Drupal\pubkey_encrypt\Form\PubkeyEncryptSettingsForm.
 */

namespace Drupal\pubkey_encrypt\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder for the Pubkey Encrypt settings admin page.
 */
class PubkeyEncryptSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pubkey_encrypt_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
