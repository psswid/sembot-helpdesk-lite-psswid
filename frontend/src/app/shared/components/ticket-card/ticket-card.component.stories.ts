import type { Meta, StoryObj } from '@storybook/angular';
import { TicketCardComponent } from './ticket-card.component';

const meta: Meta<TicketCardComponent> = {
  title: 'Components/TicketCard',
  component: TicketCardComponent,
  tags: ['autodocs'],
  argTypes: {
    title: {
      control: 'text',
      description: 'Ticket title',
    },
    description: {
      control: 'text',
      description: 'Ticket description/summary',
    },
    status: {
      control: 'select',
      options: ['open', 'in_progress', 'resolved', 'closed'],
      description: 'Current ticket status',
    },
    priority: {
      control: 'select',
      options: ['low', 'medium', 'high'],
      description: 'Ticket priority level',
    },
    tags: {
      control: 'object',
      description: 'Array of tag strings',
    },
    loading: {
      control: 'boolean',
      description: 'Shows loading skeleton state',
    },
    clicked: {
      action: 'clicked',
      description: 'Emitted when card is clicked or activated via keyboard',
    },
  },
  parameters: {
    docs: {
      description: {
        component: 'A card component for displaying ticket summaries in list views. Supports loading states, priority badges, status indicators, and tags. Fully keyboard accessible and responsive.',
      },
    },
  },
};

export default meta;
type Story = StoryObj<TicketCardComponent>;

export const Default: Story = {
  args: {
    title: 'Cannot login to admin panel',
    description: 'Users are reporting issues accessing the admin dashboard. The login button appears to be unresponsive.',
    status: 'open',
    priority: 'high',
    tags: ['authentication', 'urgent'],
    loading: false,
  },
  parameters: {
    docs: {
      description: {
        story: 'Default ticket card with all information displayed including title, description, status, priority, and tags.',
      },
    },
  },
};

export const Loading: Story = {
  args: {
    title: '',
    description: '',
    status: 'open',
    priority: 'low',
    tags: [],
    loading: true,
  },
  parameters: {
    docs: {
      description: {
        story: 'Loading state with animated skeleton showing placeholder content while data is being fetched.',
      },
    },
  },
};

export const WithoutTags: Story = {
  args: {
    title: 'Update user profile page',
    description: 'Need to add phone number field to the user profile form.',
    status: 'in_progress',
    priority: 'medium',
    tags: [],
    loading: false,
  },
  parameters: {
    docs: {
      description: {
        story: 'Ticket card without tags showing how the layout adapts when tags are not present.',
      },
    },
  },
};

export const ShortDescription: Story = {
  args: {
    title: 'Fix broken link in footer',
    description: 'Privacy policy link returns 404',
    status: 'open',
    priority: 'low',
    tags: ['bug', 'frontend'],
    loading: false,
  },
  parameters: {
    docs: {
      description: {
        story: 'Ticket with short description demonstrating flexible content layout.',
      },
    },
  },
};

export const LongContent: Story = {
  args: {
    title: 'Implement comprehensive data export feature for all user accounts with filtering and scheduling capabilities',
    description: 'We need to build a robust data export system that allows administrators to extract user data in various formats (CSV, JSON, XML) with advanced filtering options including date ranges, user roles, account status, and activity levels. The export should support scheduling for automated daily, weekly, or monthly reports.',
    status: 'in_progress',
    priority: 'medium',
    tags: ['feature', 'admin', 'data-export', 'reporting', 'automation'],
    loading: false,
  },
  parameters: {
    docs: {
      description: {
        story: 'Ticket with long title, description, and multiple tags showing text truncation and wrapping behavior.',
      },
    },
  },
};

export const ResolvedTicket: Story = {
  args: {
    title: 'Database backup script failing',
    description: 'Automated backup script exits with error code 1. Needs investigation.',
    status: 'resolved',
    priority: 'high',
    tags: ['database', 'infrastructure'],
    loading: false,
  },
  parameters: {
    docs: {
      description: {
        story: 'Resolved ticket showing the success status indicator.',
      },
    },
  },
};

export const ClosedTicket: Story = {
  args: {
    title: 'Request for additional storage space',
    description: 'User requested 50GB additional storage for project files.',
    status: 'closed',
    priority: 'low',
    tags: ['storage', 'request'],
    loading: false,
  },
  parameters: {
    docs: {
      description: {
        story: 'Closed ticket showing the completed/archived state.',
      },
    },
  },
};

export const AllStatuses: Story = {
  render: () => ({
    template: `
      <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: var(--space-4);">
        <app-ticket-card
          title="New bug report"
          description="Found issue in payment processing"
          status="open"
          priority="high"
          [tags]="['bug', 'payment']"
        />
        <app-ticket-card
          title="Update documentation"
          description="API docs need to be updated for v2"
          status="in_progress"
          priority="medium"
          [tags]="['docs']"
        />
        <app-ticket-card
          title="Security patch applied"
          description="Applied latest security updates to all servers"
          status="resolved"
          priority="high"
          [tags]="['security']"
        />
        <app-ticket-card
          title="Old feature request"
          description="This feature was implemented in v1.5"
          status="closed"
          priority="low"
          [tags]="['feature']"
        />
      </div>
    `,
  }),
  parameters: {
    docs: {
      description: {
        story: 'Comparison view showing all status variants in a grid layout.',
      },
    },
  },
};

export const ThemeLight: Story = {
  render: (args) => ({
    props: args,
    template: `
      <div style="padding: var(--space-4); background: var(--color-bg);">
        <app-ticket-card
          [title]="title"
          [description]="description"
          [status]="status"
          [priority]="priority"
          [tags]="tags"
          [loading]="loading"
        ></app-ticket-card>
      </div>
    `,
  }),
  args: {
    title: 'Cannot login to admin panel',
    description: 'Users are reporting issues accessing the admin dashboard. The login button appears to be unresponsive.',
    status: 'open',
    priority: 'high',
    tags: ['authentication', 'urgent'],
    loading: false,
  },
  parameters: {
    docs: { description: { story: 'Ticket card in the default (light) theme.' } },
  },
};

export const ThemeDark: Story = {
  render: (args) => ({
    props: args,
    template: `
      <div data-theme="dark" style="padding: var(--space-4); background: var(--color-bg);">
        <app-ticket-card
          [title]="title"
          [description]="description"
          [status]="status"
          [priority]="priority"
          [tags]="tags"
          [loading]="loading"
        ></app-ticket-card>
      </div>
    `,
  }),
  args: {
    title: 'Cannot login to admin panel',
    description: 'Users are reporting issues accessing the admin dashboard. The login button appears to be unresponsive.',
    status: 'open',
    priority: 'high',
    tags: ['authentication', 'urgent'],
    loading: false,
  },
  parameters: {
    docs: { description: { story: 'Ticket card in the dark theme (using `data-theme="dark"`).' } },
  },
};
