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
