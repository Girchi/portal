<?php

namespace Drupal\girchi_utils\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'PoliticianBlock' block.
 *
 * @Block(
 *  id = "politician_block",
 *  admin_label = @Translation("Politician block"),
 * )
 */
class PoliticianBlock extends BlockBase
{

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $build = [];
        $build['politician_block']['#markup'] = 'Implement PoliticianBlock.';
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();


        return array(
            '#theme' => 'politician_block',
            '#language' => $language,
        );
    }

    public function getCacheMaxAge()
    {
        return 0;
    }
}
