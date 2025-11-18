import { Injectable, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';
import { apiUrl, parseHttpError, parseWith } from '../api/api.util';
import { TriageSuggestionSchema, type TriageSuggestion } from '../models/triage-suggestion.model';
import { TicketSchema, type Ticket } from '../models/ticket.model';
import { TicketService } from './ticket.service';

export type AcceptPatch = {
  priority: Ticket['priority'];
  tags?: string[];
  assignee_id?: number | null;
  status?: Ticket['status'];
};

@Injectable({ providedIn: 'root' })
export class TriageService {
  private readonly http = inject(HttpClient);
  private readonly tickets = inject(TicketService);

  readonly suggestion = signal<TriageSuggestion | null>(null);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  async suggestTriage(ticketId: number): Promise<TriageSuggestion | null> {
    this.loading.set(true);
    this.error.set(null);
    try {
      const url = apiUrl(`/api/tickets/${ticketId}/triage-suggest`);
      const res = await firstValueFrom(this.http.post(url, {}));
      const parsed = parseWith(TriageSuggestionSchema, res);
      this.suggestion.set(parsed);
      return parsed;
    } catch (e) {
      const { message } = parseHttpError(e);
      this.error.set(message);
      this.suggestion.set(null);
      return null;
    } finally {
      this.loading.set(false);
    }
  }

  async acceptSuggestion(ticketId: number, patch: AcceptPatch): Promise<Ticket | null> {
    this.loading.set(true);
    this.error.set(null);
    try {
      const url = apiUrl(`/api/tickets/${ticketId}/triage-accept`);
      const res = await firstValueFrom(this.http.post(url, patch));
      const updated = parseWith(TicketSchema, res);
      // Update TicketService caches
      this.tickets.selectedTicket.set(updated);
      const list = this.tickets.tickets();
      const idx = list.findIndex((t) => t.id === updated.id);
      if (idx >= 0) {
        const next = list.slice();
        next[idx] = updated;
        this.tickets.tickets.set(next);
      }
      this.suggestion.set(null);
      return updated;
    } catch (e) {
      const { message } = parseHttpError(e);
      this.error.set(message);
      return null;
    } finally {
      this.loading.set(false);
    }
  }

  async rejectSuggestion(ticketId: number, reason?: string): Promise<boolean> {
    this.loading.set(true);
    this.error.set(null);
    try {
      const url = apiUrl(`/api/tickets/${ticketId}/triage-reject`);
      await firstValueFrom(this.http.post(url, reason ? { reason } : {}));
      this.suggestion.set(null);
      return true;
    } catch (e) {
      const { message } = parseHttpError(e);
      this.error.set(message);
      return false;
    } finally {
      this.loading.set(false);
    }
  }
}
