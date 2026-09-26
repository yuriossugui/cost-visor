import { useState, useEffect } from 'react';
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
import type { Category } from '@/types';
import { cn } from '@/lib/utils';
import { Plus, Edit, Trash2, Circle } from 'lucide-react';

export function Categories() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState<Category | null>(null);
  const [formData, setFormData] = useState({
    name: '',
    color: '#6366f1',
    type: 'DESPESA' as 'RECEITA' | 'DESPESA',
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  const fetchCategories = async () => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await api.getCategories();
      setCategories(data);
    } catch (err: any) {
      setError(err.message || 'Erro ao carregar categorias');
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchCategories();
  }, []);

  const openCreateDialog = () => {
    setEditingCategory(null);
    setFormData({ name: '', color: '#6366f1', type: 'DESPESA' });
    setFormError(null);
    setIsDialogOpen(true);
  };

  const openEditDialog = (category: Category) => {
    setEditingCategory(category);
    setFormData({
      name: category.name,
      color: category.color,
      type: category.type,
    });
    setFormError(null);
    setIsDialogOpen(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormError(null);
    setIsSubmitting(true);

    try {
      if (editingCategory) {
        await api.updateCategory(editingCategory.id, formData);
      } else {
        await api.createCategory(formData);
      }
      setIsDialogOpen(false);
      fetchCategories();
    } catch (err: any) {
      setFormError(err.message || 'Erro ao salvar categoria');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Tem certeza que deseja excluir esta categoria?')) return;

    try {
      await api.deleteCategory(id);
      fetchCategories();
    } catch (err: any) {
      setError(err.message || 'Erro ao excluir categoria');
    }
  };

  const getTypeBadge = (type: string) => {
    return type === 'RECEITA'
      ? 'bg-green-500/20 text-green-400'
      : 'bg-red-500/20 text-red-400';
  };

  if (isLoading) {
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
        <Button onClick={fetchCategories} className="mt-4">
          Tentar novamente
        </Button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Categorias</h1>
          <p className="text-gray-400">Gerencie suas categorias de receitas e despesas</p>
        </div>
        <Button onClick={openCreateDialog}>
          <Plus className="h-4 w-4 mr-2" />
          Nova Categoria
        </Button>
      </div>

      {categories.length === 0 ? (
        <Card>
          <CardContent className="py-12 text-center">
            <p className="text-gray-500">Nenhuma categoria cadastrada</p>
            <Button onClick={openCreateDialog} className="mt-4">
              <Plus className="h-4 w-4 mr-2" />
              Criar primeira categoria
            </Button>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-4">
          {categories.map((category) => (
            <Card key={category.id}>
              <CardContent className="p-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-4">
                    <div
                      className="w-10 h-10 rounded-full flex items-center justify-center"
                      style={{ backgroundColor: category.color }}
                    >
                      <Circle className="h-5 w-5 text-white" />
                    </div>
                    <div>
                      <p className="font-medium">{category.name}</p>
                      <span className={cn('text-xs px-2 py-0.5 rounded-full', getTypeBadge(category.type))}>
                        {category.type === 'RECEITA' ? 'Receita' : 'Despesa'}
                      </span>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => openEditDialog(category)}
                    >
                      <Edit className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleDelete(category.id)}
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
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{editingCategory ? 'Editar Categoria' : 'Nova Categoria'}</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            {formError && (
              <div className="p-3 text-sm text-red-400 bg-red-900/30 rounded-lg">
                {formError}
              </div>
            )}
            <div className="space-y-2">
              <Label htmlFor="name">Nome</Label>
              <Input
                id="name"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                placeholder="Ex: Alimentação, Salário, Transporte"
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="color">Cor</Label>
              <Input
                id="color"
                type="color"
                value={formData.color}
                onChange={(e) => setFormData({ ...formData, color: e.target.value })}
              />
            </div>
            <div className="space-y-2">
              <Label>Tipo</Label>
              <Select
                value={formData.type}
                onValueChange={(v) => setFormData({ ...formData, type: v as 'RECEITA' | 'DESPESA' })}
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