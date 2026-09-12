<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_suite_uikit\Kernel;

use Drupal\Tests\sdc_devel\Kernel\SdcDevelComponentKernelTestBase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Validate the components definitions and templates with SDC Devel.
 */
#[CoversNothing]
#[RunTestsInSeparateProcesses]
#[Group('ui_suite_uikit')]
final class ComponentValidatorTest extends SdcDevelComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'sdc_devel',
    'ui_patterns',
    'ui_styles',
    'ui_icons',
    'ui_icons_patterns',
    'ui_skins',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $themes = [
    'ui_suite_uikit',
  ];

}
