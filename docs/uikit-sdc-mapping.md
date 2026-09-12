# UIkit design system ↔ Drupal components (SDC) mapping

How every [UIkit 3.25](https://getuikit.com/docs/introduction) component is implemented in **UI Suite UIkit**
(Single Directory Components for UI Patterns 2 and Display Builder), how it is drawn in the **Penpot design
system** (`~/workspace/designs/uikit-design-system`), and the equivalent component in the other themes studied:
[UI Suite Bootstrap](https://www.drupal.org/project/ui_suite_bootstrap), [UI Suite daisyUI](https://www.drupal.org/project/ui_suite_daisyui),
[UI Suite DSFR](https://www.drupal.org/project/ui_suite_dsfr), [UI Suite USWDS](https://www.drupal.org/project/ui_suite_uswds),
[UI Suite Material](https://www.drupal.org/project/ui_suite_material), [Pico](https://www.drupal.org/project/pico),
[Vartheme BS5](https://www.drupal.org/project/vartheme_bs5) and the Sobki theme (Bootstrap sub-theme of
[Sobki](https://www.drupal.org/project/sobki)).

Legend: `sdc` = SDC component id (`ui_suite_uikit:<id>`), **styles** = implemented as UI Styles utilities
(`ui_suite_uikit.ui_styles.yml`), **skins** = UI Skins CSS variables / color modes, **tpl** = Drupal template
integration, — = none.

## Layout

| UIkit | UI Suite UIkit (SDC) | Penpot | Bootstrap | daisyUI | DSFR | USWDS | Material | Vartheme BS5 | Sobki |
|---|---|---|---|---|---|---|---|---|---|
| [Section](https://getuikit.com/docs/section) | `section` (variants default/muted/primary/secondary) | tokens | — | hero | — | section | — | — | — |
| [Container](https://getuikit.com/docs/container) | `container` | tokens | grid_row_1 (container prop) | grid_1_region | — | — | — | atoms/container | container |
| [Grid](https://getuikit.com/docs/grid) + [Width](https://getuikit.com/docs/width) | `grid`, `grid_2_columns` (50/50, 66/33, 33/66, 75/25, 25/75), `grid_3_columns` (33×3, 50/25/25, 25/50/25, 25/25/50), `grid_4_columns` | — | grid_row, grid_row_2..4 | grid_2..4_regions, grid_cols | grid_row_2..4 | grid_row_2..4 | grid_row_2..4 | molecules/grid, atoms/row, atoms/column | grid_row_2_25_75, grid_row_3_50_25_25… |
| [Tile](https://getuikit.com/docs/tile) | `tile` | tokens | — | — | tile | — | — | — | — |
| [Card](https://getuikit.com/docs/card) | `card` (default/primary/secondary/blank, hover, image top/bottom/left/right) | Card ✅ | card, card_body, card_group, card_overlay | card | card | card, card_layout | card | organisms/card-* | card |
| [Cover](https://getuikit.com/docs/cover) | `cover` | — | — | hero | content_media | hero | — | organisms/media-header | — |
| [Overlay](https://getuikit.com/docs/overlay) | `overlay` | — | card_overlay | — | — | — | — | organisms/card-overlay | — |
| [Placeholder](https://getuikit.com/docs/placeholder) | `placeholder` | — | — | — | — | — | — | — | — |
| [Column](https://getuikit.com/docs/column), [Flex](https://getuikit.com/docs/flex), [Height](https://getuikit.com/docs/height), [Margin](https://getuikit.com/docs/margin), [Padding](https://getuikit.com/docs/padding), [Position](https://getuikit.com/docs/position), [Align](https://getuikit.com/docs/align) | **styles** | — | styles | styles | styles | — | — | scss utilities | styles |

## Navigation

| UIkit | UI Suite UIkit (SDC) | Penpot | Bootstrap | daisyUI | DSFR | USWDS | Material | Vartheme BS5 | Sobki |
|---|---|---|---|---|---|---|---|---|---|
| [Navbar](https://getuikit.com/docs/navbar) | `navbar`, `navbar_nav`, `navbar_item`, `navbar_toggle`, `logo`; tpl `menu--uikit-navbar` | Navbar ✅ | navbar, navbar_nav | navbar | header | header | top_app_bar | organisms/navbar, molecules/navbar-brand | navbar, navbar_nav, dropdown_megamenu |
| [Nav](https://getuikit.com/docs/nav) | `nav` (default/primary/secondary/dropdown/navbar_dropdown, accordion); tpl `menu`, `menu--uikit-offcanvas` | Nav ✅ | nav | menu | side_menu, nav_menu | side_navigation | list | organisms/nav, nav-menu | nav |
| [Subnav](https://getuikit.com/docs/subnav) | `subnav` (default/divider/pill); tpl `menu--uikit-subnav` | Subnav ✅ | nav (pills) | menu horizontal | link_group | nav_footer | chip_set | — | — |
| [Breadcrumb](https://getuikit.com/docs/breadcrumb) | `breadcrumb`; tpl `breadcrumb` | Breadcrumb ✅ | breadcrumb | breadcrumbs | breadcrumb | breadcrumb | — | molecules/breadcrumb | breadcrumb |
| [Pagination](https://getuikit.com/docs/pagination) | `pagination`; tpl `pager`, `views-mini-pager` | Pagination ✅ | pagination | pagination | pagination | pagination | pagination | molecules/pagination | pagination |
| [Tab](https://getuikit.com/docs/tab) | `tab` (links); tpl `menu-local-tasks` | Tab ✅ | nav (tabs) | tab, tabs | tabs, tab, tab_panel | — | tab_bar, tab | molecules/tabs, organisms/local-tasks | — |
| [Totop](https://getuikit.com/docs/totop) | `totop` | — | — | — | — | — | — | — | — |
| [Iconnav](https://getuikit.com/docs/iconnav) | — (planned: links with icons) | — | — | — | follow | social_links_group | — | — | — |
| [Dotnav](https://getuikit.com/docs/dotnav), [Thumbnav](https://getuikit.com/docs/thumbnav), [Slidenav](https://getuikit.com/docs/slidenav) | inside `slider` / `slideshow` (arrows, dotnav props) | — | carousel controls | carousel | — | — | — | organisms/carousel | carousel |
| [Dropnav](https://getuikit.com/docs/dropnav), [Dropbar](https://getuikit.com/docs/dropbar) | `navbar` dropdown mode (planned: dropbar option) | — | dropdown | — | — | — | menu | molecules/dropdown | dropdown_megamenu |

## Elements

| UIkit | UI Suite UIkit (SDC) | Penpot | Bootstrap | daisyUI | DSFR | USWDS | Material | Pico | Vartheme BS5 |
|---|---|---|---|---|---|---|---|---|---|
| [Button](https://getuikit.com/docs/button) | `button` (default/primary/secondary/danger/text/link, small/large, icon, toggle target), `button_group` | Button ✅ (54 variants) | button, button_group, button_toolbar | button, join | button, button_group | button, button_group | button, fab, icon_button | button | atoms/button |
| [Heading](https://getuikit.com/docs/heading) | `heading` (divider/bullet/line, sizes) | tokens | — | — | — | — | — | heading_group | atoms/heading |
| [Link](https://getuikit.com/docs/link) | `link` (muted/text/heading/reset) | — | — | link | link | — | — | link | atoms/link |
| [Label](https://getuikit.com/docs/label) | `label` (default/success/warning/danger) | Label ✅ | badge | badge | tag | tag | chip | — | — |
| [Badge](https://getuikit.com/docs/badge) | `badge` | Badge ✅ | badge | badge | badge | — | — | — | atoms/badge |
| [Divider](https://getuikit.com/docs/divider) | `divider` (icon/small/vertical) | Divider ✅ | — | divider | — | — | — | — | — |
| [Close](https://getuikit.com/docs/close) | `close` | — | close_button | — | — | — | — | — | atoms/close-button |
| [Icon](https://getuikit.com/docs/icon) | `icon` + UIkit icon pack (162 SVG, UI Icons) | — | icons.yml | icons.yml | icons | icon_list | icon_button | — | atoms/icon |
| [List](https://getuikit.com/docs/list) | `list` (divider/striped, markers, colors) | List ✅ | list, list_group | list, list_row | — | icon_list, process_list | list, list_item | — | atoms/list, molecules/list-group |
| [Description list](https://getuikit.com/docs/description-list) | `description_list`, `description_list_item` | — | — | — | — | summary_box | — | — | — |
| [Table](https://getuikit.com/docs/table) | `table`, `table_row`, `table_cell`; preprocess `table` | Table ✅ | table, table_row, table_cell | table, table_row, table_cell | — | table, table_row, table_cell | data_table, data_table_row | table, table_row, table_cell | molecules/table |
| [Base: blockquote](https://getuikit.com/docs/base) | `blockquote` | — | blockquote | — | quote | — | — | blockquote | — |
| [Article](https://getuikit.com/docs/article) | `article` (Website Starter: Web Blog full display) | — | — | prose | — | — | — | — | organisms/node |
| [Comment](https://getuikit.com/docs/comment) | `comment` | — | — | chat | — | — | — | — | molecules/comment |
| [Progress](https://getuikit.com/docs/progress) | `progress` | Progress ✅ | progress, progress_stacked | progress | — | step_indicator | — | — | atoms/progress-bar |
| [Spinner](https://getuikit.com/docs/spinner) | `spinner` | Spinner ✅ | spinner | loading | — | — | — | — | atoms/spinner |
| [Countdown](https://getuikit.com/docs/countdown) | `countdown` | — | — | — | — | — | — | — | — |
| [Form](https://getuikit.com/docs/form), [Search](https://getuikit.com/docs/search) | preprocess `input`, `select`, `textarea`, `fieldset`, labels, buttons; `search` | Form ✅, Search ✅ | form templates | form templates | — | text_input, search, check, combo_box | — | — | atoms/input, select, textarea, radios |
| [Marker](https://getuikit.com/docs/marker), [Leader](https://getuikit.com/docs/leader) | — (planned) | — | — | — | — | — | — | — | — |
| [Text](https://getuikit.com/docs/text), [Background](https://getuikit.com/docs/background), [Inverse](https://getuikit.com/docs/inverse), [Utility](https://getuikit.com/docs/utility), [Visibility](https://getuikit.com/docs/visibility), [Animation](https://getuikit.com/docs/animation), [Transition](https://getuikit.com/docs/transition) | **styles** | tokens | styles | styles | styles | — | — | — | scss |

## Common and JavaScript components

| UIkit | UI Suite UIkit (SDC) | Penpot | Bootstrap | daisyUI | DSFR | USWDS | Material | Vartheme BS5 | Sobki |
|---|---|---|---|---|---|---|---|---|---|
| [Accordion](https://getuikit.com/docs/accordion) | `accordion`, `accordion_item` | Accordion ✅ | accordion, accordion_item | accordion, collapse | accordion, accordion_group | accordion, accordion_item | — | molecules/accordion, atoms/accordion-item | accordion |
| [Alert](https://getuikit.com/docs/alert) | `alert` (default/primary/success/warning/danger, close); tpl `status-messages` | Alert ✅ | alert | alert | alert, notice | alert, banner | banner | molecules/alert | alert |
| [Dropdown](https://getuikit.com/docs/dropdown) / [Drop](https://getuikit.com/docs/drop) | `dropdown` | Dropdown ✅ | dropdown | — | — | — | menu | molecules/dropdown | dropdown |
| [Modal](https://getuikit.com/docs/modal) | `modal` (default/container/full) | Modal ✅ | modal | modal | modal | modal | — | — | modal |
| [Offcanvas](https://getuikit.com/docs/offcanvas) | `offcanvas`; page `offcanvas` region | — | offcanvas | drawer | — | — | drawer | — | offcanvas |
| [Switcher](https://getuikit.com/docs/switcher) | `switcher`, `switcher_tab`, `switcher_panel` | — | nav + tab panes | tabs | tabs, tab_panel | — | tab_bar | molecules/tabs | — |
| [Tooltip](https://getuikit.com/docs/tooltip) | `tooltip` | Tooltip ✅ | — | tooltip | — | tooltip | — | — | — |
| [Slider](https://getuikit.com/docs/slider) | `slider` | — | carousel, carousel_item | carousel | — | — | slider | organisms/carousel | carousel |
| [Slideshow](https://getuikit.com/docs/slideshow) | `slideshow`, `slideshow_item` | — | carousel | carousel | — | — | — | organisms/heroslider | carousel |
| [Lightbox](https://getuikit.com/docs/lightbox) | `lightbox`, `lightbox_item` | — | — | — | — | — | image_list | — | — |
| [Notification](https://getuikit.com/docs/notification) | — (JS API; Drupal messages use `alert`) | — | toast, toast_container | toast | — | — | snackbar | — | toast |
| [Sticky](https://getuikit.com/docs/sticky) | `navbar` sticky prop, theme setting | — | navbar placement | — | — | — | — | — | — |
| [Scrollspy](https://getuikit.com/docs/scrollspy), [Parallax](https://getuikit.com/docs/parallax), [Filter](https://getuikit.com/docs/filter), [Sortable](https://getuikit.com/docs/sortable), [Upload](https://getuikit.com/docs/upload), [Toggle](https://getuikit.com/docs/toggle), [Video](https://getuikit.com/docs/video), [SVG](https://getuikit.com/docs/svg) | attribute behaviors (`attributes` prop, `uk-*`); Toggle via `toggle_target` props | — | — | — | — | — | — | — | — |

## Design tokens and color modes (UI Skins)

| UIkit LESS variable | UI Skins CSS variable | Penpot token |
|---|---|---|
| `@global-color` | `--uk-global-color` | `color` |
| `@global-emphasis-color` | `--uk-global-emphasis-color` | `emphasis-color` |
| `@global-muted-color` | `--uk-global-muted-color` | `muted-color` |
| `@global-link-color`, `@global-link-hover-color` | `--uk-global-link-color`, `--uk-global-link-hover-color` | `link`, `link-hover` |
| `@global-inverse-color` | `--uk-global-inverse-color` | `inverse-color` |
| `@global-background`, `@global-muted-background` | `--uk-global-background`, `--uk-global-muted-background` | `background`, `muted-background` |
| `@global-primary/secondary/success/warning/danger-background` | `--uk-global-*-background` (+ `-hover`) | `*-background` |
| `@global-border` | `--uk-global-border` | `border` |
| `@alert-*-background` | `--uk-alert-*-background` | `alert-*-background` |
| `@global-font-family` | `--uk-global-font-family` | `font-family` |
| Inverse / dark | UI Skins themes `light`, `dark` (`data-theme` on `html`) | — |

## Drupal integration without components

| Drupal | Implementation |
|---|---|
| Page | `page.html.twig` (Navbar, Offcanvas, Section, Grid) or a Display Builder page layout |
| Page wrapper | `off-canvas-page-wrapper.html.twig`: HTMX boosted navigation (theme setting) |
| Menus | region-aware suggestions: navbar → `navbar_nav`, offcanvas → primary `nav`, footer → `subnav` |
| Status messages, pager, breadcrumb, local tasks | `alert`, `pagination`, `breadcrumb`, `tab` + UIkit subnav |
| Forms and tables | preprocess hooks adding the UIkit classes; primary submit buttons |

## Gaps and plan

Not implemented as components yet, by priority: **Iconnav** (social links with icons), **Notification** (toasts
for Drupal messages), **Dropbar** mode for the Navbar, **Marker**, **Leader**, **Thumbnav**. The JavaScript
behaviors (Scrollspy, Parallax, Filter, Sortable, Upload, Video, SVG) are available on any component through the
`attributes` prop.
