Feature: Drupal forms and local tasks use UIkit
  As a visitor
  I want the Drupal forms and tabs to be styled with UIkit
  So that the whole site shares the same design system

  Scenario: The login form uses the UIkit form classes
    Given the "/user/login" page is rendered by the UIkit theme
      And I am an anonymous user
     When I go to "/user/login"
     Then "#edit-name" should have class "uk-input"
      And "#edit-pass" should have class "uk-input"
      And "#edit-submit" should have class "uk-button"
      And "#edit-submit" should have class "uk-button-primary"
      And the computed style "background-color" of "#edit-submit" should be "rgb(15, 86, 149)"

  Scenario: The sign-in screens link to each other with plain links
    Given the "/user/password" page is the sign-in page of the UIkit theme
      And I am an anonymous user
     When I go to "/user/password"
     Then ".ui-suite-uikit-sign-in__links" should contain text "Log in"
      And "ul.uk-tab" should not be attached
     When I go to "/user/login"
     Then ".ui-suite-uikit-sign-in__forgot" should contain text "Forgot your password?"

  Scenario: The status messages are UIkit alerts
    Given the "/user/password" page is rendered by the UIkit theme
      And I am an anonymous user
     When I go to "/user/password"
      And I fill in "name" with "nobody-at-all-test"
      And I move the mouse over the page
      And I wait 3 seconds
      And I press "Send reset link"
     Then ".uk-alert" should be visible within 10 seconds
