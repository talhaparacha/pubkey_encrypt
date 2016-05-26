<?php

/**
 * @file
 * Contains \Drupal\pubkey_encrypt\KeysManager.
 */

namespace Drupal\pubkey_encrypt;

use Drupal\user\UserInterface;

/**
 * Handles users' Public/Private key pairs.
 */
class KeysManager
{
  /*
   * Initialize all users' keys.
   */
  public function initializeAllUserKeys(){
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple();

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
      ->set('pubkey',$publicKey)
      ->set('privkey',$privateKey)
      ->set('pkey_protected',0)
      ->save();
  }

  /*
   * Protect user keys with his credentials.
   */
  public function protectUserKeys(){

  }
}
