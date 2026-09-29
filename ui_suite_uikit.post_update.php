<?php

/**
 * @file
 * Post update functions for UI Suite UIkit.
 */

declare(strict_types=1);

use Drupal\ui_suite_uikit\Hook\ThemeHooks;

/**
 * Give the UI Skins ids of the theme its name, and one color mode control.
 *
 * The ids of UI Skins are shared by every theme of the site: the color modes
 * and the design tokens of this theme now carry its name, so another theme
 * declaring the same UIkit names does not replace them. A color mode picked
 * in UI Skins becomes the "Color mode" theme setting.
 */
function ui_suite_uikit_post_update_theme_named_ui_skins_ids(): void {
  $config = \Drupal::configFactory()->getEditable('ui_suite_uikit.settings');
  if ($config->isNew()) {
    return;
  }
  $theme = (string) $config->get('third_party_settings.ui_skins.theme');
  $mode = \preg_replace('/^ui_suite_uikit_/', '', $theme);
  if ($config->get('color_mode') === NULL) {
    $config->set('color_mode', \in_array($mode, ['light', 'dark'], TRUE) ? $mode : ThemeHooks::COLOR_MODE);
  }
  $variables = $config->get('third_party_settings.ui_skins.css_variables');
  if (\is_array($variables)) {
    $renamed = [];
    foreach ($variables as $id => $values) {
      $renamed[\str_starts_with((string) $id, 'uk-') ? 'ui-suite-uikit-' . \substr((string) $id, 3) : $id] = $values;
    }
    $config->set('third_party_settings.ui_skins.css_variables', $renamed);
  }
  ThemeHooks::syncUiSkinsColorMode($config);
}

/**
 * Keep the look of the design tokens saved in UI Skins.
 *
 * The text, muted, inverse and border colors take a transparency: a saved
 * color gets the opaque one. The text colors of primary, success, warning and
 * danger, and the focus ring, have a value of their own now: a site that
 * saved the background, or the emphasis color, keeps it for them.
 */
function ui_suite_uikit_post_update_ui_skins_token_values(): void {
  $config = \Drupal::configFactory()->getEditable('ui_suite_uikit.settings');
  $variables = $config->get('third_party_settings.ui_skins.css_variables');
  if ($config->isNew() || !\is_array($variables) || !$variables) {
    return;
  }

  $alpha = ['global-color', 'global-muted-color', 'global-inverse-color', 'global-border'];
  foreach ($alpha as $name) {
    foreach ($variables['ui-suite-uikit-' . $name] ?? [] as $scope => $value) {
      if (\preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', (string) $value, $matches)) {
        $hex = \strlen($matches[1]) === 3 ? \preg_replace('/(.)/', '$1$1', $matches[1]) : $matches[1];
        $variables['ui-suite-uikit-' . $name][$scope] = '#' . \strtolower($hex) . 'ff';
      }
    }
  }

  $follows = [
    'global-primary-color' => 'global-primary-background',
    'global-success-color' => 'global-success-background',
    'global-warning-color' => 'global-warning-background',
    'global-danger-color' => 'global-danger-background',
    'focus-color' => 'global-emphasis-color',
  ];
  foreach ($follows as $name => $source) {
    foreach ($variables['ui-suite-uikit-' . $source] ?? [] as $scope => $value) {
      // The dark mode already had text colors of its own.
      if (!isset($variables['ui-suite-uikit-' . $name][$scope]) && ($scope === ':root' || $name === 'focus-color')) {
        $variables['ui-suite-uikit-' . $name][$scope] = $value;
      }
    }
  }

  $config->set('third_party_settings.ui_skins.css_variables', $variables)->save();
}
