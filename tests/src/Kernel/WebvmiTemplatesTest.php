<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_suite_uikit\Kernel;

use Drupal\Component\Serialization\Yaml;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Validate the Web View Modes Inventory templates against the components.
 */
#[CoversNothing]
#[RunTestsInSeparateProcesses]
#[Group('ui_suite_uikit')]
final class WebvmiTemplatesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'ui_patterns',
    'ui_styles',
    'ui_icons',
    'ui_icons_patterns',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('theme_installer')->install(['ui_suite_uikit']);
  }

  /**
   * Tests every template uses existing components, variants, props and slots.
   */
  public function testTemplates(): void {
    $files = glob(\dirname(__DIR__, 3) . '/webvmi/CONTENT_TYPE_NAME/*.yml') ?: [];
    $this->assertCount(19, $files);

    foreach ($files as $file) {
      $template = Yaml::decode((string) file_get_contents($file));
      $name = basename($file);
      $this->assertSame("core.entity_view_display.node.CONTENT_TYPE_NAME.{$template['mode']}.yml", $name);
      $this->assertSame("node.CONTENT_TYPE_NAME.{$template['mode']}", $template['id']);
      $sources = $template['third_party_settings']['display_builder']['sources'] ?? [];
      $this->assertNotEmpty($sources, $name);
      $this->assertComponents($sources, $name);
    }
  }

  /**
   * Asserts the component sources match the component definitions.
   *
   * @param array $data
   *   The sources, or a part of them.
   * @param string $name
   *   The template file name.
   */
  private function assertComponents(array $data, string $name): void {
    foreach ($data as $key => $value) {
      if (!\is_array($value)) {
        continue;
      }
      if ($key === 'component' && isset($value['component_id'])) {
        $this->assertComponent($value, $name);
      }
      $this->assertComponents($value, $name);
    }
  }

  /**
   * Asserts a component source matches its component definition.
   *
   * @param array $source
   *   The component source.
   * @param string $name
   *   The template file name.
   */
  private function assertComponent(array $source, string $name): void {
    $id = $source['component_id'];
    $message = "$name: $id";
    $this->assertStringStartsWith('ui_suite_uikit:', $id, $message);

    /** @var \Drupal\Core\Plugin\Component $component */
    $component = $this->container->get('plugin.manager.sdc')->find($id);
    $definition = $component->getPluginDefinition();

    $variant = $source['variant_id']['source']['value'] ?? NULL;
    if ($variant !== NULL) {
      $this->assertArrayHasKey($variant, $definition['variants'] ?? [], "$message variant");
    }

    $properties = $component->metadata->schema['properties'] ?? [];
    foreach ($source['props'] ?? [] as $prop_id => $prop) {
      $this->assertArrayHasKey($prop_id, $properties, "$message prop $prop_id");
      $enum = $properties[$prop_id]['enum'] ?? NULL;
      if ($enum !== NULL) {
        $this->assertContains($prop['source']['value'], $enum, "$message prop $prop_id value");
      }
    }

    foreach (array_keys($source['slots'] ?? []) as $slot_id) {
      $this->assertArrayHasKey($slot_id, $definition['slots'] ?? [], "$message slot $slot_id");
    }
  }

}
