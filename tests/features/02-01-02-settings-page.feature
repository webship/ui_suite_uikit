Feature: The settings page of the theme
  As a site builder
  I want the settings of the theme grouped in sections, with previews and a Save button in reach
  So that I can restyle the site without reading every field

  Scenario: The settings are grouped in sections
    Given I am logged in as the Drupal administrator
     When I go to "/admin/appearance/settings/ui_suite_uikit"
     Then ".ui-suite-uikit-settings .vertical-tabs" should be visible
      And ".vertical-tabs__menu" should contain text "Colors and color mode"
      And ".vertical-tabs__menu" should contain text "Typography"
      And ".vertical-tabs__menu" should contain text "Layout and navigation"
      And ".vertical-tabs__menu" should contain text "Sign-in screens"
      And ".vertical-tabs__menu" should contain text "Accessibility"
      And ".vertical-tabs__menu" should contain text "Advanced"
      And the computed style "position" of ".ui-suite-uikit-settings .form-actions" should be "sticky"
      And every visible "fieldset.ui-suite-uikit-picker .form-type-radio" should be at least 44 by 44 pixels

  Scenario Outline: The settings page passes the WCAG AAA audit in the <mode> color mode
    Given I am logged in as the Drupal administrator
     When I go to "/admin/appearance/settings/ui_suite_uikit"
      And I set the "data-theme" attribute of the document to "<mode>"
     Then the element ".ui-suite-uikit-settings" should pass an accessibility audit at level "AAA"

    Examples:
      | mode  |
      | light |
      | dark  |

  Scenario: The pickers show a preview of each choice
    Given I am logged in as the Drupal administrator
     When I go to "/admin/appearance/settings/ui_suite_uikit"
     Then the preview of the "light" choice of the "color_mode" picker should be drawn
      And the preview of the "atkinson" choice of the "font_family" picker should be drawn
      And the preview of the "8px" choice of the "ui_suite_uikit_skin_radius" picker should be drawn
      And the preview of the "end" choice of the "sign_in_layout" picker should be drawn

  Scenario: A brand color below 7:1 with white is refused
    Given I am logged in as the Drupal administrator
     When I set the "ui_suite_uikit_skin_brand" setting of the UIkit theme to "#66aaff"
     Then I should see "Pick a darker color."

  Scenario: The brand color, the corners and the target size restyle the site
    Given I am logged in as the Drupal administrator
     When I set the "ui_suite_uikit_skin_brand" setting of the UIkit theme to "#7a1f5c"
      And I set the "ui_suite_uikit_skin_radius" setting of the UIkit theme to "8px"
      And I set the "ui_suite_uikit_skin_target_size" setting of the UIkit theme to "48px"
     Then I should see "The configuration options have been saved."
    Given I am an anonymous user
     When I go to "/?settings=brand"
     Then the computed style "--uk-global-primary-background" of "html" should be "#7a1f5c"
      And the computed style "--uk-global-link-color" of "html" should be "#7a1f5c"
      And the computed style "--uk-global-border-radius" of "html" should be "8px"
      And the computed style "--ui-suite-uikit-target-size" of "html" should be "48px"
    Given I am logged in as the Drupal administrator
     When I set the "ui_suite_uikit_skin_brand" setting of the UIkit theme to "#0f5695"
      And I set the "ui_suite_uikit_skin_radius" setting of the UIkit theme to "0"
      And I set the "ui_suite_uikit_skin_target_size" setting of the UIkit theme to "44px"
      And I am an anonymous user
      And I go to "/?settings=default"
     Then the computed style "--uk-global-primary-background" of "html" should be "#0f5695"
      And the computed style "--uk-global-border-radius" of "html" should be "0"
