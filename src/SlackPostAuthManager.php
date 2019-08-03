<?php

namespace Drupal\social_post_slack;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Drupal\social_post\PostManager\PostManager;

/**
 * Manages the authorization process and post on user behalf.
 */
class SlackPostAuthManager extends PostManager {

  /**
   * Saves access token.
   */
  public function authenticate() {
    $this->setAccesstoken($this->client->getAccessToken('authorization_code',
      ['code' => $_GET['code']]));
  }

  /**
   * Returns the Slack login URL where user will be redirected.
   *
   * @return string
   *   Absolute Slack login URL where user will be redirected
   */
  public function getAuthorizationUrl() {
    $scopes = ['chat:write:user','users:read'];

    $login_url = $this->client->getAuthorizationUrl([
      'scope' => $scopes,
    ]);
    // Generate and return the URL where we should redirect the user.
    return $login_url;
  }

  /**
   * Gets the data by using the access token returned.
   *
   * @return AdamPaterson\OAuth2\Client\Provider\SlackResourceOwner
   *   User Info returned by the slack.
   */
  public function getUserInfo() {
    if (!$this->user) {
      $this->user = $this->client->getResourceOwner($this->getAccessToken());
    }
    return $this->user;
  }


  /**
   * Returns the Slack login URL where user will be redirected.
   *
   * @return string
   *   Absolute Slack login URL where user will be redirected
   */
  public function getState() {
    $state = $this->client->getState();

    // Generate and return the URL where we should redirect the user.
    return $state;
  }

}
