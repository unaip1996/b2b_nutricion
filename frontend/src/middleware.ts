import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

export function middleware(request: NextRequest) {
  // Aquí leemos la cookie donde en el futuro guardaremos el JWT de tu backend en Symfony
  const token = request.cookies.get('auth_token')?.value;
  
  const { pathname } = request.nextUrl;
  const isLoginPage = pathname.startsWith('/login');

  // 1. Si no hay token y NO está intentando acceder al login -> Redirigir a /login
  if (!token && !isLoginPage) {
    return NextResponse.redirect(new URL('/login', request.url));
  }

  // 2. Si hay token (logueado) e intenta acceder a /login -> Redirigir al dashboard
  if (token && isLoginPage) {
    return NextResponse.redirect(new URL('/dashboard', request.url));
  }

  // 3. Si alguien entra directamente a la raíz (/) -> Redirigir al dashboard (y el middleware evaluará si tiene token)
  if (pathname === '/') {
    return NextResponse.redirect(new URL('/dashboard', request.url));
  }

  // Si todo está correcto, dejamos que la petición continúe
  return NextResponse.next();
}

// Configuramos las rutas en las que queremos que se ejecute este middleware
export const config = {
  matcher: [
    /*
     * Intercepta todas las rutas excepto:
     * - api (rutas internas de Next.js)
     * - _next/static (archivos estáticos de React)
     * - _next/image (optimización de imágenes)
     * - Archivos estáticos públicos (svg, png, ico, etc.)
     */
    '/((?!api|_next/static|_next/image|.*\\.svg|.*\\.png|favicon.ico).*)',
  ],
};