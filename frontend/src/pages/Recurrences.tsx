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
import type { Recurrence, Category } from '@/types';
import { Plus, Trash2, RotateCcw, Calendar } from 'lucide-react';

const frequencies = [
  { value: 'daily', label: 'Diária' },
  { value: 'weekly', label: 'Semanal' },
  { value: 'monthly', label: 'Mensal' },
  { value: 'yearly', label: 'Anual' },
];

export function Recurrences() {
  const [recurrences, setRecurrences] = useState<Recurrence[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingRecurrence, setEditingRecurrence] = useState<Recurrence | null>(null);
  const [formData, setFormData] = useState({
    category_id: '',
    amount: '',
    description: '',
    type: 'DESPESA' as 'RECEITA' | 'DESPESA',
    frequency: 'monthly' as 'daily' | 'weekly' | 'monthly' | 'yearly',
    start_date: new Date().toISOString().split('T')[0],
    end_date: '',
    is_business_day: false,
    business_day_number: '',
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  const fetchCategories = async () => {
    try {
      const data = await api.getCategories();
      setCategories(data);
    } catch (err: any) {
      console.error('Erro ao carregar categorias:', err);
    }
  };

  const fetchRecurrences = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await api.getRecurrences();
      setRecurrences(data);
    } catch (err: any) {
      setError(err.message || 'Erro ao carregar recorrências');
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchCategories();
  }, []);

  useEffect(() => {
    fetchRecurrences();
  }, [fetchRecurrences]);

  const openCreateDialog = () => {
    setEditingRecurrence(null);
    setFormData({
      category_id: '',
      amount: '',
      description: '',
      type: 'DESPESA',
      frequency: 'monthly',
      start_date: new Date().toISOString().split('T')[0],
      end_date: '',
      is_business_day: false,
      business_day_number: '',
    });
    setFormError(null);
    setIsDialogOpen(true);
  };

  const openEditDialog = (recurrence: Recurrence) => {
    setEditingRecurrence(recurrence);
    setFormData({
      category_id: String(recurrence.category_id),
      amount: String(recurrence.amount),
      description: recurrence.description,
      type: recurrence.type,
      frequency: recurrence.frequency,
      start_date: recurrence.start_date.split('T')[0],
      end_date: recurrence.end_date?.split('T')[0] || '',
      is_business_day: recurrence.is_business_day || false,
      business_day_number: recurrence.business_day_number ? String(recurrence.business_day_number) : '',
    });
    setFormError(null);
    setIsDialogOpen(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormError(null);
    setIsSubmitting(true);

    try {
      const data: any = {
        category_id: parseInt(formData.category_id),
        amount: parseFloat(formData.amount),
        description: formData.description,
        type: formData.type,
        frequency: formData.frequency,
        start_date: formData.start_date,
        end_date: formData.end_date || undefined,
        is_business_day: formData.is_business_day,
        business_day_number: formData.business_day_number ? parseInt(formData.business_day_number) : undefined,
      };

      if (editingRecurrence) {
        await api.updateRecurrence(editingRecurrence.id, data);
      } else {
        await api.createRecurrence(data);
      }
      setIsDialogOpen(false);
      fetchRecurrences();
    } catch (err: any) {
      setFormError(err.message || 'Erro ao salvar recorrência');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Tem certeza que deseja excluir esta recorrência?')) return;

    try {
      await api.deleteRecurrence(id);
      fetchRecurrences();
    } catch (err: any) {
      setError(err.message || 'Erro ao excluir recorrência');
    }
  };

  const handleGenerate = async (id: number) => {
    try {
      const result = await api.generateRecurrenceTransactions(id);
      alert(`${result.generated_count} transações geradas com sucesso!`);
      fetchRecurrences();
    } catch (err: any) {
      setError(err.message || 'Erro ao gerar transações');
    }
  };

  const getCompatibleCategories = () => {
    return categories.filter(c => c.type === formData.type);
  };

  const getFrequencyLabel = (freq: string) => {
    const f = frequencies.find(f => f.value === freq);
    return f ? f.label : freq;
  };

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }).format(value);
  };

  if (isLoading && recurrences.length === 0) {
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
        <Button onClick={fetchRecurrences} className="mt-4">
          Tentar novamente
        </Button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Recorrências</h1>
          <p className="text-gray-400">Gerencie suas transações recorrentes</p>
        </div>
        <Button onClick={openCreateDialog}>
          <Plus className="h-4 w-4 mr-2" />
          Nova Recorrência
        </Button>
      </div>

      {recurrences.length === 0 ? (
        <Card>
          <CardContent className="py-12 text-center">
            <p className="text-gray-500">Nenhuma recorrência cadastrada</p>
            <Button onClick={openCreateDialog} className="mt-4">
              <Plus className="h-4 w-4 mr-2" />
              Criar primeira recorrência
            </Button>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-4">
          {recurrences.map((recurrence) => (
            <Card key={recurrence.id}>
              <CardContent className="p-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center">
                      <RotateCcw className="h-5 w-5 text-blue-400" />
                    </div>
                    <div>
                      <p className="font-medium">{recurrence.description}</p>
                      <p className="text-sm text-gray-400">
                        {formatCurrency(recurrence.amount)} • {getFrequencyLabel(recurrence.frequency)}
                        • Início: {new Date(recurrence.start_date).toLocaleDateString('pt-BR')}
                        {recurrence.end_date && ` • Fim: ${new Date(recurrence.end_date).toLocaleDateString('pt-BR')}`}
                        {recurrence.is_business_day && ` • Dia útil ${recurrence.business_day_number}`}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => openEditDialog(recurrence)}
                      title="Editar"
                    >
                      <Calendar className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleGenerate(recurrence.id)}
                      title="Gerar transações"
                    >
                      <RotateCcw className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleDelete(recurrence.id)}
                      className="text-red-400 hover:text-red-300"
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent className="max-w-md max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{editingRecurrence ? 'Editar Recorrência' : 'Nova Recorrência'}</DialogTitle>
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
                placeholder="Descrição da recorrência"
                required
              />
            </div>
            <div className="space-y-2">
              <Label>Frequência</Label>
              <Select
                value={formData.frequency}
                onValueChange={(v) => setFormData({ ...formData, frequency: v as any })}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Selecione a frequência" />
                </SelectTrigger>
                <SelectContent>
                  {frequencies.map((f) => (
                    <SelectItem key={f.value} value={f.value}>{f.label}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="start_date">Data Inicial</Label>
              <Input
                id="start_date"
                type="date"
                value={formData.start_date}
                onChange={(e) => setFormData({ ...formData, start_date: e.target.value })}
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="end_date">Data Final (opcional)</Label>
              <Input
                id="end_date"
                type="date"
                value={formData.end_date}
                onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
              />
            </div>
            <div className="space-y-2">
              <Label className="flex items-center gap-2">
                <Input
                  type="checkbox"
                  checked={formData.is_business_day}
                  onChange={(e) => setFormData({ ...formData, is_business_day: e.target.checked })}
                />
                Dia útil
              </Label>
            </div>
            {formData.is_business_day && (
              <div className="space-y-2">
                <Label htmlFor="business_day_number">N-ésimo dia útil (1-5)</Label>
                <Input
                  id="business_day_number"
                  type="number"
                  min="1"
                  max="5"
                  value={formData.business_day_number}
                  onChange={(e) => setFormData({ ...formData, business_day_number: e.target.value })}
                  placeholder="Ex: 1 (primeiro dia útil)"
                  required
                />
              </div>
            )}
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