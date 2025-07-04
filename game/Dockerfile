FROM php:latest

# Installation des dépendances système + MySQLi
RUN apt-get update && apt-get install -y \
    mariadb-client \
	procps \
    && docker-php-ext-install mysqli \
	&& docker-php-ext-install pcntl \
    && rm -rf /var/lib/apt/lists/*

# Définir le répertoire de travail
WORKDIR /var/klodworld

# Copier les sources
COPY ./game ./game
COPY ./common ./common

# Point d’entrée dans le répertoire game
WORKDIR /var/klodworld/game

# Commande par défaut
CMD ["php", "klodgame.php"]
