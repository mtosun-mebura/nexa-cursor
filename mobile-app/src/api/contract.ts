import { apiRequest } from './client';

export type ContractUser = {
  id: number;
  name: string;
  email?: string | null;
  company_name?: string | null;
  portal_role?: 'contractant' | 'contractouder' | string | null;
  portal_role_label?: string | null;
  is_contractant?: boolean;
};

export type ContractMe = {
  user: ContractUser;
};

export function fetchContractMe(token: string) {
  return apiRequest<ContractMe>('/api/taxi/v1/contract/me', { token });
}
