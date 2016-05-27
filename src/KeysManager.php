<?php

/**
 * @file
 * Contains \Drupal\pubkey_encrypt\KeysManager.
 */

namespace Drupal\pubkey_encrypt;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\user\UserInterface;

/**
 * Handles users' Public/Private key pairs.
 */
class KeysManager
{
  protected $entityTypeManager;
  protected $tempStore;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, PrivateTempStoreFactory $tempStoreFactory)
  {
    $this->entityTypeManager = $entityTypeManager;
    $this->tempStore = $tempStoreFactory->get('pubkey_encrypt');
  }

  /*
   * Initialize all users' keys.
   */
  public function initializeAllUserKeys(){
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple();

    foreach($users as $user){
      $this->initializeUserKeys($user);
    }
  }

  /*
   * Initialize a specific user's keys.
   */
  public function initializeUserKeys(UserInterface $user){
    $privateKey = 'blah';
    $publicKey = 'hello';

    // Set Public/Private keys.
    $user
      ->set('field_public_key',$publicKey)
      ->set('field_private_key',$privateKey)
      ->set('field_private_key_protected',0)
      ->save();
  }

  /*
   * Protect a user keys with his credentials.
   */
  public function protectUserKeys(UserInterface $user, $credentials){

  }

  /*
   * Handle a change in user credentials.
   */
  public function userCredentialsChanged($userId, $currentCredentials, $newCredentials){
    $user = $this->entityTypeManager->getStorage('user')->load($userId);
    $user = $user;
  }

  /*
   * Fetch and temporarily store user's private key upon login.
   */
  public function userLoggedIn(UserInterface $user, $credentials){
    $isProtected = $user->get('field_private_key_protected')->get(0)->getValue();
    $isProtected = $isProtected['value'];

    $privateKey = $user->get('field_private_key')->get(0)->getValue();
    $privateKey = $privateKey['value'];

    // If it was the first-time login of a user, protect his keys first.
    if(!$isProtected){
      $this->protectUserKeys($user, $credentials);
    }

    // Store private key in tempstore.
    $this->tempStore->set('private_key',$privateKey);
  }
}
