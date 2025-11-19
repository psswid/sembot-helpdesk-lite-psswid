import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TicketService } from '../../../core/services/ticket.service';
import { AuthService } from '../../../core/services/auth.service';
import { TicketPriority, TicketStatus } from '../../../core/models/ticket.model';
import { TriageService } from '../../../core/services/triage.service';
import { TriageSuggestionPanelComponent } from '../../../shared/components/triage-suggestion-panel/triage-suggestion-panel.component';

const PRIORITY_OPTIONS: TicketPriority[] = ['low', 'medium', 'high'];
const STATUS_OPTIONS: TicketStatus[] = ['open', 'in_progress', 'resolved', 'closed'];

@Component({
  selector: 'app-ticket-form',
  imports: [ReactiveFormsModule, RouterLink, TriageSuggestionPanelComponent],
  templateUrl: './ticket-form.component.html',
  styleUrl: './ticket-form.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { class: 'ticket-form-page' }
})
export class TicketFormComponent {
  private readonly fb = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  protected readonly tickets = inject(TicketService);
  private readonly auth = inject(AuthService);
  protected readonly triage = inject(TriageService);

  readonly id = signal<number | null>(null);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);
  readonly success = signal<string | null>(null);

  readonly canAssign = computed(() => {
    const role = this.auth.currentUser()?.role_name;
    return role === 'admin' || role === 'agent';
  });

  readonly isEdit = computed(() => this.id() !== null);

  readonly form = this.fb.nonNullable.group({
    title: this.fb.nonNullable.control('', [Validators.required, Validators.maxLength(200)]),
    description: this.fb.nonNullable.control('', [Validators.required]),
    priority: this.fb.nonNullable.control<TicketPriority>('medium', [Validators.required]),
    status: this.fb.nonNullable.control<TicketStatus>('open', [Validators.required]),
    assignee_id: this.fb.control<string>(''),
    tags: this.fb.control<string>(''),
    location: this.fb.control<string>('')
  });

  protected readonly PRIORITY_OPTIONS = PRIORITY_OPTIONS;
  protected readonly STATUS_OPTIONS = STATUS_OPTIONS;

  constructor() {
    const idParam = this.route.snapshot.paramMap.get('id');
    const id = idParam ? Number(idParam) : NaN;
    if (Number.isFinite(id) && id > 0) {
      this.id.set(id);
      // Load ticket for editing
      this.loading.set(true);
      this.tickets
        .getTicket(id)
        .then((t) => {
          if (!t) return;
          this.form.patchValue({
            title: t.title,
            description: t.description,
            priority: t.priority,
            status: t.status,
            assignee_id: t.assignee?.id != null ? String(t.assignee.id) : '',
            tags: t.tags?.join(', ') ?? '',
            location: t.location ?? ''
          }, { emitEvent: false });
        })
        .finally(() => this.loading.set(false));
    }
  }

  async onSuggested(): Promise<void> {
    const s = this.triage.suggestion();
    if (s) {
      this.form.patchValue(
        {
          priority: s.priority as any,
          tags: (s.tags ?? []).join(', '),
        },
        { emitEvent: false }
      );
    }
  }

  async acceptTriage(): Promise<void> {
    const id = this.id();
    if (!id || this.triage.loading()) return;
    const raw = this.form.getRawValue();
    const tags = raw.tags ? raw.tags.split(',').map((t) => t.trim()).filter(Boolean) : [];
    const assignee_id = raw.assignee_id ? Number(raw.assignee_id) : undefined;
    const updated = await this.triage.acceptSuggestion(id, {
      priority: raw.priority as any,
      tags: tags.length ? tags : undefined,
      assignee_id: Number.isFinite(assignee_id) ? assignee_id! : undefined,
    });
    if (updated) {
      await this.router.navigate(['/tickets', updated.id]);
    }
  }

  // Reject is handled inside the panel for inline mode

  async onSubmit(): Promise<void> {
    if (this.form.invalid || this.loading()) {
      this.form.markAllAsTouched();
      return;
    }
    this.loading.set(true);
    this.error.set(null);
    this.success.set(null);

    const raw = this.form.getRawValue();
    const assignee_id = raw.assignee_id ? Number(raw.assignee_id) : undefined;
    const tags = raw.tags ? raw.tags.split(',').map((t) => t.trim()).filter(Boolean) : [];

    try {
      if (this.isEdit()) {
        const updated = await this.tickets.updateTicket(this.id()!, {
          title: raw.title,
          description: raw.description,
          priority: raw.priority,
          status: raw.status as any,
          assignee_id: Number.isFinite(assignee_id) ? assignee_id! : null,
          tags,
          location: raw.location ? raw.location : null,
        });
        if (updated) {
          this.success.set('Ticket updated');
          await this.router.navigate(['/tickets', updated.id]);
        }
      } else {
        const created = await this.tickets.createTicket({
          title: raw.title,
          description: raw.description,
          priority: raw.priority,
          assignee_id: Number.isFinite(assignee_id) ? assignee_id! : null,
          tags,
          location: raw.location ? raw.location : null,
        });
        if (created) {
          this.success.set('Ticket created');
          await this.router.navigate(['/tickets', created.id]);
        }
      }
    } catch (e) {
      const message = (e as any)?.message ?? 'Failed to save ticket';
      this.error.set(message);
    } finally {
      this.loading.set(false);
    }
  }

  async onCancel(): Promise<void> {
    if (this.isEdit()) {
      await this.router.navigate(['/tickets', this.id()]);
    } else {
      await this.router.navigate(['/tickets']);
    }
  }
}
