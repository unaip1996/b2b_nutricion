FROM php:8.4-cli

# Instalamos PostgreSQL, Poppler (para convertir PDF a imagen) y Tesseract OCR (para extraer texto de la imagen)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    poppler-utils \
    tesseract-ocr \
    tesseract-ocr-spa \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Instalamos Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuramos el directorio de trabajo
WORKDIR /app