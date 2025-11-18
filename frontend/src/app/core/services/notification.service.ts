import { Injectable, signal } from '@angular/core';

export type NotificationType = 'success' | 'error' | 'info';

export interface NotificationItem {
  id: number;
  type: NotificationType;
  message: string;
}

@Injectable({ providedIn: 'root' })
export class NotificationService {
  private seq = 1;
  readonly items = signal<readonly NotificationItem[]>([]);

  success(message: string, timeoutMs = 4000): void {
    this.push('success', message, timeoutMs);
  }

  error(message: string, timeoutMs = 6000): void {
    this.push('error', message, timeoutMs);
  }

  info(message: string, timeoutMs = 4000): void {
    this.push('info', message, timeoutMs);
  }

  dismiss(id: number): void {
    this.items.set(this.items().filter((n) => n.id !== id));
  }

  private push(type: NotificationType, message: string, timeoutMs: number): void {
    const id = this.seq++;
    const next = [...this.items(), { id, type, message } as NotificationItem];
    this.items.set(next);
    if (timeoutMs > 0) {
      setTimeout(() => this.dismiss(id), timeoutMs);
    }
  }
}
