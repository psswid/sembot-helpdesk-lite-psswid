import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';
// Root redirect handled via a tiny component instead of guards

export const routes: Routes = [
	// Dynamic default handled in component to avoid canMatch + redirectTo conflict
	{
		path: '',
		pathMatch: 'full',
		loadComponent: () => import('./features/redirect/landing-redirect.component').then(m => m.LandingRedirectComponent)
	},
	{
		path: 'login',
		loadComponent: () =>
			import('./features/auth/login/login.component').then((m) => m.LoginComponent)
	},
	{
		path: 'tickets',
		canActivate: [authGuard],
		loadComponent: () =>
			import('./features/tickets/ticket-list/ticket-list.component').then((m) => m.TicketListComponent)
	},
	{
		path: 'tickets/new',
		canActivate: [authGuard],
		loadComponent: () =>
			import('./features/tickets/ticket-form/ticket-form.component').then((m) => m.TicketFormComponent)
	},
	{
		path: 'tickets/:id',
		canActivate: [authGuard],
		loadComponent: () =>
			import('./features/tickets/ticket-detail/ticket-detail.component').then((m) => m.TicketDetailComponent)
	},
	{
		path: 'tickets/:id/edit',
		canActivate: [authGuard],
		loadComponent: () =>
			import('./features/tickets/ticket-form/ticket-form.component').then((m) => m.TicketFormComponent)
	},
	{ path: '**', redirectTo: '' }
];
