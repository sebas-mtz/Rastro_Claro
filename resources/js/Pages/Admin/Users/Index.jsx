import React, { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

function UsersIndex() {
    const { props } = usePage();
    const { users, flash } = props;

    const [localUsers, setLocalUsers] = useState(
        users.map(u => ({
            ...u,
            role: u.role || 'user',
            plan: u.plan || 'normal',
            activo: !!u.activo,
        }))
    );

    const handleChange = (id, field, value) => {
        setLocalUsers(prev =>
            prev.map(u => (u.id === id ? { ...u, [field]: value } : u))
        );
    };

    const handleSave = (user) => {
        router.put(route('admin.usuarios.update', user.id), {
            role: user.role,
            plan: user.plan,
            activo: user.activo ? 1 : 0,
        });
    };

    return (
        <>
            <Head title="Usuarios" />

            <div className="py-8 px-4 max-w-5xl mx-auto">
                <h1 className="text-2xl font-bold text-gray-800 mb-2">
                    Gestión de Usuarios
                </h1>
                <p className="text-sm text-gray-600 mb-4">
                    Cambia el rol y el plan de cada usuario. Solo tú (admin) puedes ver esto.
                </p>

                {flash?.success && (
                    <div className="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded p-2">
                        {flash.success}
                    </div>
                )}

                <div className="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
                    <table className="min-w-full text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-2 text-left">ID</th>
                                <th className="px-4 py-2 text-left">Nombre</th>
                                <th className="px-4 py-2 text-left">Email</th>
                                <th className="px-4 py-2 text-left">Rol</th>
                                <th className="px-4 py-2 text-left">Plan</th>
                                <th className="px-4 py-2 text-left">Activo</th>
                                <th className="px-4 py-2 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {localUsers.map(u => (
                                <tr key={u.id} className="border-t">
                                    <td className="px-4 py-2">{u.id}</td>
                                    <td className="px-4 py-2">{u.name}</td>
                                    <td className="px-4 py-2">{u.email}</td>
                                    <td className="px-4 py-2">
                                        <select
                                            className="border-gray-300 rounded text-xs"
                                            value={u.role}
                                            onChange={e =>
                                                handleChange(u.id, 'role', e.target.value)
                                            }
                                        >
                                            <option value="user">Usuario</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </td>
                                    <td className="px-4 py-2">
                                        <select
                                            className="border-gray-300 rounded text-xs"
                                            value={u.plan}
                                            onChange={e =>
                                                handleChange(u.id, 'plan', e.target.value)
                                            }
                                        >
                                            <option value="normal">Normal</option>
                                            <option value="premium">Premium</option>
                                        </select>
                                    </td>
                                    <td className="px-4 py-2">
                                        <input
                                            type="checkbox"
                                            checked={u.activo}
                                            onChange={e =>
                                                handleChange(u.id, 'activo', e.target.checked)
                                            }
                                        />
                                    </td>
                                    <td className="px-4 py-2 text-right">
                                        <button
                                            onClick={() => handleSave(u)}
                                            className="px-3 py-1 text-xs rounded bg-blue-600 text-white hover:bg-blue-700"
                                        >
                                            Guardar
                                        </button>
                                    </td>
                                </tr>
                            ))}

                            {localUsers.length === 0 && (
                                <tr>
                                    <td
                                        colSpan="7"
                                        className="px-4 py-6 text-center text-gray-500 text-sm"
                                    >
                                        No hay usuarios registrados.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

UsersIndex.layout = (page) => <AppLayout>{page}</AppLayout>;

export default UsersIndex;
