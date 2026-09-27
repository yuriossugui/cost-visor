import type { ApiError } from '@/types';

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

class ApiService {
  private token: string | null = null;

  constructor() {
    this.token = localStorage.getItem('auth_token');
  }

  setToken(token: string | null) {
    this.token = token;
    if (token) {
      localStorage.setItem('auth_token', token);
    } else {
      localStorage.removeItem('auth_token');
    }
  }

  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<T> {
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      ...(this.token && { Authorization: `Bearer ${this.token}` }),
      ...options.headers,
    };

    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      ...options,
      headers,
    });

    const data = await response.json();

    if (!response.ok) {
      const error: ApiError = data;
      throw error;
    }

    return data;
  }

  // Auth
  async register(data: { name: string; email: string; password: string; password_confirmation: string }) {
    return this.request<{ message: string; user: any }>('/register', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async login(data: { email: string; password: string }) {
    return this.request<{ access_token: string; token_type: string; expires_in: number }>('/login', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async logout() {
    return this.request<{ message: string }>('/logout', {
      method: 'POST',
    });
  }

  async getProfile() {
    return this.request<{ user: any }>('/me');
  }

  async updateProfile(data: { name?: string; email?: string }) {
    return this.request<{ message: string; user: any }>('/me', {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  // Categories
  async getCategories() {
    return this.request<any[]>('/categories');
  }

  async createCategory(data: { name: string; color: string; type: 'RECEITA' | 'DESPESA' }) {
    return this.request<any>('/categories', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async getCategory(id: number) {
    return this.request<any>(`/categories/${id}`);
  }

  async updateCategory(id: number, data: { name?: string; color?: string; type?: 'RECEITA' | 'DESPESA' }) {
    return this.request<any>(`/categories/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  async deleteCategory(id: number) {
    return this.request<{ message: string }>(`/categories/${id}`, {
      method: 'DELETE',
    });
  }

  // Transactions
  async getTransactions(params?: {
    type?: 'RECEITA' | 'DESPESA';
    category_id?: number;
    description?: string;
    month?: string;
    start_date?: string;
    end_date?: string;
    per_page?: number;
    page?: number;
  }) {
    const searchParams = new URLSearchParams();
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined) searchParams.append(key, String(value));
      });
    }
    return this.request<any>(`/transactions?${searchParams.toString()}`);
  }

  async createTransaction(data: {
    category_id: number;
    amount: number;
    description: string;
    date: string;
    type: 'RECEITA' | 'DESPESA';
  }) {
    return this.request<any>('/transactions', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async getTransaction(id: number) {
    return this.request<any>(`/transactions/${id}`);
  }

  async updateTransaction(id: number, data: {
    category_id?: number;
    amount?: number;
    description?: string;
    date?: string;
    type?: 'RECEITA' | 'DESPESA';
  }) {
    return this.request<any>(`/transactions/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  async deleteTransaction(id: number) {
    return this.request<{ message: string }>(`/transactions/${id}`, {
      method: 'DELETE',
    });
  }

  // Installments
  async getInstallments() {
    return this.request<any[]>('/installments');
  }

  async createInstallment(data: {
    category_id: number;
    total_amount: number;
    installment_count: number;
    first_installment_date: string;
    description: string;
  }) {
    return this.request<any>('/installments', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async getInstallment(id: number) {
    return this.request<any>(`/installments/${id}`);
  }

  async deleteInstallment(id: number) {
    return this.request<{ message: string }>(`/installments/${id}`, {
      method: 'DELETE',
    });
  }

  // Recurrences
  async getRecurrences() {
    return this.request<any[]>('/recurrences');
  }

  async createRecurrence(data: {
    category_id: number;
    amount: number;
    description: string;
    type: 'RECEITA' | 'DESPESA';
    frequency: 'daily' | 'weekly' | 'monthly' | 'yearly';
    start_date: string;
    end_date?: string;
    is_business_day?: boolean;
    business_day_number?: number;
  }) {
    return this.request<any>('/recurrences', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async getRecurrence(id: number) {
    return this.request<any>(`/recurrences/${id}`);
  }

  async updateRecurrence(id: number, data: {
    category_id?: number;
    amount?: number;
    description?: string;
    type?: 'RECEITA' | 'DESPESA';
    frequency?: 'daily' | 'weekly' | 'monthly' | 'yearly';
    start_date?: string;
    end_date?: string;
    is_business_day?: boolean;
    business_day_number?: number;
  }) {
    return this.request<any>(`/recurrences/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  async deleteRecurrence(id: number) {
    return this.request<{ message: string }>(`/recurrences/${id}`, {
      method: 'DELETE',
    });
  }

  async generateRecurrenceTransactions(id: number, until?: string) {
    return this.request<{ message: string; generated_count: number }>(`/recurrences/${id}/generate`, {
      method: 'POST',
      body: JSON.stringify({ until }),
    });
  }

  // Dashboard
  async getDashboard(year?: number, month?: number) {
    const params = new URLSearchParams();
    if (year) params.append('year', String(year));
    if (month) params.append('month', String(month));
    return this.request<any>(`/dashboard?${params.toString()}`);
  }

  async getDashboardTransactions(year?: number, month?: number, perPage = 20) {
    const params = new URLSearchParams();
    if (year) params.append('year', String(year));
    if (month) params.append('month', String(month));
    params.append('per_page', String(perPage));
    return this.request<any>(`/dashboard/transactions?${params.toString()}`);
  }
}

export const api = new ApiService();