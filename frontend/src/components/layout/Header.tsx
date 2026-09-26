import { Link, useLocation } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { useAuth } from '@/context/AuthContext';

export function Header() {
  const { user, logout } = useAuth();

  const handleLogout = async () => {
    await logout();
  };

  return (
    <header className="sticky top-0 z-40 w-full bg-gray-900/80 backdrop-blur-sm border-b border-gray-800">
      <div className="flex h-16 items-center justify-between px-4 lg:px-6">
        <div className="flex items-center gap-4 lg:hidden">
          <Button variant="ghost" size="icon" asChild>
            <Link to="/dashboard">
              <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </Link>
          </Button>
          <h1 className="text-lg font-bold text-white">Cost Visor</h1>
        </div>

        <nav className="hidden md:flex items-center gap-6">
          <NavLink to="/dashboard">Dashboard</NavLink>
          <NavLink to="/dashboard/transactions">Transações</NavLink>
          <NavLink to="/dashboard/categories">Categorias</NavLink>
          <NavLink to="/dashboard/installments">Parcelamentos</NavLink>
          <NavLink to="/dashboard/recurrences">Recorrências</NavLink>
        </nav>

        <div className="flex items-center gap-4">
          <div className="hidden sm:block text-right">
            <p className="text-sm font-medium text-white">{user?.name}</p>
            <p className="text-xs text-gray-500">{user?.email}</p>
          </div>
          <Button variant="ghost" size="sm" onClick={handleLogout}>
            Sair
          </Button>
        </div>
      </div>
    </header>
  );
}

function NavLink({ children, to }: { children: React.ReactNode; to: string }) {
  const location = useLocation();
  const isActive = location.pathname === to || location.pathname.startsWith(to + '/');

  return (
    <Link
      to={to}
      className={cn(
        'text-sm font-medium transition-colors',
        isActive ? 'text-white' : 'text-gray-400 hover:text-white'
      )}
    >
      {children}
    </Link>
  );
}