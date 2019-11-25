FROM php:7.3-fpm

RUN apt-get update && apt-get install -y git-core

RUN pecl install xdebug && \
    docker-php-ext-enable xdebug

RUN echo "xdebug.remote_host=docker.for.mac.host.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
        echo 'xdebug.file_link_format="phpstorm://open?url=file://%%f&line=%%l"' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
        echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
        echo "xdebug.remote_autostart=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
        echo "xdebug.remote_connect_back=0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
        echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
        echo "xdebug.remote_port=9090" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
        echo "xdebug.max_nesting_level=10000" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
        echo "xdebug.remote_handler=dbgp" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
        echo "xdebug.remote_mode=req" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /srv/www
