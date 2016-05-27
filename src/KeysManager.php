<?php

/**
 * @file
 * Contains \Drupal\pubkey_encrypt\KeysManager.
 */

namespace Drupal\pubkey_encrypt;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;

/**
 * Handles users' Public/Private key pairs.
 */
class KeysManager
{
  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager)
  {
    $this->entityTypeManager = $entityTypeManager;
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

    //Set Public/Private keys.
    $user
      ->set('field_public_key',$publicKey)
      ->set('field_private_key',$privateKey)
      ->set('field_private_key_protected',0)
      ->save();
  }

  /*
   * Protect user keys with his credentials.
   */
  public function protectUserKeys(){

  }
}
