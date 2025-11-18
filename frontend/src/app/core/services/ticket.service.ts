import { inject, Injectable, computed, signal } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';
import { apiUrl, parseHttpError, parseWith } from '../api/api.util';
import { NotificationService } from './notification.service';
import { z } from 'zod';
import {
  TicketSchema,
  type Ticket,
  type TicketPriority,
  type TicketStatus,
  PaginatedTicketSchema,
  type Paginated,
} from '../models/ticket.model';
import { TicketStatusChangeSchema, type TicketStatusChange } from '../models/ticket-status-change.model';

type TicketFilters = {
  status?: TicketStatus;
  priority?: TicketPriority;
  assignee_id?: number | null;
  tags?: string[];
};

export type CreateTicketDto = {
  title: string;
  description: string;
  priority: TicketPriority;
  tags?: string[];
  assignee_id?: number | null;
  location?: string | null;
};

export type UpdateTicketDto = Partial<Omit<CreateTicketDto, 'title' | 'priority'>> & {
  title?: string;
  priority?: TicketPriority;
  status?: TicketStatus;
};

@Injectable({ providedIn: 'root' })
export class TicketService {
  private readonly http = inject(HttpClient);
  private readonly notify = inject(NotificationService);

  // State signals
  readonly tickets = signal<readonly Ticket[]>([]);
  readonly selectedTicket = signal<Ticket | null>(null);
  readonly statusHistory = signal<readonly TicketStatusChange[]>([]);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  // Pagination signals
  readonly page = signal(1);
  readonly perPage = signal(10);
  readonly total = signal(0);
  readonly lastPage = signal(1);

  // Filter signals
  readonly filters = signal<TicketFilters>({});

  // Derived/computed
  readonly pagination = computed(() => ({
    current_page: this.page(),
    per_page: this.perPage(),
    total: this.total(),
    last_page: this.lastPage(),
  }));

  readonly hasNextPage = computed(() => this.page() < this.lastPage());
  readonly hasPrevPage = computed(() => this.page() > 1);

  // Client-side refinement hook if needed (server filtering is the source of truth)
  readonly refinedTickets = computed(() => this.tickets());

  setPage(page: number): void {
    this.page.set(Math.max(1, page));
  }

  setPerPage(per: number): void {
    this.perPage.set(Math.max(1, per));
  }

  setFilters(partial: Partial<TicketFilters>): void {
    this.filters.set({ ...this.filters(), ...partial });
  }

  clearFilters(): void {
    this.filters.set({});
  }

  refresh(): void {
    void this.getTickets();
  }

  async getTickets(): Promise<void> {
    this.loading.set(true);
    this.error.set(null);
    try {
      let params = new HttpParams()
        .set('page', String(this.page()))
        .set('per_page', String(this.perPage()));

      const f = this.filters();
      if (f.status) params = params.set('status', f.status);
      if (f.priority) params = params.set('priority', f.priority);
      if (typeof f.assignee_id === 'number') params = params.set('assignee_id', String(f.assignee_id));
      if (f.tags && f.tags.length) {
        f.tags.forEach((t) => {
          params = params.append('tags[]', t);
        });
      }

      const url = apiUrl('/api/tickets');
      const res = await firstValueFrom(this.http.get(url, { params }));
      const parsed = parseWith(PaginatedTicketSchema, res);

      this.tickets.set(parsed.data);
      this.total.set(parsed.meta.total);
      this.lastPage.set(parsed.meta.last_page);
      // Keep current page/per_page as-is
    } catch (e) {
      const { message } = parseHttpError(e);
      this.error.set(message);
      this.notify.error(message);
    } finally {
      this.loading.set(false);
    }
  }

  async getTicket(id: number): Promise<Ticket | null> {
    this.loading.set(true);
    this.error.set(null);
    try {
      const url = apiUrl(`/api/tickets/${id}`);
      const res = await firstValueFrom(this.http.get(url));
      const payload = (res as any)?.data ?? res;
      const ticket = parseWith(TicketSchema, payload);
      this.selectedTicket.set(ticket);
      // Update list cache if present
      const list = this.tickets();
      const idx = list.findIndex((t) => t.id === id);
      if (idx >= 0) {
        const updated = list.slice();
        updated[idx] = ticket;
        this.tickets.set(updated);
      }
      return ticket;
    } catch (e) {
      const { message } = parseHttpError(e);
      this.error.set(message);
      this.notify.error(message);
      return null;
    } finally {
      this.loading.set(false);
    }
  }

  async createTicket(dto: CreateTicketDto): Promise<Ticket | null> {
    this.loading.set(true);
    this.error.set(null);
    try {
      const url = apiUrl('/api/tickets');
      const res = await firstValueFrom(this.http.post(url, dto));
      const payload = (res as any)?.data ?? res;
      const created = parseWith(TicketSchema, payload);

      // Optimistic: prepend if it likely matches current filters
      const f = this.filters();
      const matches =
        (!f.status || f.status === created.status) &&
        (!f.priority || f.priority === created.priority) &&
        (typeof f.assignee_id !== 'number' || f.assignee_id === (created.assignee?.id ?? null)) &&
        (!f.tags || f.tags.every((tag) => created.tags.includes(tag)));
      if (matches) this.tickets.set([created, ...this.tickets()]);

      // Refresh totals by refetching page 1 if item may be outside current page
      this.setPage(1);
      this.refresh();
      return created;
    } catch (e) {
      const { message } = parseHttpError(e);
      this.error.set(message);
      this.notify.error(message);
      return null;
    } finally {
      this.loading.set(false);
    }
  }

  async updateTicket(id: number, dto: UpdateTicketDto): Promise<Ticket | null> {
    this.loading.set(true);
    this.error.set(null);
    try {
      const url = apiUrl(`/api/tickets/${id}`);
      const res = await firstValueFrom(this.http.patch(url, dto));
      const payload = (res as any)?.data ?? res;
      const updated = parseWith(TicketSchema, payload);

      // Update selected and list cache
      this.selectedTicket.set(updated);
      const list = this.tickets();
      const idx = list.findIndex((t) => t.id === id);
      if (idx >= 0) {
        const next = list.slice();
        next[idx] = updated;
        this.tickets.set(next);
      }
      return updated;
    } catch (e) {
      const { message } = parseHttpError(e);
      this.error.set(message);
      return null;
    } finally {
      this.loading.set(false);
    }
  }

  async deleteTicket(id: number): Promise<boolean> {
    this.loading.set(true);
    this.error.set(null);
    // Optimistic remove
    const prev = this.tickets();
    const afterDelete = prev.filter((t) => t.id !== id);
    this.tickets.set(afterDelete);
    try {
      const url = apiUrl(`/api/tickets/${id}`);
      await firstValueFrom(this.http.delete(url));
      // Adjust totals and maybe refetch
      this.total.set(Math.max(0, this.total() - 1));
      if (afterDelete.length === 0 && this.page() > 1) {
        this.setPage(this.page() - 1);
      }
      this.refresh();
      return true;
    } catch (e) {
      // Revert on failure
      this.tickets.set(prev);
      const { message } = parseHttpError(e);
      this.error.set(message);
      this.notify.error(message);
      return false;
    } finally {
      this.loading.set(false);
    }
  }

  async loadStatusHistory(ticketId: number): Promise<readonly TicketStatusChange[] | null> {
    this.loading.set(true);
    this.error.set(null);
    try {
      const url = apiUrl(`/api/tickets/${ticketId}/status-history`);
      const res = await firstValueFrom(this.http.get(url));
      const payload = (res as any)?.data ?? res;
      const parsed = parseWith(z.array(TicketStatusChangeSchema), payload);
      this.statusHistory.set(parsed);
      return parsed;
    } catch (e) {
      const { message } = parseHttpError(e);
      this.error.set(message);
      this.notify.error(message);
      return null;
    } finally {
      this.loading.set(false);
    }
  }
}
