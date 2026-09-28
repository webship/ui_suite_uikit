/**
 * @file
 * WCAG 2.2 AAA step definitions for the UI Suite UIkit theme.
 *
 * They cover what the webship-js accessibility steps do not: the focus
 * appearance (2.4.13), the target size (2.5.5), the visual presentation of
 * the text (1.4.8), the animations from interactions (2.3.3), the reflow
 * (1.4.10) and an axe-core audit of every component of the library.
 */

// The steps drive one browser page: the keyboard presses and the page visits
// run one after the other.
/* eslint-disable no-await-in-loop, no-restricted-syntax */

const assert = require('node:assert');
const { existsSync, readdirSync } = require('node:fs');
const path = require('node:path');
const { Given, When, Then } = require('@cucumber/cucumber');
const AxeBuilder = require('@axe-core/playwright').default;

const THEME_ROOT = path.resolve(__dirname, '..', '..');

const WCAG_AAA_TAGS = [
  'wcag2a',
  'wcag2aa',
  'wcag2aaa',
  'wcag21a',
  'wcag21aa',
  'wcag21aaa',
  'wcag22aa',
  'wcag22aaa',
];

/**
 * Lists the machine names of the UIkit components of the theme.
 *
 * @return {string[]}
 *   The component machine names.
 */
function componentIds() {
  const dir = path.join(THEME_ROOT, 'components');
  return readdirSync(dir, { withFileTypes: true })
    .filter(
      (entry) =>
        entry.isDirectory() &&
        existsSync(path.join(dir, entry.name, `${entry.name}.component.yml`)),
    )
    .map((entry) => entry.name);
}

/**
 * Formats the axe-core violations for an assertion message.
 *
 * @param {Array} violations
 *   The violations of an axe-core result.
 *
 * @return {string}
 *   One line per violation, with its first targets.
 */
function formatViolations(violations) {
  return violations
    .map((violation) => {
      const targets = violation.nodes
        .slice(0, 3)
        .map((node) => node.target.join(' '))
        .join(', ');
      return `[${violation.impact}] ${violation.id} (${violation.nodes.length}): ${targets}`;
    })
    .join('\n');
}

/**
 * Defines the colour helpers used by the checks, in the page.
 *
 * The effective background is the first opaque background colour of the
 * element or its ancestors, the translucent layers blended on top of it. It
 * is null when an image or a gradient comes first: the contrast can not be
 * measured there.
 *
 * @param {import('playwright').Page} page
 *   The page.
 */
async function injectColorHelpers(page) {
  await page.evaluate(() => {
    if (window.__uiSuiteUikitA11y) {
      return;
    }
    const canvas = document.createElement('canvas');
    canvas.width = 1;
    canvas.height = 1;
    const context = canvas.getContext('2d', { willReadFrequently: true });
    const parse = (color) => {
      context.clearRect(0, 0, 1, 1);
      context.fillStyle = '#000';
      context.fillStyle = color;
      context.fillRect(0, 0, 1, 1);
      const [r, g, b, a] = context.getImageData(0, 0, 1, 1).data;
      return { r, g, b, a: a / 255 };
    };
    const blend = (top, bottom) => ({
      r: top.r * top.a + bottom.r * (1 - top.a),
      g: top.g * top.a + bottom.g * (1 - top.a),
      b: top.b * top.a + bottom.b * (1 - top.a),
      a: 1,
    });
    const luminance = ({ r, g, b }) => {
      const channel = (value) => {
        const c = value / 255;
        return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
      };
      return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
    };
    const contrast = (first, second) => {
      const [light, dark] = [luminance(first), luminance(second)].sort(
        (x, y) => y - x,
      );
      return (light + 0.05) / (dark + 0.05);
    };
    const background = (element) => {
      const layers = [];
      for (
        let node = element;
        node && node.nodeType === 1;
        node = node.parentElement
      ) {
        const style = getComputedStyle(node);
        if (style.backgroundImage !== 'none') {
          return null;
        }
        const color = parse(style.backgroundColor);
        if (color.a > 0) {
          layers.push(color);
          if (color.a === 1) {
            break;
          }
        }
      }
      return layers.reverse().reduce((bottom, top) => blend(top, bottom), {
        r: 255,
        g: 255,
        b: 255,
        a: 1,
      });
    };
    const isVisible = (element) =>
      element.checkVisibility({
        checkOpacity: true,
        checkVisibilityCSS: true,
      }) && element.getClientRects().length > 0;
    const describe = (element) => {
      const label = (
        element.getAttribute('aria-label') ||
        element.textContent ||
        element.value ||
        ''
      )
        .trim()
        .replace(/\s+/g, ' ')
        .slice(0, 40);
      const classes = [...element.classList]
        .slice(0, 3)
        .map((name) => `.${name}`)
        .join('');
      return `${element.tagName.toLowerCase()}${element.id ? `#${element.id}` : ''}${classes} "${label}"`;
    };
    window.__uiSuiteUikitA11y = {
      parse,
      blend,
      contrast,
      background,
      isVisible,
      describe,
    };
  });
}

/**
 * Skips the scenario when another theme renders the page.
 *
 * A site can render some pages with another theme, like the login pages with
 * the administration theme. The theme is read from the Drupal settings of the
 * page.
 *
 * Example: Given the "/user/login" page is rendered by the UIkit theme
 */
Given(
  /^the "([^"]*)" page is rendered by the UIkit theme$/,
  async function (pagePath) {
    const response = await this.page.request.get(
      `${this.launchUrl}${pagePath}`,
    );
    const html = await response.text();
    const match = html.match(
      /<script[^>]*data-drupal-selector="drupal-settings-json"[^>]*>([\s\S]*?)<\/script>/,
    );
    const theme = match ? JSON.parse(match[1]).ajaxPageState?.theme : undefined;
    return theme === 'ui_suite_uikit' ? undefined : 'skipped';
  },
);

/**
 * Emulates the "reduce motion" preference of the operating system.
 *
 * Example: Given I prefer reduced motion
 */
Given(/^I prefer reduced motion$/, async function () {
  await this.page.emulateMedia({ reducedMotion: 'reduce' });
});

/**
 * Opens the page with a link of the page, as a visitor would.
 *
 * Example: When I follow the first link of ".uk-navbar-center .uk-navbar-nav"
 */
When(/^I follow the first link of "([^"]*)"$/, async function (selector) {
  const link = this.page.locator(`${selector} a[href]`).first();
  await link.waitFor({ state: 'visible', timeout: 15000 });
  const href = await link.evaluate((element) => element.href);
  await this.page.goto(href);
  await this.page.waitForLoadState('load');
});

/**
 * Moves the focus through the page with the Tab key and checks the ring.
 *
 * WCAG 2.4.7 and 2.4.13: every element the keyboard reaches shows a solid
 * outline of at least 2 CSS pixels, with a contrast of at least 3:1 against
 * the background around it. The contrast is not measured on images.
 *
 * Example: Then every element the keyboard reaches should have a focus ring of at least 2px with a 3:1 contrast
 */
Then(
  /^every element the keyboard reaches should have a focus ring of at least (\d+)px with a (\d+(?:\.\d+)?):1 contrast$/,
  { timeout: 120000 },
  async function (minimumWidth, minimumRatio) {
    await injectColorHelpers(this.page);
    await this.page.evaluate(() => document.activeElement?.blur());
    const failures = [];
    const seen = new Set();
    for (let step = 0; step < 80; step++) {
      await this.page.keyboard.press('Tab');
      const result = await this.page.evaluate(
        ([width, ratio]) => {
          const helpers = window.__uiSuiteUikitA11y;
          const element = document.activeElement;
          if (!element || element === document.body) {
            return { done: true };
          }
          if (!element.matches(':focus-visible') || element.closest('iframe')) {
            return { key: helpers.describe(element) };
          }
          const style = getComputedStyle(element);
          const outline = parseFloat(style.outlineWidth);
          const problems = [];
          if (style.outlineStyle !== 'solid' || outline < width) {
            problems.push(
              `outline ${style.outlineStyle} ${style.outlineWidth}`,
            );
          } else {
            const ring = helpers.parse(style.outlineColor);
            // The ring is drawn outside the element when the offset is positive
            // or zero: compare it with the background around the element.
            const around = helpers.background(
              parseFloat(style.outlineOffset) >= 0
                ? element.parentElement
                : element,
            );
            if (around) {
              const measured = helpers.contrast(
                helpers.blend(ring, around),
                around,
              );
              if (measured < ratio) {
                problems.push(
                  `ring ${style.outlineColor} at ${measured.toFixed(2)}:1`,
                );
              }
            }
          }
          return { key: helpers.describe(element), problems };
        },
        [Number(minimumWidth), Number(minimumRatio)],
      );
      if (result.done || seen.has(result.key)) {
        break;
      }
      seen.add(result.key);
      if (result.problems?.length) {
        failures.push(`${result.key}: ${result.problems.join(', ')}`);
      }
    }
    assert.ok(seen.size > 0, 'The keyboard reached no element.');
    assert.deepStrictEqual(
      failures,
      [],
      `Focus rings below WCAG 2.4.13:\n${failures.join('\n')}`,
    );
  },
);

/**
 * Checks the size of the visible targets matching a selector (WCAG 2.5.5).
 *
 * Example: Then every visible ".uk-navbar-nav > li > a" should be at least 44 by 44 pixels
 */
Then(
  /^every visible "([^"]*)" should be at least (\d+) by (\d+) pixels$/,
  async function (selector, minimumWidth, minimumHeight) {
    await injectColorHelpers(this.page);
    await this.page
      .locator(selector)
      .first()
      .waitFor({ state: 'attached', timeout: 15000 });
    const { count, failures } = await this.page.evaluate(
      ([target, width, height]) => {
        const helpers = window.__uiSuiteUikitA11y;
        const elements = [...document.querySelectorAll(target)].filter(
          helpers.isVisible,
        );
        return {
          count: elements.length,
          failures: elements
            .map((element) => ({
              element,
              rect: element.getBoundingClientRect(),
            }))
            // Half a pixel of rounding.
            .filter(
              ({ rect }) =>
                rect.width + 0.5 < width || rect.height + 0.5 < height,
            )
            .map(
              ({ element, rect }) =>
                `${helpers.describe(element)}: ${rect.width.toFixed(1)} x ${rect.height.toFixed(1)}`,
            ),
        };
      },
      [selector, Number(minimumWidth), Number(minimumHeight)],
    );
    assert.ok(count > 0, `No visible "${selector}" on the page.`);
    assert.deepStrictEqual(
      failures,
      [],
      `Targets smaller than ${minimumWidth} x ${minimumHeight}:\n${failures.join('\n')}`,
    );
  },
);

/**
 * Checks the transitions and animations of every element (WCAG 2.3.3).
 *
 * Example: Then no element should have a transition or an animation longer than 10 milliseconds
 */
Then(
  /^no element should have a transition or an animation longer than (\d+) milliseconds$/,
  async function (maximum) {
    await injectColorHelpers(this.page);
    const failures = await this.page.evaluate((limit) => {
      const helpers = window.__uiSuiteUikitA11y;
      const seconds = (value) =>
        value.split(',').map((item) => {
          const number = parseFloat(item);
          return item.trim().endsWith('ms') ? number : number * 1000;
        });
      const longest = (durations, delays) =>
        Math.max(
          ...durations.map(
            (duration, index) =>
              duration + (delays[index % delays.length] || 0),
          ),
        );
      const found = [];
      for (const element of document.querySelectorAll('body *')) {
        for (const pseudo of [null, '::before', '::after']) {
          const style = getComputedStyle(element, pseudo);
          const transition =
            style.transitionProperty === 'none'
              ? 0
              : longest(
                  seconds(style.transitionDuration),
                  seconds(style.transitionDelay),
                );
          const animation =
            style.animationName === 'none'
              ? 0
              : longest(
                  seconds(style.animationDuration),
                  seconds(style.animationDelay),
                );
          if (Math.max(transition, animation) > limit) {
            found.push(
              `${helpers.describe(element)}${pseudo || ''}: transition ${transition}ms, animation ${animation}ms`,
            );
          }
        }
      }
      return found;
    }, Number(maximum));
    assert.deepStrictEqual(
      failures.slice(0, 20),
      [],
      `${failures.length} element(s) still move:\n${failures.slice(0, 20).join('\n')}`,
    );
  },
);

/**
 * Checks that the page does not scroll horizontally (WCAG 1.4.10).
 *
 * Example: Then the page should not scroll horizontally
 */
Then(/^the page should not scroll horizontally$/, async function () {
  const { scrollWidth, clientWidth, wider } = await this.page.evaluate(() => {
    const width = document.documentElement.clientWidth;
    return {
      scrollWidth: document.documentElement.scrollWidth,
      clientWidth: width,
      wider: [...document.querySelectorAll('body *')]
        .filter(
          (element) =>
            element.getBoundingClientRect().right > width + 1 &&
            element.checkVisibility(),
        )
        .filter(
          (element) =>
            !element.closest(
              '.uk-slider-items, .uk-slideshow-items, [aria-hidden="true"], .uk-offcanvas',
            ),
        )
        .slice(0, 5)
        .map(
          (element) =>
            `${element.tagName.toLowerCase()}.${[...element.classList].join('.')}`,
        ),
    };
  });
  assert.ok(
    scrollWidth <= clientWidth,
    `The page is ${scrollWidth}px wide in a ${clientWidth}px viewport: ${wider.join(', ')}`,
  );
});

/**
 * Checks the visual presentation of the text blocks (WCAG 1.4.8).
 *
 * The line height is at least 1.5 times the font size, the text is not
 * justified and the lines hold at most 80 characters ("0" glyphs).
 *
 * Example: Then the text blocks in "main" should have a line height of at least 1.5, at most 80 characters per line and no justified text
 */
Then(
  /^the text blocks in "([^"]*)" should have a line height of at least (\d+(?:\.\d+)?), at most (\d+) characters per line and no justified text$/,
  async function (selector, minimumLineHeight, maximumCharacters) {
    await injectColorHelpers(this.page);
    const { count, failures } = await this.page.evaluate(
      ([root, lineHeight, characters]) => {
        const helpers = window.__uiSuiteUikitA11y;
        const probe = document.createElement('span');
        probe.textContent = '0'.repeat(10);
        probe.style.cssText =
          'position:absolute;visibility:hidden;white-space:nowrap;';
        const blocks = [
          ...document.querySelectorAll(
            `${root} p, ${root} dd, ${root} blockquote, ${root} .description`,
          ),
        ].filter(
          (element) =>
            helpers.isVisible(element) &&
            element.textContent.trim().length > 40,
        );
        const found = [];
        for (const element of blocks) {
          const style = getComputedStyle(element);
          const size = parseFloat(style.fontSize);
          const height =
            style.lineHeight === 'normal'
              ? 1.2 * size
              : parseFloat(style.lineHeight);
          element.append(probe);
          const zero = probe.getBoundingClientRect().width / 10;
          probe.remove();
          const width =
            element.getBoundingClientRect().width -
            parseFloat(style.paddingLeft) -
            parseFloat(style.paddingRight);
          const problems = [];
          if (height / size + 0.01 < lineHeight) {
            problems.push(`line height ${(height / size).toFixed(2)}`);
          }
          if (/justify/.test(style.textAlign)) {
            problems.push('justified');
          }
          if (width / zero > characters + 0.5) {
            problems.push(`${Math.round(width / zero)} characters per line`);
          }
          if (problems.length) {
            found.push(`${helpers.describe(element)}: ${problems.join(', ')}`);
          }
        }
        return { count: blocks.length, failures: found };
      },
      [selector, Number(minimumLineHeight), Number(maximumCharacters)],
    );
    assert.ok(count > 0, `No text block in "${selector}".`);
    assert.deepStrictEqual(
      failures,
      [],
      `Text blocks below WCAG 1.4.8:\n${failures.join('\n')}`,
    );
  },
);

/**
 * Runs an axe-core WCAG 2.2 AAA audit on the component page of the library.
 *
 * The audit covers the component documentation and its stories, in the given
 * color mode. A few internal sub-components only make sense in their parent
 * (a lone list item for example): name them after "except".
 *
 * Example: Then every UIkit component page of the library should pass the WCAG AAA audit in the "dark" color mode
 * Example: Then every UIkit component page of the library should pass the WCAG AAA audit in the "light" color mode except "switcher_tab, description_list_item"
 */
Then(
  /^every UIkit component page of the library should pass the WCAG AAA audit in the "(light|dark)" color mode(?: except "([^"]*)")?$/,
  { timeout: 900000 },
  async function (mode, except) {
    const skipped = (except || '')
      .split(',')
      .map((id) => id.trim())
      .filter(Boolean);
    const failures = [];
    for (const id of componentIds().filter((name) => !skipped.includes(name))) {
      await this.page.goto(
        `${this.launchUrl}/admin/appearance/ui/components/ui_suite_uikit/${id}`,
      );
      await this.page.waitForLoadState('load');
      await this.page.evaluate(
        (theme) => document.documentElement.setAttribute('data-theme', theme),
        mode,
      );
      // Let the color transitions end.
      await this.page.evaluate(() =>
        Promise.allSettled(
          document
            .getAnimations()
            .filter((animation) => animation instanceof window.CSSTransition)
            .map((animation) => animation.finished),
        ),
      );
      const result = await new AxeBuilder({ page: this.page })
        .include('.ui_patterns_component')
        .withTags(WCAG_AAA_TAGS)
        .analyze();
      if (result.violations.length) {
        failures.push(`${id}:\n${formatViolations(result.violations)}`);
      }
    }
    assert.deepStrictEqual(
      failures,
      [],
      `WCAG AAA violations in the ${mode} color mode:\n${failures.join('\n')}`,
    );
  },
);
