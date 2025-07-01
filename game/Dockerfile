FROM php:latest

WORKDIR /var/klodworld

COPY ./game ./game
COPY ./common ./common

RUN apt-get update && apt-get install -y mariadb-client

CMD ["php", "game/klodgame.php"]