Feature: The UIkit components with Display Builder
  As a site builder
  I want to build displays with the UIkit components in Display Builder
  So that I can design pages without writing templates

  Scenario: Every UIkit component has a Display Builder preview
    Given I am a logged in user with the "Webmaster" user
      And the "display_builder" module is enabled
     Then every UIkit component should have a Display Builder preview

  Scenario: The page layout builder lists the UIkit components by group
    Given I am a logged in user with the "Webmaster" user
      And the "display_builder_page_layout" module is enabled
      And there is no default page layout
      And the blocks of the UIkit theme are placed
     When I create the default page layout from the current site
     Then I should see "Created new page layout Default."
     When I go to "/admin/structure/page-layout/default/builder"
     Then "#island-page_layout__default-component_library" should contain text "Grid: 2 columns"
      And "#island-page_layout__default-component_library" should contain text "Navbar"
      And "#island-page_layout__default-component_library" should contain text "Slideshow"
      And "#island-page_layout__default-component_library" should contain text "Accordion"
      And the builder canvas should have the element ".uk-navbar-container"
      And there should be no JavaScript errors

  # The theme draws the sign-in screens without the page layout (next
  # scenario): the page layout is checked on another page.
  Scenario: The page rendered through the page layout keeps the UIkit navbar
    Given the "display_builder_page_layout" module is enabled
      And the "/ui-suite-uikit-test-page-not-found" page is rendered by the UIkit theme
      And I am an anonymous user
     When I go to "/ui-suite-uikit-test-page-not-found"
     Then ".uk-navbar-left .uk-logo" should be visible
      And ".uk-navbar-center .uk-navbar-nav > li > a" should be visible
      And ".uk-navbar-right .uk-navbar-nav" should contain text "Log in"
      And "#ui-suite-uikit-offcanvas .uk-nav-primary" should be attached

  Scenario: Without page layout, the sign-in screen is the page of the theme again
    Given I am a logged in user with the "Webmaster" user
      And the "display_builder_page_layout" module is enabled
      And the "/user/login" page is rendered by the UIkit theme
      And there is no default page layout
      And I am an anonymous user
     When I go to "/user/login"
     Then ".ui-suite-uikit-sign-in" should be visible
      And "#edit-name" should be visible

  # Left out on a site installed from a site template (--tags "not @theme-optional-config"):
  # a recipe installs the theme without its optional configuration, the Display
  # Builder profiles and the sign-in page layout. A site that installs the theme
  # itself has them.
  @theme-optional-config
  Scenario: The theme ships its Display Builder profiles and a disabled sign-in page layout
    Given the "display_builder_page_layout" module is enabled
     Then the configuration "display_builder.profile.ui_suite_uikit" should exist
      And the configuration "display_builder.profile.ui_suite_uikit_sign_in" should exist
      And the page layout "ui_suite_uikit_sign_in" should be disabled
      And the configuration "display_builder.profile.ui_suite_uikit" should match its schema
      And the configuration "display_builder.profile.ui_suite_uikit_sign_in" should match its schema

  Scenario: The header and footer sections of a page layout are the banner and the contentinfo
    Given the "display_builder_page_layout" module is enabled
      And the page layout "ui_suite_uikit_test_landmarks" of the test fixtures exists
      And I am an anonymous user
     When I go to "/node?landmarks=1"
     Then the page should be drawn by the page layout "ui_suite_uikit_test_landmarks"
      And the page should have 1 "banner" landmark
      And the page should have 1 "main" landmark
      And the page should have 1 "contentinfo" landmark
      And the page should have exactly one h1
      And the page should have no critical accessibility violations
    Then the page layout "ui_suite_uikit_test_landmarks" is deleted

  # Role switch: the Webmaster picks the page layout, a visitor sees the sign-in
  # screen, and the Webmaster puts the setting back.
  # Left out on a site installed from a site template (--tags "not @theme-optional-config"):
  # a recipe installs the theme without its optional configuration, the Display
  # Builder profiles and the sign-in page layout. A site that installs the theme
  # itself has them. The settings form saves every setting of the theme: they
  # are put back after the scenario (@theme-settings).
  @role-switch @theme-optional-config @theme-settings
  Scenario: The sign-in page layout draws the sign-in screens when the theme settings pick it
    Given I am a logged in user with the "Webmaster" user
      And the "display_builder_page_layout" module is enabled
      And the sign-in screens are shown by the UIkit theme
     When I pick the page layout "Sign-in screens" for the sign-in screens of the UIkit theme
     Then the page layout "ui_suite_uikit_sign_in" should be enabled
    Given I am an anonymous user
     When I go to "/user/login?page-layout=1"
     Then the page should be drawn by the page layout "ui_suite_uikit_sign_in"
      And ".ui-suite-uikit-sign-in" should be visible
      And "#edit-name" should be visible
      And the page should have exactly one h1
    Given I am a logged in user with the "Webmaster" user
     When I pick the page layout "- The page of the theme -" for the sign-in screens of the UIkit theme
     Then the page layout "ui_suite_uikit_sign_in" should be disabled
      And the sign-in screens are given back to the site

  # The scenarios above replace the default page layout of the site: put it
  # back for the next features.
  Scenario: The default page layout of the site is put back
    Given the "display_builder_page_layout" module is enabled
     Then the default page layout of the site is restored
      And the blocks placed for the scenarios are removed
