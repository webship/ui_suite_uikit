<?php

/**
 * @file
 * Post update functions for UI Suite UIkit.
 */

declare(strict_types=1);

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\MemoryStorage;
use Drupal\ui_suite_uikit\Hook\ThemeHooks;
use Drupal\ui_suite_uikit\PageTitleHeading;

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

/**
 * Pick the font of the theme on the existing sites.
 *
 * The theme serves its own fonts. "Font" in the theme settings goes back to
 * the fonts of the operating system.
 */
function ui_suite_uikit_post_update_font_family(): void {
  $config = \Drupal::configFactory()->getEditable('ui_suite_uikit.settings');
  if ($config->isNew() || $config->get('font_family') !== NULL) {
    return;
  }
  $config->set('font_family', ThemeHooks::FONT_FAMILY)->save();
}

/**
 * Add the options of the sign-in screens, with their defaults.
 *
 * Their texts are translatable: the settings get a language code, or the
 * theme settings form can not save them.
 */
function ui_suite_uikit_post_update_sign_in_options(): void {
  $config = \Drupal::configFactory()->getEditable('ui_suite_uikit.settings');
  if ($config->isNew()) {
    return;
  }
  if (!$config->get('langcode')) {
    $config->set('langcode', \Drupal::languageManager()->getDefaultLanguage()->getId());
  }
  $defaults = [
    'sign_in_layout' => 'center',
    'sign_in_message' => '',
    'sign_in_header' => FALSE,
    'sign_in_footer' => FALSE,
    'sign_in_page_layout' => '',
    'sign_in_logo' => 'site',
    'sign_in_image' => '',
    'sign_in_image_credit' => '',
    'sign_in_help' => '',
  ];
  foreach ($defaults as $key => $value) {
    if ($config->get($key) === NULL) {
      $config->set($key, $value);
    }
  }
  $config->save();
}

/**
 * Install the Display Builder profiles and the page part agents.
 *
 * A site that has Display Builder gets the profiles of the theme, the
 * sign-in page layout (disabled) and the agents of the Page, Header, Footer
 * and Sign in components. Configuration that exists is kept.
 */
function ui_suite_uikit_post_update_display_builder_config(): void {
  $path = \Drupal::service('extension.list.theme')->getPath('ui_suite_uikit') . '/config/optional';
  $names = [
    'display_builder.profile.ui_suite_uikit',
    'display_builder.profile.ui_suite_uikit_sign_in',
    'display_builder_page_layout.page_layout.ui_suite_uikit_sign_in',
    'ai_agents.ai_agent.ui_suite_uikit_page',
    'ai_agents.ai_agent.ui_suite_uikit_header',
    'ai_agents.ai_agent.ui_suite_uikit_footer',
    'ai_agents.ai_agent.ui_suite_uikit_sign_in',
  ];
  $source = new FileStorage($path);
  $storage = new MemoryStorage();
  foreach ($names as $name) {
    if ($source->exists($name) && \Drupal::configFactory()->get($name)->isNew()) {
      $storage->write($name, $source->read($name));
    }
  }
  \Drupal::service('config.installer')->installOptionalConfig($storage);
}

/**
 * Store the ids the Display Builder profiles leave out as lists.
 *
 * Display Builder 1.0.0-beta10 keeps the "Exclude by id" setting of its
 * library islands as a list of ids. The profiles of the theme held them as one
 * string, one id by line, which a site that installed them with Display
 * Builder 1.0.0-beta10 or later still carries.
 */
function ui_suite_uikit_post_update_display_builder_exclude_id_list(): void {
  $entity_type_manager = \Drupal::entityTypeManager();
  if (!$entity_type_manager->hasDefinition('display_builder_profile')) {
    return;
  }
  $storage = $entity_type_manager->getStorage('display_builder_profile');
  foreach ($storage->loadMultiple(['ui_suite_uikit', 'ui_suite_uikit_sign_in']) as $profile) {
    if (!$profile instanceof ConfigEntityInterface) {
      continue;
    }
    $islands = $profile->get('islands');
    if (!\is_array($islands)) {
      continue;
    }
    $updated = $islands;
    foreach (['component_library', 'block_library'] as $island) {
      $ids = $islands[$island]['exclude_id'] ?? NULL;
      if (\is_string($ids)) {
        $updated[$island]['exclude_id'] = \array_values(\array_filter(\preg_split('/\s+/', \trim($ids)) ?: []));
      }
    }
    if ($updated !== $islands) {
      $profile->set('islands', $updated)->save();
    }
  }
}

/**
 * Put the page title of the sign-in page layout in a level 1 heading.
 *
 * Display Builder 1.0.0-beta10 prints the page title source as text only: the
 * sign-in screens a page layout draws lost their h1. A page title straight in
 * the title slot of the Sign in component goes in the Heading component,
 * level 1, as in the page layout the theme ships.
 */
function ui_suite_uikit_post_update_sign_in_page_title_heading(): void {
  $entity_type_manager = \Drupal::entityTypeManager();
  if (!$entity_type_manager->hasDefinition('page_layout')) {
    return;
  }
  $layout = $entity_type_manager->getStorage('page_layout')->load('ui_suite_uikit_sign_in');
  if (!$layout instanceof ConfigEntityInterface) {
    return;
  }
  $sources = $layout->get('sources');
  if (!\is_array($sources)) {
    return;
  }
  $updated = PageTitleHeading::inSlot($sources, 'ui_suite_uikit:sign_in', 'title');
  if ($updated === $sources) {
    return;
  }
  // Saved as a configuration import: Display Builder makes the new sources
  // the present state of the builder too, so the old title does not stay as a
  // draft that the next publish would bring back.
  $layout->set('sources', $updated)->setSyncing(TRUE)->save();
}
