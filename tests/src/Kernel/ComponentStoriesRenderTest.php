<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_suite_uikit\Kernel;

use Drupal\Core\Render\RenderContext;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Render every component, empty and with each of its stories.
 */
#[CoversNothing]
#[RunTestsInSeparateProcesses]
#[Group('ui_suite_uikit')]
final class ComponentStoriesRenderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'ui_patterns',
    'ui_patterns_library',
    'ui_styles',
    'ui_icons',
    'ui_icons_patterns',
    'ui_skins',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('theme_installer')->install(['ui_suite_uikit']);
    $this->config('system.theme')->set('default', 'ui_suite_uikit')->save();
    $theme = $this->container->get('theme.initialization')->initTheme('ui_suite_uikit');
    $this->container->get('theme.manager')->setActiveTheme($theme);
  }

  /**
   * Tests all the components render without errors.
   */
  public function testComponentsRender(): void {
    $components = \array_filter(
      $this->container->get('plugin.manager.sdc')->getDefinitions(),
      static fn (array $definition): bool => ($definition['provider'] ?? '') === 'ui_suite_uikit',
    );
    $this->assertGreaterThan(50, \count($components), 'All the UIkit components are discovered.');

    $story_manager = $this->container->get('plugin.manager.component_story');
    $renderer = $this->container->get('renderer');
    $errors = [];
    $story_count = 0;

    foreach (\array_keys($components) as $component_id) {
      $stories = $story_manager->getComponentStories($component_id);
      if (empty($stories)) {
        $errors[] = \sprintf('%s: no story.', $component_id);
      }
      $elements = ['(empty)' => ['#type' => 'component', '#component' => $component_id]];
      foreach (\array_keys($stories) as $story_id) {
        $story_count++;
        $elements[$story_id] = [
          '#type' => 'component',
          '#component' => $component_id,
          '#story' => $story_id,
        ];
      }
      foreach ($elements as $label => $element) {
        try {
          $html = (string) $renderer->executeInRenderContext(new RenderContext(), static fn () => $renderer->render($element));
          if ($label !== '(empty)' && \trim($html) === '') {
            $errors[] = \sprintf('%s:%s rendered nothing.', $component_id, $label);
          }
        }
        catch (\Throwable $exception) {
          $errors[] = \sprintf('%s:%s %s', $component_id, $label, $exception->getMessage());
        }
      }
    }

    $this->assertGreaterThan(50, $story_count);
    $this->assertEmpty($errors, \implode("\n", $errors));
  }

  /**
   * Tests the UIkit classes of a few key components.
   */
  public function testComponentsMarkup(): void {
    $renderer = $this->container->get('renderer');
    $render = static fn (array $element): string => (string) $renderer->executeInRenderContext(new RenderContext(), static fn () => $renderer->render($element));

    $button = $render([
      '#type' => 'component',
      '#component' => 'ui_suite_uikit:button',
      '#slots' => ['label' => 'Save'],
      '#props' => ['variant' => 'primary', 'size' => 'large', 'url' => '/node/add'],
    ]);
    $this->assertStringContainsString('uk-button uk-button-primary uk-button-large', $button);
    $this->assertStringContainsString('href="/node/add"', $button);

    $grid = $render([
      '#type' => 'component',
      '#component' => 'ui_suite_uikit:grid_2_columns',
      '#slots' => ['column_1' => 'One', 'column_2' => 'Two'],
      '#props' => ['variant' => 'col_66_33', 'breakpoint' => 'l'],
    ]);
    $this->assertStringContainsString('uk-width-2-3@l', $grid);
    $this->assertStringContainsString('uk-width-1-3@l', $grid);

    $nav = $render([
      '#type' => 'component',
      '#component' => 'ui_suite_uikit:navbar_nav',
      '#props' => [
        'items' => [
          ['title' => 'Home', 'url' => '/', 'in_active_trail' => TRUE],
          ['title' => 'Parent', 'url' => '/parent', 'below' => [['title' => 'Child', 'url' => '/child']]],
        ],
      ],
    ]);
    $this->assertStringContainsString('class="uk-active"', $nav);
    $this->assertStringContainsString('uk-navbar-dropdown', $nav);

    $icon = $render([
      '#type' => 'component',
      '#component' => 'ui_suite_uikit:icon',
      '#props' => ['icon' => ['pack_id' => 'uikit', 'icon_id' => 'heart', 'settings' => ['ratio' => 2]]],
    ]);
    $this->assertStringContainsString('<svg', $icon);
    $this->assertStringContainsString('width="40"', $icon);
  }

}
