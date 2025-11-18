import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { Router, RouterLink, RouterLinkActive } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-header',
  imports: [RouterLink, RouterLinkActive],
  templateUrl: './app-header.component.html',
  styleUrl: './app-header.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { class: 'app-header', 'aria-label': 'Main header' }
})
export class AppHeaderComponent {
  // Simple menu toggle for small screens
  readonly menuOpen = signal(false);
  readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  toggleMenu(): void {
    this.menuOpen.update((v) => !v);
  }

  closeMenu(): void {
    this.menuOpen.set(false);
  }

  logout(): void {
    this.auth
      .logout()
      .subscribe(() => {
        this.closeMenu();
        this.router.navigateByUrl('/login');
      });
  }
}
