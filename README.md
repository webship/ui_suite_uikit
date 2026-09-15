# UI Suite UIkit

A site-builder friendly [UIkit](https://getuikit.com) 3 theme for Drupal, using the
[UI Suite](https://www.drupal.org/project/ui_suite) approach.

Use UIkit directly from the Drupal back office: [Display Builder](https://www.drupal.org/project/display_builder),
Layout Builder, Manage display, Views, blocks… Every UIkit component is a
[Single Directory Component](https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components)
with typed props, slots, variants and stories.

## Requirements

- Drupal 11.4 or 12
- [UI Patterns 2](https://www.drupal.org/project/ui_patterns) (components, library, blocks, layouts, field formatters, views)
- [UI Styles](https://www.drupal.org/project/ui_styles): the UIkit utility classes
- [UI Icons](https://www.drupal.org/project/ui_icons): the UIkit icon pack, shipped with the theme

Recommended:

- [Display Builder](https://www.drupal.org/project/display_builder): build page layouts, entity displays and views with the components
- [UI Skins](https://www.drupal.org/project/ui_skins): UIkit design tokens (CSS variables) and the dark color mode
- [UI Examples](https://www.drupal.org/project/ui_examples)
- [Web View Modes Inventory](https://www.drupal.org/project/webvmi): card view modes laid out with the theme
  components

The [UI Suite Base](https://www.drupal.org/project/ui_suite_base) recipe installs the whole stack.

## Installation

```shell
composer require drupal/ui_suite_uikit
drush theme:enable ui_suite_uikit
drush config:set system.theme default ui_suite_uikit
```

The theme places its default blocks (branding, menus, breadcrumb, messages, tabs, footer) in its regions.

## UIkit library

UIkit 3.25.22 is loaded from the jsDelivr CDN by default. To serve it locally, install the library in
`web/libraries/uikit` and select *Local copy* in the theme settings
(*Appearance > Settings > UI Suite UIkit*).

With [Asset Packagist](https://asset-packagist.org):

```json
{
    "require": {
        "npm-asset/uikit": "3.25.22"
    },
    "extra": {
        "installer-types": ["npm-asset"],
        "installer-paths": {
            "web/libraries/{$name}": ["type:drupal-library", "type:npm-asset"]
        }
    }
}
```

## Components

| Group | Components |
|---|---|
| Layout | Section, Container, Grid, Grid: 2 columns, Grid: 3 columns, Grid: 4 columns, Tile, Placeholder |
| Navigation | Navbar, Navbar nav, Navbar item, Navbar toggle, Nav, Subnav, Tab, Breadcrumb, Pagination, To top |
| Elements | Button, Button group, Heading, Link, Label, Badge, Divider, Close, Icon, Blockquote |
| Data display | Card, Article, Comment, List, Description list, Table, Countdown |
| Feedback | Alert, Progress, Spinner, Tooltip |
| Interactive | Accordion, Dropdown, Modal, Offcanvas, Switcher |
| Media | Cover, Overlay, Slider, Slideshow, Lightbox |
| Forms | Search |

Internal sub-components, used in the slots of their parent, are named between parentheses: Accordion item,
Description list item, Lightbox item, Slideshow item, Switcher tab, Switcher panel, Table row, Table cell.

Browse them in the component library: *Appearance > UI libraries* (`/admin/appearance/ui/components/ui_suite_uikit`).

### Made for Display Builder

- Every component has a `group`, clear slot titles and typed props, shown in the Display Builder panels.
- Container components declare the `expected` components of their slots (accordion items, switcher tabs and
  panels, table rows and cells, slideshow items…).
- Layouts are variants: *Grid: 2 columns* offers 50/50, 66/33, 33/66, 75/25 and 25/75; *Grid: 3 columns*
  offers 33/33/33, 50/25/25, 25/50/25 and 25/25/50.
- Components linked by an ID (Button or Navbar toggle → Modal or Offcanvas) use the *Toggle target* prop.
- UIkit JavaScript initializes the components added to the page at any time, so previews and AJAX updates
  work without Drupal behaviors.

### Web View Modes Inventory

The theme lays out the view modes of [Web View Modes Inventory](https://www.drupal.org/project/webvmi) with its
components, in Display Builder. When one of these view modes is enabled for a content type (or with
`drush webvmi:apply <content type>`), its display is built from the template in `webvmi/CONTENT_TYPE_NAME`:

| View modes | Layout |
|---|---|
| Card | Card: image on top, h3 title, trimmed body |
| Impressed card: xlarge, large, medium, small, xsmall | Card: image on top, title, trimmed body, date in the footer |
| Featured card: xlarge, large, medium, small, xsmall | Card: image on the left, title, trimmed body, date in the footer |
| Text card: large, medium, small | Card with a hover effect: title, trimmed body, date in the footer |
| Overlay card: xlarge, large, medium | Overlay (dark, at the bottom, on hover for medium): heading, trimmed body |
| Hero card | Cover (large, light content at the bottom left): medium heading, trimmed body |
| Full | Article: date in the meta, image and body |

The sizes change the card padding (large to small), the heading level (h2 to h5), the image style (wide, large,
medium) and the trim length. The templates use `CONTENT_TYPE_NAME`, `MEDIA_FIELD_NAME` and `DESCRIPTION_FIELD_NAME`
placeholders: the fields a content type does not have are removed. Themes generated from UI Suite UIkit use these
templates with their own components.

## Styles, design tokens and color modes

- `ui_suite_uikit.ui_styles.yml`: UIkit utilities (text, typography, colors, inverse, margin, padding, width,
  height, flex, position, visibility, box shadow, border radius, animation…) available in UI Styles and in the
  Display Builder *Styles* panel.
- `ui_suite_uikit.ui_skins.css_variables.yml`: the UIkit global colors and font family as CSS variables,
  editable in *Appearance > CSS variables* and in the Display Builder *Design tokens* panel.
- `assets/css/tokens.css` is generated from the UIkit CSS (`npm run build:tokens`): every declaration using a
  UIkit global color falls back to the UIkit value, so the rendering is identical to UIkit until a variable is
  set.
- `ui_suite_uikit.ui_skins.themes.yml`: *Light* and *Dark* color modes (`data-theme` attribute on `html`).

## Icons

The 162 UIkit icons are shipped in `icons/uikit` and declared as the `uikit` icon pack
(`ui_suite_uikit.icons.yml`), rendered as inline SVG with a `ratio` setting.

## Theme settings

- UIkit library source: CDN or local.
- Sticky navbar.
- HTMX navigation (enabled by default).

## HTMX navigation

With the HTMX navigation, the links of the page are boosted by [HTMX](https://htmx.org) through Drupal core's
`core/drupal.htmx` library: only the page wrapper is swapped, without full page reloads. Drupal core loads
the new CSS and JavaScript of the target page, merges `drupalSettings` and attaches the behaviors; UIkit
initializes the swapped components by itself.

There is no custom JavaScript: the rest is rendered on the server by `src/Hook/HtmxNavigationHooks.php`.

- Forms (except GET forms like search), administration, edit and delete pages, files, contextual and dialog
  links are rendered with `hx-boost="false"` and keep the normal navigation.
- UIkit closes the open offcanvas and dropdowns when HTMX swaps them; the links of modals keep the normal
  navigation.
- On boosted requests the main content anchor gets `autofocus`, which HTMX focuses after the swap, and the
  new page title is announced in a polite live region updated with `hx-select-oob`.

## Mapping with the UIkit design system

[docs/uikit-sdc-mapping.md](docs/uikit-sdc-mapping.md) maps every UIkit component to its SDC component, to
the Penpot design system and to the equivalent components of other UI Suite themes.

## Development

The tooling uses Node.js 20+:

```shell
npm install
npm run build:tokens   # Regenerate assets/css/tokens.css from node_modules/uikit.
npm run build:icons    # Refresh icons/uikit from node_modules/uikit.
```

### Tests

```shell
# Component definitions and templates (SDC Devel).
drush sdc-devel:validate ui_suite_uikit

# PHPUnit kernel tests: SDC Devel validation, and the rendering of every component and story.
SIMPLETEST_DB=mysql://db:db@db/db vendor/bin/phpunit -c web/core web/themes/contrib/ui_suite_uikit/tests/src/Kernel

# Functional tests (webship-js: Cucumber + Playwright) against a site running the theme.
LAUNCH_URL=https://example.ddev.site DRUPAL_PROJECT_DIR=/path/to/project npm test
```

The webship-js features cover the front end rendering, the offcanvas menu, forms and local tasks, color
modes, HTMX navigation, theme settings, UI Skins design tokens, every component page of the library, the
interactive components and Display Builder (component previews, builder, page layouts). Enable the Twig
debug (`drush theme:dev on`) to see the SDC component of every piece of markup in the HTML comments.

## Maintainers

- Rajab Natshah: [RajabNatshah](https://www.drupal.org/u/rajabnatshah)
