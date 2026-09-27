<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_suite_uikit\Kernel;

use Drupal\Component\Serialization\Yaml;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Validates the block placements the theme ships.
 *
 * A recipe validates the configuration it imports: a block with an invalid
 * setting stops the recipe on an existing site.
 */
#[CoversNothing]
#[RunTestsInSeparateProcesses]
#[Group('ui_suite_uikit')]
final class BlockConfigTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'help',
    'system',
    'user',
    'ui_patterns',
    'ui_skins',
    'ui_styles',
    'ui_icons',
    'ui_icons_patterns',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'user']);
    $this->container->get('theme_installer')->install(['ui_suite_uikit']);
  }

  /**
   * Tests every block of config/optional passes the config validation.
   */
  public function testBlocksAreValid(): void {
    $files = glob(\dirname(__DIR__, 3) . '/config/optional/block.block.*.yml') ?: [];
    $this->assertNotEmpty($files);

    $typed_config = $this->container->get('config.typed');
    foreach ($files as $file) {
      $name = basename($file, '.yml');
      $data = Yaml::decode((string) file_get_contents($file));
      // The config installer gives imported configuration a UUID.
      $data['uuid'] = $this->container->get('uuid')->generate();
      $violations = $typed_config->createFromNameAndData($name, $data)->validate();
      $messages = [];
      foreach ($violations as $violation) {
        $messages[] = $violation->getPropertyPath() . ': ' . strip_tags((string) $violation->getMessage());
      }
      $this->assertSame([], $messages, $name);
    }
  }

}
