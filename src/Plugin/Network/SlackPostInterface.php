<?php

namespace Drupal\social_post_slack\Plugin\Network;

use Drupal\social_post\Plugin\Network\SocialPostNetworkInterface;

/**
 * Defines the slack Post interface.
 */
interface SlackPostInterface extends SocialPostNetworkInterface {

  /**
   * Wrapper for post method.
   *
   * @param string $access_token
   *   The access token.
   * @param string $status
   *   The tweet text.
   */
  public function doPost($access_token, $status);

}
