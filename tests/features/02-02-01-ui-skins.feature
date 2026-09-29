Feature: UI Skins design tokens and color modes
  As a site builder
  I want to change the UIkit design tokens from UI Skins and the color mode from the theme settings
  So that the whole UIkit design system follows the brand without code

  # The pages are loaded with a different query string after each change: a
  # site can let the browser cache its pages.

  Scenario: A CSS variable changes the UIkit primary color
    Given I am logged in as the Drupal administrator
     When I set the UI Skins CSS variable "ui-suite-uikit-global-primary-background" of the UIkit theme to "#ff3300"
      And I am an anonymous user
      And I go to "/?ui-skins=custom-primary"
     Then the computed style "--uk-global-primary-background" of "html" should be "#ff3300"
    Given I am logged in as the Drupal administrator
     When I set the UI Skins CSS variable "ui-suite-uikit-global-primary-background" of the UIkit theme to "#0f5695"
      And I am an anonymous user
      And I go to "/?ui-skins=default-primary"
     Then the computed style "--uk-global-primary-background" of "html" should be "#0f5695"

  Scenario: A CSS variable changes the color of the UIkit buttons
    Given the "/user/login" page is rendered by the UIkit theme
      And I am logged in as the Drupal administrator
     When I set the UI Skins CSS variable "ui-suite-uikit-global-primary-background" of the UIkit theme to "#ff3300"
      And I am an anonymous user
      And I go to "/user/login?ui-skins=custom-primary"
     Then the computed style "background-color" of "#edit-submit" should be "rgb(255, 51, 0)"
    Given I am logged in as the Drupal administrator
     When I set the UI Skins CSS variable "ui-suite-uikit-global-primary-background" of the UIkit theme to "#0f5695"
      And I am an anonymous user
      And I go to "/user/login?ui-skins=default-primary"
     Then the computed style "background-color" of "#edit-submit" should be "rgb(15, 86, 149)"

  Scenario: The dark color mode is selected in the theme settings
    Given I am logged in as the Drupal administrator
     When I set the color mode of the UIkit theme to "Dark"
      And I am an anonymous user
      And I go to "/?color-mode=dark"
     Then "html" should have attribute "data-theme" with value "dark"
      And the computed style "background-color" of "html" should be "rgb(23, 23, 23)"
    Given I am logged in as the Drupal administrator
     When I set the color mode of the UIkit theme to "Light"
      And I am an anonymous user
      And I go to "/?color-mode=light"
     Then "html" should have attribute "data-theme" with value "light"
      And the computed style "background-color" of "html" should be "rgb(255, 255, 255)"

  Scenario: The color mode follows the operating system
    Given I am logged in as the Drupal administrator
     When I set the color mode of the UIkit theme to "Follow the operating system"
      And I am an anonymous user
      And the operating system asks for the dark color scheme
      And I go to "/?color-mode=auto"
     Then the computed style "background-color" of "html" should be "rgb(23, 23, 23)"
    Given the operating system asks for the light color scheme
      And I go to "/?color-mode=auto-light"
     Then the computed style "background-color" of "html" should be "rgb(255, 255, 255)"
    Given I am logged in as the Drupal administrator
     When I set the color mode of the UIkit theme to "Light"

  Scenario: Every design token has a description and a field in UI Skins
    Given I am logged in as the Drupal administrator
     When I go to "/admin/appearance/css-variables/ui_suite_uikit"
     Then every UI Skins CSS variable of the UIkit theme should have a description and a field
      And I should see "Corner radius"
      And I should see "Target size"
      And I should see "Text color on inverse areas"

  Scenario Outline: The defaults of UI Skins are the values of the <mode> color mode
    Given I am an anonymous user
     When I go to "/?ui-skins=defaults-<mode>"
      And I set the "data-theme" attribute of the document to "<mode>"
     Then the defaults of the UI Skins CSS variables should be the values of the page in the "<mode>" color mode

    Examples:
      | mode  |
      | light |
      | dark  |

  Scenario: The links and the focus ring follow their design tokens
    Given I am logged in as the Drupal administrator
     When I set the UI Skins CSS variable "ui-suite-uikit-global-link-color" of the UIkit theme to "#7a1f5c"
      And I set the UI Skins CSS variable "ui-suite-uikit-focus-color" of the UIkit theme to "#7a1f5c"
      And I am an anonymous user
      And I go to "/?ui-skins=custom-link"
     Then the computed style "--uk-global-link-color" of "html" should be "#7a1f5c"
     When I press the key "Tab"
     Then the computed style "outline-color" of ":focus" should be "rgb(122, 31, 92)"
    Given I am logged in as the Drupal administrator
     When I set the UI Skins CSS variable "ui-suite-uikit-global-link-color" of the UIkit theme to "#0f5695"
      And I set the UI Skins CSS variable "ui-suite-uikit-focus-color" of the UIkit theme to "#333333"
      And I am an anonymous user
      And I go to "/?ui-skins=default-link"
     Then the computed style "--uk-global-link-color" of "html" should be "#0f5695"

  Scenario: A dark value saved in UI Skins shows in the dark mode of the operating system
    Given I am logged in as the Drupal administrator
     When I set the color mode of the UIkit theme to "Follow the operating system"
      And I set the UI Skins CSS variable "ui-suite-uikit-global-link-color" of the UIkit theme to "#ffcc00" in the dark color mode
      And I am an anonymous user
      And the operating system asks for the dark color scheme
      And I go to "/?ui-skins=system-dark"
     Then the computed style "--uk-global-link-color" of "html" should be "#ffcc00"
    Given the operating system asks for the light color scheme
      And I go to "/?ui-skins=system-light"
     Then the computed style "--uk-global-link-color" of "html" should be "#0f5695"
    Given I am logged in as the Drupal administrator
     When I set the UI Skins CSS variable "ui-suite-uikit-global-link-color" of the UIkit theme to "#85c0f9" in the dark color mode
      And I set the color mode of the UIkit theme to "Light"
      And I am an anonymous user
      And I go to "/?ui-skins=system-default"
     Then the computed style "--uk-global-link-color" of "html" should be "#0f5695"
