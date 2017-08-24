<?php

namespace Drupal\social_post_slack\Settings;

/**
 * Defines an interface for Social Post Slack settings.
 */
interface SlackPostSettingsInterface {

  /**
   * Gets the application ID.
   *
   * @return mixed
   *   The application ID.
   */
  public function getClientId();

  /**
   * Gets the application secret.
   *
   * @return string
   *   The application secret.
   */
  public function getClientSecret();

}
