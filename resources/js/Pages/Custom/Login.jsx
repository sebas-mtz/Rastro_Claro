import { useState, useEffect } from "react";
import { Head, Link, useForm } from "@inertiajs/react";

export default function LoginRegister() {
  const [activeTab, setActiveTab] = useState("login");

  const loginForm = useForm({ email: "", password: "", remember: false });
  const registerForm = useForm({ name: "", email: "", password: "", password_confirmation: "" });

  const submitLogin = (e) => {
    e.preventDefault();
    loginForm.post(route("login"), { onSuccess: () => loginForm.reset("password") });
  };

  const submitRegister = (e) => {
    e.preventDefault();
    registerForm.post(route("register"));
  };

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get("tab") === "register") setActiveTab("register");
  }, []);

  return (
    <>
      <Head title="Acceso | Rastro Facil" />
      <div className="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 flex items-center justify-center p-4">
        <div className="w-full max-w-md">
          <div className="bg-white shadow-xl rounded-2xl p-6 sm:p-8">
            <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100">
              <img src="/assets/rastro Facil.png" alt="icono" className="h-6 w-6" />

            </div>

            <h1 className="text-center text-xl font-semibold text-gray-900">Rastro Facil</h1>
            <p className="mt-1 text-center text-sm text-gray-500">Gestión integral de animales de granja</p>

            <div className="mt-6 grid grid-cols-2 rounded-xl bg-gray-100 p-1 text-sm font-medium">
              <button
                className={`rounded-lg py-2 text-center transition ${activeTab === "login" ? "bg-white shadow text-emerald-700" : "text-gray-600"}`}
                onClick={() => setActiveTab("login")}
              >
                Iniciar Sesión
              </button>
              <button
                className={`rounded-lg py-2 text-center transition ${activeTab === "register" ? "bg-white shadow text-emerald-700" : "text-gray-600"}`}
                onClick={() => setActiveTab("register")}
              >
                Registrarse
              </button>
            </div>

            {activeTab === "login" && (
              <form className="mt-6 space-y-4" onSubmit={submitLogin}>
                <div>
                  <label className="mb-1 block text-sm text-gray-700">Correo electrónico</label>
                  <input
                    type="email" autoComplete="email" required placeholder="tu@email.com"
                    value={loginForm.data.email}
                    onChange={(e) => loginForm.setData("email", e.target.value)}
                    className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                  />
                  {loginForm.errors.email && <p className="mt-1 text-xs text-red-600">{loginForm.errors.email}</p>}
                </div>

                <div>
                  <label className="mb-1 block text-sm text-gray-700">Contraseña</label>
                  <input
                    type="password" autoComplete="current-password" required placeholder="********"
                    value={loginForm.data.password}
                    onChange={(e) => loginForm.setData("password", e.target.value)}
                    className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                  />
                  {loginForm.errors.password && <p className="mt-1 text-xs text-red-600">{loginForm.errors.password}</p>}
                </div>

                <div className="flex items-center justify-between">
                  <label className="inline-flex items-center gap-2 text-sm text-gray-600">
                    <input
                      type="checkbox"
                      checked={loginForm.data.remember}
                      onChange={(e) => loginForm.setData("remember", e.target.checked)}
                      className="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                    />
                    Recordarme
                  </label>

                  {/* Link a Forgot usando la ruta de Breeze; si no la tienes, usa href="/password/forgot" */}
                  <Link href={route("password.request")} className="text-sm font-medium text-emerald-700 hover:underline">
                    ¿Olvidaste tu contraseña?
                  </Link>
                </div>

                <button
                  type="submit"
                  disabled={loginForm.processing}
                  className="mt-2 w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white shadow hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-200 disabled:opacity-60 disabled:cursor-not-allowed"
                >
                  {loginForm.processing ? "Procesando…" : "Iniciar Sesión"}
                </button>

                <p className="text-center text-xs text-gray-400">Demo: usa cualquier email/contraseña</p>
              </form>
            )}

            {activeTab === "register" && (
              <form className="mt-6 space-y-4" onSubmit={submitRegister}>
                <div>
                  <label className="mb-1 block text-sm text-gray-700">Nombre</label>
                  <input
                    type="text" required placeholder="Tu nombre"
                    value={registerForm.data.name}
                    onChange={(e) => registerForm.setData("name", e.target.value)}
                    className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                  />
                  {registerForm.errors.name && <p className="mt-1 text-xs text-red-600">{registerForm.errors.name}</p>}
                </div>

                <div>
                  <label className="mb-1 block text-sm text-gray-700">Correo electrónico</label>
                  <input
                    type="email" required placeholder="tu@email.com"
                    value={registerForm.data.email}
                    onChange={(e) => registerForm.setData("email", e.target.value)}
                    className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                  />
                  {registerForm.errors.email && <p className="mt-1 text-xs text-red-600">{registerForm.errors.email}</p>}
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label className="mb-1 block text-sm text-gray-700">Contraseña</label>
                    <input
                      type="password" required placeholder="********"
                      value={registerForm.data.password}
                      onChange={(e) => registerForm.setData("password", e.target.value)}
                      className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                    />
                    {registerForm.errors.password && <p className="mt-1 text-xs text-red-600">{registerForm.errors.password}</p>}
                  </div>
                  <div>
                    <label className="mb-1 block text-sm text-gray-700">Confirmar contraseña</label>
                    <input
                      type="password" required placeholder="********"
                      value={registerForm.data.password_confirmation}
                      onChange={(e) => registerForm.setData("password_confirmation", e.target.value)}
                      className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                    />
                  </div>
                </div>

                <button
                  type="submit"
                  disabled={registerForm.processing}
                  className="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white shadow hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-200 disabled:opacity-60 disabled:cursor-not-allowed"
                >
                  {registerForm.processing ? "Procesando…" : "Crear cuenta"}
                </button>

                <p className="text-center text-xs text-gray-400">
                  Al registrarte aceptas los Términos y la Política de Privacidad.
                </p>
              </form>
            )}
          </div>

          <p className="mt-4 text-center text-xs text-gray-400">
            © {new Date().getFullYear()} FarmManager Pro
          </p>
        </div>
      </div>
    </>
  );
}
