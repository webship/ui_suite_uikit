Feature: UI Skins design tokens and color modes
  As a site builder
  I want to change the UIkit design tokens and the color mode from the UI Skins settings
  So that the whole UIkit design system follows the brand without code

  # The pages are loaded with a different query string after each change: a
  # site can let the browser cache its pages.

  Scenario: A CSS variable changes the UIkit primary color
    Given I am logged in as the Drupal administrator
     When I set the UI Skins CSS variable "uk-global-primary-background" of the UIkit theme to "#ff3300"
      And I am an anonymous user
      And I go to "/?ui-skins=custom-primary"
     Then the computed style "--uk-global-primary-background" of "html" should be "#ff3300"
    Given I am logged in as the Drupal administrator
     When I set the UI Skins CSS variable "uk-global-primary-background" of the UIkit theme to "#0f5695"
      And I am an anonymous user
      And I go to "/?ui-skins=default-primary"
     Then the computed style "--uk-global-primary-background" of "html" should be "#0f5695"

  Scenario: A CSS variable changes the color of the UIkit buttons
    Given the "/user/login" page is rendered by the UIkit theme
      And I am logged in as the Drupal administrator
     When I set the UI Skins CSS variable "uk-global-primary-background" of the UIkit theme to "#ff3300"
      And I am an anonymous user
      And I go to "/user/login?ui-skins=custom-primary"
     Then the computed style "background-color" of "#edit-submit" should be "rgb(255, 51, 0)"
    Given I am logged in as the Drupal administrator
     When I set the UI Skins CSS variable "uk-global-primary-background" of the UIkit theme to "#0f5695"
      And I am an anonymous user
      And I go to "/user/login?ui-skins=default-primary"
     Then the computed style "background-color" of "#edit-submit" should be "rgb(15, 86, 149)"

  Scenario: The dark color mode is selected in the theme settings
    Given I am logged in as the Drupal administrator
      And the theme settings of the UIkit theme offer the UI Skins color modes
     When I select the UI Skins theme "Dark" for the UIkit theme
      And I am an anonymous user
      And I go to "/?ui-skins=dark"
     Then "html" should have attribute "data-theme" with value "dark"
      And the computed style "background-color" of "html" should be "rgb(23, 23, 23)"
    Given I am logged in as the Drupal administrator
     When I select the UI Skins theme "Light" for the UIkit theme
      And I am an anonymous user
      And I go to "/?ui-skins=light"
     Then the computed style "background-color" of "html" should be "rgb(255, 255, 255)"
