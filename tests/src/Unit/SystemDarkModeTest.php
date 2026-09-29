<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_suite_uikit\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\ui_suite_uikit\SystemDarkMode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the dark values printed for the dark mode of the operating system.
 */
#[CoversClass(SystemDarkMode::class)]
#[Group('ui_suite_uikit')]
final class SystemDarkModeTest extends UnitTestCase {

  /**
   * Tests that only the dark values are printed, in the media query.
   */
  public function testDarkValuesArePrinted(): void {
    $css = SystemDarkMode::css([
      'ui-suite-uikit-global-link-color' => [
        ':root' => '#aa0000',
        ':root[data-theme="dark"]' => '#ffcc00',
      ],
      'ui-suite-uikit-global-border-radius' => [
        ':root' => '6px',
      ],
    ]);
    $this->assertSame('@media (prefers-color-scheme: dark){:root:not([data-theme="light"]){--ui-suite-uikit-global-link-color:#ffcc00;}}', $css);
  }

  /**
   * Tests that nothing is printed without a saved dark value.
   */
  public function testNothingWithoutDarkValues(): void {
    $this->assertSame('', SystemDarkMode::css(NULL));
    $this->assertSame('', SystemDarkMode::css([]));
    $this->assertSame('', SystemDarkMode::css(['ui-suite-uikit-global-color' => [':root' => '#444444ff']]));
  }

  /**
   * Tests that a value closing the rule or the style element is left out.
   */
  public function testUnsafeValuesAreLeftOut(): void {
    $css = SystemDarkMode::css([
      'ui-suite-uikit-global-font-family' => [
        ':root[data-theme="dark"]' => 'serif}</style><script>',
      ],
    ]);
    $this->assertSame('', $css);
  }

}
