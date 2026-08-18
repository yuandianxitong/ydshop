FROM php:8.2-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libonig-dev libicu-dev \
        default-mysql-client \
    && docker-php-ext-install pdo_mysql zip intl mbstring \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node 20 + pnpm（构建 admin/pc/uniapp）
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g pnpm miniprogram-ci \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /workspace/server

CMD ["php", "think", "queue:work", "--queue=frontend-builds", "--tries=1", "--timeout=900", "--sleep=3"]
