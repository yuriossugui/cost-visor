import { createContext, useContext, useState, useEffect, type ReactNode } from 'react';
import type { User } from '@/types';
import { api } from '@/services/api';

interface AuthContextType {
  user: User | null;
  token: string | null;
  login: (email: string, password: string) => Promise<void>;
  register: (name: string, email: string, password: string, password_confirmation: string) => Promise<void>;
  logout: () => Promise<void>;
  updateProfile: (data: { name?: string; email?: string }) => Promise<void>;
  isLoading: boolean;
  error: string | null;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const storedToken = localStorage.getItem('auth_token');
    if (storedToken) {
      api.setToken(storedToken);
      setToken(storedToken);
      loadUser();
    } else {
      setIsLoading(false);
    }
  }, []);

  const loadUser = async () => {
    try {
      const response = await api.getProfile();
      setUser(response.user);
    } catch {
      api.setToken(null);
      setToken(null);
      setUser(null);
    } finally {
      setIsLoading(false);
    }
  };

  const login = async (email: string, password: string) => {
    setError(null);
    try {
      const response = await api.login({ email, password });
      api.setToken(response.access_token);
      setToken(response.access_token);
      await loadUser();
    } catch (err: any) {
      setError(err.message || 'Erro ao fazer login');
      throw err;
    }
  };

  const register = async (name: string, email: string, password: string, password_confirmation: string) => {
    setError(null);
    try {
      await api.register({ name, email, password, password_confirmation });
      await login(email, password);
    } catch (err: any) {
      setError(err.message || 'Erro ao cadastrar');
      throw err;
    }
  };

  const logout = async () => {
    try {
      await api.logout();
    } catch {
      // Ignore logout errors
    } finally {
      api.setToken(null);
      setToken(null);
      setUser(null);
    }
  };

  const updateProfile = async (data: { name?: string; email?: string }) => {
    setError(null);
    try {
      const response = await api.updateProfile(data);
      setUser(response.user);
    } catch (err: any) {
      setError(err.message || 'Erro ao atualizar perfil');
      throw err;
    }
  };

  return (
    <AuthContext.Provider value={{
      user,
      token,
      login,
      register,
      logout,
      updateProfile,
      isLoading,
      error,
    }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}