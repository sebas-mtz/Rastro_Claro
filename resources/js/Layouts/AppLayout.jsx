import Sidebar from '@/Components/Sidebar';
import { usePage } from '@inertiajs/react'
import { useEffect } from 'react'
import { toast } from 'react-hot-toast'

export default function AppLayout({ header, children }) {
  const { flash } = usePage().props

  useEffect(() => {
    if (flash.success) toast.success(flash.success)
    if (flash.error)   toast.error(flash.error)
  }, [flash])

  return (
    <div className="app-frame">
      <Sidebar />
      <div className="app-content flex flex-col">
        {header && (
          <header className="bg-white shadow dark:bg-slate-900">
            <div className="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
              {header}
            </div>
          </header>
        )}
        <main className="flex-1">{children}</main>
      </div>
    </div>
  );
}
