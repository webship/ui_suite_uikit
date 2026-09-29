@a11y @aaa
Feature: WCAG 2.2 AAA
  As a visitor with low vision, a motor impairment or a vestibular disorder
  I want the theme to meet the WCAG 2.2 AAA criteria a theme can meet
  So that I can read, reach and use every part of the site in both color modes

  Scenario Outline: The <mode> color mode of <name> passes the WCAG AAA audit
    Given the "<path>" page is rendered by the UIkit theme
      And I am an anonymous user
     When I go to "<path>"
      And I set the "data-theme" attribute of the document to "<mode>"
     Then the page should pass an accessibility audit at level "AAA"

    Examples:
      | path                                | name                    | mode  |
      | /                                   | the front page          | light |
      | /                                   | the front page          | dark  |
      | /user/login                         | the login page          | light |
      | /user/login                         | the login page          | dark  |
      | /user/password                      | the password reset page | light |
      | /user/password                      | the password reset page | dark  |
      | /ui-suite-uikit-test-page-not-found | the page not found      | light |
      | /ui-suite-uikit-test-page-not-found | the page not found      | dark  |

  # A lone switcher tab or description list item is a list item without its
  # list: they are checked in the Switcher and Description list components.
  # The sign-in and page components are whole pages, with their own main
  # landmark: they are checked on the pages that use them.
  Scenario Outline: Every component of the library passes the WCAG AAA audit in the <mode> color mode
    Given the "ui_patterns_library" module is enabled
      And I am logged in as the Drupal administrator
     Then every UIkit component page of the library should pass the WCAG AAA audit in the "<mode>" color mode except "switcher_tab, description_list_item, sign_in, page"

    Examples:
      | mode  |
      | light |
      | dark  |

  Scenario Outline: The focus ring of <name> is visible in the <mode> color mode
    Given the "<path>" page is rendered by the UIkit theme
      And I am an anonymous user
     When I go to "<path>"
      And I set the "data-theme" attribute of the document to "<mode>"
     Then every element the keyboard reaches should have a focus ring of at least 2px with a 3:1 contrast

    Examples:
      | path           | name                    | mode  |
      | /              | the front page          | light |
      | /              | the front page          | dark  |
      | /user/password | the password reset page | light |
      | /user/password | the password reset page | dark  |

  Scenario: The focus ring is a solid 2px outline, 2px away from the element
    Given I am an anonymous user
     When I go to "/"
      And I press the key "Tab"
     Then the computed style "outline-style" of ":focus" should be "solid"
      And the computed style "outline-width" of ":focus" should be "2px"
      And the computed style "outline-offset" of ":focus" should be "2px"

  Scenario: The navigation links and the navbar toggle are targets of at least 44 by 44 pixels
    Given I am an anonymous user
     When I go to "/"
     Then every visible ".uk-navbar-nav > li > a" should be at least 44 by 44 pixels
     When I set the viewport to the "xs" breakpoint
      And I go to "/"
     Then every visible ".uk-navbar-toggle" should be at least 44 by 44 pixels
     When I click on the element ".uk-navbar-toggle"
     Then ".uk-offcanvas.uk-open .uk-offcanvas-bar" should be visible within 5 seconds
      And every visible ".uk-offcanvas.uk-open .uk-offcanvas-close" should be at least 44 by 44 pixels
      And every visible ".uk-offcanvas.uk-open .uk-nav > li > a" should be at least 44 by 44 pixels

  Scenario Outline: The buttons and the form fields of <name> are targets of at least 44 by 44 pixels
    Given the "<module>" module is enabled
      And the "<path>" page is rendered by the UIkit theme
      And I am an anonymous user
     When I go to "<path>"
     Then every visible "form .form-submit" should be at least 44 by 44 pixels
      And every visible "form .uk-input" should be at least 44 by 44 pixels

    Examples:
      | path           | name                    | module  |
      | /user/password | the password reset page | user    |
      | /form/contact  | the contact webform     | webform |

  Scenario: With reduced motion, the offcanvas menu opens without animation
    Given I am an anonymous user
      And I prefer reduced motion
      And I set the viewport to the "xs" breakpoint
     When I go to "/"
     Then no element should have a transition or an animation longer than 10 milliseconds
     When I click on the element ".uk-navbar-toggle"
     Then ".uk-offcanvas.uk-open .uk-offcanvas-bar" should be visible within 1 second
      And no element should have a transition or an animation longer than 10 milliseconds

  Scenario: With reduced motion, the accordion opens without animation
    Given the "ui_patterns_library" module is enabled
      And I am logged in as the Drupal administrator
      And I prefer reduced motion
     When I go to "/admin/appearance/ui/components/ui_suite_uikit/accordion"
      And I click on the element ":nth-match(.uk-accordion > div, 2) > .uk-accordion-title"
     Then ":nth-match(.uk-accordion > div, 2) > .uk-accordion-content" should be visible within 1 second
      And no element should have a transition or an animation longer than 10 milliseconds

  Scenario Outline: <name> reflows at 320 pixels without horizontal scrolling
    Given the "<path>" page is rendered by the UIkit theme
      And I am an anonymous user
      And I set the viewport to 320 by 640
     When I go to "<path>"
     Then the page should not scroll horizontally

    Examples:
      | path                                | name                    |
      | /                                   | The front page          |
      | /user/login                         | The login page          |
      | /user/password                      | The password reset page |
      | /ui-suite-uikit-test-page-not-found | The page not found      |

  Scenario Outline: The text of the <component> component is easy to read
    Given the "ui_patterns_library" module is enabled
      And I am logged in as the Drupal administrator
     When I go to "/admin/appearance/ui/components/ui_suite_uikit/<component>"
     Then the text blocks in ".ui_patterns_component" should have a line height of at least 1.5, at most 80 characters per line and no justified text

    Examples:
      | component  |
      | article    |
      | alert      |
      | blockquote |
      | comment    |
