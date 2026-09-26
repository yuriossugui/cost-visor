import { useState, useEffect, useCallback } from 'react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
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
import { api } from '@/services/api';
import type { Installment, Category } from '@/types';
import { Plus, Trash2, CreditCard } from 'lucide-react';

export function Installments() {
  const [installments, setInstallments] = useState<Installment[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingInstallment, setEditingInstallment] = useState<Installment | null>(null);
  const [formData, setFormData] = useState({
    category_id: '',
    total_amount: '',
    installment_count: '',
    first_installment_date: new Date().toISOString().split('T')[0],
    description: '',
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  const fetchCategories = async () => {
    try {
      const data = await api.getCategories();
      setCategories(data.filter(c => c.type === 'DESPESA'));
    } catch (err: any) {
      console.error('Erro ao carregar categorias:', err);
    }
  };

  const fetchInstallments = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await api.getInstallments();
      setInstallments(data);
    } catch (err: any) {
      setError(err.message || 'Erro ao carregar parcelamentos');
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchCategories();
  }, []);

  useEffect(() => {
    fetchInstallments();
  }, [fetchInstallments]);

  const openCreateDialog = () => {
    setEditingInstallment(null);
    setFormData({
      category_id: '',
      total_amount: '',
      installment_count: '',
      first_installment_date: new Date().toISOString().split('T')[0],
      description: '',
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
        total_amount: parseFloat(formData.total_amount),
        installment_count: parseInt(formData.installment_count),
        first_installment_date: formData.first_installment_date,
        description: formData.description,
      };

      await api.createInstallment(data);
      setIsDialogOpen(false);
      fetchInstallments();
    } catch (err: any) {
      setFormError(err.message || 'Erro ao salvar parcelamento');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Tem certeza que deseja excluir este parcelamento? Isso também excluirá todas as parcelas geradas.')) return;

    try {
      await api.deleteInstallment(id);
      fetchInstallments();
    } catch (err: any) {
      setError(err.message || 'Erro ao excluir parcelamento');
    }
  };

  const handleGenerate = async (id: number) => {
    try {
      const result = await api.generateInstallmentTransactions(id);
      alert(`${result.generated_count} transações geradas com sucesso!`);
      fetchInstallments();
    } catch (err: any) {
      setError(err.message || 'Erro ao gerar parcelas');
    }
  };

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }).format(value);
  };

  if (isLoading && installments.length === 0) {
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
        <Button onClick={fetchInstallments} className="mt-4">
          Tentar novamente
        </Button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Parcelamentos</h1>
          <p className="text-gray-400">Gerencie suas despesas parceladas</p>
        </div>
        <Button onClick={openCreateDialog}>
          <Plus className="h-4 w-4 mr-2" />
          Novo Parcelamento
        </Button>
      </div>

      {installments.length === 0 ? (
        <Card>
          <CardContent className="py-12 text-center">
            <p className="text-gray-500">Nenhum parcelamento cadastrado</p>
            <Button onClick={openCreateDialog} className="mt-4">
              <Plus className="h-4 w-4 mr-2" />
              Criar primeiro parcelamento
            </Button>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-4">
          {installments.map((installment) => (
            <Card key={installment.id}>
              <CardContent className="p-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 rounded-full bg-purple-500/20 flex items-center justify-center">
                      <CreditCard className="h-5 w-5 text-purple-400" />
                    </div>
                    <div>
                      <p className="font-medium">{installment.description}</p>
                      <p className="text-sm text-gray-400">
                        {installment.installment_count} parcelas de {formatCurrency(Number(installment.total_amount) / installment.installment_count)} 
                        • Total: {formatCurrency(installment.total_amount)}
                        • Início: {new Date(installment.first_installment_date).toLocaleDateString('pt-BR')}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleGenerate(installment.id)}
                      title="Gerar transações"
                    >
                      <CreditCard className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleDelete(installment.id)}
                      className="text-red-400 hover:text-red-300"
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
                
                {installment.transactions && installment.transactions.length > 0 && (
                  <div className="mt-4 pt-4 border-t border-gray-800">
                    <p className="text-sm font-medium text-gray-400 mb-2">Parcelas geradas ({installment.transactions.length})</p>
                    <div className="space-y-2 max-h-60 overflow-y-auto">
                      {installment.transactions.map((transaction: any) => (
                        <div
                          key={transaction.id}
                          className="flex items-center justify-between p-2 bg-gray-800/50 rounded-lg text-sm"
                        >
                          <div className="flex items-center gap-3">
                            <span className="text-gray-500">Parcela {transaction.installment_number}</span>
                            <span>{new Date(transaction.date).toLocaleDateString('pt-BR')}</span>
                          </div>
                          <span className="font-medium text-red-400">
                            -{formatCurrency(transaction.amount)}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>{editingInstallment ? 'Editar Parcelamento' : 'Novo Parcelamento'}</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            {formError && (
              <div className="p-3 text-sm text-red-400 bg-red-900/30 rounded-lg">
                {formError}
              </div>
            )}
            <div className="space-y-2">
              <Label>Categoria (Despesa)</Label>
              <Select
                value={formData.category_id}
                onValueChange={(v) => setFormData({ ...formData, category_id: v })}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Selecione a categoria" />
                </SelectTrigger>
                <SelectContent>
                  {categories.map((c) => (
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
              <Label htmlFor="total_amount">Valor Total</Label>
              <Input
                id="total_amount"
                type="number"
                step="0.01"
                min="0.01"
                value={formData.total_amount}
                onChange={(e) => setFormData({ ...formData, total_amount: e.target.value })}
                placeholder="0,00"
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="installment_count">Número de Parcelas</Label>
              <Input
                id="installment_count"
                type="number"
                min="2"
                max="60"
                value={formData.installment_count}
                onChange={(e) => setFormData({ ...formData, installment_count: e.target.value })}
                placeholder="Ex: 10"
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="first_installment_date">Data da Primeira Parcela</Label>
              <Input
                id="first_installment_date"
                type="date"
                value={formData.first_installment_date}
                onChange={(e) => setFormData({ ...formData, first_installment_date: e.target.value })}
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="description">Descrição</Label>
              <Input
                id="description"
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                placeholder="Descrição do parcelamento"
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