import { Injectable, computed, inject, signal } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { toObservable } from '@angular/core/rxjs-interop';
import { map, switchMap, catchError, of, throwError } from 'rxjs';
import { apiUrl, authHeaders, parseHttpError } from '../api/api.util';
import type { User } from '../models/user.model';

const TOKEN_KEY = 'auth.token' as const;

interface LoginPayload {
  email: string;
  password: string;
}

interface LoginResponse {
  token: string;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);

  readonly token = signal<string | null>(localStorage.getItem(TOKEN_KEY));
  readonly currentUser = signal<User | null>(null);
  readonly isAuthenticated = computed(() => !!this.token());

  // Optional legacy bridge: prefer signals, but expose as Observable if needed
  readonly user$ = toObservable(this.currentUser);

  init(): Promise<void> {
    const token = this.token();
    if (!token) return Promise.resolve();
    return this.fetchMe().toPromise().then(() => void 0).catch(() => {
      this.clearSession();
    });
  }

  login(payload: LoginPayload) {
    return this.http.post<LoginResponse>(apiUrl('/api/login'), payload).pipe(
      switchMap((res) => {
        if (!res?.token) {
          return throwError(() => new Error('Invalid login response'));
        }
        this.setToken(res.token);
        return this.fetchMe();
      }),
      catchError((err) => throwError(() => parseHttpError(err)))
    );
  }

  register(payload: LoginPayload) {
    return this.http.post<LoginResponse>(apiUrl('/api/register'), payload).pipe(
      switchMap((res) => {
        if (!res?.token) {
          return throwError(() => new Error('Invalid register response'));
        }
        this.setToken(res.token);
        return this.fetchMe();
      }),
      catchError((err) => throwError(() => parseHttpError(err)))
    );
  }

  logout() {
    const token = this.token();
    const headers = new HttpHeaders(authHeaders(token ?? undefined));
    // Best-effort server logout; clear session locally regardless of outcome
    return this.http.post<void>(apiUrl('/api/logout'), {}, { headers }).pipe(
      catchError(() => of(void 0)),
      map(() => {
        this.clearSession();
      })
    );
  }

  private fetchMe() {
    const token = this.token();
    const headers = new HttpHeaders(authHeaders(token ?? undefined));
    return this.http.get<User>(apiUrl('/api/me'), { headers }).pipe(
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

  private setToken(token: string | null) {
    if (token) {
      localStorage.setItem(TOKEN_KEY, token);
    } else {
      localStorage.removeItem(TOKEN_KEY);
    }
    this.token.set(token);
  }

  private clearSession() {
    this.setToken(null);
    this.currentUser.set(null);
  }
}
