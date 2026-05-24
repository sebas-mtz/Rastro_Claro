import React, { useEffect } from 'react';
import { Head, router } from '@inertiajs/react';

export default function Welcome({
  canLogin,
  canRegister,
  laravelVersion,
  phpVersion,
}) {
  useEffect(() => {
    const t = setTimeout(() => {
      router.visit('/login');
      // O con Ziggy: router.visit(route('login'));
    }, 700);

    return () => clearTimeout(t);
  }, []);

  // 👇 Saludo según la hora local del navegador
  const hour = new Date().getHours();
  let greeting = 'Hola';

  if (hour >= 5 && hour < 12) {
    greeting = 'Buenos días';
  } else if (hour >= 12 && hour < 19) {
    greeting = 'Buenas tardes';
  } else {
    greeting = 'Buenas noches';
  }

  return (
    <>
      <Head title="Bienvenido" />

      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-950 to-sky-900 relative overflow-hidden">
        <div className="absolute w-80 h-80 bg-sky-500/20 rounded-full blur-3xl -top-10 -left-10 animate-pulse" />
        <div className="absolute w-72 h-72 bg-indigo-500/20 rounded-full blur-3xl bottom-0 right-0 animate-pulse" />

        <div className="relative z-10 max-w-md w-full px-8 py-10 bg-white/5 border border-white/10 rounded-3xl shadow-2xl backdrop-blur-xl flex flex-col items-center text-center space-y-6">
          <div className="w-24 h-24 rounded-2xl bg-slate-900/70 border border-white/15 flex items-center justify-center shadow-lg animate-[fadeIn_0.6s_ease-out]">
            <img
              src="/assets/logo.png"
              alt="Logo Itzcóatl Tech"
              className="w-20 h-20 object-contain animate-[float_2.8s_ease-in-out_infinite]"
            />
          </div>

          <div className="space-y-1">
            <h1 className="text-2xl font-semibold tracking-tight text-white">
              Itzcóatl Tech
            </h1>
            {/* 👇 Aquí usamos el saludo dinámico */}
            <p className="text-sm text-slate-200/80">
              {greeting}, "Transformamos ideas en codigo y codigo en soluciones"
            </p>
          </div>

          <div className="flex items-center gap-3 text-xs text-slate-200/70">
            <span className="inline-flex h-4 w-4 rounded-full border-[2px] border-slate-200/40 border-t-transparent animate-spin" />
            <span className="uppercase tracking-[0.2em] text-[0.7rem]">
              CARGANDO
            </span>
          </div>

          <div className="pt-2 text-[0.65rem] text-slate-300/60">
            <p>Laravel {laravelVersion} • PHP {phpVersion}</p>
          </div>

          <noscript>
            <p className="mt-4 text-xs text-red-100/90">
              JavaScript está desactivado.
              <a href="/login" className="underline ml-1">
                Entra aquí al login
              </a>
            </p>
          </noscript>
        </div>
      </div>

      <style>{`
        @keyframes float {
          0%, 100% { transform: translateY(0); }
          50% { transform: translateY(-6px); }
        }
        @keyframes fadeIn {
          from { opacity: 0; transform: translateY(10px); }
          to { opacity: 1; transform: translateY(0); }
        }
      `}</style>
    </>
  );
}
