"use client";

import { useState, useEffect, useRef } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import {
    Brain,
    Users,
    BookOpen,
    UserCog,
    User,
    LogOut,
    ChevronUp,
} from "lucide-react";

export function ClinicalSidebar() {
    const pathname = usePathname();
    const router = useRouter();

    // Estados para la lógica B2B y el menú
    const [isAdmin, setIsAdmin] = useState(false);
    const [userEmail, setUserEmail] = useState("Cargando...");
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);

    // 1. Efecto para leer el token y saber los permisos
    useEffect(() => {
        const token = document.cookie
            .split("; ")
            .find((row) => row.startsWith("auth_token="))
            ?.split("=")[1];
        if (token) {
            try {
                const payload = JSON.parse(atob(token.split(".")[1]));
                if (payload.roles && payload.roles.includes("ROLE_ADMIN")) {
                    setIsAdmin(true);
                }
                if (payload.email) {
                    setUserEmail(payload.email);
                }
            } catch (error) {
                console.error("Error decodificando el token JWT", error);
            }
        }
    }, []);

    // 2. Efecto para cerrar el desplegable al hacer clic fuera
    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            if (
                dropdownRef.current &&
                !dropdownRef.current.contains(event.target as Node)
            ) {
                setIsDropdownOpen(false);
            }
        }
        document.addEventListener("mousedown", handleClickOutside);
        return () =>
            document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    const handleLogout = () => {
        document.cookie = "auth_token=; path=/; max-age=0;";
        router.push("/login");
    };

    // 3. Matriz de navegación dinámica (movida dentro del componente para leer isAdmin)
    const navigation = [
        {
            name: "Motor RAG Clínico",
            href: "/dashboard/rag",
            icon: Brain,
            show: true,
        },
        {
            name: "Directorio Pacientes",
            href: "/dashboard/patients",
            icon: Users,
            show: true,
        },
        {
            name: "Base de Conocimiento",
            href: "/dashboard/knowledge-base",
            icon: BookOpen,
            show: true,
        },
        {
            name: "Directorio Usuarios",
            href: "/dashboard/users",
            icon: UserCog,
            show: isAdmin,
        }, // Protegido
    ];

    return (
        <aside className="flex w-64 flex-col bg-slate-900">
            {/* Header */}
            <div className="flex h-20 items-center border-b border-slate-800 px-6">
                <span className="text-xl font-bold text-white">
                    NutriSupport<span className="text-blue-500">.AI</span>
                </span>
            </div>

            {/* Navigation */}
            <nav className="flex-1 px-4 py-6">
                <h2 className="mb-4 px-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    Panel Clínico
                </h2>
                <ul className="flex flex-col gap-1">
                    {navigation.map((item) => {
                        if (!item.show) return null; // Si no tiene permisos, no pinta el <li>

                        const isActive = pathname.startsWith(item.href);
                        return (
                            <li key={item.name}>
                                <Link
                                    href={item.href}
                                    aria-current={isActive ? "page" : undefined}
                                    className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors ${isActive ? "bg-blue-600/10 text-blue-400" : "text-slate-400 hover:bg-slate-800 hover:text-slate-200"}`}
                                >
                                    <item.icon
                                        className="h-5 w-5"
                                        aria-hidden="true"
                                    />
                                    {item.name}
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>

            {/* User Profile Footer (Con Menú Desplegable) */}
            <div
                className="relative mt-auto border-t border-slate-800 p-4"
                ref={dropdownRef}
            >
                {/* Menú Flotante */}
                {isDropdownOpen && (
                    <div className="absolute bottom-[100%] left-4 right-4 z-50 mb-2 rounded-lg border border-slate-800 bg-slate-900 py-1 shadow-xl ring-1 ring-white/10">
                        <button
                            onClick={() => {
                                setIsDropdownOpen(false);
                                router.push("/dashboard/profile");
                            }}
                            className="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                        >
                            <User className="h-4 w-4 text-slate-400" />
                            Editar perfil
                        </button>
                        <button
                            onClick={handleLogout}
                            className="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-red-400 transition-colors hover:bg-slate-800 hover:text-red-300"
                        >
                            <LogOut className="h-4 w-4" />
                            Cerrar Sesión
                        </button>
                    </div>
                )}

                {/* Botón Togleador del Usuario */}
                <button
                    onClick={() => setIsDropdownOpen(!isDropdownOpen)}
                    className="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left transition-colors hover:bg-slate-800"
                >
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-slate-700 text-xs font-bold text-slate-300">
                        {isAdmin ? "AD" : "DR"}
                    </div>
                    <div className="flex-1 overflow-hidden">
                        <p className="truncate text-sm font-medium text-slate-200">
                            {userEmail !== "Cargando..."
                                ? userEmail.split("@")[0]
                                : "Dr. Facultativo"}
                        </p>
                        <p className="text-xs text-slate-500">
                            Licencia Activa
                        </p>
                    </div>
                    <ChevronUp
                        className={`h-4 w-4 text-slate-500 transition-transform ${isDropdownOpen ? "rotate-180" : ""}`}
                    />
                </button>
            </div>
        </aside>
    );
}
