<?php

namespace Drupal\mentions\Plugin\Mentions;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\Token;
use Drupal\mentions\MentionsPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Entity.
 *
 * @Mention(
 *  id = "entity",
 *  name = @Translation("Entity")
 * )
 */
class Entity implements MentionsPluginInterface {
  private $tokenService;
  private $entityManager;
  private $entityQueryService;
  private $config;

  /**
   * Entity constructor.
   */
  public function __construct(Token $token, EntityManager $entity_manager, QueryFactory $entity_query) {
    $this->tokenService = $token;
    $this->entityManager = $entity_manager;
    $this->entityQueryService = $entity_query;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $token = $container->get('token');
    $entity_manager = $container->get('entity.manager');
    $entity_query = $container->get('entity.query');
    $config = $container->get('config.factory');
    return new static(
      $token,
      $entity_manager,
      $entity_query,
      $config
    );
  }

  /**
   * {@inheritdoc}
   */
  public function outputCallback($mention, $settings) {
    // If the mentions is run with cron, replace the output ourself.
    if (PHP_SAPI === 'cli') {
      // Get the profile.
      $profile = $this->entityManager->getStorage($mention['target']['entity_type'])
        ->load($mention['target']['entity_id']);

      // Get the output value according to the config settings.
      $config = $this->config->get('mentions.settings');
      switch ($config->get('suggestions_format')) {
        case SOCIAL_PROFILE_SUGGESTIONS_FULL_NAME:
        case SOCIAL_PROFILE_SUGGESTIONS_ALL:
          $output['value'] = $profile->getOwner()->getDisplayName();
      }
      if (empty($output['value'])) {
        $output['value'] = $profile->getOwner()->getAccountName();
      }

      // Convert to the correct output link based on the mention config.
      // Ex: user/[user:uid] will replace between the brackets for the OwnerId.
      $output['link'] = preg_replace("/\[([^\[\]]++|(?R))*+\]/", $profile->getOwnerId(), $settings['rendertextbox']);

      return $output;
    }

    $entity = $this->entityManager->getStorage($mention['target']['entity_type'])
      ->load($mention['target']['entity_id']);
    $output['value'] = $this->tokenService->replace($settings['value'], [$mention['target']['entity_type'] => $entity]);
    if ($settings['renderlink']) {
      $output['link'] = $this->tokenService->replace($settings['rendertextbox'], [$mention['target']['entity_type'] => $entity]);
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function targetCallback($value, $settings) {
    $entity_type = $settings['entity_type'];
    $input_value = $settings['value'];
    $query = $this->entityQueryService
      ->get($entity_type)
      ->condition($input_value, $value)
      ->accessCheck(FALSE);
    $result = $query->execute();

    if (!empty($result)) {
      $result = reset($result);
      $target['entity_type'] = $entity_type;
      $target['entity_id'] = $result;

      return $target;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mentionPresaveCallback(EntityInterface $entity) {

  }

  /**
   * {@inheritdoc}
   */
  public function patternCallback($settings, $regex) {

  }

  /**
   * {@inheritdoc}
   */
  public function settingsCallback(FormInterface $form, FormStateInterface $form_state, $type) {

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSubmitCallback(FormInterface $form, FormStateInterface $form_state, $type) {

  }

}
