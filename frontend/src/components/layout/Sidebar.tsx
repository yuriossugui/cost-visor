import { NavLink } from 'react-router-dom';
import { cn } from '@/lib/utils';
import { useAuth } from '@/context/AuthContext';

const navigation = [
  { name: 'Dashboard', href: '/dashboard', icon: 'LayoutDashboard' },
  { name: 'Transações', href: '/dashboard/transactions', icon: 'List' },
  { name: 'Categorias', href: '/dashboard/categories', icon: 'Tags' },
  { name: 'Parcelamentos', href: '/dashboard/installments', icon: 'CreditCard' },
  { name: 'Recorrências', href: '/dashboard/recurrences', icon: 'RotateCcw' },
];

export function Sidebar() {

  const { user } = useAuth();

  return (
    <aside className="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 border-r border-gray-800 hidden lg:flex flex-col">
      <div className="flex h-16 items-center justify-between px-6 border-b border-gray-800">
        <h1 className="text-xl font-bold text-white">Cost Visor</h1>
      </div>
      <nav className="flex-1 space-y-1 p-4" aria-label="Main navigation">
        {navigation.map((item) => (
          <NavLink
            key={item.name}
            to={item.href}
            className={({ isActive }) =>
              cn(
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                isActive
                  ? 'bg-gray-800 text-white'
                  : 'text-gray-400 hover:bg-gray-800 hover:text-white'
              )
            }
          >
            <span>{item.name}</span>
          </NavLink>
        ))}
      </nav>
      <div className="p-4 border-t border-gray-800">
        <div className="flex items-center gap-3 px-3 py-2">
          <div className="flex-1 min-w-0">
            <p className="text-sm font-medium text-white truncate">{user?.name}</p>
            <p className="text-xs text-gray-500 truncate">{user?.email}</p>
          </div>
        </div>
      </div>
    </aside>
  );
}