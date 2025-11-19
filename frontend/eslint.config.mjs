// For more info, see https://github.com/storybookjs/eslint-plugin-storybook#configuration-flat-config-format
import storybook from "eslint-plugin-storybook";

// ESLint Flat Config for Angular 20 + Templates
// Uses @angular-eslint for TypeScript and HTML template linting
import tsParser from '@typescript-eslint/parser';
import angularPlugin from '@angular-eslint/eslint-plugin';
import angularTemplatePlugin from '@angular-eslint/eslint-plugin-template';
import angularTemplateParser from '@angular-eslint/template-parser';

export default [{
  files: ['**/*.ts'],
  ignores: ['dist/**', 'node_modules/**'],
  languageOptions: {
    parser: tsParser,
    parserOptions: {
      ecmaVersion: 2022,
      sourceType: 'module'
    }
  },
  plugins: {
    '@angular-eslint': angularPlugin
  },
  rules: {
    // Follow our conventions: standalone + OnPush by default
    '@angular-eslint/prefer-standalone': 'error',
    '@angular-eslint/prefer-on-push-component-change-detection': 'error',
    // Reasonable Angular best-practices
    '@angular-eslint/component-class-suffix': ['error', { suffixes: ['Component'] }],
    '@angular-eslint/directive-class-suffix': ['error', { suffixes: ['Directive'] }],
    '@angular-eslint/no-host-metadata-property': 'off'
  }
}, {
  files: ['**/*.html'],
  ignores: ['dist/**', 'node_modules/**'],
  languageOptions: {
    parser: angularTemplateParser
  },
  plugins: {
    '@angular-eslint/template': angularTemplatePlugin
  },
  rules: {
    // Accessibility and template hygiene
    '@angular-eslint/template/button-has-type': 'warn',
    '@angular-eslint/template/attributes-order': 'warn',
    '@angular-eslint/template/alt-text': 'warn'
  }
}, ...storybook.configs["flat/recommended"]];
