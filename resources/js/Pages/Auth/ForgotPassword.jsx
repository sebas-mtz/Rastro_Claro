import { Head, Link, useForm } from "@inertiajs/react";

export default function ForgotPassword() {
  const form = useForm({ email: "" });

  const submit = (e) => {
    e.preventDefault();
    form.post(route("password.email"));
  };

  return (
    <>
      <Head title="Recuperar contraseña" />
      <form onSubmit={submit} className="max-w-md mx-auto mt-16 bg-white p-6 rounded-2xl shadow">
        <h2 className="text-lg font-semibold mb-4 text-gray-900">Recuperar contraseña</h2>

        <label className="block text-sm text-gray-700 mb-1">Correo electrónico</label>
        <input
          type="email"
          required
          placeholder="tu@email.com"
          value={form.data.email}
          onChange={(e) => form.setData("email", e.target.value)}
          className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
        />
        {form.errors.email && <p className="mt-1 text-xs text-red-600">{form.errors.email}</p>}

        <button
          disabled={form.processing}
          className="mt-4 w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
        >
          {form.processing ? "Procesando…" : "Enviar enlace de recuperación"}
        </button>

        <Link href="/auth" className="mt-3 block text-center text-sm text-emerald-700 hover:underline">
          Volver
        </Link>
      </form>
    </>
  );
}
