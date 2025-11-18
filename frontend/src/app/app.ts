import { ChangeDetectionStrategy, Component, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { AppHeaderComponent } from './shared/components/app-header/app-header.component';
import { ToastsComponent } from './shared/components/toasts/toasts.component';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, AppHeaderComponent, ToastsComponent],
  templateUrl: './app.html',
  styleUrl: './app.scss',
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class AppComponent {
  protected readonly title = signal('frontend');
}
