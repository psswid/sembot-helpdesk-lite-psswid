export type UserRole = 'admin' | 'agent' | 'reporter';

export interface User {
  id: number;
  name: string;
  email: string;
  role: UserRole;
}
