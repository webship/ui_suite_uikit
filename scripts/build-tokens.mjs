#!/usr/bin/env node
/**
 * @file
 * Generates assets/css/tokens.css from the compiled UIkit CSS.
 *
 * Every declaration of uikit.css using one of the UIkit global colors (or the
 * global font family) is re-emitted with the literal value replaced by a CSS
 * custom property falling back to the original value:
 *
 *   .uk-button-primary { background-color: var(--uk-global-primary-background, #1e87f0); }
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

const linkSelector = /(^|[\s,>+~(])(a|\.uk-link)(?=$|[\s,:.[>+~)])/;

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
    return `var(--uk-global-font-family, ${value})`;
  }
  const isText = property === 'color' || property === '-webkit-text-fill-color';
  result = value.replace(/#[0-9a-fA-F]{3,6}\b/g, (hex) => {
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
    return `var(--uk-${token}, ${hex})`;
  });
  return changed ? result : null;
}

/**
 * Serializes the tokenized declarations of a node list.
 */
function emit(nodes, indent = '') {
  let out = '';
  for (const node of nodes) {
    if (node.children) {
      const inner = emit(node.children, `${indent}  `);
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
      const rewritten = tokenize(node.selector, property, value);
      if (rewritten) {
        declarations.push(`${property}: ${rewritten};`);
      }
    }
    if (declarations.length) {
      const selector = split(node.selector, ',').join(`,\n${indent}`);
      out += `${indent}${selector} {\n${declarations.map((d) => `${indent}  ${d}`).join('\n')}\n${indent}}\n`;
    }
  }
  return out;
}

const body = emit(parse(css.replace(/\/\*[\s\S]*?\*\//g, '')));
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
