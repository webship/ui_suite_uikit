<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_suite_uikit\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the post updates of the theme settings.
 */
#[CoversNothing]
#[RunTestsInSeparateProcesses]
#[Group('ui_suite_uikit')]
final class PostUpdateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
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
    $this->container->get('theme_installer')->install(['ui_suite_uikit']);
    require_once \dirname(__DIR__, 3) . '/ui_suite_uikit.post_update.php';
  }

  /**
   * Tests the saved design tokens keep their look.
   */
  public function testUiSkinsTokenValues(): void {
    $this->config('ui_suite_uikit.settings')
      ->set('third_party_settings.ui_skins.css_variables', [
        'ui-suite-uikit-global-color' => [':root' => '#112233'],
        'ui-suite-uikit-global-border' => [':root[data-theme="dark"]' => '#abc'],
        'ui-suite-uikit-global-primary-background' => [':root' => '#7a1f5c'],
        'ui-suite-uikit-global-danger-background' => [':root' => '#990000'],
        'ui-suite-uikit-global-danger-color' => [':root' => '#880000'],
        'ui-suite-uikit-global-emphasis-color' => [':root[data-theme="dark"]' => '#eeeeee'],
      ])
      ->save();

    ui_suite_uikit_post_update_ui_skins_token_values();

    $variables = $this->config('ui_suite_uikit.settings')->get('third_party_settings.ui_skins.css_variables');
    $this->assertSame('#112233ff', $variables['ui-suite-uikit-global-color'][':root']);
    $this->assertSame('#aabbccff', $variables['ui-suite-uikit-global-border'][':root[data-theme="dark"]']);
    $this->assertSame('#7a1f5c', $variables['ui-suite-uikit-global-primary-color'][':root']);
    $this->assertSame('#880000', $variables['ui-suite-uikit-global-danger-color'][':root']);
    $this->assertSame('#eeeeee', $variables['ui-suite-uikit-focus-color'][':root[data-theme="dark"]']);
    $this->assertArrayNotHasKey('ui-suite-uikit-global-success-color', $variables);
  }

  /**
   * Tests a site without saved design tokens is left as it is.
   */
  public function testNoSavedTokens(): void {
    ui_suite_uikit_post_update_ui_skins_token_values();
    $this->assertNull($this->config('ui_suite_uikit.settings')->get('third_party_settings.ui_skins.css_variables'));
  }

  /**
   * Tests the existing sites get the font of the theme.
   */
  public function testFontFamily(): void {
    ui_suite_uikit_post_update_font_family();
    $this->assertSame('atkinson', $this->config('ui_suite_uikit.settings')->get('font_family'));

    $this->config('ui_suite_uikit.settings')->set('font_family', 'system')->save();
    ui_suite_uikit_post_update_font_family();
    $this->assertSame('system', $this->config('ui_suite_uikit.settings')->get('font_family'));
  }

  /**
   * Tests the existing sites get the options of the sign-in screens.
   */
  public function testSignInOptions(): void {
    $this->config('ui_suite_uikit.settings')->set('sign_in_layout', 'end')->save();
    ui_suite_uikit_post_update_sign_in_options();
    $settings = $this->config('ui_suite_uikit.settings');
    $this->assertSame('end', $settings->get('sign_in_layout'));
    $this->assertFalse($settings->get('sign_in_header'));
    $this->assertSame('site', $settings->get('sign_in_logo'));
    $this->assertSame('', $settings->get('sign_in_page_layout'));
    $this->assertSame('en', $settings->get('langcode'));
  }

}
