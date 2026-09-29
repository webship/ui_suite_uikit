<?php

declare(strict_types=1);

namespace Drupal\ui_suite_uikit;

/**
 * The contrast of two colors, as WCAG 2.2 measures it.
 */
final class Contrast {

  /**
   * The contrast ratio of two hex colors, from 1 to 21.
   *
   * @param string $first
   *   A color like #0f5695 or #fff.
   * @param string $second
   *   Another color.
   *
   * @return float
   *   The ratio, 0 when a color can not be read.
   */
  public static function ratio(string $first, string $second): float {
    $a = self::luminance($first);
    $b = self::luminance($second);
    if ($a === NULL || $b === NULL) {
      return 0.0;
    }
    return (\max($a, $b) + 0.05) / (\min($a, $b) + 0.05);
  }

  /**
   * A darker or lighter version of a hex color.
   *
   * @param string $color
   *   A color like #0f5695.
   * @param float $amount
   *   From -1 (black) to 1 (white).
   *
   * @return string
   *   The color, as #rrggbb.
   */
  public static function shade(string $color, float $amount): string {
    $rgb = self::rgb($color) ?? [0, 0, 0];
    $target = $amount < 0 ? 0 : 255;
    $amount = \abs($amount);
    return \sprintf('#%02x%02x%02x', ...\array_map(static fn (int $channel): int => (int) \round($channel + ($target - $channel) * $amount), $rgb));
  }

  /**
   * The relative luminance of a hex color.
   */
  private static function luminance(string $color): ?float {
    $rgb = self::rgb($color);
    if ($rgb === NULL) {
      return NULL;
    }
    [$r, $g, $b] = \array_map(static function (int $channel): float {
      $value = $channel / 255;
      return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
    }, $rgb);
    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
  }

  /**
   * The red, green and blue channels of a hex color.
   *
   * @return int[]|null
   *   The channels, or NULL when the color can not be read.
   */
  private static function rgb(string $color): ?array {
    if (!\preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})([0-9a-f]{2})?$/i', \trim($color), $matches)) {
      return NULL;
    }
    $hex = \strlen($matches[1]) === 3 ? \preg_replace('/(.)/', '$1$1', $matches[1]) : $matches[1];
    return \array_map('hexdec', \str_split((string) $hex, 2));
  }

}
