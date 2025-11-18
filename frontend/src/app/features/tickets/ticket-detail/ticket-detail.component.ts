import { ChangeDetectionStrategy, Component, computed, effect, inject, signal } from '@angular/core';
import { JsonPipe, DecimalPipe } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { TicketService } from '../../../core/services/ticket.service';
import { AuthService } from '../../../core/services/auth.service';
import { TriageService } from '../../../core/services/triage.service';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';

@Component({
  selector: 'app-ticket-detail',
  imports: [RouterLink, JsonPipe, DecimalPipe, ReactiveFormsModule],
  templateUrl: './ticket-detail.component.html',
  styleUrl: './ticket-detail.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { class: 'ticket-detail-page' }
})
export class TicketDetailComponent {
  private readonly route = inject(ActivatedRoute);
  protected readonly tickets = inject(TicketService);
  private readonly auth = inject(AuthService);
  protected readonly triage = inject(TriageService);
  private readonly fb = inject(FormBuilder);

  readonly loading = computed(() => this.tickets.loading());
  readonly error = computed(() => this.tickets.error());
  readonly ticket = computed(() => this.tickets.selectedTicket());
  readonly history = computed(() => this.tickets.statusHistory());

  readonly triageSuggestion = computed(() => this.triage.suggestion());
  readonly triageLoading = computed(() => this.triage.loading());
  readonly triageError = computed(() => this.triage.error());

  readonly canEdit = computed(() => {
    const user = this.auth.currentUser();
    const role = user?.role;
    return role === 'admin' || role === 'agent';
  });

  readonly ticketId = signal<number | null>(null);

  readonly acceptForm = this.fb.nonNullable.group({
    priority: this.fb.nonNullable.control<'low' | 'medium' | 'high'>('medium', { validators: [Validators.required] }),
    tags: this.fb.control<string>(''),
    assignee_id: this.fb.control<string>(''),
    status: this.fb.control<string>('')
  });

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
    const s = this.triageSuggestion();
    if (s) {
      this.acceptForm.patchValue(
        {
          priority: (s.priority as any) ?? 'medium',
          tags: (s.tags ?? []).join(', '),
        },
        { emitEvent: false }
      );
    }
  }

  async suggest(): Promise<void> {
    const id = this.ticketId();
    if (!id) return;
    const s = await this.triage.suggestTriage(id);
    if (s) {
      this.acceptForm.patchValue(
        {
          priority: (s.priority as any) ?? 'medium',
          tags: (s.tags ?? []).join(', '),
        },
        { emitEvent: false }
      );
    }
  }

  async accept(): Promise<void> {
    if (this.acceptForm.invalid || this.triageLoading()) return;
    const id = this.ticketId();
    if (!id) return;
    const raw = this.acceptForm.getRawValue();
    const tags = raw.tags ? raw.tags.split(',').map((t: string) => t.trim()).filter(Boolean) : [];
    const assignee_id = raw.assignee_id ? Number(raw.assignee_id) : undefined;
    const status = raw.status ? (raw.status as any) : undefined;
    await this.triage.acceptSuggestion(id, {
      priority: raw.priority as any,
      tags: tags.length ? tags : undefined,
      assignee_id: Number.isFinite(assignee_id) ? assignee_id! : undefined,
      status,
    });
  }

  async reject(): Promise<void> {
    const id = this.ticketId();
    if (!id) return;
    await this.triage.rejectSuggestion(id);
  }
}
