<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_suite_uikit\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\ui_suite_uikit\Contrast;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the contrast of two colors.
 */
#[CoversClass(Contrast::class)]
#[Group('ui_suite_uikit')]
final class ContrastTest extends UnitTestCase {

  /**
   * Tests the ratios WCAG gives for known colors.
   */
  public function testRatio(): void {
    $this->assertEqualsWithDelta(21.0, Contrast::ratio('#000', '#ffffff'), 0.01);
    $this->assertEqualsWithDelta(1.0, Contrast::ratio('#0f5695', '#0f5695'), 0.01);
    $this->assertGreaterThan(7, Contrast::ratio('#0f5695', '#fff'));
    $this->assertLessThan(7, Contrast::ratio('#66aaff', '#fff'));
    $this->assertSame(0.0, Contrast::ratio('blue', '#fff'));
  }

  /**
   * Tests the darker and lighter shades.
   */
  public function testShade(): void {
    $this->assertSame('#000000', Contrast::shade('#0f5695', -1));
    $this->assertSame('#ffffff', Contrast::shade('#0f5695', 1));
    $this->assertSame('#0f5695', Contrast::shade('#0f5695', 0));
  }

}
