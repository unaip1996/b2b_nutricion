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
use Symfony\Component\Security\Http\Attribute\IsGranted;

readonly class PatientController
{
    public function __construct(
        private PatientRepository $patientRepository,
    ) {}

    #[Route('/api/patients', name: 'api_patients_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(): JsonResponse
    {
        /** @var Patient[] $patients */
        $patients = $this->patientRepository->findAll();

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

        // Obtener última medición para peso y altura
        $weight = "";
        $height = "";
        if (method_exists($patient, 'getMeasurements')) {
            $measurements = $patient->getMeasurements();
            $latest = $measurements->count() > 0 ? $measurements->last() : null;
            if ($latest) {
                $weight = (string) $latest->getWeight();
                $height = (string) $latest->getHeight();
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
            'pathologies' => $patient->getPathologies(),
            'goal' => $patient->getNutritionalGoal(),
            'notes' => $patient->getClinicalNotes(),
            'allergies' => $allergies,
        ]], Response::HTTP_OK);
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

            $age = (int) ($payload['age'] ?? 30);
            $patient->setBirthDate(new \DateTimeImmutable('-' . $age . ' years'));

            if (!empty($payload['weight'])) {
                $measurement = new Measurement();
                $measurement->setWeight((float) $payload['weight']);
                
                if (method_exists($measurement, 'setHeight')) {
                    $measurement->setHeight((float) ($payload['height'] ?? 0.0));
                }

                // Recibir el dato dinámico del front o usar valor seguro para el NOT NULL
                $measurement->setBodyFatPercentage((float) ($payload['bodyFatPercentage'] ?? 0.0));
                
                // Garantizar los demás campos NOT NULL de la base de datos
                $measurement->setMuscleMass(0.0);
                $measurement->setWaistCircumference(0.0);
                $measurement->setTakenAt(new \DateTimeImmutable());

                $patient->addMeasurement($measurement);
                $em->persist($measurement);
            }

            if (!empty($payload['allergies']) && is_array($payload['allergies'])) {
                $allergyRepo = $em->getRepository(Allergy::class);
                foreach ($payload['allergies'] as $name) {
                    $allergy = $allergyRepo->findOneBy(['name' => $name]);
                    if (!$allergy) {
                        $allergy = new Allergy();
                        $allergy->setName($name);
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
}
