import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-landing-redirect',
  imports: [],
  template: '<span class="sr-only">Redirecting…</span>',
  styles: [
    `.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;}`
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { class: 'landing-redirect', 'aria-live': 'polite' }
})
export class LandingRedirectComponent {
  private readonly router = inject(Router);
  private readonly auth = inject(AuthService);

  constructor() {
    queueMicrotask(() => {
      const target = this.auth.isAuthenticated() ? '/tickets' : '/login';
      void this.router.navigateByUrl(target);
    });
  }
}
