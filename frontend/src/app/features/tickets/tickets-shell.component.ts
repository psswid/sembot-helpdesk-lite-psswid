import { ChangeDetectionStrategy, Component } from '@angular/core';

@Component({
  selector: 'app-tickets-shell',
  template: `
    <section class="tickets">
      <h1>Tickets</h1>
      <p>Placeholder tickets shell. Implement lists/details/forms in later epics.</p>
    </section>
  `,
  styles: [
    `
      .tickets { padding: 2rem; }
      h1 { margin: 0 0 0.5rem 0; }
    `
  ],
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class TicketsShellComponent {}
