FROM php:7.4-cli-buster
RUN apt-get update \
 && apt-get install -y \
    curl \
    tini \
    libicu-dev \
 && curl -sS https://getcomposer.org/installer \
  | php -- --install-dir=/usr/local/bin --filename=composer \
 && docker-php-ext-install intl
WORKDIR /app
COPY docker-entrypoint.sh /docker-entrypoint.sh
ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["composer"]
