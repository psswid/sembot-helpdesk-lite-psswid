import { HttpErrorResponse } from '@angular/common/http';
import { environment } from '../../../environments/environment';
import { z, type ZodSchema, type ZodTypeAny } from 'zod';

export function apiUrl(path: string): string {
  if (/^https?:\/\//i.test(path)) return path;
  const base = environment.apiBaseUrl.replace(/\/+$/, '');
  const cleaned = path.replace(/^\/+/, '');
  return `${base}/${cleaned}`;
}

export function authHeaders(token?: string): Record<string, string> {
  return token ? { Authorization: `Bearer ${token}` } : {};
}

export function parseHttpError(err: unknown): { status: number; message: string } {
  if (err instanceof HttpErrorResponse) {
    const status = err.status ?? 0;
    const message =
      (typeof err.error === 'string' && err.error) ||
      (err.error && typeof err.error?.message === 'string' && err.error.message) ||
      err.statusText ||
      'Unexpected error';
    return { status, message };
  }

  if (err && typeof err === 'object' && 'message' in (err as any)) {
    const e = err as { message?: string };
    return { status: 0, message: e.message ?? 'Unexpected error' };
  }

  return { status: 0, message: 'Unexpected error' };
}

export function parseWith<T>(schema: ZodSchema<T>, data: unknown): T {
  const result = schema.safeParse(data);
  if (!result.success) {
    const details = result.error.issues
      .map((i) => `${i.path.join('.')} ${i.message}`.trim())
      .join('; ');
    throw new Error(`Invalid response: ${details || 'schema validation failed'}`);
  }
  return result.data;
}

export const paginatedSchema = <T extends ZodTypeAny>(item: T) =>
  z.object({
    data: z.array(item),
    meta: z.object({
      current_page: z.number(),
      per_page: z.number(),
      total: z.number(),
      last_page: z.number()
    }),
    links: z.object({
      first: z.string().optional(),
      last: z.string().optional(),
      next: z.string().nullable().optional(),
      prev: z.string().nullable().optional()
    })
  });
