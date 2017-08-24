<?php

namespace Drupal\social_post_slack\Settings;

use Drupal\social_api\Settings\SettingsBase;

/**
 * Returns the app information.
 */
class SlackPostSettings extends SettingsBase implements SlackPostSettingsInterface {

  /**
   * Clients ID.
   *
   * @var string
   */
  protected $clientId;

  /**
   * Client secret.
   *
   * @var string
   */
  protected $clientSecret;


  /**
   * The default access token.
   *
   * @var string
   */
  protected $defaultToken;

  /**
   * {@inheritdoc}
   */
  public function getClientId() {
    if (!$this->clientId) {
      $this->clientId = $this->config->get('client_id');
    }
    return $this->clientId;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientSecret() {
    if (!$this->clientSecret) {
      $this->clientSecret = $this->config->get('client_secret');
    }
    return $this->clientSecret;
  }

}
