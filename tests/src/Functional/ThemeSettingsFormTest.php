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

    $this->submitForm([
      'navbar_sticky' => FALSE,
      'htmx_navigation' => FALSE,
    ], 'Save configuration');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->checkboxNotChecked('navbar_sticky');

    $settings = $this->config('ui_suite_uikit.settings');
    $this->assertFalse((bool) $settings->get('navbar_sticky'));
    $this->assertFalse((bool) $settings->get('htmx_navigation'));
  }

}
