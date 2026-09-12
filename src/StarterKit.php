<?php

declare(strict_types=1);

namespace Drupal\ui_suite_uikit;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Theme\StarterKitInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Generates a theme from UI Suite UIkit.
 *
 * Core already renames the machine name, the label and their camelCase and
 * PascalCase forms (ui_suite_uikit, UI Suite UIkit, uiSuiteUikit,
 * UiSuiteUikit). This post-processing renames the remaining forms: the
 * kebab-case name used in CSS classes and HTML ids, and prepares the info
 * file and the README of the generated theme.
 *
 * Usage:
 * php core/scripts/dr generate-theme my_theme --starterkit ui_suite_uikit
 */
final class StarterKit implements StarterKitInterface {

  /**
   * Files that are copied as they are.
   */
  private const array SKIP_CONTENT_EDIT = [
    'starterkit.md',
  ];

  /**
   * Development folders that are not part of a generated theme.
   */
  private const array DEVELOPMENT_FOLDERS = [
    '.playwright-mcp',
    'docs',
    'node_modules',
    'screenshots',
    'scripts',
    'tests',
  ];

  /**
   * {@inheritdoc}
   */
  public static function postProcess(string $working_dir, string $machine_name, string $theme_name): void {
    // Core globs skip dot files, so remove what is left of the ignored
    // development folders (like node_modules/*/.npmignore).
    $filesystem = new Filesystem();
    foreach (self::DEVELOPMENT_FOLDERS as $folder) {
      $filesystem->remove("$working_dir/$folder");
    }

    // The kebab-case name, like the ui-suite-uikit-offcanvas id.
    self::findAndReplace($working_dir, 'ui-suite-uikit', str_replace('_', '-', $machine_name));

    // The README of the generated theme.
    if (file_exists("$working_dir/starterkit.md")) {
      $readme = file_get_contents("$working_dir/starterkit.md");
      $readme = str_replace(['STARTERKIT_NAME', 'STARTERKIT_MACHINE_NAME'], [$theme_name, $machine_name], $readme);
      file_put_contents("$working_dir/README.md", $readme);
      $filesystem->remove("$working_dir/starterkit.md");
    }

    self::updateThemeInfo($working_dir, $machine_name);
  }

  /**
   * Replaces a string in file names and file contents.
   *
   * @param string $dir
   *   The working directory of the theme being generated.
   * @param string $find
   *   The string to replace.
   * @param string $replace
   *   The replacement.
   */
  private static function findAndReplace(string $dir, string $find, string $replace): void {
    $filesystem = new Filesystem();

    $finder = (new Finder())->files()->in($dir)->ignoreDotFiles(FALSE)->name('*' . $find . '*');
    foreach ($finder as $file) {
      $filesystem->rename($file->getRealPath(), $file->getPath() . '/' . str_replace($find, $replace, $file->getFilename()));
    }

    $finder = (new Finder())->files()->in($dir)->ignoreDotFiles(FALSE)->contains($find)
      ->filter(static fn (\SplFileInfo $file): bool => !in_array(str_replace($dir . '/', '', $file->getPathname()), self::SKIP_CONTENT_EDIT, TRUE));
    foreach ($finder as $file) {
      file_put_contents($file->getRealPath(), str_replace($find, $replace, file_get_contents($file->getRealPath())));
    }
  }

  /**
   * Updates the info file of the generated theme.
   *
   * @param string $dir
   *   The working directory of the theme being generated.
   * @param string $machine_name
   *   The machine name of the theme.
   */
  private static function updateThemeInfo(string $dir, string $machine_name): void {
    $info_file = "$dir/$machine_name.info.yml";
    $info = Yaml::decode(file_get_contents($info_file));
    // A generated theme is not a starterkit, and has its own package.
    unset($info['starterkit'], $info['package']);
    $info['core_version_requirement'] = '^11.4 || ^12';
    $info['version'] = '1.0.0';
    file_put_contents($info_file, Yaml::encode($info));
  }

}
