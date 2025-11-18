import { z } from 'zod';
import { TicketPrioritySchema } from './ticket.model';

export const TriageSuggestionSchema = z.object({
  priority: TicketPrioritySchema,
  tags: z.array(z.string()).default([]),
  assignee_hint: z.string().nullable().optional(),
  reasoning: z.string().nullable().optional(),
  confidence: z.number().nullable().optional(),
  driver: z.string(),
});

export type TriageSuggestion = z.infer<typeof TriageSuggestionSchema>;
