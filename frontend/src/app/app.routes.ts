import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';
import { rootRedirectGuard } from './core/guards/root-redirect.guard';

export const routes: Routes = [
	{
		path: '',
		pathMatch: 'full',
		canActivate: [rootRedirectGuard],
		// Guard will return a UrlTree to /login or /tickets
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
			import('./features/tickets/tickets-shell.component').then((m) => m.TicketsShellComponent)
	},
	{
		path: 'tickets/new',
		canActivate: [authGuard],
		loadComponent: () =>
			import('./features/tickets/tickets-shell.component').then((m) => m.TicketsShellComponent)
	},
	{
		path: 'tickets/:id',
		canActivate: [authGuard],
		loadComponent: () =>
			import('./features/tickets/tickets-shell.component').then((m) => m.TicketsShellComponent)
	},
	{
		path: 'tickets/:id/edit',
		canActivate: [authGuard],
		loadComponent: () =>
			import('./features/tickets/tickets-shell.component').then((m) => m.TicketsShellComponent)
	},
	{ path: '**', redirectTo: '' }
];
