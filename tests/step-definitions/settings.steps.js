/**
 * @file
 * Step definitions for the settings page of UI Suite UIkit.
 */

// The packages are installed with the theme tooling (npm install), not where
// the code is linted.
/* eslint-disable import/no-unresolved */

const assert = require('node:assert');
const { execSync } = require('node:child_process');
const { homedir } = require('node:os');
const path = require('node:path');
const { When, Then } = require('@cucumber/cucumber');
const AxeBuilder = require('@axe-core/playwright').default;

const PROJECT_DIR =
  process.env.DRUPAL_PROJECT_DIR ||
  path.join(homedir(), 'workspace/test/uikittest');
const DRUSH = process.env.DRUSH || 'ddev drush';

/**
 * Sets one field of the theme settings, and saves.
 *
 * The field can sit in a closed section: it is set without a click.
 *
 * Example: When I set the "ui_suite_uikit_skin_radius" setting of the UIkit theme to "8px"
 */
When(
  /^I set the "([^"]*)" setting of the UIkit theme to "([^"]*)"$/,
  { timeout: 180000 },
  async function setThemeSetting(name, value) {
    await this.page.goto(
      `${this.launchUrl}/admin/appearance/settings/ui_suite_uikit`,
    );
    const field = this.page.locator(`[name="${name}"]`).first();
    await field.waitFor({ state: 'attached', timeout: 15000 });
    await this.page.evaluate(
      ([fieldName, fieldValue]) => {
        const fields = [...document.getElementsByName(fieldName)];
        fields.forEach((element) => {
          if (element.type === 'radio' || element.type === 'checkbox') {
            element.checked = element.value === fieldValue;
          } else {
            element.value = fieldValue;
          }
          element.dispatchEvent(new Event('change', { bubbles: true }));
        });
      },
      [name, value],
    );
    await this.page
      .locator('input[type="submit"][value="Save configuration"]')
      .first()
      .click();
    await this.page.waitForLoadState('load');
    execSync(`${DRUSH} cache:rebuild`, {
      cwd: PROJECT_DIR,
      stdio: ['ignore', 'pipe', 'pipe'],
    });
  },
);

/**
 * Checks that the CSS preview of a choice is drawn.
 *
 * Example: Then the preview of the "light" choice of the "color_mode" picker should be drawn
 */
Then(
  /^the preview of the "([^"]*)" choice of the "([^"]*)" picker should be drawn$/,
  async function previewIsDrawn(value, name) {
    const preview = await this.page.evaluate(
      ([fieldName, fieldValue]) => {
        const input = [...document.getElementsByName(fieldName)].find(
          (element) => element.value === fieldValue,
        );
        const label =
          input && document.querySelector(`label[for="${input.id}"]`);
        if (!label) {
          return null;
        }
        return { content: getComputedStyle(label, '::before').content };
      },
      [name, value],
    );
    assert.ok(preview, `No "${value}" choice for "${name}".`);
    assert.ok(
      !['none', 'normal'].includes(preview.content),
      `The "${value}" choice of "${name}" has no preview.`,
    );
  },
);

/**
 * Runs an axe-core audit of a part of the page at a WCAG level.
 *
 * Example: Then the element ".ui-suite-uikit-settings" should pass an accessibility audit at level "AAA"
 */
Then(
  /^the element "([^"]*)" should pass an accessibility audit at level "(AA|AAA)"$/,
  { timeout: 120000 },
  async function elementPassesAudit(selector, level) {
    const tags = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];
    if (level === 'AAA') {
      tags.push('wcag2aaa', 'wcag21aaa', 'wcag22aaa');
    }
    const result = await new AxeBuilder({ page: this.page })
      .include(selector)
      .withTags(tags)
      .analyze();
    const found = result.violations.map(
      (violation) =>
        `${violation.id} (${violation.nodes.length}): ${violation.nodes
          .slice(0, 3)
          .map((node) => node.target.join(' '))
          .join(', ')}`,
    );
    assert.deepStrictEqual(found, []);
  },
);
