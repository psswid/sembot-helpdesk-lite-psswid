import { ChangeDetectionStrategy, Component, computed, effect, inject, signal } from '@angular/core';
import { JsonPipe } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { TicketService } from '../../../core/services/ticket.service';
import { AuthService } from '../../../core/services/auth.service';
import { TriageSuggestionPanelComponent } from '../../../shared/components/triage-suggestion-panel/triage-suggestion-panel.component';

@Component({
  selector: 'app-ticket-detail',
  imports: [RouterLink, JsonPipe, TriageSuggestionPanelComponent],
  templateUrl: './ticket-detail.component.html',
  styleUrl: './ticket-detail.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { class: 'ticket-detail-page' }
})
export class TicketDetailComponent {
  private readonly route = inject(ActivatedRoute);
  protected readonly tickets = inject(TicketService);
  private readonly auth = inject(AuthService);

  readonly loading = computed(() => this.tickets.loading());
  readonly error = computed(() => this.tickets.error());
  readonly ticket = computed(() => this.tickets.selectedTicket());
  readonly history = computed(() => this.tickets.statusHistory());

  readonly canEdit = computed(() => {
    const user = this.auth.currentUser();
    const role = user?.role;
    return role === 'admin' || role === 'agent';
  });

  readonly ticketId = signal<number | null>(null);

  constructor() {
    // Support navigation within same component to different id
    effect(() => {
      const idParam = this.route.snapshot.paramMap.get('id');
      const id = idParam ? Number(idParam) : NaN;
      if (!Number.isFinite(id)) return;
      if (this.ticketId() === id) return;
      this.ticketId.set(id);
      void this.load(id);
    });
  }

  private async load(id: number): Promise<void> {
    await this.tickets.getTicket(id);
    await this.tickets.loadStatusHistory(id);
  }
}
