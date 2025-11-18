import { z } from 'zod';
import type { User } from './user.model';

export const TicketPrioritySchema = z.enum(['low', 'medium', 'high']);
export type TicketPriority = z.infer<typeof TicketPrioritySchema>;

export const TicketStatusSchema = z.enum(['open', 'in_progress', 'resolved', 'closed']);
export type TicketStatus = z.infer<typeof TicketStatusSchema>;

export const TicketSchema = z.object({
  id: z.number(),
  title: z.string(),
  description: z.string().default(''),
  priority: TicketPrioritySchema,
  status: TicketStatusSchema,
  assignee: z.custom<User | null>().optional(),
  reporter: z.custom<User>().optional(),
  tags: z.array(z.string()).default([]),
  location: z.string().max(120).nullable().optional(),
  external: z
    .object({
      weather: z.unknown().optional(),
    })
    .partial()
    .optional(),
  created_at: z.string(),
  updated_at: z.string(),
});

export type Ticket = z.infer<typeof TicketSchema>;

export const PaginatedMetaSchema = z.object({
  current_page: z.number(),
  per_page: z.number(),
  total: z.number(),
  last_page: z.number(),
});

export const PaginatedLinksSchema = z.object({
  first: z.string().optional(),
  last: z.string().optional(),
  next: z.string().nullable().optional(),
  prev: z.string().nullable().optional(),
});

export const PaginatedTicketSchema = z.object({
  data: z.array(TicketSchema),
  meta: PaginatedMetaSchema,
  links: PaginatedLinksSchema,
});

export type Paginated<T> = {
  data: T[];
  meta: { current_page: number; per_page: number; total: number; last_page: number };
  links: { first?: string; last?: string; next?: string | null; prev?: string | null };
};
