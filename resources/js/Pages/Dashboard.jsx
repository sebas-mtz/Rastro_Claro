// resources/js/Pages/Custom/Home.jsx
import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Home() {
  return (
    <>
      <Head title="Inicio – Hato360" />
      <div className="container-centered">
        <h1>Dashboard</h1>
        <p className="lead">Resumen general del día</p>

        <div className="card" style={{ marginTop: '1rem' }}>
          <div className="card-title">¡Hola!</div>
          <div>Si ves esta tarjeta, el layout y la página cargaron bien.</div>
        </div>
      </div>
    </>
  );
}

// ✅ Aplica el layout (después de la función)
Home.layout = (page) => <AppLayout>{page}</AppLayout>;
