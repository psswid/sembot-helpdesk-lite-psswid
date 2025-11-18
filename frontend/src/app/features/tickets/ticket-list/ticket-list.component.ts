import { ChangeDetectionStrategy, Component, computed, effect, inject, signal } from '@angular/core';
import { ReactiveFormsModule, FormBuilder } from '@angular/forms';
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { TicketService } from '../../../core/services/ticket.service';
import { TicketPriority, TicketStatus } from '../../../core/models/ticket.model';

const PRIORITY_OPTIONS: TicketPriority[] = ['low', 'medium', 'high'];
const STATUS_OPTIONS: TicketStatus[] = ['open', 'in_progress', 'resolved', 'closed'];

@Component({
  selector: 'app-ticket-list',
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './ticket-list.component.html',
  styleUrl: './ticket-list.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: {
    class: 'ticket-list-page'
  }
})
export class TicketListComponent {
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);
  protected readonly tickets = inject(TicketService);

  readonly initDone = signal(false);

  readonly form = this.fb.nonNullable.group({
    status: this.fb.control<string | ''>(''),
    priority: this.fb.control<string | ''>(''),
    assignee_id: this.fb.control<string>(''),
    tags: this.fb.control<string>('')
  });

  readonly isLoading = computed(() => this.tickets.loading());
  readonly error = computed(() => this.tickets.error());
  readonly page = computed(() => this.tickets.page());
  readonly perPage = computed(() => this.tickets.perPage());
  readonly total = computed(() => this.tickets.total());
  readonly lastPage = computed(() => this.tickets.lastPage());
  readonly hasPrev = computed(() => this.tickets.hasPrevPage());
  readonly hasNext = computed(() => this.tickets.hasNextPage());

  constructor() {
    // Initialize from query params on first load
    const q = this.route.snapshot.queryParamMap;
    const qpStatus = q.get('status');
    const qpPriority = q.get('priority');
    const qpAssignee = q.get('assignee_id');
    // tags can be provided as repeated tags[] or a single comma string 'tags'
    const qpTagsMulti = q.getAll('tags[]');
    const qpTagsSingle = q.get('tags');
    const qpPage = Number(q.get('page') ?? '1') || 1;
    const qpPerPage = Number(q.get('per_page') ?? '10') || 10;

    const status = (STATUS_OPTIONS as string[]).includes(qpStatus ?? '') ? (qpStatus as TicketStatus) : undefined;
    const priority = (PRIORITY_OPTIONS as string[]).includes(qpPriority ?? '') ? (qpPriority as TicketPriority) : undefined;
    const assignee_id = qpAssignee ? Number(qpAssignee) : undefined;
    const tags = qpTagsMulti.length ? qpTagsMulti : (qpTagsSingle ? qpTagsSingle.split(',').map((t) => t.trim()).filter(Boolean) : []);

    this.tickets.setFilters({ status, priority, assignee_id: Number.isFinite(assignee_id) ? assignee_id! : undefined, tags: tags.length ? tags : undefined });
    this.tickets.setPage(qpPage);
    this.tickets.setPerPage(qpPerPage);

    // Set form values for UI
    this.form.patchValue({
      status: status ?? '',
      priority: priority ?? '',
      assignee_id: Number.isFinite(assignee_id) ? String(assignee_id) : '',
      tags: tags.join(', ')
    }, { emitEvent: false });

    // Initial fetch
    this.tickets.getTickets().finally(() => this.initDone.set(true));

    // Optional: if page/perPage signals change elsewhere, keep query params synced
    effect(() => {
      if (!this.initDone()) return;
      void this.syncQueryParams(false);
    });
  }

  async applyFilters(): Promise<void> {
    const raw = this.form.getRawValue();
    const status = raw.status as TicketStatus | '';
    const priority = raw.priority as TicketPriority | '';
    const assignee_id = raw.assignee_id ? Number(raw.assignee_id) : undefined;
    const tags = raw.tags
      ? raw.tags.split(',').map((t) => t.trim()).filter(Boolean)
      : [];

    this.tickets.setFilters({
      status: status || undefined,
      priority: priority || undefined,
      assignee_id: Number.isFinite(assignee_id) ? assignee_id! : undefined,
      tags: tags.length ? tags : undefined,
    });

    this.tickets.setPage(1);
    await this.syncQueryParams(true);
    await this.tickets.getTickets();
  }

  async resetFilters(): Promise<void> {
    this.form.reset({ status: '', priority: '', assignee_id: '', tags: '' });
    this.tickets.clearFilters();
    this.tickets.setPage(1);
    await this.syncQueryParams(true);
    await this.tickets.getTickets();
  }

  async prevPage(): Promise<void> {
    if (!this.hasPrev()) return;
    this.tickets.setPage(this.page() - 1);
    await this.syncQueryParams(true);
    await this.tickets.getTickets();
  }

  async nextPage(): Promise<void> {
    if (!this.hasNext()) return;
    this.tickets.setPage(this.page() + 1);
    await this.syncQueryParams(true);
    await this.tickets.getTickets();
  }

  private async syncQueryParams(replaceUrl: boolean): Promise<void> {
    const f = this.tickets.filters();
    const qp: Record<string, any> = {
      page: this.page(),
      per_page: this.perPage(),
    };
    if (f.status) qp['status'] = f.status;
    if (f.priority) qp['priority'] = f.priority;
    if (typeof f.assignee_id === 'number') qp['assignee_id'] = f.assignee_id;
    if (f.tags && f.tags.length) qp['tags[]'] = f.tags;

    await this.router.navigate([], {
      relativeTo: this.route,
      queryParams: qp,
      queryParamsHandling: '',
      replaceUrl,
    });
  }

  protected readonly PRIORITY_OPTIONS = PRIORITY_OPTIONS;
  protected readonly STATUS_OPTIONS = STATUS_OPTIONS;
}
