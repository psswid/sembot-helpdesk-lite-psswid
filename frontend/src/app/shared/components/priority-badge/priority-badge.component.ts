import { Component, ChangeDetectionStrategy, input, computed } from '@angular/core';
import { TicketPriority } from '../../../core/models/ticket.model';

type BadgeSize = 'sm' | 'md' | 'lg';

@Component({
  selector: 'app-priority-badge',
  templateUrl: './priority-badge.component.html',
  styleUrl: './priority-badge.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: {
    class: 'priority-badge',
    role: 'status',
    '[attr.aria-label]': 'ariaLabel()',
    '[attr.data-priority]': 'priority()',
    '[attr.data-size]': 'size()',
  },
})
export class PriorityBadgeComponent {
  priority = input.required<TicketPriority>();
  size = input<BadgeSize>('md');

  label = computed(() => {
    const p = this.priority();
    return p.charAt(0).toUpperCase() + p.slice(1);
  });

  ariaLabel = computed(() => `Priority: ${this.label()}`);
}
