export async function loginUser(email?: string, password?: string) {
    // Aquí en el futuro harás el POST a tu backend Symfony
    // const response = await fetch('...', { method: 'POST', body: JSON.stringify({ email, password }) })
    // Si es exitoso, el backend puede devolver el token o setear una cookie httpOnly.

    // Para el MVP actual en frontend:
    // Creamos una cookie falsa que dura 1 día (86400 segundos)
    document.cookie = "auth_token=super-secret-jwt-mvp; path=/; max-age=86400";

    return { success: true };
}

export async function fetchWithAuth(
    endpoint: string,
    options: RequestInit = {},
) {
    // 1. Extraer token: primero localStorage, luego cookies
    let token: string | null = null;

    if (typeof window !== "undefined") {
        // Intenta primero localStorage
        token = localStorage.getItem("token");

        // Si no hay token en localStorage, intenta extraerlo de las cookies
        if (!token) {
            const cookieToken = document.cookie
                .split("; ")
                .find((row) => row.startsWith("auth_token="))
                ?.split("=")[1];
            token = cookieToken || null;
        }
    }

    // 2. Preparar headers base
    const headers: HeadersInit = {
        Accept: "application/ld+json, application/json",
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
    };

    // 3. Manejar Content-Type según el tipo de body
    const bodyIsFormData = options.body instanceof FormData;

    if (
        !bodyIsFormData &&
        !(
            options.headers &&
            (options.headers as Record<string, string>)["Content-Type"]
        )
    ) {
        // Solo establecer Content-Type si no es FormData y no ya se ha establecido
        headers["Content-Type"] = "application/json";
    }

    // 4. Combinar con headers personalizados (permiten sobrescribir los defaults)
    const finalHeaders = {
        ...headers,
        ...options.headers,
    };

    const url = `${process.env.NEXT_PUBLIC_API_URL}${endpoint}`;

    const response = await fetch(url, {
        ...options,
        headers: finalHeaders,
    });

    // INTERCEPTOR GLOBAL DE SEGURIDAD
    if (response.status === 401) {
        if (typeof window !== "undefined") {
            console.warn("Sesión expirada. Redirigiendo al login...");
            localStorage.removeItem("token");
            window.location.href = "/login"; // Redirección nativa global
        }
    }

    return response;
}
