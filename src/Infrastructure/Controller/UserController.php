<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Infrastructure\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[OA\Tag(name: 'Usuarios y Perfil', description: 'Endpoints para la gestión de cuentas, roles y perfil del nutricionista')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }
    #[Route('/api/users', name: 'api_users_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Post(summary: 'Crea un nuevo usuario en el sistema (Solo Administradores)')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', type: 'string', example: 'nutricionista@clinica.com'),
                new OA\Property(property: 'password', type: 'string', example: 'PasswordSegura123!'),
                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_USER', 'ROLE_NUTRITIONIST'])
            ]
        )
    )]
    #[OA\Response(
        response: 201, 
        description: 'Usuario creado con éxito',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'id', type: 'string')
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Faltan credenciales obligatorias')]
    #[OA\Response(response: 409, description: 'El correo electrónico ya está registrado')]
    public function create(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            if (empty($payload['email']) || empty($payload['password'])) {
                return new JsonResponse(['error' => 'El correo electrónico y la contraseña son obligatorios.'], Response::HTTP_BAD_REQUEST);
            }

            // Evitar duplicados
            $existingUser = $userRepository->findOneBy(['email' => $payload['email']]);
            if ($existingUser) {
                return new JsonResponse(['error' => 'El correo electrónico ya está registrado.'], Response::HTTP_CONFLICT);
            }

            $user = new User();
            $user->setEmail($payload['email']);
            
            $roles = $payload['roles'] ?? ['ROLE_USER'];
            if (!in_array('ROLE_USER', $roles, true)) {
                $roles[] = 'ROLE_USER';
            }
            $user->setRoles($roles);

            // Hashear contraseña descodificada
            $hashedPassword = $passwordHasher->hashPassword($user, $payload['password']);
            $user->setPassword($hashedPassword);

            // ✨ LÓGICA DE NEGOCIO: Asociación automática de perfil profesional
            if (in_array('ROLE_NUTRITIONIST', $roles, true)) {
                $profile = new \App\Infrastructure\Entity\NutritionistProfile();
                $em->persist($profile);
                $user->setNutritionistProfile($profile);
            }

            $em->persist($user);
            $em->flush();

            return new JsonResponse([
                'message' => 'Usuario creado con éxito',
                'id' => (string) $user->getId()
            ], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Error interno al crear usuario: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Endpoint exclusivo para que cualquier usuario autenticado obtenga su propio perfil.
     * Al estar en '/api/profile' evitamos por completo la colisión con el validador UUID de '/api/users/{id}'
     */
    #[Route('/api/profile', name: 'api_profile_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(summary: 'Obtiene el perfil y credenciales del usuario actualmente autenticado')]
    #[OA\Response(
        response: 200, 
        description: 'Datos del usuario autenticado',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'string'),
                        new OA\Property(property: 'email', type: 'string'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'))
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Usuario no autenticado')]
    public function showProfile(): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        /** @var User|null $user */
        $user = $token ? $token->getUser() : null;

        if (!$user) {
            return new JsonResponse(['error' => 'Usuario no autenticado'], Response::HTTP_UNAUTHORIZED);
        }


        return new JsonResponse(['data' => [
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]], Response::HTTP_OK);
    }

    /**
     * Endpoint exclusivo para que cualquier usuario modifique sus propias credenciales.
     */
    #[Route('/api/profile', name: 'api_profile_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Put(summary: 'Actualiza el email o contraseña del usuario actualmente autenticado')]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', type: 'string', description: 'Nuevo correo electrónico'),
                new OA\Property(property: 'password', type: 'string', description: 'Nueva contraseña en texto plano (será hasheada)')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Perfil actualizado con éxito')]
    public function updateProfile(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        /** @var User|null $user */
        $user = $token ? $token->getUser() : null;

        if (!$user) {
            return new JsonResponse(['error' => 'Usuario no autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            
            $user->setEmail($payload['email'] ?? $user->getEmail());

            if (!empty($payload['password'])) {
                $hashedPassword = $passwordHasher->hashPassword($user, $payload['password']);
                $user->setPassword($hashedPassword);
            }

            $em->flush();
            return new JsonResponse(['message' => 'Perfil actualizado con éxito'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Error al actualizar el perfil: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // =========================================================================
    // ENDPOINTS DE ADMINISTRACIÓN (Protegidos con ROLE_ADMIN)
    // =========================================================================

    #[Route('/api/users', name: 'api_users_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(summary: 'Lista todos los usuarios registrados en la plataforma con paginación y filtrado (Excluye al administrador actual)')]
    #[OA\QueryParameter(name: 'page', description: 'Página actual (por defecto 1)', schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\QueryParameter(name: 'itemsPerPage', description: 'Registros por página (por defecto 5)', schema: new OA\Schema(type: 'integer', default: 5))]
    #[OA\QueryParameter(name: 'email', description: 'Filtro por email', schema: new OA\Schema(type: 'string'))]
    #[OA\QueryParameter(name: 'role', description: 'Filtro por rol (ROLE_ADMIN, ROLE_NUTRITIONIST)', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200, 
        description: 'Array de usuarios paginado',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'string'),
                            new OA\Property(property: 'email', type: 'string'),
                            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                            new OA\Property(property: 'lastLogin', type: 'string', format: 'date-time', nullable: true)
                        ]
                    )
                ),
                new OA\Property(property: 'total', type: 'integer', description: 'Total de usuarios que coinciden con los filtros')
            ]
        )
    )]
    public function list(Request $request, UserRepository $userRepository): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        /** @var User|null $currentUser */
        $currentUser = $token ? $token->getUser() : null;

        // Leer parámetros de paginación y filtros
        $page = $request->query->getInt('page', 1);
        $itemsPerPage = $request->query->getInt('itemsPerPage', 5);
        $filters = [
            'email' => $request->query->get('email'),
            'role' => $request->query->get('role'),
        ];

        // Consultar usuarios con paginación y filtrado
        $result = $userRepository->searchAndPaginateActive($filters, $page, $itemsPerPage);

        // Mapear datos (excluyendo el usuario actual)
        $data = [];
        /** @var User $user */
        foreach ($result['items'] as $user) {
            if ($currentUser && $user->getId() === $currentUser->getId()) {
                continue;
            }

            $data[] = [
                'id' => (string) $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'lastLogin' => $user->getLastLogin()?->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse([
            'data' => $data,
            'total' => $result['total']
        ], Response::HTTP_OK);
    }

    #[Route('/api/users/{id}', name: 'api_users_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(summary: 'Obtiene los detalles de un usuario específico')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'UUID del usuario', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Datos del usuario')]
    #[OA\Response(response: 404, description: 'Usuario no encontrado')]
    public function show(string $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['data' => [
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]], Response::HTTP_OK);
    }

    #[Route('/api/users/{id}', name: 'api_users_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(summary: 'Modifica los datos y roles de un usuario existente')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'UUID del usuario a modificar', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', type: 'string'),
                new OA\Property(property: 'password', type: 'string'),
                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'))
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Usuario actualizado con éxito')]
    #[OA\Response(response: 403, description: 'Prohibido quitarse el rol de administrador a uno mismo')]
    #[OA\Response(response: 404, description: 'Usuario no encontrado')]
    public function update(
        string $id,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): JsonResponse
    {
        try {
            $user = $userRepository->find($id);
            if (!$user) {
                return new JsonResponse(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
            }

            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $user->setEmail($payload['email'] ?? $user->getEmail());
            
            if (isset($payload['roles']) && is_array($payload['roles'])) {
                $token = $this->tokenStorage->getToken();
                $currentUser = $token ? $token->getUser() : null;

                if ($currentUser === $user && !in_array('ROLE_ADMIN', $payload['roles'], true)) {
                    return new JsonResponse(['error' => 'Seguridad: No puedes quitarte el rol de Administrador a ti mismo.'], Response::HTTP_FORBIDDEN);
                }
                $user->setRoles($payload['roles']);
            }

            if (!empty($payload['password'])) {
                $hashedPassword = $passwordHasher->hashPassword($user, $payload['password']);
                $user->setPassword($hashedPassword);
            }

            $em->flush();
            return new JsonResponse(['message' => 'Usuario actualizado con éxito'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Error interno al actualizar: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/users/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(summary: 'Elimina (borrado lógico) un usuario específico')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'UUID del usuario a eliminar', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 204, description: 'Usuario eliminado con éxito')]
    #[OA\Response(response: 404, description: 'Usuario no encontrado')]
    public function delete(string $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $user->setDeletedAt(new \DateTimeImmutable());
        $em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}