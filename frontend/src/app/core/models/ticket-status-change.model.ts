import { z } from 'zod';
import type { User } from './user.model';
import { TicketStatusSchema } from './ticket.model';

export const TicketStatusChangeSchema = z.object({
  id: z.number().optional(),
  ticket_id: z.number().optional(),
  old_status: TicketStatusSchema.nullable(),
  new_status: TicketStatusSchema,
  changed_by_user_id: z.number().optional(),
  changed_at: z.string(),
  changed_by: z.custom<User>().optional(),
});

export type TicketStatusChange = z.infer<typeof TicketStatusChangeSchema>;
