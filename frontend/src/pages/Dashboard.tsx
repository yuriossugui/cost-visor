import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { api } from '@/services/api';
import type { DashboardData, ExpenseConcentration } from '@/types';

const months = [
  { value: 1, label: 'Janeiro' },
  { value: 2, label: 'Fevereiro' },
  { value: 3, label: 'Março' },
  { value: 4, label: 'Abril' },
  { value: 5, label: 'Maio' },
  { value: 6, label: 'Junho' },
  { value: 7, label: 'Julho' },
  { value: 8, label: 'Agosto' },
  { value: 9, label: 'Setembro' },
  { value: 10, label: 'Outubro' },
  { value: 11, label: 'Novembro' },
  { value: 12, label: 'Dezembro' },
];

const years = Array.from({ length: 10 }, (_, i) => new Date().getFullYear() - 5 + i);

export function Dashboard() {
  const [dashboard, setDashboard] = useState<DashboardData | null>(null);
  const [transactions, setTransactions] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedYear, setSelectedYear] = useState(() => new Date().getFullYear());
  const [selectedMonth, setSelectedMonth] = useState(() => new Date().getMonth() + 1);

  const fetchDashboard = async () => {
    setIsLoading(true);
    setError(null);
    try {
      const [dashboardData, transactionsData] = await Promise.all([
        api.getDashboard(selectedYear, selectedMonth),
        api.getDashboardTransactions(selectedYear, selectedMonth, 10),
      ]);
      setDashboard(dashboardData);
      setTransactions(transactionsData.transactions || []);
    } catch (err: any) {
      setError(err.message || 'Erro ao carregar dashboard');
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchDashboard();
  }, [selectedYear, selectedMonth]);

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }).format(value);
  };

  if (isLoading && !dashboard) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="text-center py-12">
        <p className="text-red-400">{error}</p>
        <Button onClick={fetchDashboard} className="mt-4">
          Tentar novamente
        </Button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold">Dashboard</h1>
          <p className="text-gray-400">Visão geral das suas finanças</p>
        </div>
        <div className="flex items-center gap-4">
          <Select value={String(selectedMonth)} onValueChange={(v) => setSelectedMonth(parseInt(v))}>
            <SelectTrigger className="w-[140px]">
              <SelectValue placeholder="Mês" />
            </SelectTrigger>
            <SelectContent>
              {months.map((m) => (
                <SelectItem key={m.value} value={String(m.value)}>{m.label}</SelectItem>
              ))}
            </SelectContent>
          </Select>
          <Select value={String(selectedYear)} onValueChange={(v) => setSelectedYear(parseInt(v))}>
            <SelectTrigger className="w-[100px]">
              <SelectValue placeholder="Ano" />
            </SelectTrigger>
            <SelectContent>
              {years.map((y) => (
                <SelectItem key={y} value={String(y)}>{y}</SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-gray-400">Saldo</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {formatCurrency(dashboard?.indicators.balance || 0)}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-gray-400">Receitas</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-green-400">
              {formatCurrency(dashboard?.indicators.total_receitas || 0)}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-gray-400">Despesas</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-red-400">
              {formatCurrency(dashboard?.indicators.total_despesas || 0)}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-gray-400">Transações</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {dashboard?.indicators.transaction_count || 0}
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Concentração de Despesas por Categoria</CardTitle>
          </CardHeader>
          <CardContent>
            {dashboard?.expense_concentration?.length && dashboard.expense_concentration.length > 0 ? (
              <div className="space-y-4">
                {dashboard.expense_concentration.map((item: ExpenseConcentration) => (
                  <div key={item.category_id} className="space-y-1">
                    <div className="flex items-center justify-between text-sm">
                      <span className="flex items-center gap-2">
                        <span
                          className="w-3 h-3 rounded-full"
                          style={{ backgroundColor: item.category_color }}
                        ></span>
                        <span>{item.category_name}</span>
                      </span>
                      <span className="font-medium">{item.percentage}%</span>
                    </div>
                    <div className="h-2 bg-gray-800 rounded-full overflow-hidden">
                      <div
                        className="h-full rounded-full transition-all"
                        style={{
                          width: `${item.percentage}%`,
                          backgroundColor: item.category_color,
                        }}
                      ></div>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8 text-gray-500">
                <p>Nenhuma despesa neste período</p>
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Transações Recentes</CardTitle>
          </CardHeader>
          <CardContent>
            {transactions.length > 0 ? (
              <div className="space-y-3">
                {transactions.map((transaction: any) => (
                  <div
                    key={transaction.id}
                    className="flex items-center justify-between p-3 bg-gray-800/50 rounded-lg"
                  >
                    <div className="flex-1 min-w-0">
                      <p className="font-medium truncate">{transaction.description}</p>
                      <p className="text-xs text-gray-400 truncate">
                        {transaction.category?.name} • {new Date(transaction.date).toLocaleDateString('pt-BR')}
                      </p>
                    </div>
                    <div className="flex items-center gap-4 text-right">
                      <span className="font-medium text-green-400">
                        {transaction.type === 'RECEITA' ? '+' : '-'}{formatCurrency(transaction.amount)}
                      </span>
                      <span className="text-xs text-gray-500 capitalize">{transaction.type === 'RECEITA' ? 'Receita' : 'Despesa'}</span>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8 text-gray-500">
                <p>Nenhuma transação neste período</p>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}