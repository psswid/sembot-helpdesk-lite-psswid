import { ChangeDetectionStrategy, Component } from '@angular/core';

@Component({
  selector: 'app-login',
  template: `
    <section class="login">
      <h1>Login</h1>
      <p>Placeholder login screen. Implemented in EPIC6-005.</p>
    </section>
  `,
  styles: [
    `
      .login { padding: 2rem; }
      h1 { margin: 0 0 0.5rem 0; }
    `
  ],
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class LoginComponent {}
