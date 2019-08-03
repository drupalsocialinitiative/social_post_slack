<?php

namespace Drupal\social_post_slack\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_post\SocialPostDataHandler;

use Drupal\social_post\SocialPostManager;
use Drupal\social_post_slack\SlackPostAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Returns responses for Simple Slack Connect module routes.
 */
class SlackPostController extends ControllerBase {

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
  private $providerManager;

  /**
   * The Social Auth Data Handler.
   *
   * @var \Drupal\social_post\SocialPostDataHandler
   */
  private $dataHandler;

  /**
   * Used to access GET parameters.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $request;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The social post manager.
   *
   * @var \Drupal\social_post\SocialPostManager
   */
  protected $postManager;

  /**
   * SlackAuthController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_slack network plugin.
   * @param \Drupal\social_post\SocialPostManager $user_manager
   *   Manages user login/registration.
   * @param \Drupal\social_post_slack\SlackPostAuthManager $slack_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Drupal\social_post\SocialPostDataHandler $social_auth_data_handler
   *   SocialAuthDataHandler object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   */
  public function __construct(NetworkManager $network_manager, SocialPostManager $user_manager, SlackPostAuthManager $provider_manager, RequestStack $request, SocialPostDataHandler $social_auth_data_handler, LoggerChannelFactoryInterface $logger_factory) {

    $this->networkManager = $network_manager;
    $this->postManager = $user_manager;
    $this->providerManager = $provider_manager;
    $this->request = $request;
    $this->dataHandler = $social_auth_data_handler;
    $this->loggerFactory = $logger_factory;

    // Sets session prefix for data handler.
    $this->dataHandler->getSessionPrefix('social_post_slack');

    // Sets the plugin id.
    // Sets the session keys to nullify if user could not logged in.
    // $this->slackManager->setSessionKeysToNullify(['access_token']);.
    $this->setting = $this->config('social_post_slack.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_post.post_manager'),
      $container->get('slack_post.social_post_auth_manager'),
      $container->get('request_stack'),
      $container->get('social_post.social_post_data_handler'),
      $container->get('logger.factory')
    );
  }

  /**
   * Redirects the user to Slack for authentication.
   */
  public function redirectToProvider() {
    /* @var \League\OAuth2\Client\Provider\Slack false $slack */
    $slack = $this->networkManager->createInstance('social_post_slack')->getSdk();

    // If slack client could not be obtained.
    if (!$slack) {
      drupal_set_message($this->t('Social Auth Slack not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Slack service was returned, inject it to $slackManager.
    $this->providerManager->setClient($slack);

    // Generates the URL where the user will be redirected for Slack login.
    // If the user did not have email permission granted on previous attempt,
    // we use the re-request URL requesting only the email address.
    $slack_login_url = $this->providerManager->getAuthorizationUrl();

    $state = $this->providerManager->getState();

    $this->dataHandler->set('oAuth2State', $state);

    return new TrustedRedirectResponse($slack_login_url);
  }

  /**
   * Response for path 'user/login/slack/callback'.
   *
   * Slack returns the user here after user has authenticated in Slack.
   */
  public function callback() {
    // Checks if user cancel login via Slack.
    $error = $this->request->getCurrentRequest()->get('error');
    if ($error == 'access_denied') {
      drupal_set_message($this->t('You could not be authenticated.'), 'error');
      return $this->redirect('user.login');
    }

    /* @var \League\OAuth2\Client\Provider\Slack false $slack */
    $slack = $this->networkManager->createInstance('social_post_slack')->getSdk();

    // If slack client could not be obtained.
    if (!$slack) {
      drupal_set_message($this->t('Social Auth Slack not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    $state = $this->dataHandler->get('oAuth2State');

    // Retrieves $_GET['state'].
    $retrievedState = $this->request->getCurrentRequest()->query->get('state');

    if ($retrievedState !== $state) {
      $this->postManager->nullifySessionKeys();
      $this->messenger->addError($this->t('Slack login failed. Invalid Oauth2 state.'));
      return $this->redirect('user.login');
    }

    $this->providerManager->setClient($slack)->authenticate();

    if (!$slack_profile = $this->providerManager->getUserInfo()) {
      drupal_set_message($this->t('Slack login failed, could not load Slack profile. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }
    if (!$this->postManager->checkIfUserExists($slack_profile->getId())) {
      $this->postManager->addRecord($slack_profile->getName(), $slack_profile->getId(),$slack_profile->getAccessToken());
    }
    return $this->redirect('entity.user.edit_form', ['user' => $this->postManager->getCurrentUser()]);
  }

}
