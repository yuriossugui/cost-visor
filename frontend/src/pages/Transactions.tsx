import { useState, useEffect, useCallback } from 'react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import { api } from '@/services/api';
import type { Transaction, Category } from '@/types';
import { cn } from '@/lib/utils';
import { Plus, Edit, Trash2 } from 'lucide-react';

export function Transactions() {
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingTransaction, setEditingTransaction] = useState<Transaction | null>(null);
  const [formData, setFormData] = useState({
    category_id: '',
    amount: '',
    description: '',
    date: new Date().toISOString().split('T')[0],
    type: 'DESPESA' as 'RECEITA' | 'DESPESA',
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  // Filters
  const [filters, setFilters] = useState({
    type: '' as 'RECEITA' | 'DESPESA' | '',
    category_id: undefined as number | undefined,
    description: '',
    month: new Date().toISOString().slice(0, 7),
    start_date: '',
    end_date: '',
    page: 1,
    per_page: 10,
  });

  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    total: 0,
  });

  const fetchCategories = async () => {
    try {
      const data = await api.getCategories();
      setCategories(data);
    } catch (err: any) {
      console.error('Erro ao carregar categorias:', err);
    }
  };

  const fetchTransactions = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await api.getTransactions({
        type: filters.type || undefined,
        category_id: filters.category_id || undefined,
        description: filters.description || undefined,
        month: filters.month || undefined,
        start_date: filters.start_date || undefined,
        end_date: filters.end_date || undefined,
        page: filters.page,
        per_page: filters.per_page,
      });
      setTransactions(data.data || []);
      setPagination({
        current_page: data.meta?.current_page || data.pagination?.current_page || 1,
        last_page: data.meta?.last_page || data.pagination?.last_page || 1,
        total: data.meta?.total || data.pagination?.total || 0,
      });
    } catch (err: any) {
      setError(err.message || 'Erro ao carregar transações');
    } finally {
      setIsLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    fetchCategories();
  }, []);

  useEffect(() => {
    fetchTransactions();
  }, [fetchTransactions]);

  const handleFilterChange = (key: string, value: any) => {
    setFilters(prev => ({ 
      ...prev, 
      [key]: key === 'page' || key === 'category_id' ? Number(value) || undefined : value, 
      page: 1 
    }));
  };

  const handlePageChange = (page: number) => {
    if (page >= 1 && page <= pagination.last_page) {
      setFilters(prev => ({ ...prev, page }));
    }
  };

  const openCreateDialog = () => {
    setEditingTransaction(null);
    setFormData({
      category_id: '',
      amount: '',
      description: '',
      date: new Date().toISOString().split('T')[0],
      type: 'DESPESA',
    });
    setFormError(null);
    setIsDialogOpen(true);
  };

  const openEditDialog = (transaction: Transaction) => {
    setEditingTransaction(transaction);
    setFormData({
      category_id: String(transaction.category_id),
      amount: String(transaction.amount),
      description: transaction.description,
      date: transaction.date.split('T')[0],
      type: transaction.type,
    });
    setFormError(null);
    setIsDialogOpen(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormError(null);
    setIsSubmitting(true);

    try {
      const data = {
        category_id: parseInt(formData.category_id),
        amount: parseFloat(formData.amount),
        description: formData.description,
        date: formData.date,
        type: formData.type,
      };

      if (editingTransaction) {
        await api.updateTransaction(editingTransaction.id, data);
      } else {
        await api.createTransaction(data);
      }
      setIsDialogOpen(false);
      fetchTransactions();
    } catch (err: any) {
      setFormError(err.message || 'Erro ao salvar transação');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Tem certeza que deseja excluir esta transação?')) return;

    try {
      await api.deleteTransaction(id);
      fetchTransactions();
    } catch (err: any) {
      setError(err.message || 'Erro ao excluir transação');
    }
  };

  const getCompatibleCategories = () => {
    return categories.filter(c => c.type === formData.type);
  };

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }).format(value);
  };

  if (isLoading && transactions.length === 0) {
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
        <Button onClick={fetchTransactions} className="mt-4">
          Tentar novamente
        </Button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold">Transações</h1>
          <p className="text-gray-400">Gerencie suas receitas e despesas</p>
        </div>
        <Button onClick={openCreateDialog}>
          <Plus className="h-4 w-4 mr-2" />
          Nova Transação
        </Button>
      </div>

      {/* Filtros */}
      <Card>
        <CardHeader>
          <CardTitle>Filtros</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-6">
            <div className="space-y-2">
              <Label>Mês</Label>
              <Input
                type="month"
                value={filters.month}
                onChange={(e) => handleFilterChange('month', e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label>Tipo</Label>
              <Select value={filters.type} onValueChange={(v) => handleFilterChange('type', v)}>
                <SelectTrigger>
                  <SelectValue placeholder="Todos" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">Todos</SelectItem>
                  <SelectItem value="RECEITA">Receita</SelectItem>
                  <SelectItem value="DESPESA">Despesa</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Categoria</Label>
              <Select value={filters.category_id ? String(filters.category_id) : ''} onValueChange={(v) => handleFilterChange('category_id', v)}>
                <SelectTrigger>
                  <SelectValue placeholder="Todas" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">Todas</SelectItem>
                  {categories.map(c => (
                    <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Descrição</Label>
              <Input
                placeholder="Buscar por descrição"
                value={filters.description}
                onChange={(e) => handleFilterChange('description', e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label>Data Inicial</Label>
              <Input
                type="date"
                value={filters.start_date}
                onChange={(e) => handleFilterChange('start_date', e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label>Data Final</Label>
              <Input
                type="date"
                value={filters.end_date}
                onChange={(e) => handleFilterChange('end_date', e.target.value)}
              />
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Tabela */}
      <Card>
        <CardContent>
          {isLoading && transactions.length === 0 ? (
            <div className="flex items-center justify-center h-64">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            </div>
          ) : transactions.length === 0 ? (
            <div className="text-center py-12">
              <p className="text-gray-500">Nenhuma transação encontrada</p>
              <Button onClick={openCreateDialog} className="mt-4">
                <Plus className="h-4 w-4 mr-2" />
                Criar primeira transação
              </Button>
            </div>
          ) : (
            <>
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead className="w-[40px]"></TableHead>
                      <TableHead>Data</TableHead>
                      <TableHead>Descrição</TableHead>
                      <TableHead>Categoria</TableHead>
                      <TableHead className="text-right">Valor</TableHead>
                      <TableHead>Tipo</TableHead>
                      <TableHead className="w-[40px]"></TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {transactions.map((transaction) => (
                      <TableRow key={transaction.id}>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => openEditDialog(transaction)}
                            >
                              <Edit className="h-4 w-4" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => handleDelete(transaction.id)}
                              className="text-red-400 hover:text-red-300"
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </div>
                        </TableCell>
                        <TableCell>{new Date(transaction.date).toLocaleDateString('pt-BR')}</TableCell>
                        <TableCell className="max-w-[200px] truncate">{transaction.description}</TableCell>
                        <TableCell>{transaction.category?.name}</TableCell>
                        <TableCell className="text-right font-medium">
                          {transaction.type === 'RECEITA' ? '+' : '-'}{formatCurrency(transaction.amount)}
                        </TableCell>
                        <TableCell>
                          <span className={cn(
                            'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                            transaction.type === 'RECEITA' 
                              ? 'bg-green-500/20 text-green-400' 
                              : 'bg-red-500/20 text-red-400'
                          )}>
                            {transaction.type === 'RECEITA' ? 'Receita' : 'Despesa'}
                          </span>
                        </TableCell>
                        <TableCell></TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
              {pagination.last_page > 1 && (
                <Pagination className="mt-4">
                  <PaginationContent>
                    <PaginationPrevious
                      href={`?page=${pagination.current_page - 1}`}
                      onClick={(e) => {
                        e.preventDefault();
                        handlePageChange(pagination.current_page - 1);
                      }}
                      disabled={pagination.current_page === 1}
                    />
                    <PaginationItem>
                      <PaginationLink
                        isActive={pagination.current_page === 1}
                        onClick={(e) => {
                          e.preventDefault();
                          handlePageChange(1);
                        }}
                      >
                        1
                      </PaginationLink>
                    </PaginationItem>
                    {pagination.current_page > 2 && (
                      <PaginationItem>
                        <PaginationLink className="text-muted-foreground">...</PaginationLink>
                      </PaginationItem>
                    )}
                    {Array.from({ length: Math.min(5, pagination.last_page) }, (_, i) => {
                      const page = Math.max(2, Math.min(pagination.current_page - 1, pagination.last_page - 4)) + i;
                      return page <= pagination.last_page - 1 ? (
                        <PaginationItem key={page}>
                          <PaginationLink
                            isActive={pagination.current_page === page}
                            onClick={(e) => {
                              e.preventDefault();
                              handlePageChange(page);
                            }}
                          >
                            {page}
                          </PaginationLink>
                        </PaginationItem>
                      ) : null;
                    })}
                    {pagination.current_page < pagination.last_page - 1 && (
                      <PaginationItem>
                        <PaginationLink className="text-muted-foreground">...</PaginationLink>
                      </PaginationItem>
                    )}
                    {pagination.last_page > 1 && (
                      <PaginationItem>
                        <PaginationLink
                          isActive={pagination.current_page === pagination.last_page}
                          onClick={(e) => {
                            e.preventDefault();
                            handlePageChange(pagination.last_page);
                          }}
                        >
                          {pagination.last_page}
                        </PaginationLink>
                      </PaginationItem>
                    )}
                    <PaginationNext
                      href={`?page=${pagination.current_page + 1}`}
                      onClick={(e) => {
                        e.preventDefault();
                        handlePageChange(pagination.current_page + 1);
                      }}
                      disabled={pagination.current_page === pagination.last_page}
                    />
                  </PaginationContent>
                </Pagination>
              )}
            </>
          )}
        </CardContent>
      </Card>

      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>{editingTransaction ? 'Editar Transação' : 'Nova Transação'}</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            {formError && (
              <div className="p-3 text-sm text-red-400 bg-red-900/30 rounded-lg">
                {formError}
              </div>
            )}
            <div className="space-y-2">
              <Label>Tipo</Label>
              <Select
                value={formData.type}
                onValueChange={(v) => setFormData({ ...formData, type: v as 'RECEITA' | 'DESPESA', category_id: '' })}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Selecione o tipo" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="RECEITA">Receita</SelectItem>
                  <SelectItem value="DESPESA">Despesa</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="category_id">Categoria</Label>
              <Select
                value={formData.category_id}
                onValueChange={(v) => setFormData({ ...formData, category_id: v })}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Selecione a categoria" />
                </SelectTrigger>
                <SelectContent>
                  {getCompatibleCategories().map((c) => (
                    <SelectItem key={c.id} value={String(c.id)}>
                      <span className="flex items-center gap-2">
                        <span
                          className="w-3 h-3 rounded-full"
                          style={{ backgroundColor: c.color }}
                        ></span>
                        {c.name}
                      </span>
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="amount">Valor</Label>
              <Input
                id="amount"
                type="number"
                step="0.01"
                min="0.01"
                value={formData.amount}
                onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
                placeholder="0,00"
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="description">Descrição</Label>
              <Input
                id="description"
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                placeholder="Descrição da transação"
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="date">Data</Label>
              <Input
                id="date"
                type="date"
                value={formData.date}
                onChange={(e) => setFormData({ ...formData, date: e.target.value })}
                required
              />
            </div>
            {formError && (
              <div className="p-3 text-sm text-red-400 bg-red-900/30 rounded-lg">
                {formError}
              </div>
            )}
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>
                Cancelar
              </Button>
              <Button type="submit" disabled={isSubmitting}>
                {isSubmitting ? 'Salvando...' : 'Salvar'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}

export function Table(
  { className, ...props }: React.HTMLAttributes<HTMLTableElement>
) {
  return (
    <div className="relative w-full overflow-auto">
      <table
        className="w-full caption-bottom text-sm"
        {...props}
      />
    </div>
  );
}

export function TableHeader(
  { className, ...props }: React.HTMLAttributes<HTMLTableSectionElement>
) {
  return (
    <thead
      className={cn("[&_tr]:border-b", className)}
      {...props}
    />
  );
}

export function TableBody(
  { className, ...props }: React.HTMLAttributes<HTMLTableSectionElement>
) {
  return (
    <tbody
      className={cn("[&_tr:last-child]:border-0", className)}
      {...props}
    />
  );
}

export function TableRow(
  { className, ...props }: React.HTMLAttributes<HTMLTableRowElement>
) {
  return (
    <tr
      className={cn(
        "border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted",
        className
      )}
      {...props}
    />
  );
}

export function TableHead(
  { className, ...props }: React.ThHTMLAttributes<HTMLTableCellElement>
) {
  return (
    <th
      className={cn(
        "h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0",
        className
      )}
      {...props}
    />
  );
}

export function TableCell(
  { className, ...props }: React.TdHTMLAttributes<HTMLTableCellElement>
) {
  return (
    <td
      className={cn("p-4 align-middle [&:has([role=checkbox])]:pr-0", className)}
      {...props}
    />
  );
}

export function TableCaption(
  { className, ...props }: React.HTMLAttributes<HTMLTableCaptionElement>
) {
  return (
    <caption
      className={cn("mt-4 text-sm text-muted-foreground", className)}
      {...props}
    />
  );
}