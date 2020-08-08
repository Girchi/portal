<?php

namespace Drupal\girchi_supporters_register_form\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a register supporters link block.
 *
 * @Block(
 *   id = "girchi_supporters_register_form_register_supporters_link",
 *   admin_label = @Translation("Register supporters link"),
 *   category = @Translation("Custom")
 * )
 */
class RegisterSupportersLinkBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $condition = in_array('missioner', $account->getRoles());
    return AccessResult::allowedIf($condition);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
      '#markup' => '<div class="container">
                      <div class="row justify-content-center p-4">
                          <a href="/rs">Register Supporters</a>
                      </div>
                    </div>',
    ];
    return $build;
  }

}
