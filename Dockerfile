FROM dunglas/frankenphp:php8.4-bookworm

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    libpq-dev \
    python3 \
    python3-pip \
    --no-install-recommends \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && pip3 install reportlab pypdf Pillow "qrcode[pil]" --break-system-packages \
    && rm -rf /var/lib/apt/lists/*

# Copia os arquivos do projeto
COPY . /app

WORKDIR /app

# Script de entrada que usa $PORT do Railway
RUN echo '#!/bin/sh\nexec frankenphp php-server --root /app --listen :${PORT:-8080}' > /entrypoint.sh \
    && chmod +x /entrypoint.sh

CMD ["/bin/sh", "/entrypoint.sh"]
