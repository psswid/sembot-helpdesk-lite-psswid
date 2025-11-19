import { Component, ChangeDetectionStrategy, input, output } from '@angular/core';
import { PriorityBadgeComponent } from '../priority-badge/priority-badge.component';
import { TicketPriority, TicketStatus } from '../../../core/models/ticket.model';

@Component({
  selector: 'app-ticket-card',
  templateUrl: './ticket-card.component.html',
  styleUrl: './ticket-card.component.scss',
  imports: [PriorityBadgeComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: {
    class: 'ticket-card',
    role: 'button',
    tabindex: '0',
    '[attr.data-loading]': 'loading()',
    '(click)': 'handleClick()',
    '(keydown.enter)': 'handleClick()',
    '(keydown.space)': 'handleClick($event)',
  },
})
export class TicketCardComponent {
  title = input.required<string>();
  description = input<string>('');
  status = input.required<TicketStatus>();
  priority = input.required<TicketPriority>();
  tags = input<string[]>([]);
  loading = input<boolean>(false);
  createdAt = input<string | null | undefined>(null);
  location = input<string | null | undefined>(null);

  clicked = output<void>();

  handleClick(event?: Event): void {
    if (event instanceof KeyboardEvent && event.key === ' ') {
      event.preventDefault();
    }
    if (!this.loading()) {
      this.clicked.emit();
    }
  }

  getStatusLabel(): string {
    const status = this.status();
    return status.split('_').map(word =>
      word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
  }
}
