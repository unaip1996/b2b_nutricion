<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Infrastructure\Entity\Patient;
use App\Infrastructure\Repository\PatientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

readonly class PatientController
{
    public function __construct(
        private PatientRepository $patientRepository,
    ) {
    }

    #[Route('/api/patients', name: 'api_patients_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(): JsonResponse
    {
        /** @var Patient[] $patients */
        $patients = $this->patientRepository->findAll();

        // Serializamos manualmente para evitar referencias circulares con DietaryPlans, Measurements, etc.
        // y para tener control absoluto sobre el contrato (payload) de nuestra API.
        $data = array_map(static function (Patient $patient) {
            return [
                'id' => (string) $patient->getId(),
                'medicalHistoryNumber' => $patient->getMedicalHistoryNumber(),
                'gender' => $patient->getGender(),
                'birthDate' => $patient->getBirthDate()?->format('Y-m-d'),
                'activeStatus' => $patient->isActiveStatus(),
                // Si necesitas más campos, agrégalos aquí
            ];
        }, $patients);

        return new JsonResponse(['data' => $data], Response::HTTP_OK);
    }

    #[Route('/api/patients', name: 'api_patients_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            // 4. Leer y decodificar el contenido JSON
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            // Validación básica de campos obligatorios
            if (empty($payload['name'])) {
                return new JsonResponse(['error' => 'El campo "name" es obligatorio.'], Response::HTTP_BAD_REQUEST);
            }

            // 5. Mapeo a nueva instancia de la entidad
            $patient = new Patient();
            
            // NOTA: Debes agregar estas propiedades y setters a tu clase Patient
            $patient->setName((string) $payload['name']);
            $patient->setAge((int) ($payload['age'] ?? 0));
            $patient->setBmi((string) ($payload['bmi'] ?? ''));
            $patient->setCondition((string) ($payload['condition'] ?? ''));
            $patient->setIsAllergy((bool) ($payload['isAllergy'] ?? false));
            $patient->setGoal((string) ($payload['goal'] ?? ''));

            // Asignación de fallbacks para las propiedades antiguas requeridas por la BD
            if (method_exists($patient, 'setMedicalHistoryNumber')) {
                $patient->setMedicalHistoryNumber('PAC-' . random_int(10000, 99999));
                $patient->setGender('No especificado');
                $patient->setBirthDate(new \DateTimeImmutable('-' . ($payload['age'] ?? 30) . ' years'));
            }

            // 6. Persistir y guardar en BD
            $em->persist($patient);
            $em->flush();

            // 7. Devolver 201 con los datos insertados
            return new JsonResponse(['data' => [
                'id' => (string) $patient->getId(),
                'name' => $payload['name'],
                'age' => $payload['age'] ?? null,
                'bmi' => $payload['bmi'] ?? null,
                'condition' => $payload['condition'] ?? null,
                'isAllergy' => $payload['isAllergy'] ?? false,
                'goal' => $payload['goal'] ?? null,
            ]], Response::HTTP_CREATED);

        } catch (\JsonException $e) { // 8. Control de JSON malformado
            return new JsonResponse(['error' => 'JSON malformado o inválido.'], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {     // Fallos generales de DB o ejecución
            return new JsonResponse(['error' => 'Error interno al crear el paciente: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}