export interface User {
  id: number;
  name: string;
  email: string;
  created_at: string;
}

export interface Category {
  id: number;
  user_id: number;
  name: string;
  color: string;
  type: 'RECEITA' | 'DESPESA';
  created_at: string;
  updated_at: string;
}

export interface Transaction {
  id: number;
  user_id: number;
  category_id: number;
  installment_id?: number;
  installment_number?: number;
  recurrence_id?: number;
  amount: number;
  description: string;
  date: string;
  type: 'RECEITA' | 'DESPESA';
  created_at: string;
  updated_at: string;
  category?: Category;
}

export interface Installment {
  id: number;
  user_id: number;
  category_id: number;
  total_amount: number;
  installment_count: number;
  first_installment_date: string;
  description: string;
  created_at: string;
  updated_at: string;
  category?: Category;
  transactions?: Transaction[];
}

export interface Recurrence {
  id: number;
  user_id: number;
  category_id: number;
  amount: number;
  description: string;
  type: 'RECEITA' | 'DESPESA';
  frequency: 'daily' | 'weekly' | 'monthly' | 'yearly';
  start_date: string;
  end_date?: string;
  is_business_day: boolean;
  business_day_number?: number;
  created_at: string;
  updated_at: string;
  category?: Category;
}

export interface DashboardIndicators {
  balance: number;
  total_receitas: number;
  total_despesas: number;
  transaction_count: number;
}

export interface ExpenseConcentration {
  category_id: number;
  category_name: string;
  category_color: string;
  total: number;
  percentage: number;
}

export interface DashboardData {
  period: {
    year: number;
    month: number;
  };
  indicators: DashboardIndicators;
  expense_concentration: ExpenseConcentration[];
}

export interface PaginatedResponse<T> {
  data: T[];
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}