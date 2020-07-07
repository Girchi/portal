<?php

namespace Drupal\girchi_chatbot_integration\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;

/**
 * Class UpdateChatbotCodesCommand.
 *
 * Drupal\Console\Annotations\DrupalCommand (
 *     extension="girchi_chatbot_integration",
 *     extensionType="module"
 * )
 */
class UpdateChatbotCodesCommand extends ContainerAwareCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('girchi_chatbot_integration:update_codes')
      ->setDescription($this->trans('commands.girchi_chatbot_integration.update_codes.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    /*
     * @var Drupal\girchi_chatbot_integration\Services\ChatbotIntegrationHelpers
     */
    $chatbot_helpers = $this->container->get('girchi_chatbot.helpers');
    $userManager = $this->get('entity_type.manager')->getStorage('user');

    /**
     * @var $users Drupal\user\Entity\User[]
     */
    $users = $userManager->loadByProperties();
    foreach ($users as $user) {
      if ($new_code = $chatbot_helpers->generateUniqueCode($user)) {
        $user->set('field_bot_integration_code', $new_code);
        $user->save();

        $this->getIo()->info($user->getDisplayName() . ' - ' . $new_code);
      }
      else {
        $this->getIo()->info($user->getDisplayName() . ' - ALREADY SET.');
      }
    }

  }

}
