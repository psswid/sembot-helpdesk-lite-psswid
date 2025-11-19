import type { Meta, StoryObj } from '@storybook/angular';
import { PriorityBadgeComponent } from './priority-badge.component';

const meta: Meta<PriorityBadgeComponent> = {
  title: 'Components/PriorityBadge',
  component: PriorityBadgeComponent,
  tags: ['autodocs'],
  argTypes: {
    priority: {
      control: 'select',
      options: ['low', 'medium', 'high'],
      description: 'Priority level of the ticket',
    },
    size: {
      control: 'select',
      options: ['sm', 'md', 'lg'],
      description: 'Size variant of the badge',
    },
  },
  parameters: {
    docs: {
      description: {
        component: 'A visual indicator for ticket priority levels. Displays color-coded badges for low, medium, and high priorities with support for different sizes and automatic theme adaptation.',
      },
    },
  },
};

export default meta;
type Story = StoryObj<PriorityBadgeComponent>;

export const Low: Story = {
  args: {
    priority: 'low',
    size: 'md',
  },
  parameters: {
    docs: {
      description: {
        story: 'Low priority badge with green color scheme indicating non-urgent items.',
      },
    },
  },
};

export const Medium: Story = {
  args: {
    priority: 'medium',
    size: 'md',
  },
  parameters: {
    docs: {
      description: {
        story: 'Medium priority badge with yellow/orange color scheme for moderate urgency.',
      },
    },
  },
};

export const High: Story = {
  args: {
    priority: 'high',
    size: 'md',
  },
  parameters: {
    docs: {
      description: {
        story: 'High priority badge with red color scheme indicating urgent items requiring immediate attention.',
      },
    },
  },
};

export const SmallSize: Story = {
  args: {
    priority: 'medium',
    size: 'sm',
  },
  parameters: {
    docs: {
      description: {
        story: 'Small size variant for compact layouts or dense information displays.',
      },
    },
  },
};

export const LargeSize: Story = {
  args: {
    priority: 'high',
    size: 'lg',
  },
  parameters: {
    docs: {
      description: {
        story: 'Large size variant for prominent displays or emphasis.',
      },
    },
  },
};

export const AllPriorities: Story = {
  render: () => ({
    template: `
      <div style="display: flex; gap: var(--space-3); align-items: center; flex-wrap: wrap;">
        <app-priority-badge priority="low" size="md" />
        <app-priority-badge priority="medium" size="md" />
        <app-priority-badge priority="high" size="md" />
      </div>
    `,
  }),
  parameters: {
    docs: {
      description: {
        story: 'Comparison view of all priority levels side by side.',
      },
    },
  },
};

export const AllSizes: Story = {
  render: () => ({
    template: `
      <div style="display: flex; gap: var(--space-3); align-items: center; flex-wrap: wrap;">
        <app-priority-badge priority="medium" size="sm" />
        <app-priority-badge priority="medium" size="md" />
        <app-priority-badge priority="medium" size="lg" />
      </div>
    `,
  }),
  parameters: {
    docs: {
      description: {
        story: 'Comparison view of all size variants.',
      },
    },
  },
};
