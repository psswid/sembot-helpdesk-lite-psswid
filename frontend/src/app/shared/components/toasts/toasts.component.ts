import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { NotificationService } from '../../../core/services/notification.service';

@Component({
  selector: 'app-toasts',
  imports: [],
  templateUrl: './toasts.component.html',
  styleUrl: './toasts.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { class: 'toasts-host', 'aria-live': 'polite', 'aria-atomic': 'true' }
})
export class ToastsComponent {
  protected readonly notifications = inject(NotificationService);

  close(id: number): void {
    this.notifications.dismiss(id);
  }
}
