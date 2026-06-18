<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Infrastructure\Entity\Allergy;
use App\Infrastructure\Entity\Measurement;
use App\Infrastructure\Entity\Patient;
use App\Infrastructure\Repository\PatientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

readonly class PatientController
{
    public function __construct(
        private PatientRepository $patientRepository,
        private AuthorizationCheckerInterface $authChecker, // Interfaz limpia de permisos
        private TokenStorageInterface $tokenStorage,
    ) {}

    #[Route('/api/patients', name: 'api_patients_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(): JsonResponse
    {
        /** @var Patient[] $patients */
        if ($this->authChecker->isGranted('ROLE_ADMIN')) {
            // ✅ El Admin ve el panel de control global de la clínica
            $patients = $this->patientRepository->findAllActive();
        } else {
            // ✅ El Nutricionista solo ve su cartera de pacientes
            $user = $this->tokenStorage->getToken()?->getUser();
            $profile = $user->getNutritionistProfile();
            
            if (!$profile) {
                // Si por algún error un nutricionista no tiene perfil, devolvemos array vacío por seguridad
                return new JsonResponse(['data' => []], Response::HTTP_OK);
            }
            
            $patients = $this->patientRepository->findActiveByProfile($profile);
        }

        $data = array_map(static function (Patient $patient) {
            $age = $patient->getBirthDate() !== null ? $patient->getBirthDate()->diff(new \DateTimeImmutable())->y : null;
            
            // Calculamos el IMC basándonos en la última medición
            $bmi = null;
            if (method_exists($patient, 'getMeasurements')) {
                $measurements = $patient->getMeasurements();
                $latest = $measurements->count() > 0 ? $measurements->last() : null;
                
                if ($latest && $latest->getWeight() > 0 && method_exists($latest, 'getHeight') && $latest->getHeight() > 0) {
                    $weight = $latest->getWeight();
                    $heightInMeters = $latest->getHeight() / 100;
                    $bmi = round($weight / ($heightInMeters ** 2), 1);
                }
            }

            return [
                'id' => (string) $patient->getId(),
                'medicalHistoryNumber' => $patient->getMedicalHistoryNumber(),
                'name' => $patient->getName(),
                'gender' => $patient->getGender(),
                'birthDate' => $patient->getBirthDate()?->format('Y-m-d'),
                'activeStatus' => $patient->isActiveStatus(),
                'email' => $patient->getEmail(),
                'phone' => $patient->getPhone(),
                'age' => $age,
                'bmi' => $bmi,
                'condition' => method_exists($patient, 'getPathologies') ? $patient->getPathologies() : null,
                'goal' => method_exists($patient, 'getNutritionalGoal') ? $patient->getNutritionalGoal() : null,
                'isAllergy' => method_exists($patient, 'getAllergies') ? count($patient->getAllergies()) > 0 : false,
            ];
        }, $patients);

        return new JsonResponse(['data' => $data], Response::HTTP_OK);
    }

    #[Route('/api/patients/{id}', name: 'api_patients_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(string $id): JsonResponse
    {
        $patient = $this->patientRepository->find($id);

        if (!$patient) {
            return new JsonResponse(['error' => 'Paciente no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $age = $patient->getBirthDate() !== null ? $patient->getBirthDate()->diff(new \DateTimeImmutable())->y : 30;

        // Obtener última medición para peso, altura y cálculo de IMC
        $weight = "";
        $height = "";
        $bmi = null;
        if (method_exists($patient, 'getMeasurements')) {
            $measurements = $patient->getMeasurements();
            $latest = $measurements->count() > 0 ? $measurements->last() : null;
            if ($latest) {
                $weight = (string) $latest->getWeight();
                $height = (string) $latest->getHeight();
                
                if ($latest->getWeight() > 0 && method_exists($latest, 'getHeight') && $latest->getHeight() > 0) {
                    $heightInMeters = $latest->getHeight() / 100;
                    $bmi = round($latest->getWeight() / ($heightInMeters ** 2), 1);
                }
            }
        }

        $allergies = [];
        if (method_exists($patient, 'getAllergies')) {
            foreach ($patient->getAllergies() as $allergy) {
                $allergies[] = $allergy->getName();
            }
        }

        return new JsonResponse(['data' => [
            'name' => $patient->getName(),
            'age' => (string) $age,
            'gender' => $patient->getGender(),
            'phone' => $patient->getPhone(),
            'email' => $patient->getEmail(),
            'weight' => $weight,
            'height' => $height,
            'bmi' => (string) $bmi, // Devolvemos el dato calculado
            'pathologies' => $patient->getPathologies(),
            'goal' => $patient->getNutritionalGoal(),
            'notes' => $patient->getClinicalNotes(),
            'allergies' => $allergies,
        ]], Response::HTTP_OK);
    }

    #[Route('/api/patients/{id}', name: 'api_patients_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(string $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $patient = $this->patientRepository->find($id);
            if (!$patient) {
                return new JsonResponse(['error' => 'Paciente no encontrado'], Response::HTTP_NOT_FOUND);
            }

            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            // Actualizar datos base
            $patient->setName($payload['name'] ?? $patient->getName());
            $patient->setGender($payload['gender'] ?? $patient->getGender());
            $patient->setEmail($payload['email'] ?? $patient->getEmail());
            $patient->setPhone($payload['phone'] ?? $patient->getPhone());
            $patient->setPathologies($payload['pathologies'] ?? $patient->getPathologies());
            $patient->setNutritionalGoal($payload['goal'] ?? $patient->getNutritionalGoal());
            $patient->setClinicalNotes($payload['notes'] ?? $patient->getClinicalNotes());

            if (!empty($payload['age'])) {
                $age = (int) $payload['age'];
                $patient->setBirthDate(new \DateTimeImmutable('-' . $age . ' years'));
            }

            // --- LÓGICA REFINADA DE MÉTRICAS ---
            $payloadWeight = isset($payload['weight']) ? (float) $payload['weight'] : null;
            $payloadHeight = isset($payload['height']) ? (float) $payload['height'] : null;

            $measurements = method_exists($patient, 'getMeasurements') ? $patient->getMeasurements() : null;
            $latest = ($measurements && $measurements->count() > 0) ? $measurements->last() : null;

            $lastWeight = $latest ? clone $latest : null;

            // Condición estricta: Solo creamos si no había ninguna, o si el peso/altura que llega es diferente al último.
            if (!$latest || ($payloadWeight !== null && $payloadWeight !== $latest->getWeight()) || ($payloadHeight !== null && $payloadHeight !== $latest->getHeight())) {
                
                $measurement = new Measurement();
                
                // Actualizamos peso y altura
                $measurement->setWeight($payloadWeight ?? ($latest ? $latest->getWeight() : 0.0));
                if (method_exists($measurement, 'setHeight')) {
                    $measurement->setHeight($payloadHeight ?? ($latest ? $latest->getHeight() : 0.0));
                }

                // Heredar o rellenar los "datos calculados" y secundarios para que no den error de base de datos
                if (method_exists($measurement, 'setBodyFatPercentage')) {
                    $measurement->setBodyFatPercentage((float) ($payload['bodyFatPercentage'] ?? ($latest ? $latest->getBodyFatPercentage() : 0.0)));
                }
                if (method_exists($measurement, 'setMuscleMass')) {
                    $measurement->setMuscleMass((float) ($payload['muscleMass'] ?? ($latest ? $latest->getMuscleMass() : 0.0)));
                }
                if (method_exists($measurement, 'setWaistCircumference')) {
                    $measurement->setWaistCircumference((float) ($payload['waistCircumference'] ?? ($latest ? $latest->getWaistCircumference() : 0.0)));
                }
                if (method_exists($measurement, 'setTakenAt')) {
                    $measurement->setTakenAt(new \DateTimeImmutable());
                }

                $patient->addMeasurement($measurement);
                $em->persist($measurement);
            }

            // Actualizar alergias
            if (isset($payload['allergies']) && is_array($payload['allergies'])) {
                foreach ($patient->getAllergies() as $existingAllergy) {
                    $patient->removeAllergy($existingAllergy);
                }
                
                $allergyRepo = $em->getRepository(Allergy::class);
                foreach ($payload['allergies'] as $name) {
                    $allergy = $allergyRepo->findOneBy(['name' => $name]);
                    if (!$allergy) {
                        $allergy = new Allergy();
                        if (method_exists($allergy, 'setName')) $allergy->setName($name);
                        $em->persist($allergy);
                    }
                    $patient->addAllergy($allergy);
                }
            }

            $em->flush();

            return new JsonResponse(['message' => 'Paciente actualizado con éxito'], Response::HTTP_OK);

        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Error interno: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/patients', name: 'api_patients_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $patient = new Patient();

            $patient->setName($payload['name'] ?? 'Paciente Anónimo');
            $patient->setGender($payload['gender'] ?? 'No especificado');
            $patient->setEmail($payload['email'] ?? null);
            $patient->setPhone($payload['phone'] ?? null);
            $patient->setPathologies($payload['pathologies'] ?? null);
            $patient->setNutritionalGoal($payload['goal'] ?? null);
            $patient->setClinicalNotes($payload['notes'] ?? null);

            $patient->setMedicalHistoryNumber('PAC-' . random_int(10000, 99999));

            $user = $this->security->getUser();
            $profile = $user->getNutritionistProfile();
            
            if ($profile && method_exists($patient, 'setNutritionistProfile')) {
                $patient->setNutritionistProfile($profile);
            }

            $age = (int) ($payload['age'] ?? 30);
            $patient->setBirthDate(new \DateTimeImmutable('-' . $age . ' years'));

            if (!empty($payload['weight'])) {
                $measurement = new Measurement();
                $measurement->setWeight((float) $payload['weight']);
                
                if (method_exists($measurement, 'setHeight')) {
                    $measurement->setHeight((float) ($payload['height'] ?? 0.0));
                }

                // Recibir el dato dinámico del front o usar valor seguro para el NOT NULL
                if (method_exists($measurement, 'setBodyFatPercentage')) {
                    $measurement->setBodyFatPercentage((float) ($payload['bodyFatPercentage'] ?? 0.0));
                }
                if (method_exists($measurement, 'setMuscleMass')) {
                    $measurement->setMuscleMass((float) ($payload['muscleMass'] ?? 0.0));
                }
                if (method_exists($measurement, 'setWaistCircumference')) {
                    $measurement->setWaistCircumference((float) ($payload['waistCircumference'] ?? 0.0));
                }
                if (method_exists($measurement, 'setTakenAt')) {
                    $measurement->setTakenAt(new \DateTimeImmutable());
                }

                $patient->addMeasurement($measurement);
                $em->persist($measurement);
            }

            if (!empty($payload['allergies']) && is_array($payload['allergies'])) {
                $allergyRepo = $em->getRepository(Allergy::class);
                foreach ($payload['allergies'] as $name) {
                    $allergy = $allergyRepo->findOneBy(['name' => $name]);
                    if (!$allergy) {
                        $allergy = new Allergy();
                        if (method_exists($allergy, 'setName')) $allergy->setName($name);
                        $em->persist($allergy);
                    }
                    $patient->addAllergy($allergy);
                }
            }

            $em->persist($patient);
            $em->flush();

            return new JsonResponse(['message' => 'Paciente creado con éxito', 'id' => (string) $patient->getId()], Response::HTTP_CREATED);
        } catch (\JsonException $e) {
            return new JsonResponse(['error' => 'JSON malformado o inválido.'], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Error interno al crear el paciente: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/patients/{id}', name: 'api_patients_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(string $id, EntityManagerInterface $em): JsonResponse
    {
        try {
            $patient = $this->patientRepository->find($id);
            if (!$patient) {
                return new JsonResponse(['error' => 'Paciente no encontrado'], Response::HTTP_NOT_FOUND);
            }

            $now = new \DateTimeImmutable();
            
            // Soft delete del paciente
            $patient->setDeletedAt($now);
            $patient->setActiveStatus(false);

            // Soft delete cascade en Measurements relacionados
            if (method_exists($patient, 'getMeasurements')) {
                foreach ($patient->getMeasurements() as $measurement) {
                    if (method_exists($measurement, 'setDeletedAt')) {
                        $measurement->setDeletedAt($now);
                    }
                }
            }

            // Soft delete cascade en DietaryPlans relacionados (si aplica en tu esquema)
            if (method_exists($patient, 'getDietaryPlans')) {
                foreach ($patient->getDietaryPlans() as $plan) {
                    if (method_exists($plan, 'setDeletedAt')) {
                        $plan->setDeletedAt($now);
                    }
                }
            }

            $em->flush();
            return new JsonResponse(['message' => 'Paciente y datos clínicos vinculados eliminados correctamente (Soft Delete)'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Error al ejecutar borrado lógico: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}