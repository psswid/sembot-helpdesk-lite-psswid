import { ChangeDetectionStrategy, Component, input, output, inject, computed } from '@angular/core';
import { DecimalPipe } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { TriageService } from '../../../core/services/triage.service';

@Component({
  selector: 'app-triage-suggestion-panel',
  imports: [ReactiveFormsModule, DecimalPipe],
  templateUrl: './triage-suggestion-panel.component.html',
  styleUrl: './triage-suggestion-panel.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { class: 'triage-panel' }
})
export class TriageSuggestionPanelComponent {
  // Inputs
  readonly ticketId = input<number | null>();
  readonly mode = input<'inline' | 'form'>('inline');
  readonly canAct = input<boolean>(true);

  // Outputs
  readonly suggested = output<void>();
  readonly accepted = output<void>();
  readonly rejected = output<void>();
  // For inline mode, parent may want to handle accept using its own form values
  readonly acceptRequested = output<void>();

  private readonly fb = inject(FormBuilder);
  private readonly triage = inject(TriageService);

  readonly loading = computed(() => this.triage.loading());
  readonly error = computed(() => this.triage.error());
  readonly suggestion = computed(() => this.triage.suggestion());

  readonly acceptForm = this.fb.nonNullable.group({
    priority: this.fb.nonNullable.control<'low' | 'medium' | 'high'>('medium', { validators: [Validators.required] }),
    tags: this.fb.control<string>(''),
    assignee_id: this.fb.control<string>(''),
    status: this.fb.control<string>('')
  });

  async suggest(): Promise<void> {
    const id = this.ticketId();
    if (!id || !this.canAct()) return;
    const s = await this.triage.suggestTriage(id);
    if (s && this.mode() === 'form') {
      this.acceptForm.patchValue(
        {
          priority: (s.priority as any) ?? 'medium',
          tags: (s.tags ?? []).join(', '),
        },
        { emitEvent: false }
      );
    }
    this.suggested.emit();
  }

  async accept(): Promise<void> {
    if (this.mode() === 'inline') {
      // Delegate to parent in inline mode
      this.acceptRequested.emit();
      return;
    }
    if (this.acceptForm.invalid || this.loading() || !this.canAct()) return;
    const id = this.ticketId();
    if (!id) return;
    const raw = this.acceptForm.getRawValue();
    const tags = raw.tags ? raw.tags.split(',').map((t: string) => t.trim()).filter(Boolean) : [];
    const assignee_id = raw.assignee_id ? Number(raw.assignee_id) : undefined;
    const status = raw.status ? (raw.status as any) : undefined;
    const updated = await this.triage.acceptSuggestion(id, {
      priority: raw.priority as any,
      tags: tags.length ? tags : undefined,
      assignee_id: Number.isFinite(assignee_id) ? assignee_id! : undefined,
      status,
    });
    if (updated) this.accepted.emit();
  }

  async reject(): Promise<void> {
    const id = this.ticketId();
    if (!id || this.loading() || !this.canAct()) return;
    const ok = await this.triage.rejectSuggestion(id);
    if (ok) this.rejected.emit();
  }
}
