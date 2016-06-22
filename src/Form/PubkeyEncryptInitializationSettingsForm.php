<?php

/**
 * @file
 * Contains \Drupal\pubkey_encrypt\Form\PubkeyEncryptInitializationSettingsForm.
 */

namespace Drupal\pubkey_encrypt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pubkey_encrypt\Plugin\AsymmetricKeysManager;
use Drupal\pubkey_encrypt\Plugin\LoginCredentialsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder for the Pubkey Encrypt settings admin page.
 */
class PubkeyEncryptInitializationSettingsForm extends ConfigFormBase {

  /**
   * The plugin manager for asymmetric keys.
   *
   * @var \Drupal\pubkey_encrypt\Plugin\AsymmetricKeysManager
   */
  protected $asymmetricKeysManager;

  /**
   * The plugin manager for login credentials.
   *
   * @var \Drupal\pubkey_encrypt\Plugin\LoginCredentialsManager
   */
  protected $loginCredentialsManager;

  /**
   * Constructs a PubkeyEncryptInitializationSettingsForm object.
   *
   * @param \Drupal\pubkey_encrypt\Plugin\AsymmetricKeysManager $asymmetric_keys_manager
   * @param \Drupal\pubkey_encrypt\Plugin\LoginCredentialsManager $login_credentials_manager
   */
  public function __construct(AsymmetricKeysManager $asymmetric_keys_manager, LoginCredentialsManager $login_credentials_manager) {
    $this->asymmetricKeysManager = $asymmetric_keys_manager;
    $this->loginCredentialsManager = $login_credentials_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.pubkey_encrypt.asymmetric_keys'),
      $container->get('plugin.manager.pubkey_encrypt.login_credentials')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pubkey_encrypt_admin_initialization_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pubkey_encrypt.initialization_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('pubkey_encrypt.initialization_settings');

    // Options for Asymmetric Keys Generator plugin.
    $options = [];
    foreach ($this->asymmetricKeysManager->getDefinitions() as $plugin) {
      $options[$plugin['id']] = (string)$plugin['name'];
    }
    $form['asymmetric_keys_generator'] = array(
      '#type' => 'select',
      '#title' => $this->t('Asymmetric Keys Generator'),
      '#description' => $this->t('Select the plugin which Pubkey Encrypt should use for operations involving asymmetric keys.'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $config->get('asymmetric_keys_generator'),
      // Don't allow the plugin to change if the module has been initialized.
      '#disabled' => $config->get('module_initialized'),
    );

    // Options for Login Credentials Provider plugin.
    $options = [];
    foreach ($this->loginCredentialsManager->getDefinitions() as $plugin) {
      $options[$plugin['id']] = (string)$plugin['name'];
    }
    $form['login_credentials_provider'] = array(
      '#type' => 'select',
      '#title' => $this->t('Login Credentials Provider'),
      '#description' => $this->t('Select the plugin which Pubkey Encrypt should use for operations involving login credentials.'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $config->get('login_credentials_provider'),
      // Don't allow the plugin to change if the module has been initialized.
      '#disabled' => $config->get('module_initialized'),
    );

    // Overwrite submit button provided by ConfigFormBase.
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Initialize module'),
      '#button_type' => 'primary',
    );

    // Remove the submit button from form if the module has already been
    // initialized and notify the user about it.
    if ($config->get('module_initialized')) {
      unset($form['actions']['submit']);
      drupal_set_message($this->t('The module has been initialized. You cannot change these settings now.'), 'warning');
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate that the Asymmetric Keys Generator plugin is working.
    $asymmetric_keys_generator = $this
      ->asymmetricKeysManager
      ->createInstance($form_state->getValue('asymmetric_keys_generator'));
    $keys = $asymmetric_keys_generator->generateAsymmetricKeys();
    if (in_array('', $keys) || in_array('NULL', $keys)) {
      $form_state->setErrorByName('asymmetric_keys_generator', 'The Asymmetric Keys Generator plugin is not working.');
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the configuration.
    $this->config('pubkey_encrypt.initialization_settings')
      ->set('module_initialized', 1)
      ->set('asymmetric_keys_generator', $form_state->getValue('asymmetric_keys_generator'))
      ->set('login_credentials_provider', $form_state->getValue('login_credentials_provider'))
      ->save();
  }

}
