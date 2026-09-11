# 🧠 NutriSupport AI - B2B Clinical Nutritional Support Platform

![PHP Version](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php)
![Symfony Version](https://img.shields.io/badge/Symfony-8.0-000000?style=flat-square&logo=symfony)
![Next.js](https://img.shields.io/badge/Next.js-16-000000?style=flat-square&logo=next.js)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL_pgvector-336791?style=flat-square&logo=postgresql)
![Docker](https://img.shields.io/badge/Docker-Infrastucture-2496ED?style=flat-square&logo=docker)

Corporate (B2B) clinical decision support platform for nutrition professionals. The system automates the design of personalized dietary plans by integrating Artificial Intelligence through **Retrieval-Augmented Generation (RAG)**, mitigating the risk of algorithmic hallucinations and confining medical reasoning to validated scientific literature.

---

## 📑 Table of Contents
- [🧠 NutriSupport AI - B2B Clinical Nutritional Support Platform](#-nutrisupport-ai---b2b-clinical-nutritional-support-platform)
  - [📑 Table of Contents](#-table-of-contents)
  - [🚀 Key Features](#-key-features)
  - [🏗 System Architecture](#-system-architecture)
  - [⚙️ Prerequisites](#️-prerequisites)
  - [🛠 Installation and Deployment (Local)](#-installation-and-deployment-local)
  - [💻 Usage and Endpoints](#-usage-and-endpoints)
  - [🧪 Testing and Code Quality](#-testing-and-code-quality)
  - [👨‍💻 Author](#-author)

---

## 🚀 Key Features

* **RAG Inference Engine:** Ingestion of medical literature in PDF format, semantic chunking, and vectorization using OpenAI's `text-embedding-3-small` model.
* **Vector Search:** Knowledge persistence and high-efficiency retrieval via cosine similarity using **PostgreSQL + pgvector**.
* **Deterministic Generation:** Strict injection of medical context and patient history (allergies, biometrics, pathologies) into dynamic prompts for `GPT-4o-mini`.
* **Domain-Driven Design (DDD):** Core clinical logic completely isolated from the infrastructure, ensuring mathematical and medical integrity.
* **Reactive & Secure SPA:** B2B interface developed in Next.js featuring a clinical design system (Tailwind CSS) and Role-Based Access Control (JWT).

---

## 🏗 System Architecture

The backend is built under the **Hexagonal Architecture (Ports and Adapters)** pattern coupled with TDD and DDD methodologies.

* **Domain Layer:** Pure clinical entities (`Patient`, `DietaryPlan`, `Measurement`) with no external dependencies.
* **Application Layer:** Use Case orchestration (e.g., `GenerateClinicalDietUseCase`, `IngestClinicalDocumentUseCase`).
* **Infrastructure Layer:** Symfony 8.0 framework, Doctrine repositories, API Platform endpoints, and external communication adapters (OpenAI API).

All infrastructure is encapsulated in **immutable Docker containers**, guaranteeing exact parity between development and production environments.

---

## ⚙️ Prerequisites

Ensure you have the following components installed on your machine:
* [Docker](https://docs.docker.com/get-docker/) and Docker Compose.
* Git.
* A valid [OpenAI](https://platform.openai.com/) API Key.

---

## 🛠 Installation and Deployment (Local)

Follow these steps to spin up the entire platform in an isolated environment.

**1. Clone the repository**
```bash
git clone https://github.com/unaip1996/b2b_nutricion.git
cd b2b_nutricion
```

**2. Environment Variables Configuration**
Copy the base configuration file and add your credentials (especially the OpenAI key):
```bash
cp .env.example .env
# Edit the .env file and insert your OPENAI_API_KEY
```

**3. Spin up the Immutable Infrastructure**
Build and start the Nginx server, PHP-FPM, PostgreSQL (pgvector), and the Next.js Frontend:
```bash
docker-compose up -d --build
```

**4. Prepare the Backend (Dependencies, DB, and Security)**
Access the PHP container to initialize the application:
```bash
# Install PHP dependencies
docker exec -it nutri_php composer install

# Generate SSL keys for the JWT authentication system
docker exec -it nutri_php php bin/console lexik:jwt:generate-keypair

# Create the vector database schema and run migrations
docker exec -it nutri_php php bin/console doctrine:migrations:migrate -n
```

**5. Create Administrator User (Optional)**
```bash
docker exec -it nutri_php php bin/console app:create-admin admin@clinica.com password123
```

---

## 💻 Usage and Endpoints

Once the containers are running stably, the services are mapped to the following local ports:

* **B2B Platform (Next.js Frontend):** [http://localhost:3000](http://localhost:3000) *(or your configured port)*
* **Base RESTful API:** `http://localhost:8000/api`
* **Interactive Documentation (Swagger OpenAPI):** [http://localhost:8000/api/doc](http://localhost:8000/api/doc)

---

## 🧪 Testing and Code Quality

The project follows a strict **Test-Driven Development (TDD)** methodology with Code Coverage exceeding **95%** in the core clinical domain.

To run the complete suite of unit and integration tests:
```bash
docker exec -it nutri_php php bin/phpunit
```

To generate the visual code coverage report (requires Xdebug):
```bash
docker exec -e XDEBUG_MODE=coverage -it nutri_php php bin/phpunit --coverage-text
```

---

## 👨‍💻 Author

**Unai Perez De la Torre**  
*Full Stack Software Engineer*  
Project developed as a Bachelor's Thesis (TFG) in Computer Engineering - Universidad Internacional de La Rioja (UNIR).