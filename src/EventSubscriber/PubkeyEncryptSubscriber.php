<?php

namespace Drupal\pubkey_encrypt\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Pubkey Encrypt event subscriber.
 */
class PubkeyEncryptSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('tempStoreKey');
    return $events;
  }

  /**
   * Temporarily store the Private key for a logged-in user, in a cookie.
   *
   * This cookie will be later used by PubkeyEncryptKeyProvider during key
   * retrievals.
   */
  public function tempStoreKey(FilterResponseEvent $event) {
    $pubkey_encrypt_manager = \Drupal::service('pubkey_encrypt.pubkey_encrypt_manager');
    // Proceed only if a user is logged-in and the module has been initialized.
    if (\Drupal::currentUser()->isAuthenticated() && $pubkey_encrypt_manager->moduleInitialized) {
      $cookies = $event->getRequest()->cookies;
      $cookie_name = \Drupal::currentUser()->id() . '_private_key';
      // Do nothing if the cookie already exists.
      if (!$cookies->get($cookie_name)) {
        // Otherwise, set the cookie. But it can only be set if a user JUST
        // logged in with his credentials. Because in that case, we can grab his
        // Private key from the user.shared_tempstore. See
        // PubkeyEncryptManager::userLoggedIn() for more details.
        $temp_store = \Drupal::service('user.shared_tempstore')
          ->get('pubkey_encrypt');
        $private_key = $temp_store->get($cookie_name);
        if ($private_key) {
          $cookie = new Cookie($cookie_name, $private_key);
          $event->getResponse()->headers->setCookie($cookie);
          // Since the cookie has been set, clear the Private Key from
          // tempstore.
          $temp_store->delete($cookie_name);
        }
        // If not possible to set the cookie, log-out the user so he could
        // log-in again.
        else {
          user_logout();
          $event->setResponse(new RedirectResponse(Url::fromRoute('<front>')->toString()));
        }
      }
    }
  }

}
