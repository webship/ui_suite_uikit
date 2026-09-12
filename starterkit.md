# STARTERKIT_NAME

A theme generated from [UI Suite UIkit](https://www.drupal.org/project/ui_suite_uikit): single directory components
(SDC) built with [UIkit](https://getuikit.com), [HTMX](https://htmx.org) navigation, UI Skins, UI Styles and UI Icons,
ready for [Display Builder](https://www.drupal.org/project/display_builder).

## Customize

- Colors and fonts: the CSS variables in `assets/css/tokens.css`, and the UI Skins settings in
  `STARTERKIT_MACHINE_NAME.ui_skins.css_variables.yml` and `STARTERKIT_MACHINE_NAME.ui_skins.themes.yml`.
- Components: the `components` folder. Each component has its `*.component.yml`, Twig template and stories.
- Utilities: `STARTERKIT_MACHINE_NAME.ui_styles.yml`.
- Card view modes: the Display Builder templates of [Web View Modes Inventory](https://www.drupal.org/project/webvmi)
  in the `webvmi` folder.
- Theme settings: Appearance > STARTERKIT_NAME (UIkit from the CDN or local libraries, sticky navbar, HTMX
  navigation).

## Update from UI Suite UIkit

Generate the theme again with the new UI Suite UIkit and compare:

```shell
php core/scripts/dr generate-theme STARTERKIT_MACHINE_NAME --starterkit ui_suite_uikit --path themes/custom
```
