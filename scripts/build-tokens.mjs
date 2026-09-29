#!/usr/bin/env node
/**
 * @file
 * Generates assets/css/tokens.css from the compiled UIkit CSS.
 *
 * Every declaration of uikit.css using one of the UIkit global colors, the
 * font of the text, the headings or the code, the font size, the line
 * height, the margins or the box shadows is
 * re-emitted with the literal value replaced by a CSS custom property falling
 * back to the original value:
 *
 *   .uk-button-primary { background-color: var(--uk-global-primary-background, #1e87f0); }
 *
 * UIkit has no corner radius of its own: the script adds one rule giving the
 * buttons, fields, cards and panels the radius token, which falls back to 0.
 *
 * Without any custom property set, the rendering is identical to UIkit. Setting
 * a property (from UI Skins, a sub-theme or the dark color mode) re-skins every
 * component using it.
 *
 * Usage: node scripts/build-tokens.mjs [path/to/uikit.css]
 */

import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const source = process.argv[2] ?? resolve(root, 'node_modules/uikit/dist/css/uikit.css');
const target = resolve(root, 'assets/css/tokens.css');
const css = readFileSync(source, 'utf8');
const version = (css.match(/UIkit (\d+\.\d+\.\d+)/) ?? [])[1] ?? 'unknown';

// Literal value => token, for "color" like properties.
const textColors = {
  '#666': 'global-color',
  '#333': 'global-emphasis-color',
  '#999': 'global-muted-color',
  '#fff': 'global-inverse-color',
  '#ffffff': 'global-inverse-color',
  '#1e87f0': 'global-primary-background',
  '#0f6ecd': 'global-link-hover-color',
  '#222': 'global-secondary-background',
  '#32d296': 'global-success-background',
  '#faa05a': 'global-warning-background',
  '#f0506e': 'global-danger-background',
};

// Literal value => token, for background, border, fill and stroke properties.
const surfaceColors = {
  '#fff': 'global-background',
  '#ffffff': 'global-background',
  '#f8f8f8': 'global-muted-background',
  '#ebebeb': 'global-muted-background-hover',
  '#f2f2f2': 'global-muted-background-hover',
  '#e5e5e5': 'global-border',
  '#1e87f0': 'global-primary-background',
  '#0e6dcd': 'global-primary-background-hover',
  '#0f7ae5': 'global-primary-background-hover',
  '#222': 'global-secondary-background',
  '#151515': 'global-secondary-background-hover',
  '#080808': 'global-secondary-background-hover',
  '#32d296': 'global-success-background',
  '#faa05a': 'global-warning-background',
  '#f0506e': 'global-danger-background',
  '#ee395b': 'global-danger-background-hover',
  '#ec2147': 'global-danger-background-hover',
  '#666': 'global-color',
  '#333': 'global-emphasis-color',
  '#999': 'global-muted-color',
  '#d8eafc': 'alert-primary-background',
  '#edfbf6': 'alert-success-background',
  '#fff6ee': 'alert-warning-background',
  '#fef4f6': 'alert-danger-background',
};

const headingSelector = /(^|[\s,])(h1|\.uk-h1|\.uk-heading-small)(?=$|[\s,])/;

// The font of the code, in the "font" and "font-family" declarations.
const codeFont = 'Consolas, monaco, monospace';

const linkSelector = /(^|[\s,>+~(])(a|\.uk-link)(?=$|[\s,:.[>+~)])/;

// Form controls: their border is a non-text indicator (WCAG 1.4.11), so it
// gets its own token instead of the decorative global border.
const formControlSelector =
  /\.uk-(input|select|textarea|checkbox|radio|search-input|search-default|search-navbar|search-medium|search-large)\b/;

// Background colors also printed as text (.uk-text-primary, .uk-form-danger).
// A color works either as a background behind white text or as text on the
// page background, rarely as both, so text gets its own token falling back
// to the background one: sites that only customized the background keep it.
const textOfBackground = {
  'global-primary-background': 'global-primary-color',
  'global-success-background': 'global-success-color',
  'global-warning-background': 'global-warning-color',
  'global-danger-background': 'global-danger-color',
};

// Translucent white used by UIkit for the text of inverse (.uk-light) areas.
const inverseText = {
  0.7: 'inverse-color',
  0.5: 'inverse-muted-color',
};

// Literal value => token, for the box shadows.
const shadows = {
  '0 2px 8px rgba(0, 0, 0, 0.08)': 'global-small-box-shadow',
  '0 5px 15px rgba(0, 0, 0, 0.08)': 'global-medium-box-shadow',
  '0 14px 25px rgba(0, 0, 0, 0.16)': 'global-large-box-shadow',
  '0 28px 50px rgba(0, 0, 0, 0.16)': 'global-xlarge-box-shadow',
  '0 5px 12px rgba(0, 0, 0, 0.15)': 'dropdown-box-shadow',
};

// Literal value => token, for the margins between the blocks of a page.
const margins = {
  '10px': 'global-small-margin',
  '20px': 'global-margin',
  '40px': 'global-medium-margin',
  '70px': 'global-large-margin',
};

// The same lengths are gutters or dividers in these components, not margins.
const notMarginSelector = /uk-(grid|align|breadcrumb|subnav|dropcap)/;

// The components that take the corner radius of the theme. UIkit prints them
// with square corners, so the rule is added, not rewritten.
const radiusSelectors = [
  '.uk-button:not(.uk-button-text):not(.uk-button-link)',
  '.uk-input',
  '.uk-select',
  '.uk-textarea',
  '.uk-search-default .uk-search-input',
  '.uk-card',
  '.uk-alert',
  '.uk-placeholder',
  '.uk-modal-dialog',
  '.uk-dropdown',
  '.uk-navbar-dropdown',
  '.uk-notification-message',
];

/**
 * Splits a string on a separator, ignoring separators in quotes/parentheses.
 */
function split(text, separator) {
  const parts = [];
  let depth = 0;
  let quote = null;
  let current = '';
  for (const char of text) {
    if (quote) {
      quote = char === quote ? null : quote;
    }
    else if (char === '"' || char === "'") {
      quote = char;
    }
    else if (char === '(') {
      depth++;
    }
    else if (char === ')') {
      depth--;
    }
    else if (char === separator && depth === 0) {
      parts.push(current);
      current = '';
      continue;
    }
    current += char;
  }
  parts.push(current);
  return parts.map((part) => part.trim()).filter(Boolean);
}

/**
 * Parses a CSS string into rules and at-rule blocks.
 */
function parse(text) {
  const nodes = [];
  let i = 0;
  while (i < text.length) {
    const open = text.indexOf('{', i);
    if (open === -1) {
      break;
    }
    const prelude = text.slice(i, open).trim();
    let depth = 1;
    let j = open + 1;
    while (j < text.length && depth > 0) {
      if (text[j] === '{') depth++;
      if (text[j] === '}') depth--;
      j++;
    }
    const body = text.slice(open + 1, j - 1);
    if (/^@(media|supports|layer|container)/.test(prelude)) {
      nodes.push({ prelude, children: parse(body) });
    }
    else if (!prelude.startsWith('@')) {
      nodes.push({ selector: prelude, body });
    }
    i = j;
  }
  return nodes;
}

const tokens = new Map();

/**
 * Rewrites a declaration value, or returns null if nothing is tokenized.
 */
function tokenize(selector, property, value) {
  if (value.includes('url(')) {
    return null;
  }
  let changed = false;
  let result = value;
  if (property === 'font-family' && value.includes('-apple-system')) {
    tokens.set('global-font-family', value);
    // The headings have a font of their own, the font of the text when it is
    // not set.
    if (headingSelector.test(selector)) {
      tokens.set('base-heading-font-family', 'var(--uk-global-font-family)');
      return `var(--uk-base-heading-font-family, var(--uk-global-font-family, ${value}))`;
    }
    return `var(--uk-global-font-family, ${value})`;
  }
  // The "font" shorthand of "pre" is printed as a font family: the shorthand
  // would reset the other font properties, like the ligatures.
  if (['font', 'font-family'].includes(property) && value.includes(codeFont)) {
    tokens.set('base-code-font-family', codeFont);
    return `var(--uk-base-code-font-family, ${codeFont})`;
  }
  const important = value.endsWith(' !important') ? ' !important' : '';
  const bare = value.replace(/ !important$/, '');
  if (property === 'box-shadow') {
    const token = shadows[bare];
    if (!token) {
      return null;
    }
    tokens.set(token, bare);
    return `var(--uk-${token}, ${bare})${important}`;
  }
  if (selector === 'html' && property === 'font-size') {
    tokens.set('global-font-size', bare);
    return `var(--uk-global-font-size, ${bare})`;
  }
  if (property === 'line-height' && bare === '1.5') {
    tokens.set('global-line-height', bare);
    return `var(--uk-global-line-height, ${bare})`;
  }
  if (property.startsWith('margin')) {
    const vertical = !/-(left|right)$/.test(property);
    if (notMarginSelector.test(selector) || !(vertical || selector.includes('.uk-margin'))) {
      return null;
    }
    const lengths = bare.split(' ');
    // A shorthand of four values: only the top and the bottom are margins
    // between blocks.
    const rewritten = lengths.map((length, index) => {
      const token = margins[length];
      if (!token || (lengths.length === 4 && index % 2 === 1)) {
        return length;
      }
      changed = true;
      tokens.set(token, length);
      return `var(--uk-${token}, ${length})`;
    });
    return changed ? `${rewritten.join(' ')}${important}` : null;
  }
  const isText = ['color', '-webkit-text-fill-color', 'outline-color'].includes(
    property,
  );
  const isBorder = property.startsWith('border');
  const isFormControl = formControlSelector.test(selector);
  result = value.replace(/rgba\(255, 255, 255, (0\.\d+)\)/g, (rgba, alpha) => {
    let token = isText ? inverseText[alpha] : null;
    if (!token && isBorder && isFormControl && alpha === '0.2') {
      token = 'inverse-form-border-color';
    }
    if (!token) {
      return rgba;
    }
    changed = true;
    if (!tokens.has(token)) {
      tokens.set(token, rgba);
    }
    return `var(--uk-${token}, ${rgba})`;
  });
  result = result.replace(/#[0-9a-fA-F]{3,6}\b/g, (hex) => {
    const literal = hex.toLowerCase();
    let token = isText ? textColors[literal] : surfaceColors[literal];
    if (isText && literal === '#1e87f0' && linkSelector.test(selector)) {
      token = 'global-link-color';
    }
    if (!token) {
      return hex;
    }
    changed = true;
    if (!tokens.has(token)) {
      tokens.set(token, literal);
    }
    if (isText && textOfBackground[token]) {
      const text = textOfBackground[token];
      if (!tokens.has(text)) {
        tokens.set(text, `var(--uk-${token})`);
      }
      return `var(--uk-${text}, var(--uk-${token}, ${hex}))`;
    }
    if (token === 'global-border' && isBorder && isFormControl) {
      if (!tokens.has('form-border-color')) {
        tokens.set('form-border-color', 'var(--uk-global-border)');
      }
      return `var(--uk-form-border-color, var(--uk-${token}, ${hex}))`;
    }
    return `var(--uk-${token}, ${hex})`;
  });
  return changed ? result : null;
}

/**
 * The properties that keep their order in the generated file.
 *
 * A rewritten declaration moves after the whole UIkit CSS. A later UIkit
 * declaration of the same property, left as it is, would lose against it
 * (".uk-nav-medium" sets a line height after ".uk-nav-primary"): from the
 * first rewritten declaration on, the declarations of these properties are
 * all printed, rewritten or not.
 */
function family(property) {
  if (property.startsWith('margin')) {
    return 'margin';
  }
  return ['box-shadow', 'line-height'].includes(property) ? property : null;
}

// The position of the first rewritten declaration of each family.
const firstRewritten = new Map();

/**
 * Serializes the tokenized declarations of a node list.
 *
 * @param {Array} nodes
 *   The rules and at-rule blocks.
 * @param {string} indent
 *   The indentation of the block.
 * @param {object} position
 *   The count of the declarations read so far.
 * @param {boolean} collect
 *   Whether this pass only looks for the first rewritten declarations.
 */
function emit(nodes, indent, position, collect) {
  let out = '';
  for (const node of nodes) {
    if (node.children) {
      const inner = emit(node.children, `${indent}  `, position, collect);
      if (inner) {
        out += `${indent}${node.prelude} {\n${inner}${indent}}\n`;
      }
      continue;
    }
    const declarations = [];
    for (const declaration of split(node.body, ';')) {
      const colon = declaration.indexOf(':');
      if (colon === -1) {
        continue;
      }
      const property = declaration.slice(0, colon).trim();
      const value = declaration.slice(colon + 1).trim();
      if (property.startsWith('--')) {
        continue;
      }
      position.count++;
      const rewritten = tokenize(node.selector, property, value);
      const group = family(property);
      if (rewritten) {
        declarations.push(`${property === 'font' ? 'font-family' : property}: ${rewritten};`);
        if (collect && group && !firstRewritten.has(group)) {
          firstRewritten.set(group, position.count);
        }
      }
      else if (!collect && group && firstRewritten.get(group) < position.count) {
        declarations.push(`${property}: ${value};`);
      }
    }
    if (declarations.length) {
      const selector = split(node.selector, ',').join(`,\n${indent}`);
      out += `${indent}${selector} {\n${declarations.map((d) => `${indent}  ${d}`).join('\n')}\n${indent}}\n`;
    }
  }
  return out;
}

/**
 * The rules of the corner radius, added to the ones rewritten from UIkit.
 */
function radius() {
  tokens.set('global-border-radius', '0');
  const value = 'var(--uk-global-border-radius, 0)';
  return `${radiusSelectors.join(',\n')} {
  border-radius: ${value};
}
.uk-button-group > .uk-button:not(:first-child),
.uk-button-group > :not(:first-child) > .uk-button {
  border-start-start-radius: 0;
  border-end-start-radius: 0;
}
.uk-button-group > .uk-button:not(:last-child),
.uk-button-group > :not(:last-child) > .uk-button {
  border-start-end-radius: 0;
  border-end-end-radius: 0;
}
.uk-card-media-top,
.uk-card-media-top img {
  border-radius: ${value} ${value} 0 0;
}
.uk-card-media-bottom,
.uk-card-media-bottom img {
  border-radius: 0 0 ${value} ${value};
}
.uk-card-media-left,
.uk-card-media-left img {
  border-start-start-radius: ${value};
  border-end-start-radius: ${value};
}
.uk-card-media-right,
.uk-card-media-right img {
  border-start-end-radius: ${value};
  border-end-end-radius: ${value};
}
`;
}

const rules = parse(css.replace(/\/\*[\s\S]*?\*\//g, ''));
emit(rules, '', { count: 0 }, true);
const body = emit(rules, '', { count: 0 }, false) + radius();
const list = [...tokens.entries()].map(([name, value]) => ` *   --uk-${name}: ${value}`).join('\n');

writeFileSync(target, `/**
 * @file
 * UIkit design tokens.
 *
 * GENERATED FILE, DO NOT EDIT: run "npm run build:tokens".
 * Source: UIkit ${version} dist/css/uikit.css.
 *
 * Custom properties (with their UIkit default):
${list}
 */

${body}`);

console.log(`Wrote ${target}: ${tokens.size} tokens, ${body.split('\n').length} lines.`);
