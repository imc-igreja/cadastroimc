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
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

WORKDIR /app

CMD ["/docker-entrypoint.sh"]
