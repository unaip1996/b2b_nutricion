"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { Users, Shield, ShieldCheck, Edit2, Plus } from "lucide-react";

interface UserRow {
    id: string;
    email: string;
    roles: string[];
    lastLogin: string | null;
}

export default function UsersListPage() {
    const [users, setUsers] = useState<UserRow[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const router = useRouter();

    useEffect(() => {
        const fetchUsers = async () => {
            try {
                const token = document.cookie
                    .split("; ")
                    .find((row) => row.startsWith("auth_token="))
                    ?.split("=")[1];
                const response = await fetch(
                    `${process.env.NEXT_PUBLIC_API_URL}/api/users`,
                    {
                        headers: { Authorization: `Bearer ${token}` },
                    },
                );
                if (response.ok) {
                    const { data } = await response.json();
                    setUsers(data);
                }
            } catch (error) {
                console.error("Error cargando usuarios:", error);
            } finally {
                setIsLoading(false);
            }
        };
        fetchUsers();
    }, []);

    return (
        <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <header className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 pb-6 mb-8">
                <div>
                    <h1 className="text-3xl font-bold text-slate-800 flex items-center gap-3">
                        <Users className="h-8 w-8 text-blue-600" />
                        Gestión de Usuarios
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Administración de accesos y roles de la plataforma
                    </p>
                </div>
                {/* Botón de acceso a la pantalla de creación */}
                <div>
                    <button
                        onClick={() => router.push("/dashboard/users/create")}
                        className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700"
                    >
                        <Plus className="h-4 w-4" />
                        Nuevo Usuario
                    </button>
                </div>
            </header>

            {isLoading ? (
                <div className="text-center py-12 text-slate-500">
                    Cargando usuarios...
                </div>
            ) : (
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table className="min-w-full divide-y divide-slate-200">
                        <thead className="bg-slate-50/75">
                            <tr>
                                <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Cuenta (Email)
                                </th>
                                <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Privilegios
                                </th>
                                <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Último Acceso
                                </th>
                                <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200 bg-white">
                            {users.map((user) => (
                                <tr
                                    key={user.id}
                                    className="hover:bg-slate-50/50 transition-colors"
                                >
                                    <td className="whitespace-nowrap px-6 py-4 font-medium text-slate-900">
                                        {user.email}
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm">
                                        {user.roles.includes("ROLE_ADMIN") ? (
                                            <span className="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2.5 py-1 text-xs font-medium text-purple-700 border border-purple-200">
                                                <ShieldCheck className="h-3 w-3" />{" "}
                                                Admin
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 border border-blue-200">
                                                <Shield className="h-3 w-3" />{" "}
                                                Nutricionista
                                            </span>
                                        )}
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-400 font-mono">
                                        {user.lastLogin || "Nunca"}
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <button
                                            onClick={() =>
                                                router.push(
                                                    `/dashboard/users/${user.id}`,
                                                )
                                            }
                                            className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-slate-700 shadow-sm transition-colors hover:bg-slate-50"
                                        >
                                            <Edit2 className="h-3.5 w-3.5 text-slate-500" />
                                            Editar
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
