import { useState, useEffect } from "react";
import { Head, Link, useForm } from "@inertiajs/react";

export default function LoginRegister() {
  const [activeTab, setActiveTab] = useState("login"); // 'login' | 'register'

  // Forms
  const loginForm = useForm({
    email: "",
    password: "",
    remember: false,
  });

  const registerForm = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
  });

  const submitLogin = (e) => {
    e.preventDefault();
    loginForm.post(route("login"), {
      onSuccess: () => loginForm.reset("password"),
    });
  };

  const submitRegister = (e) => {
    e.preventDefault();
    registerForm.post(route("register"));
  };

  useEffect(() => {
    // Permite abrir directamente en registro con ?tab=register
    const params = new URLSearchParams(window.location.search);
    if (params.get("tab") === "register") setActiveTab("register");
  }, []);

  return (
    <>
      <Head title="Acceso | FarmManager Pro" />
      <div className="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 flex items-center justify-center p-4">
        <div className="w-full max-w-md">
          <div className="bg-white shadow-xl rounded-2xl p-6 sm:p-8">
            {/* Logo */}
            <div className="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100">
              <img
                src="/assets/rastro Facil.png"
                alt="icono"
                className="h-16 w-16 rounded-full object-cover"
              />
            </div>

            <h1 className="text-center text-xl font-semibold text-gray-900">
              Rastro Facil
            </h1>
            <p className="mt-1 text-center text-sm text-gray-500">
              Gestión integral de animales de granja
            </p>

            {/* Tabs */}
            <div className="mt-6 grid grid-cols-2 rounded-xl bg-gray-100 p-1 text-sm font-medium">
              <button
                className={`rounded-lg py-2 text-center transition ${
                  activeTab === "login"
                    ? "bg-white shadow text-emerald-700"
                    : "text-gray-600"
                }`}
                onClick={() => setActiveTab("login")}
              >
                Iniciar Sesión
              </button>
              <button
                className={`rounded-lg py-2 text-center transition ${
                  activeTab === "register"
                    ? "bg-white shadow text-emerald-700"
                    : "text-gray-600"
                }`}
                onClick={() => setActiveTab("register")}
              >
                Registrarse
              </button>
            </div>

            {/* LOGIN */}
            {activeTab === "login" && (
              <form className="mt-6 space-y-4" onSubmit={submitLogin}>
                <div>
                  <label className="mb-1 block text-sm text-gray-700">
                    Correo electrónico
                  </label>
                  <input
                    type="email"
                    autoComplete="email"
                    required
                    placeholder="tu@email.com"
                    value={loginForm.data.email}
                    onChange={(e) =>
                      loginForm.setData("email", e.target.value)
                    }
                    className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                  />
                  {loginForm.errors.email && (
                    <p className="mt-1 text-xs text-red-600">
                      {loginForm.errors.email}
                    </p>
                  )}
                </div>

                <div>
                  <label className="mb-1 block text-sm text-gray-700">
                    Contraseña
                  </label>
                  <input
                    type="password"
                    autoComplete="current-password"
                    required
                    placeholder="********"
                    value={loginForm.data.password}
                    onChange={(e) =>
                      loginForm.setData("password", e.target.value)
                    }
                    className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                  />
                  {loginForm.errors.password && (
                    <p className="mt-1 text-xs text-red-600">
                      {loginForm.errors.password}
                    </p>
                  )}
                </div>

                <div className="flex items-center justify-between">
                  <label className="inline-flex items-center gap-2 text-sm text-gray-600">
                    <input
                      type="checkbox"
                      checked={loginForm.data.remember}
                      onChange={(e) =>
                        loginForm.setData("remember", e.target.checked)
                      }
                      className="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                    />
                    Recordarme
                  </label>

                  <Link
                    href={route("password.request")}
                    className="text-sm font-medium text-emerald-700 hover:underline"
                  >
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

                <p className="text-center text-xs text-gray-400">
                  Demo: usa cualquier email/contraseña
                </p>
              </form>
            )}

            {/* REGISTRO */}
            {activeTab === "register" && (
              <form className="mt-6 space-y-4" onSubmit={submitRegister}>
                <div>
                  <label className="mb-1 block text-sm text-gray-700">
                    Nombre
                  </label>
                  <input
                    type="text"
                    required
                    placeholder="Tu nombre"
                    value={registerForm.data.name}
                    onChange={(e) =>
                      registerForm.setData("name", e.target.value)
                    }
                    className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                  />
                  {registerForm.errors.name && (
                    <p className="mt-1 text-xs text-red-600">
                      {registerForm.errors.name}
                    </p>
                  )}
                </div>

                <div>
                  <label className="mb-1 block text-sm text-gray-700">
                    Correo electrónico
                  </label>
                  <input
                    type="email"
                    required
                    placeholder="tu@email.com"
                    value={registerForm.data.email}
                    onChange={(e) =>
                      registerForm.setData("email", e.target.value)
                    }
                    className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                  />
                  {registerForm.errors.email && (
                    <p className="mt-1 text-xs text-red-600">
                      {registerForm.errors.email}
                    </p>
                  )}
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label className="mb-1 block text-sm text-gray-700">
                      Contraseña
                    </label>
                    <input
                      type="password"
                      required
                      placeholder="********"
                      value={registerForm.data.password}
                      onChange={(e) =>
                        registerForm.setData("password", e.target.value)
                      }
                      className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                    />
                    {registerForm.errors.password && (
                      <p className="mt-1 text-xs text-red-600">
                        {registerForm.errors.password}
                      </p>
                    )}
                  </div>
                  <div>
                    <label className="mb-1 block text-sm text-gray-700">
                      Confirmar contraseña
                    </label>
                    <input
                      type="password"
                      required
                      placeholder="********"
                      value={registerForm.data.password_confirmation}
                      onChange={(e) =>
                        registerForm.setData(
                          "password_confirmation",
                          e.target.value
                        )
                      }
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
                  Al registrarte aceptas los Términos y la Política de
                  Privacidad.
                </p>
              </form>
            )}

            {/* BOTONES SOCIALES (para ambos casos) */}
            <div className="mt-6">
              <div className="flex items-center gap-2">
                <div className="h-px flex-1 bg-gray-200" />
                <span className="text-xs uppercase tracking-wide text-gray-400">
                  o continúa con
                </span>
                <div className="h-px flex-1 bg-gray-200" />
              </div>

              <div className="mt-3 space-y-3">
                <Link href="/auth/google" className="flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                  <img
                    src="/img/google-logo.png"
                    alt="Google"
                    className="mr-2 h-5 w-5"
                  /> Iniciar sesión con Google
                </Link>
              </div>
            </div>
          </div>
          <p className="mt-4 text-center text-xs text-gray-400">
            © {new Date().getFullYear()} Rastro Facil
          </p>
        </div>
      </div>
    </>
  );
}
