<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_suite_uikit\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the theme settings form of UI Suite UIkit.
 */
#[CoversNothing]
#[RunTestsInSeparateProcesses]
#[Group('ui_suite_uikit')]
final class ThemeSettingsFormTest extends BrowserTestBase {

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
  protected $defaultTheme = 'stark';

  /**
   * Tests the settings page opens and saves, for an installed theme.
   *
   * Core passes NULL as the form ID when it builds the settings of a theme.
   */
  public function testThemeSettingsForm(): void {
    $this->container->get('theme_installer')->install(['ui_suite_uikit']);
    $this->drupalLogin($this->drupalCreateUser(['administer themes']));
    $this->assertSettingsFormSaves();
  }

  /**
   * Tests the settings page when UI Suite UIkit is also the active theme.
   *
   * The form alter then runs twice: the settings are added once.
   */
  public function testThemeSettingsFormAsDefaultTheme(): void {
    $this->container->get('theme_installer')->install(['ui_suite_uikit']);
    $this->config('system.theme')->set('default', 'ui_suite_uikit')->save();
    $this->drupalLogin($this->drupalCreateUser(['administer themes']));
    $this->assertSettingsFormSaves();
    $this->assertSession()->elementsCount('css', 'input[name="navbar_sticky"]', 1);
  }

  /**
   * Opens the settings page of the theme, changes a setting and saves.
   */
  protected function assertSettingsFormSaves(): void {
    $this->drupalGet('admin/appearance/settings/ui_suite_uikit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('UIkit library source');
    $this->assertSession()->checkboxChecked('navbar_sticky');

    $this->assertSession()->fieldValueEquals('font_family', 'atkinson');

    $this->submitForm([
      'navbar_sticky' => FALSE,
      'htmx_navigation' => FALSE,
      'font_family' => 'system',
      'ui_suite_uikit_skin_brand' => '#7a1f5c',
      'ui_suite_uikit_skin_radius' => '8px',
    ], 'Save configuration');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->checkboxNotChecked('navbar_sticky');

    $settings = $this->config('ui_suite_uikit.settings');
    $this->assertFalse((bool) $settings->get('navbar_sticky'));
    $this->assertFalse((bool) $settings->get('htmx_navigation'));
    $this->assertSame('system', $settings->get('font_family'));
    // The design tokens are stored for UI Skins, not as theme settings.
    $variables = $settings->get('third_party_settings.ui_skins.css_variables');
    $this->assertSame('#7a1f5c', $variables['ui-suite-uikit-global-primary-background'][':root']);
    $this->assertSame('#7a1f5c', $variables['ui-suite-uikit-global-link-color'][':root']);
    $this->assertSame('8px', $variables['ui-suite-uikit-global-border-radius'][':root']);
    $this->assertNull($settings->get('ui_suite_uikit_skin_brand'));

    // A brand color below 7:1 with white is refused.
    $this->submitForm(['ui_suite_uikit_skin_brand' => '#66aaff'], 'Save configuration');
    $this->assertSession()->pageTextContains('Pick a darker color.');
  }

}
