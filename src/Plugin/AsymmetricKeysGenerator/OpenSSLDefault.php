<?php

/**
 * @file
 * Contains \Drupal\pubkey_encrypt\Plugin\AsymmetricKeysGenerator\OpenSSLDefault.
 */

namespace Drupal\pubkey_encrypt\Plugin\AsymmetricKeysGenerator;

use Drupal\pubkey_encrypt\Plugin\AsymmetricKeysGeneratorBase;

/**
 * Provides a default asymmetric keys generator based on OpenSSL.
 *
 * @AsymmetricKeysGenerator(
 *   id = "openssl_default",
 *   name = @Translation("OpenSSL Default"),
 *   description = @Translation("RSA-based 4096-bit keys generated via OpenSSL using sha512 digest algorithm.")
 * )
 */
class OpenSSLDefault extends AsymmetricKeysGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function generateAsymmetricKeys() {
    // Generate a Public/Private key pair.
    $config = array(
      "digest_alg" => "sha512",
      "private_key_bits" => 4096,
      "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );
    $res = openssl_pkey_new($config);
    // Extract the private key.
    openssl_pkey_export($res, $private_key, NULL, $config);
    // Extract the public key.
    $public_key = openssl_pkey_get_details($res);
    $public_key = $public_key["key"];

    // Return the keys.
    return array(
      "public_key" => $public_key,
      "private_key" => $private_key,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function encryptWithPublicKey($original_data, $public_key) {
    openssl_public_encrypt($original_data, $encrypted, $public_key);
    return $encrypted;
  }

  /**
   * {@inheritdoc}
   */
  public function decryptWithPrivateKey($encrypted_data, $private_key) {
    openssl_private_decrypt($encrypted_data, $decrypted, $private_key);
    return $decrypted;
  }

}
