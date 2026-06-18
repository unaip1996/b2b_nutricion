"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { Save, UserCheck, Key, Shield, User as UserIcon } from "lucide-react";

interface UserFormProps {
    apiUrl: string;
    redirectUrl: string;
    isProfile?: boolean;
    method?: "POST" | "PUT";
}

export function UserForm({ apiUrl, redirectUrl, isProfile = false, method = "PUT" }: UserFormProps) {
    const router = useRouter();
    const [formData, setFormData] = useState({
        email: "",
        password: "",
        roles: [] as string[],
    });

    const [isLoading, setIsLoading] = useState(false);
    // Inicializamos isFetching a false en el servidor.
    // Se establecerá a true en el lado del cliente después del montaje para activar la carga.
    const [isFetching, setIsFetching] = useState(false);
    const [errorMsg, setErrorMsg] = useState("");
    const [isClient, setIsClient] = useState(false); // Para rastrear si estamos en el lado del cliente

    useEffect(() => {
        setIsClient(true); // El componente se ha montado en el cliente
    }, []); // Se ejecuta solo una vez en el montaje del cliente

    useEffect(() => {
        // Solo comenzamos a buscar si estamos en el lado del cliente y apiUrl está proporcionado
        if (isClient && apiUrl) {
            setIsFetching(true); // Establecer a true para deshabilitar los botones mientras se carga
            const fetchUser = async () => {
                try {
                    const token = document.cookie.split("; ").find(row => row.startsWith("auth_token="))?.split("=")[1];
                    const res = await fetch(apiUrl, {
                        headers: { Authorization: `Bearer ${token}` },
                    });
                    if (res.ok) {
                        const { data } = await res.json();
                        setFormData({
                            email: data.email || "",
                            password: "",
                            roles: data.roles || [],
                        });
                    }
                } catch (error) {
                    console.error("Error al cargar datos", error);
                } finally {
                    setIsFetching(false);
                }
            };
            fetchUser();
        } // <--- ¡AQUÍ FALTABA ESTA LLAVE PARA CERRAR EL IF!
    }, [apiUrl, isClient]);

    const handleFieldChange = (field: string, value: any) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsLoading(true);
        setErrorMsg("");

        try {
            const token = document.cookie.split("; ").find(row => row.startsWith("auth_token="))?.split("=")[1];
            
            // Si es perfil, no enviamos roles para evitar errores de seguridad en el backend
            const payload = isProfile 
                ? { email: formData.email, password: formData.password }
                : formData;

            const response = await fetch(apiUrl, {
                method: method,
                headers: {
                    "Content-Type": "application/json",
                    Authorization: `Bearer ${token}`,
                },
                body: JSON.stringify(payload),
            });

            if (response.ok) {
                router.push(redirectUrl);
                router.refresh();
            } else {
                const data = await response.json();
                setErrorMsg(data.error || "Error al procesar la solicitud");
            }
        } catch (error) {
            setErrorMsg("Error de red al conectar con el servidor.");
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <form onSubmit={handleSubmit} className="mx-auto flex max-w-3xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
            <header className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between border-b border-slate-200 pb-6">
                <div>
                    <h1 className="text-3xl font-bold text-slate-800 flex items-center gap-2">
                        {isProfile ? (
                            <UserIcon className="h-7 w-7 text-blue-600" />
                        ) : (
                            <UserCheck className="h-7 w-7 text-blue-600" />
                        )}
                        {/* En el servidor, isFetching es false, por lo que mostrará "Mi Perfil" o "Editar: email" */}
                        {/* En el cliente, mostrará "Cargando..." brevemente, luego el título real */}
                        {isClient && isFetching ? "Cargando..." : isProfile ? "Mi Perfil" : `Editar: ${formData.email}`}
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">
                        {isProfile ? "Gestiona tu información personal" : "Gestión de credenciales y nivel de acceso"}
                    </p>
                </div>
                <div className="flex items-center gap-3">
                    <button
                        type="button"
                        onClick={() => router.back()}
                        className="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50"
                    >
                        Volver
                    </button>
                    <button
                        type="submit"
                        disabled={isLoading || isFetching}
                        className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 disabled:opacity-50"
                    >
                        <Save className="h-4 w-4" />
                        {isLoading ? "Guardando..." : "Guardar Cambios"}
                    </button>
                </div>
            </header>

            {errorMsg && (
                <div className="rounded-lg bg-red-50 p-4 text-sm text-red-600 border border-red-200">
                    {errorMsg}
                </div>
            )}

            <div className="space-y-6">
                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 className="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                        <Key className="h-5 w-5 text-slate-500" /> Credenciales de Acceso
                    </h2>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Correo Electrónico</label>
                            <input
                                type="email"
                                value={formData.email}
                                onChange={(e) => handleFieldChange("email", e.target.value)}
                                className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                                required
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Nueva Contraseña</label>
                            <input
                                type="password"
                                placeholder="Mantenla vacía para no cambiarla"
                                value={formData.password}
                                onChange={(e) => handleFieldChange("password", e.target.value)}
                                className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none font-mono"
                            />
                        </div>
                    </div>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 className="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                        <Shield className="h-5 w-5 text-slate-500" /> Nivel de Privilegios
                        {isProfile && <span className="text-xs font-normal text-slate-400 ml-auto">(Solo lectura)</span>}
                    </h2>
                    <div className="flex flex-col gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        {["ROLE_NUTRITIONIST", "ROLE_ADMIN"].map((role) => (
                            <label key={role} className={`flex items-start gap-3 text-sm text-slate-700 ${isProfile ? 'cursor-default' : 'cursor-pointer'}`}>
                                <input
                                    type="checkbox"
                                    disabled={isProfile || isLoading || isFetching}
                                    checked={formData.roles.includes(role)}
                                    onChange={(e) => {
                                        if (isProfile) return;
                                        let newRoles = e.target.checked
                                            ? [...formData.roles, role]
                                            : formData.roles.filter(r => r !== role);
                                        if (!newRoles.includes("ROLE_USER")) newRoles.push("ROLE_USER");
                                        handleFieldChange("roles", newRoles);
                                    }}
                                    className={`mt-0.5 h-4 w-4 rounded border-slate-300 ${role === 'ROLE_ADMIN' ? 'text-purple-600 focus:ring-purple-500' : 'text-blue-600 focus:ring-blue-500'} disabled:opacity-50`}
                                />
                                <div>
                                    <span className="font-medium text-slate-900 block">{role === 'ROLE_ADMIN' ? 'Administrador' : 'Nutricionista'}</span>
                                    <span className="text-slate-500 text-xs">{role === 'ROLE_ADMIN' ? 'Control total del sistema.' : 'Acceso a gestión clínica.'}</span>
                                </div>
                            </label>
                        ))}
                    </div>
                </div>
            </div>
        </form>
    );
}