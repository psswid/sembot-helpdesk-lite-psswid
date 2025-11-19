import type { Preview } from '@storybook/angular';

export const globalTypes = {
  theme: {
    name: 'Theme',
    description: 'Global theme for components',
    defaultValue: 'light',
    toolbar: {
      icon: 'paintbrush',
      items: [
        { value: 'light', title: 'Light', icon: 'sun' },
        { value: 'dark', title: 'Dark', icon: 'moon' },
      ],
      dynamicTitle: true,
    },
  },
};

const preview: Preview = {
  parameters: {
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
      expanded: true,
    },
    backgrounds: { disable: true },
  },
  decorators: [
    (story, context) => {
      const theme = context.globals['theme'] || 'light';
      document.documentElement.setAttribute('data-theme', theme);
      return story();
    },
  ],
};

export default preview;
