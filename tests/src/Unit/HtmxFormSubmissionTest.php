<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_suite_uikit\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\ui_suite_uikit\Hook\HtmxNavigationHooks;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests which forms keep their normal submission in the HTMX navigation.
 */
#[CoversClass(HtmxNavigationHooks::class)]
#[Group('ui_suite_uikit')]
final class HtmxFormSubmissionTest extends UnitTestCase {

  /**
   * Tests the POST forms, webforms included, are not boosted.
   */
  public function testPostForms(): void {
    $this->assertTrue(HtmxNavigationHooks::keepsNormalSubmission([]));
    $this->assertTrue(HtmxNavigationHooks::keepsNormalSubmission(['#method' => 'post']));
    $this->assertTrue(HtmxNavigationHooks::keepsNormalSubmission([
      '#method' => 'POST',
      '#attributes' => ['class' => ['webform-submission-form', 'antibot']],
      'elements' => ['name' => ['#type' => 'textfield']],
    ]));
  }

  /**
   * Tests the GET forms stay boosted, unless they use Drupal AJAX.
   */
  public function testGetForms(): void {
    $search = [
      '#method' => 'get',
      'keys' => ['#type' => 'search'],
      'actions' => ['submit' => ['#type' => 'submit']],
    ];
    $this->assertFalse(HtmxNavigationHooks::keepsNormalSubmission($search));
    $this->assertTrue(HtmxNavigationHooks::keepsNormalSubmission($search, TRUE));

    $ajax = $search;
    $ajax['actions']['submit']['#ajax'] = ['callback' => '::refresh'];
    $this->assertTrue(HtmxNavigationHooks::keepsNormalSubmission($ajax));

    $ajax_submit = $search;
    $ajax_submit['#attributes']['class'] = ['use-ajax-submit'];
    $this->assertTrue(HtmxNavigationHooks::keepsNormalSubmission($ajax_submit));

    $properties = $search;
    $properties['#ajax_like'] = ['#ajax' => TRUE];
    $this->assertFalse(HtmxNavigationHooks::keepsNormalSubmission($properties));
  }

  /**
   * Tests the HTMX page state is removed from the form action.
   */
  public function testRemovePageState(): void {
    $uri = '/form/contact?ajax_page_state%5Btheme%5D=ui_suite_uikit&ajax_page_state%5Btheme_token%5D=&ajax_page_state%5Blibraries%5D=system/base&source=menu';
    $this->assertSame('/form/contact?source=menu', HtmxNavigationHooks::removePageState($uri));
    $this->assertSame('/contact', HtmxNavigationHooks::removePageState('/contact?_wrapper_format=drupal_htmx&ajax_page_state%5Btheme%5D=ui_suite_uikit'));
    $this->assertSame('/user/login', HtmxNavigationHooks::removePageState('/user/login'));
  }

}
