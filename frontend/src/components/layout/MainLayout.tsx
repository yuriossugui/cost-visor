import { Outlet } from 'react-router-dom';
import { Sidebar } from './Sidebar';
import { Header } from './Header';
import { cn } from '@/lib/utils';

export function MainLayout() {
  return (
    <div className="min-h-screen bg-gray-950 text-white">
      <Sidebar />
      <div className={cn('lg:pl-64', 'flex flex-col min-h-screen')}>
        <Header />
        <main className="flex-1 p-4 lg:p-6 overflow-auto">
          <Outlet />
        </main>
      </div>
    </div>
  );
}