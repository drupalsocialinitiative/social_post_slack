<?php

namespace Drupal\social_post_slack;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Drupal\social_post\PostManager;

/**
 * Manages the authorization process and post on user behalf.
 */
class SlackPostAuthManager extends PostManager\PostManager {
  /**
   * The session manager.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * The Slack client object.
   *
   * @var \League\OAuth2\Client\Provider\Slack
   */
  protected $client;

  /**
   * The HTTP client object.
   *
   * @var \League\OAuth2\Client\Provider\Slack
   */
  protected $httpClient;


  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The Slack access token.
   *
   * @var \League\OAuth2\Client\Token\AccessToken
   */
  protected $token;

  /**
   * SlackPostManager constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   *   The session manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to get the parameter code returned by Slack.
   */
  public function __construct(Session $session, RequestStack $request) {
    $this->session = $session;
    $this->request = $request->getCurrentRequest();
  }

  /**
   * Saves access token.
   */
  public function authenticate() {
    $this->token = $this->client->getAccessToken('authorization_code',
      ['code' => $_GET['code']]);
  }

  /**
   * Returns the Slack login URL where user will be redirected.
   *
   * @return string
   *   Absolute Slack login URL where user will be redirected
   */
  public function getFbLoginUrl() {
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
   * @return League\OAuth2\Client\Provider\SlackUser
   *   User Info returned by the slack.
   */
  public function getUserInfo() {
    $this->user = $this->client->getResourceOwner($this->token);

    var_dump($this->user->getName());
    return $this->user;
  }

  /**
   * Returns token generated after authorization.
   *
   * @return string
   *   Used for making API calls.
   */
  public function getAccessToken() {
    return $this->token;
  }

  /**
   * Makes an API call to slack server.
   */
  public function requestApiCall($message, $token, $userId) {
    $post = [
      'text' => $message,
      'token' => $token,
      'channel' => '#general'
    ];

    $ch = curl_init('https://slack.com/api/chat.postMessage');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

    // execute!
    curl_exec($ch);

    // Close the connection, release resources used.
    curl_close($ch);
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
