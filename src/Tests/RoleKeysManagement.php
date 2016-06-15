<?php

/**
 * @file
 * Definition of Drupal\password_policy\Tests\PasswordManualReset.
 */

namespace Drupal\pubkey_encrypt\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the management of Role keys.
 *
 * @group pubkey_encrypt
 */
class RoleKeysManagement extends WebTestBase {

  public static $modules = array(
    'key',
    'encrypt',
    'pubkey_encrypt',

  );

  protected $profile = 'minimal';

  /**
   * Test Role keys.
   */
  public function testRoleKeys() {
    // Key Repository service.
    $key_repository = \Drupal::service('key.repository');

    // Create a new role.
    $new_role_id = $this->drupalCreateRole(array());

    // Test that a Role key has been created.
    $new_role_key = $key_repository->getKey($new_role_id . "_role_key");
    $this->assertNotNull($new_role_key, "Role key gets created upon the creation of a role");

    // Create two new users.
    $user1 = $this->drupalCreateUser(array());
    $user2 = $this->drupalCreateUser(array());

    // Login with root user.
    $this->drupalLogin($this->rootUser);

    // Add user1 to the newly created role.
    $edit = array();
    $edit['roles[' . $new_role_id . ']'] = $new_role_id;
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));

    // Test user1 is able to access the Role key because he is in the role.
    $this->drupalLogin($user1);
    $role_key_value = $key_repository
      ->getKey($new_role_id . "_role_key")
      ->getKeyValue(TRUE);

    $this->assertNotEqual('', $role_key_value, "A user is able to access Role key value if he is in the role");

    // Test user2 is not able to access the Role key because he is not in the
    // role.
    $this->drupalLogin($user2);
    $role_key_value = $key_repository
      ->getKey($new_role_id . "_role_key")
      ->getKeyValue(TRUE);

    $this->assertEqual('', $role_key_value, "A user is not able to access Role key value if he is not in the role");

    // Remove the role.
    \Drupal::entityTypeManager()
      ->getStorage('user_role')
      ->delete(array(Role::load($new_role_id)));

    // Test that the Role key has been deleted.
    $new_role_key = $key_repository->getKey($new_role_id . "_role_key");
    $this->assertNull($new_role_key, "Role key gets deleted upon the deletion of a role");
  }

}
