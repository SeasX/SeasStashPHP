FROM php:7.2-cli-alpine

MAINTAINER albert <63851587@qq.com>

RUN apk update && apk add --no-cache --virtual .build-deps \
    g++ \
    gcc \
    autoconf \
    make \
    libmcrypt-dev \
    gmp-dev \
    icu-dev \
    zlib-dev \
    musl \
    libc-dev \
    linux-headers \
    libaio-dev \
    && apk add --no-cache \
    libcurl \
    tzdata \
    pcre-dev \
    perl-dev \
    nghttp2-dev \
    openssl-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libmcrypt-dev \

    && cp /usr/share/zoneinfo/Asia/Shanghai /etc/localtime \
    && echo 'Asia/Shanghai' > /etc/timezone \

    && docker-php-ext-install pdo_mysql bcmath sockets && pecl install seaslog msgpack\
    && docker-php-ext-configure gd --with-freetype-dir --with-jpeg-dir \
    && docker-php-ext-install -j$(nproc) gd \

    && wget https://github.com/swoole/swoole-src/archive/v4.2.11.tar.gz -O swoole.tar.gz \
    && mkdir -p swoole \
    && tar -xf swoole.tar.gz -C swoole --strip-components=1 \
    && rm swoole.tar.gz \
    && ( \
        cd swoole \
        && phpize \
        && ./configure --enable-async-redis --enable-mysqlnd --enable-openssl --enable-http2 \
        && make -j$(nproc) \
        && make install \
    ) \
    && rm -r swoole \
    && docker-php-ext-enable swoole seaslog msgpack && pecl clear-cache\
    && echo "swoole.fast_serialize=On" >> /usr/local/etc/php/conf.d/docker-php-ext-swoole-serialize.ini\

    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man \
    && mkdir -p /data

ADD ./docker/docker-php-ext-seaslog.ini /usr/local/etc/php/conf.d/docker-php-ext-seaslog.ini

WORKDIR /data

EXPOSE 80

CMD ["php", "bin/SeasStash","start"]
