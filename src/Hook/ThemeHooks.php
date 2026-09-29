<?php

declare(strict_types=1);

namespace Drupal\ui_suite_uikit\Hook;

use Drupal\block\BlockInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ui_skins\UiSkinsInterface;
use Drupal\ui_suite_uikit\Contrast;
use Drupal\ui_suite_uikit\SystemDarkMode;

/**
 * Library, theme settings, page and block hooks for UI Suite UIkit.
 */
class ThemeHooks {

  use StringTranslationTrait;

  /**
   * The UIkit version shipped with the theme.
   */
  public const string UIKIT_VERSION = '3.25.22';

  /**
   * The ID of the offcanvas printing the "Offcanvas" region.
   */
  public const string OFFCANVAS_ID = 'ui-suite-uikit-offcanvas';

  /**
   * The color mode of a site that has not picked one.
   *
   * The front end stays light until the site picks another mode: the
   * operating system of a visitor does not change the brand by surprise.
   */
  public const string COLOR_MODE = 'light';

  /**
   * The font of a site that has not picked one: the font of the theme.
   */
  public const string FONT_FAMILY = 'atkinson';

  /**
   * The file of the font of the text, loaded before the stylesheets.
   */
  public const string FONT_PRELOAD = 'assets/fonts/atkinson-hyperlegible-next/atkinson-hyperlegible-next-latin-wght-normal.woff2';

  public function __construct(
    protected ThemeSettingsProvider $themeSettingsProvider,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected HtmxNavigationHooks $htmxNavigationHooks,
    protected ThemeExtensionList $themeExtensionList,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected SignInHooks $signInHooks,
  ) {}

  /**
   * Implements hook_library_info_alter().
   *
   * Switches the UIkit framework assets to a local copy when requested in the
   * theme settings.
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, string $extension): void {
    if ($extension !== 'ui_suite_uikit' || !isset($libraries['uikit'])) {
      return;
    }
    if ($this->themeSettingsProvider->getSetting('uikit_source', 'ui_suite_uikit') !== 'local') {
      return;
    }
    $libraries['uikit']['css'] = [
      'base' => [
        '/libraries/uikit/dist/css/uikit.min.css' => ['minified' => TRUE],
      ],
    ];
    $libraries['uikit']['js'] = [
      '/libraries/uikit/dist/js/uikit.min.js' => ['minified' => TRUE],
      '/libraries/uikit/dist/js/uikit-icons.min.js' => ['minified' => TRUE],
    ];
  }

  /**
   * Implements hook_form_FORM_ID_alter() for 'system_theme_settings'.
   */
  #[Hook('form_system_theme_settings_alter')]
  public function formSystemThemeSettingsAlter(array &$form, FormStateInterface $form_state): void {
    // The theme settings form calls theme alters directly, without a form ID,
    // and the form alter runs again when this theme is also the active theme:
    // add the settings only once, and only to the form of this theme, never
    // to the form of another theme. See issue #943212.
    if (isset($form['ui_suite_uikit_sections']) || ($form['config_key']['#value'] ?? NULL) !== 'ui_suite_uikit.settings') {
      return;
    }

    $form['#attributes']['class'][] = 'ui-suite-uikit-settings';
    $form['#attached']['library'][] = 'ui_suite_uikit/settings';
    $setting = fn (string $name, mixed $default): mixed => $this->themeSettingsProvider->getSetting($name, 'ui_suite_uikit') ?? $default;
    $skin = $this->skinValues();

    // The sections of the page, as vertical tabs. The page elements, the logo
    // and the favicon of core are sections too.
    $form['ui_suite_uikit_sections'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Settings of the theme'),
      '#default_tab' => 'edit-ui-suite-uikit-colors',
      '#weight' => -100,
    ];
    $sections = [
      'ui_suite_uikit_colors' => [
        $this->t('Colors and color mode'),
        $this->t('The color mode, and the brand color that the buttons, links and focused fields use. Every other color is a CSS variable.'),
      ],
      'ui_suite_uikit_typography' => [
        $this->t('Typography'),
        $this->t('The font, the size of the text and the length of its lines.'),
      ],
      'ui_suite_uikit_layout' => [
        $this->t('Layout and navigation'),
        $this->t('The navbar, the navigation between the pages, and the corners of the components.'),
      ],
      'ui_suite_uikit_accessibility' => [
        $this->t('Accessibility'),
        $this->t('The theme meets WCAG 2.2 AAA in both color modes. These settings can only make it easier to use: the smallest values keep the level.'),
      ],
      'ui_suite_uikit_advanced' => [
        $this->t('Advanced'),
        $this->t('Where UIkit comes from, and where to change everything else.'),
      ],
    ];
    $weight = 0;
    foreach ($sections as $key => [$title, $description]) {
      $form[$key] = [
        '#type' => 'details',
        '#title' => $title,
        '#description' => $description,
        '#group' => 'ui_suite_uikit_sections',
        '#weight' => $weight++,
      ];
    }
    foreach (['theme_settings', 'logo', 'favicon'] as $key) {
      if (isset($form[$key])) {
        $form[$key]['#group'] = 'ui_suite_uikit_sections';
        $form[$key]['#weight'] = 10 + $weight++;
        unset($form[$key]['#open']);
      }
    }

    // Colors and color mode.
    $form['ui_suite_uikit_colors']['color_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Color mode'),
      '#description' => $this->t('Both modes meet WCAG 2.2 AAA. "Follow the operating system" shows the dark mode to the visitors whose system asks for it.'),
      '#default_value' => $this->colorMode(),
      '#options' => [
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
        'auto' => $this->t('Follow the operating system'),
      ],
      '#attributes' => ['class' => ['ui-suite-uikit-picker', 'ui-suite-uikit-picker--color-mode']],
    ];
    $form['ui_suite_uikit_colors']['ui_suite_uikit_skin_brand'] = [
      '#type' => 'color',
      '#title' => $this->t('Brand color'),
      '#description' => $this->t('The background of the primary buttons and sections, and the color of the links in the light mode. It needs a contrast of 7:1 with white. The dark mode keeps its own light link color.'),
      '#default_value' => $skin['brand'],
    ];
    $link = $this->link($this->t('Every color, in both modes, is a CSS variable'), 'ui_skins.css_variables.theme_settings');
    if ($link) {
      $form['ui_suite_uikit_colors']['ui_suite_uikit_css_variables_link'] = $link;
    }

    // Typography.
    $form['ui_suite_uikit_typography']['font_family'] = [
      '#type' => 'radios',
      '#title' => $this->t('Font'),
      '#description' => $this->t('Atkinson Hyperlegible Next is served by the theme, with no request to another site: its letters and figures are easy to tell apart.'),
      '#default_value' => $this->fontFamily(),
      '#options' => [
        'atkinson' => $this->t('Atkinson Hyperlegible Next'),
        'system' => $this->t('The fonts of the operating system'),
      ],
      '#attributes' => ['class' => ['ui-suite-uikit-picker', 'ui-suite-uikit-picker--font']],
    ];
    $form['ui_suite_uikit_typography']['ui_suite_uikit_skin_font_size'] = [
      '#type' => 'radios',
      '#title' => $this->t('Text size'),
      '#description' => $this->t('The size of the body text. The headings grow with it.'),
      '#default_value' => $skin['font_size'],
      '#options' => [
        '16px' => $this->t('16 pixels'),
        '17px' => $this->t('17 pixels'),
        '18px' => $this->t('18 pixels'),
        '20px' => $this->t('20 pixels'),
      ],
      '#attributes' => ['class' => ['ui-suite-uikit-picker', 'ui-suite-uikit-picker--size']],
    ];
    $form['ui_suite_uikit_typography']['ui_suite_uikit_skin_measure'] = [
      '#type' => 'radios',
      '#title' => $this->t('Line length'),
      '#description' => $this->t('The widest a paragraph gets. All three stay under the 80 characters that WCAG 1.4.8 allows.'),
      '#default_value' => $skin['measure'],
      '#options' => [
        '28em' => $this->t('Narrow, about 62 characters'),
        '32em' => $this->t('Comfortable, about 72 characters'),
        '35em' => $this->t('Wide, about 78 characters'),
      ],
      '#attributes' => ['class' => ['ui-suite-uikit-picker', 'ui-suite-uikit-picker--measure']],
    ];

    // Layout and navigation.
    $form['ui_suite_uikit_layout']['navbar_sticky'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sticky navbar'),
      '#description' => $this->t('The navbar stays at the top of the window while the page scrolls.'),
      '#default_value' => (bool) $setting('navbar_sticky', TRUE),
    ];
    $form['ui_suite_uikit_layout']['htmx_navigation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('HTMX navigation'),
      '#description' => $this->t('Links are loaded with HTMX: only the page content is swapped, without full page reloads. Forms, administration pages and files keep the normal navigation.'),
      '#default_value' => (bool) $setting('htmx_navigation', TRUE),
    ];
    $form['ui_suite_uikit_layout']['ui_suite_uikit_skin_radius'] = [
      '#type' => 'radios',
      '#title' => $this->t('Corners'),
      '#description' => $this->t('How round the buttons, fields, cards, alerts and dialogs are.'),
      '#default_value' => $skin['radius'],
      '#options' => [
        '0' => $this->t('Square'),
        '4px' => $this->t('Soft'),
        '8px' => $this->t('Rounded'),
        '16px' => $this->t('Round'),
      ],
      '#attributes' => ['class' => ['ui-suite-uikit-picker', 'ui-suite-uikit-picker--radius']],
    ];

    // Accessibility.
    $form['ui_suite_uikit_accessibility']['ui_suite_uikit_skin_focus'] = [
      '#type' => 'color',
      '#title' => $this->t('Focus ring color'),
      '#description' => $this->t('The ring around the element the keyboard is on, in the light mode. It needs a contrast of 3:1 with white.'),
      '#default_value' => $skin['focus'],
    ];
    $form['ui_suite_uikit_accessibility']['ui_suite_uikit_skin_focus_width'] = [
      '#type' => 'radios',
      '#title' => $this->t('Focus ring width'),
      '#default_value' => $skin['focus_width'],
      '#options' => [
        '2px' => $this->t('2 pixels'),
        '3px' => $this->t('3 pixels'),
        '4px' => $this->t('4 pixels'),
      ],
      '#attributes' => ['class' => ['ui-suite-uikit-picker', 'ui-suite-uikit-picker--ring']],
    ];
    $form['ui_suite_uikit_accessibility']['ui_suite_uikit_skin_target_size'] = [
      '#type' => 'radios',
      '#title' => $this->t('Target size'),
      '#description' => $this->t('The smallest width and height of what a visitor clicks or taps, and the height of the buttons and fields.'),
      '#default_value' => $skin['target_size'],
      '#options' => [
        '44px' => $this->t('44 pixels'),
        '48px' => $this->t('48 pixels'),
        '56px' => $this->t('56 pixels'),
      ],
      '#attributes' => ['class' => ['ui-suite-uikit-picker', 'ui-suite-uikit-picker--size']],
    ];

    // Advanced.
    $form['ui_suite_uikit_advanced']['uikit_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('UIkit library source'),
      '#options' => [
        'cdn' => $this->t('jsDelivr CDN (UIkit @version)', ['@version' => static::UIKIT_VERSION]),
        'local' => $this->t('Local copy in <code>/libraries/uikit</code>'),
      ],
      '#default_value' => $setting('uikit_source', 'cdn'),
    ];
    $form['ui_suite_uikit_advanced']['ui_suite_uikit_more'] = [
      '#theme' => 'item_list',
      '#title' => $this->t('Everything else is configuration'),
      '#items' => \array_filter([
        $this->link($this->t('CSS variables: every color, size, space and shadow'), 'ui_skins.css_variables.theme_settings'),
        $this->link($this->t('Page layouts of Display Builder'), 'entity.page_layout.collection'),
        $this->link($this->t('The components of the theme'), 'ui_patterns_library.provider', ['provider' => 'ui_suite_uikit']),
      ]),
    ];

    $this->signInSettings($form);
    // UI Skins offers the color modes of this theme as well: a second control
    // for the same attribute. The setting above is the one control, and it is
    // stored for UI Skins too, so both agree. The theme settings form calls
    // this alter before the form alter of UI Skins: hide its control once the
    // form is built.
    $form['#after_build'][] = [static::class, 'hideUiSkinsColorMode'];
    $form['#validate'][] = [static::class, 'skinValidate'];
    $form['#submit'][] = [static::class, 'themeSettingsSubmit'];
    $form['#submit'][] = [static::class, 'skinSubmit'];
  }

  /**
   * Adds the settings of the sign-in screens to the theme settings form.
   *
   * @param array $form
   *   The theme settings form.
   */
  protected function signInSettings(array &$form): void {
    $setting = fn (string $name, mixed $default): mixed => $this->themeSettingsProvider->getSetting($name, 'ui_suite_uikit') ?? $default;
    $form['ui_suite_uikit_sign_in'] = [
      '#type' => 'details',
      '#title' => $this->t('Sign-in screens'),
      '#description' => $this->t('The log in, create account, password reset and log out screens, when this theme shows them.'),
      '#group' => 'ui_suite_uikit_sections',
      '#weight' => 2.5,
    ];
    $group = &$form['ui_suite_uikit_sign_in'];
    $group['sign_in_layout'] = [
      '#type' => 'radios',
      '#title' => $this->t('Layout'),
      '#default_value' => $setting('sign_in_layout', 'center'),
      '#options' => [
        'center' => $this->t('Centered: the form alone, the site name above it'),
        'start' => $this->t('Form first: the form at the start, the brand panel next to it'),
        'end' => $this->t('Brand first: the brand panel, then the form'),
        'top' => $this->t('Brand band above the form'),
        'bottom' => $this->t('Brand band under the form'),
        'spotlight' => $this->t('Spotlight: the form floating over the brand color'),
      ],
      '#attributes' => ['class' => ['ui-suite-uikit-picker', 'ui-suite-uikit-picker--sign-in']],
    ];
    $group['sign_in_header'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the site header'),
      '#description' => $this->t('The navbar of the site above the screen. The site name then leaves the brand panel.'),
      '#default_value' => (bool) $setting('sign_in_header', FALSE),
    ];
    $group['sign_in_footer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the site footer'),
      '#default_value' => (bool) $setting('sign_in_footer', FALSE),
    ];
    $group['sign_in_logo'] = [
      '#type' => 'radios',
      '#title' => $this->t('Logo'),
      '#default_value' => $setting('sign_in_logo', 'site'),
      '#options' => [
        'site' => $this->t('The logo of the site (see Logo image above)'),
        'theme' => $this->t('The logo of the theme'),
        'none' => $this->t('No logo, the site name only'),
      ],
      '#attributes' => ['class' => ['ui-suite-uikit-picker', 'ui-suite-uikit-picker--list']],
    ];
    $group['sign_in_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#description' => $this->t('One sentence under the site name, like "Sign in to write, review and publish."'),
      '#maxlength' => 160,
      '#default_value' => $setting('sign_in_message', ''),
    ];
    $group['sign_in_image'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image'),
      '#description' => $this->t('The URL or the path of an image for the brand panel, like /sites/default/files/sign-in.jpg. It is decorative. The centered layout shows no image.'),
      '#maxlength' => 2048,
      '#default_value' => $setting('sign_in_image', ''),
    ];
    $group['sign_in_image_credit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image credit'),
      '#description' => $this->t('The author and the license of the image.'),
      '#maxlength' => 160,
      '#default_value' => $setting('sign_in_image_credit', ''),
    ];
    $group['sign_in_help'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Help'),
      '#description' => $this->t('A support line under the form, like "Trouble signing in? Write to the site team."'),
      '#maxlength' => 255,
      '#default_value' => $setting('sign_in_help', ''),
    ];
    $options = $this->pageLayoutOptions();
    if ($options !== NULL) {
      $group['sign_in_page_layout'] = [
        '#type' => 'select',
        '#title' => $this->t('Page layout'),
        '#description' => $this->t('A Display Builder page layout that draws the sign-in screens instead of the page of the theme. The chosen layout is enabled, the one chosen before is disabled.'),
        '#options' => ['' => $this->t('- The page of the theme -')] + $options,
        '#default_value' => $setting('sign_in_page_layout', ''),
      ];
      $form['#submit'][] = [static::class, 'signInPageLayoutSubmit'];
    }
  }

  /**
   * The page layouts of Display Builder, or NULL without the module.
   *
   * @return array<string, string>|null
   *   The labels of the page layouts, keyed by id.
   */
  protected function pageLayoutOptions(): ?array {
    if (!$this->entityTypeManager->hasDefinition('page_layout')) {
      return NULL;
    }
    $options = [];
    foreach ($this->entityTypeManager->getStorage('page_layout')->loadMultiple() as $id => $page_layout) {
      $options[(string) $id] = (string) $page_layout->label();
    }
    \asort($options);
    return $options;
  }

  /**
   * Submit callback: enables the sign-in page layout, disables the old one.
   *
   * The theme settings are saved by then: the form value holds the new one,
   * the element its default value the old one.
   */
  public static function signInPageLayoutSubmit(array &$form, FormStateInterface $form_state): void {
    $new = (string) $form_state->getValue('sign_in_page_layout');
    $old = (string) ($form['ui_suite_uikit_sign_in']['sign_in_page_layout']['#default_value'] ?? '');
    if ($new === $old) {
      return;
    }
    $storage = \Drupal::entityTypeManager()->getStorage('page_layout');
    if ($old !== '' && ($layout = $storage->load($old))) {
      $layout->disable()->save();
    }
    if ($new !== '' && ($layout = $storage->load($new))) {
      $layout->enable()->save();
    }
  }

  /**
   * The design tokens the settings page changes, and their UI Skins ids.
   */
  public const array SKIN_TOKENS = [
    'brand' => ['ui-suite-uikit-global-primary-background', '#0f5695'],
    'font_size' => ['ui-suite-uikit-global-font-size', '16px'],
    'measure' => ['ui-suite-uikit-measure', '32em'],
    'radius' => ['ui-suite-uikit-global-border-radius', '0'],
    'focus' => ['ui-suite-uikit-focus-color', '#333333'],
    'focus_width' => ['ui-suite-uikit-focus-width', '2px'],
    'target_size' => ['ui-suite-uikit-target-size', '44px'],
  ];

  /**
   * The values of the design tokens the settings page changes.
   *
   * @return array<string, string>
   *   The values of the light mode, keyed like SKIN_TOKENS.
   */
  protected function skinValues(): array {
    $saved = $this->themeSettingsProvider->getSetting(UiSkinsInterface::CSS_VARIABLES_THEME_SETTING_KEY, 'ui_suite_uikit');
    $values = [];
    foreach (static::SKIN_TOKENS as $key => [$id, $default]) {
      $values[$key] = (string) (\is_array($saved) ? ($saved[$id][':root'] ?? $default) : $default);
    }
    return $values;
  }

  /**
   * A link to a route, or NULL when it does not exist or is not allowed.
   */
  protected function link(\Stringable|string $title, string $route, array $parameters = []): ?array {
    try {
      $url = Url::fromRoute($route, $route === 'ui_skins.css_variables.theme_settings' ? ['theme' => 'ui_suite_uikit'] : $parameters);
      // The route must exist and take these parameters.
      $url->toString();
      if (!$url->access()) {
        return NULL;
      }
    }
    catch (\Exception) {
      return NULL;
    }
    return ['#type' => 'link', '#title' => $title, '#url' => $url];
  }

  /**
   * Validate callback: the colors keep WCAG AAA, the tokens leave the values.
   *
   * Core saves every value of the form as a setting of the theme. The design
   * tokens belong in the keys of UI Skins: they leave the values here, and
   * skinSubmit() stores them once core has saved the settings.
   */
  public static function skinValidate(array &$form, FormStateInterface $form_state): void {
    $brand = (string) $form_state->getValue('ui_suite_uikit_skin_brand');
    if ($brand !== '' && Contrast::ratio($brand, '#ffffff') < 7) {
      $form_state->setErrorByName('ui_suite_uikit_skin_brand', new TranslatableMarkup('The brand color has a contrast of @ratio:1 with white: WCAG 2.2 AAA asks for 7:1. Pick a darker color.', ['@ratio' => \number_format(Contrast::ratio($brand, '#ffffff'), 2)]));
    }
    $focus = (string) $form_state->getValue('ui_suite_uikit_skin_focus');
    if ($focus !== '' && Contrast::ratio($focus, '#ffffff') < 3) {
      $form_state->setErrorByName('ui_suite_uikit_skin_focus', new TranslatableMarkup('The focus ring color has a contrast of @ratio:1 with white: WCAG 2.2 asks for 3:1. Pick a darker color.', ['@ratio' => \number_format(Contrast::ratio($focus, '#ffffff'), 2)]));
    }
    $skin = [];
    foreach (\array_keys(static::SKIN_TOKENS) as $key) {
      $skin[$key] = (string) $form_state->getValue('ui_suite_uikit_skin_' . $key);
      $form_state->unsetValue('ui_suite_uikit_skin_' . $key);
    }
    $form_state->set('ui_suite_uikit_skin', $skin);
  }

  /**
   * Submit callback: stores the design tokens in the keys of UI Skins.
   *
   * A value equal to the default of the theme is removed, like UI Skins does.
   * The brand color also sets the text and link colors of the light mode, and
   * their hover color.
   */
  public static function skinSubmit(array &$form, FormStateInterface $form_state): void {
    $skin = $form_state->get('ui_suite_uikit_skin');
    if (!\is_array($skin)) {
      return;
    }
    $config = \Drupal::configFactory()->getEditable('ui_suite_uikit.settings');
    $variables = $config->get(UiSkinsInterface::CSS_VARIABLES_THEME_SETTING_KEY);
    $variables = \is_array($variables) ? $variables : [];
    $values = [];
    foreach (static::SKIN_TOKENS as $key => [$id]) {
      $values[$id] = $skin[$key] ?? '';
    }
    $brand = \strtolower($skin['brand'] ?? '');
    $hover = $brand === '' ? '' : Contrast::shade($brand, -0.3);
    $values += [
      'ui-suite-uikit-global-primary-color' => $brand,
      'ui-suite-uikit-global-link-color' => $brand,
      'ui-suite-uikit-global-primary-background-hover' => $hover,
      'ui-suite-uikit-global-link-hover-color' => $hover,
      'ui-suite-uikit-control-height' => $skin['target_size'] ?? '',
    ];
    $defaults = [
      'ui-suite-uikit-global-primary-color' => '#0f5695',
      'ui-suite-uikit-global-link-color' => '#0f5695',
      'ui-suite-uikit-global-primary-background-hover' => '#093f6e',
      'ui-suite-uikit-global-link-hover-color' => '#093f6e',
      'ui-suite-uikit-control-height' => '44px',
    ];
    foreach (static::SKIN_TOKENS as [$id, $default]) {
      $defaults[$id] = $default;
    }
    // The default brand color gives the colors it sets their defaults back.
    if ($brand === $defaults['ui-suite-uikit-global-primary-background']) {
      foreach (['primary-color', 'link-color', 'primary-background-hover', 'link-hover-color'] as $name) {
        $values['ui-suite-uikit-global-' . $name] = $defaults['ui-suite-uikit-global-' . $name];
      }
    }
    foreach ($values as $id => $value) {
      if ($value === '' || $value === $defaults[$id]) {
        unset($variables[$id][':root']);
      }
      else {
        $variables[$id][':root'] = $value;
      }
      if (empty($variables[$id])) {
        unset($variables[$id]);
      }
    }
    $config->set(UiSkinsInterface::CSS_VARIABLES_THEME_SETTING_KEY, $variables)->save();
  }

  /**
   * Submit callback: the library source changes the library definitions.
   */
  public static function themeSettingsSubmit(array &$form, FormStateInterface $form_state): void {
    Cache::invalidateTags(['library_info']);
  }

  /**
   * Submit callback: stores the color mode for UI Skins too.
   */
  public static function colorModeSubmit(array &$form, FormStateInterface $form_state): void {
    static::syncUiSkinsColorMode(\Drupal::configFactory()->getEditable('ui_suite_uikit.settings'));
  }

  /**
   * After build callback: one color mode control.
   *
   * Hides the color mode control of UI Skins, and stores the color mode for
   * UI Skins once the settings are saved. Both are done here, once the form
   * is built: the theme settings form calls the alter of the theme it shows
   * before the form alter of UI Skins, and before it adds its own submit
   * handler, which saves the settings.
   */
  public static function hideUiSkinsColorMode(array $form, FormStateInterface $form_state): array {
    if (isset($form['third_party_settings']['ui_skins']['theme'])) {
      $form['third_party_settings']['ui_skins']['theme']['#access'] = FALSE;
    }
    $submit = [static::class, 'colorModeSubmit'];
    if (!\in_array($submit, $form['#submit'] ?? [], TRUE)) {
      $form['#submit'][] = $submit;
    }
    return $form;
  }

  /**
   * Stores the color mode of the theme settings as the UI Skins theme.
   *
   * "Follow the operating system" clears it: the stylesheet follows the
   * system when the html element has no data-theme.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The editable settings of the theme.
   */
  public static function syncUiSkinsColorMode(Config $config): void {
    $mode = $config->get('color_mode') ?: static::COLOR_MODE;
    if (\in_array($mode, ['light', 'dark'], TRUE)) {
      $config->set('third_party_settings.ui_skins.theme', 'ui_suite_uikit_' . $mode);
    }
    else {
      $config->clear('third_party_settings.ui_skins.theme');
    }
    $config->save();
  }

  /**
   * The color mode of the theme settings: auto, light or dark.
   */
  protected function colorMode(): string {
    $mode = $this->themeSettingsProvider->getSetting('color_mode', 'ui_suite_uikit');
    return \in_array($mode, ['auto', 'light', 'dark'], TRUE) ? $mode : static::COLOR_MODE;
  }

  /**
   * The font of the theme settings: atkinson or system.
   */
  protected function fontFamily(): string {
    $font = $this->themeSettingsProvider->getSetting('font_family', 'ui_suite_uikit');
    return \in_array($font, ['atkinson', 'system'], TRUE) ? $font : static::FONT_FAMILY;
  }

  /**
   * Implements hook_preprocess_HOOK() for 'html'.
   *
   * The color mode reaches the stylesheet as the data-theme attribute of the
   * html element: none when the operating system decides. The dark values
   * saved in UI Skins are printed for that case too, at the top of the page,
   * where UI Skins prints its own. The font of the theme settings is the
   * data-font attribute.
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(array &$variables): void {
    $mode = $this->colorMode();
    if ($mode !== 'auto') {
      $variables['html_attributes']->setAttribute('data-theme', $mode);
    }
    // The font reaches the stylesheet as the data-font attribute. The file of
    // the text is loaded early, so the first paint uses it.
    $font = $this->fontFamily();
    $variables['html_attributes']->setAttribute('data-font', $font);
    if ($font === 'atkinson') {
      $variables['#attached']['html_head_link'][] = [
        [
          'rel' => 'preload',
          'href' => $this->fileUrlGenerator->generateString($this->themeExtensionList->getPath('ui_suite_uikit') . '/' . static::FONT_PRELOAD),
          'as' => 'font',
          'type' => 'font/woff2',
          'crossorigin' => 'anonymous',
        ],
      ];
    }
    $css = SystemDarkMode::css($this->themeSettingsProvider->getSetting(UiSkinsInterface::CSS_VARIABLES_THEME_SETTING_KEY, 'ui_suite_uikit'));
    if ($css !== '') {
      if (!\is_array($variables['page_top'] ?? NULL)) {
        $variables['page_top'] = [];
      }
      $variables['page_top']['ui_suite_uikit_system_dark_mode'] = [
        '#type' => 'html_tag',
        '#tag' => 'style',
        '#value' => $css,
        // After the style element of UI Skins.
        '#weight' => 100,
      ];
    }
    $variables['#cache']['tags'][] = 'config:ui_suite_uikit.settings';
    $this->htmxNavigationHooks->preprocessHtml($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for 'page'.
   */
  #[Hook('preprocess_page')]
  public function preprocessPage(array &$variables): void {
    $variables['navbar_sticky'] = (bool) ($this->themeSettingsProvider->getSetting('navbar_sticky', 'ui_suite_uikit') ?? TRUE);
    $variables['offcanvas_id'] = static::OFFCANVAS_ID;
    $this->signInHooks->preprocessPage($variables);

    // Display Builder page layouts render the blocks without block entities
    // and block templates: tag the menus with their region here, and render
    // the site branding as the UIkit logo.
    foreach (['navbar_left', 'navbar_center', 'navbar_right', 'offcanvas', 'footer'] as $region) {
      if (isset($variables['page'][$region]) && \is_array($variables['page'][$region])) {
        $this->prepareRegionElements($variables['page'][$region], $region);
      }
    }
  }

  /**
   * Prepares the eagerly built menus and branding elements of a region.
   *
   * @param array $element
   *   A render array of the region.
   * @param string $region
   *   The region machine name.
   * @param int $depth
   *   The recursion depth.
   */
  protected function prepareRegionElements(array &$element, string $region, int $depth = 0): void {
    if ($depth > 8) {
      return;
    }
    foreach ($element as $key => &$child) {
      if (!\is_array($child) || (\is_string($key) && \str_starts_with($key, '#'))) {
        continue;
      }
      $theme = $child['#theme'] ?? '';
      if (\is_string($theme) && \str_starts_with($theme, 'menu')) {
        $child['#attributes']['data-uikit-region'] = $region;
        continue;
      }
      if (\str_starts_with($region, 'navbar_') && \array_key_exists('site_name', $child) && \array_key_exists('site_logo', $child)) {
        $child = [
          '#type' => 'inline_template',
          '#template' => '<a href="{{ url }}" class="uk-navbar-item uk-logo" rel="home">{% if logo %}<span class="uk-margin-small-right">{{ logo }}</span>{% endif %}{% if name %}<span>{{ name }}</span>{% endif %}</a>',
          '#context' => [
            'url' => Url::fromRoute('<front>')->toString(),
            'logo' => $child['site_logo'] ?? NULL,
            'name' => $child['site_name'] ?? NULL,
          ],
          '#cache' => $child['#cache'] ?? [],
        ];
        continue;
      }
      $this->prepareRegionElements($child, $region, $depth + 1);
    }
  }

  /**
   * Tells if the HTMX navigation is enabled.
   */
  protected function htmxNavigation(): bool {
    return (bool) ($this->themeSettingsProvider->getSetting('htmx_navigation', 'ui_suite_uikit') ?? TRUE);
  }

  /**
   * Implements hook_preprocess_HOOK() for 'off_canvas_page_wrapper'.
   */
  #[Hook('preprocess_off_canvas_page_wrapper')]
  public function preprocessOffCanvasPageWrapper(array &$variables): void {
    $variables['htmx_navigation'] = $this->htmxNavigation();
    $variables['#cache']['tags'][] = 'config:ui_suite_uikit.settings';
    $this->htmxNavigationHooks->preprocessOffCanvasPageWrapper($variables);
    if ($variables['htmx_navigation']) {
      $variables['#attached']['library'][] = 'ui_suite_uikit/htmx';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for 'page_title'.
   *
   * Display Builder page layouts cast the title markup (for example the
   * <span> of the node title field) to a plain string, which would be
   * escaped: restore it as filtered markup.
   */
  #[Hook('preprocess_page_title')]
  public function preprocessPageTitle(array &$variables): void {
    $title = $variables['title'] ?? NULL;
    if (\is_string($title) && \str_contains($title, '<')) {
      $variables['title'] = Markup::create(Xss::filter($title, ['span', 'em', 'strong', 'i', 'b', 'small', 'sup', 'sub']));
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for 'block'.
   *
   * Passes the block region to the menus, so they are rendered with the
   * navbar, offcanvas or subnav components.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(array &$variables): void {
    if (($variables['base_plugin_id'] ?? '') !== 'system_menu_block' || empty($variables['elements']['#id'])) {
      return;
    }
    $block = $this->entityTypeManager->getStorage('block')->load($variables['elements']['#id']);
    if ($block instanceof BlockInterface) {
      $variables['content']['#attributes']['data-uikit-region'] = $block->getRegion();
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter() for 'menu'.
   */
  #[Hook('theme_suggestions_menu_alter')]
  public function themeSuggestionsMenuAlter(array &$suggestions, array $variables): void {
    $region = $variables['attributes']['data-uikit-region'] ?? NULL;
    $suggestion = match ($region) {
      'navbar_left', 'navbar_center', 'navbar_right' => 'menu__uikit_navbar',
      'offcanvas' => 'menu__uikit_offcanvas',
      'footer' => 'menu__uikit_subnav',
      default => NULL,
    };
    if ($suggestion) {
      $suggestions[] = $suggestion;
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for 'menu'.
   */
  #[Hook('preprocess_menu')]
  public function preprocessMenu(array &$variables): void {
    $variables['uikit_region'] = $variables['attributes']['data-uikit-region'] ?? NULL;
    unset($variables['attributes']['data-uikit-region']);
    $this->htmxNavigationHooks->preprocessMenu($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for 'menu_local_action'.
   */
  #[Hook('preprocess_menu_local_action')]
  public function preprocessMenuLocalAction(array &$variables): void {
    $variables['link']['#options']['attributes']['class'][] = 'uk-button';
    $variables['link']['#options']['attributes']['class'][] = 'uk-button-primary';
    $variables['link']['#options']['attributes']['class'][] = 'uk-button-small';
    $this->htmxNavigationHooks->preprocessMenuLocalAction($variables);
  }

}
