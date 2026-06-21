export async function loginUser(email?: string, password?: string) {
  // Aquí en el futuro harás el POST a tu backend Symfony
  // const response = await fetch('...', { method: 'POST', body: JSON.stringify({ email, password }) })
  // Si es exitoso, el backend puede devolver el token o setear una cookie httpOnly.

  // Para el MVP actual en frontend: 
  // Creamos una cookie falsa que dura 1 día (86400 segundos)
  document.cookie = "auth_token=super-secret-jwt-mvp; path=/; max-age=86400";

  return { success: true };
}