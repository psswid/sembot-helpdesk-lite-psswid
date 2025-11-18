import { Injectable, computed, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { toObservable } from '@angular/core/rxjs-interop';
import { map, switchMap, catchError, of, throwError, tap } from 'rxjs';
import { apiUrl, parseHttpError } from '../api/api.util';
import type { User } from '../models/user.model';

const TOKEN_KEY = 'auth.token' as const;

interface LoginPayload {
  email: string;
  password: string;
}

interface LoginResponse { token: string }

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);

  readonly currentUser = signal<User | null>(null);
  // Cookie-based auth: authenticated when we have a currentUser
  readonly isAuthenticated = computed(() => !!this.currentUser());

  // Optional legacy bridge: prefer signals, but expose as Observable if needed
  readonly user$ = toObservable(this.currentUser);

  private readonly tokenSig = signal<string | null>(this.getStoredToken());

  // Exposed for interceptor
  token(): string | null {
    return this.tokenSig();
  }

  init(): Promise<void> {
    // If a token exists, try to fetch current user
    if (this.token()) {
      return this.fetchMe()
        .pipe(
          catchError(() => of(void 0)),
          map(() => void 0)
        )
        .toPromise();
    }
    return Promise.resolve();
  }

  login(payload: LoginPayload) {
    // Sanctum Personal Access Token flow: POST /api/login -> { token }
    return this.http.post<LoginResponse>(apiUrl('/api/login'), payload).pipe(
      tap((res) => this.setToken(res.token)),
      switchMap(() => this.fetchMe()),
      catchError((err) => throwError(() => parseHttpError(err)))
    );
  }

  register(payload: LoginPayload) {
    // If API returns a token on register, store it; otherwise fallback to login
    return this.http.post<LoginResponse | void>(apiUrl('/api/register'), payload).pipe(
      switchMap((res) => {
        const token = (res as LoginResponse | undefined)?.token;
        if (token) {
          this.setToken(token);
          return this.fetchMe();
        }
        // Fallback: perform login to get token
        return this.login(payload);
      }),
      catchError((err) => throwError(() => parseHttpError(err)))
    );
  }

  logout() {
    // Best-effort server logout (token revoke); clear locally regardless
    return this.http.post<void>(apiUrl('/api/logout'), {}).pipe(
      catchError(() => of(void 0)),
      map(() => {
        this.clearSession();
      })
    );
  }

  private fetchMe() {
    return this.http.get<User>(apiUrl('/api/me')).pipe(
      map((user) => {
        this.currentUser.set(user);
        return user;
      }),
      catchError((err) => {
        this.clearSession();
        return throwError(() => parseHttpError(err));
      })
    );
  }

  clearSession() {
    this.currentUser.set(null);
    this.setToken(null);
  }

  private getStoredToken(): string | null {
    try {
      return localStorage.getItem(TOKEN_KEY);
    } catch {
      return null;
    }
  }

  private setToken(token: string | null) {
    this.tokenSig.set(token);
    try {
      if (token) {
        localStorage.setItem(TOKEN_KEY, token);
      } else {
        localStorage.removeItem(TOKEN_KEY);
      }
    } catch {
      // ignore storage errors
    }
  }
}
