<?php

namespace Drupal\social_post_slack\Plugin\RulesAction;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rules\Core\RulesActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_post\SocialPostManager;
use Drupal\social_post_slack\SlackPostAuthManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Provides a 'Post' action.
 *
 * @RulesAction(
 *   id = "social_post_slack",
 *   label = @Translation("Slack Post"),
 *   category = @Translation("Social Post"),
 *   context = {
 *     "status" = @ContextDefinition("string",
 *       label = @Translation("Post content"),
 *       description = @Translation("Specifies the status to post.")
 *     )
 *   }
 * )
 */
class Post extends RulesActionBase implements ContainerFactoryPluginInterface {
  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  private $networkManager;

  /**
   * The Slack authentication manager.
   *
   * @var \Drupal\social_post_slack\SlackPostAuthManager
   */
  private $slackManager;

  /**
   * The social post manager.
   *
   * @var \Drupal\social_post\SocialPostManager
   */
  protected $postManager;

  /**
   * The slack post network plugin.
   *
   * @var \Drupal\social_post_slack\Plugin\Network\SlackPostInterface
   */
  protected $slackPost;

  /**
   * The social post slack entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $slackEntity;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;
  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $current_user;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('plugin.network.manager'),
      $container->get('social_post.post_manager'),
      $container->get('slack_post.social_post_auth_manager'),
      $container->get('logger.factory')

    );
  }

  /**
   * Slack Post constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Network plugin manager.
   * @param \Drupal\social_post\SocialPostManager $user_manager
   *   Manages user login/registration.
   * @param \Drupal\social_post_slack\SlackPostAuthManager $slack_manager
   *   Used to manage authentication methods.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_manager,
                              AccountInterface $current_user,
                              NetworkManager $network_manager,
                              SocialPostManager $user_manager,
                              SlackPostAuthManager $slack_manager,
                              LoggerChannelFactoryInterface $logger_factory) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->slackEntity = $entity_manager->getStorage('social_post');
    $this->currentUser = $current_user;
    $this->networkManager = $network_manager;
    $this->postManager = $user_manager;
    $this->slackManager = $slack_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Executes the action with the given context.
   *
   * @param string $status
   *   The Post text.
   */
  protected function execute($status) {

    $accounts = $this->postManager->getAccountsByUserId('social_post_slack', $this->currentUser->id());


    /* @var \Drupal\social_post_slack\Entity\SlackUser $account */
    foreach ($accounts as $account) {
      $access_token = json_decode($this->postManager->getToken($account->getProviderUserId()), TRUE);
      $this->slackPost->doPost($access_token, $status);
    }
  }

}
