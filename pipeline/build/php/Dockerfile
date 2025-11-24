FROM registry.digitalocean.com/realsources-africa-com/php:8.2-alpine
RUN apk add bash rsync

RUN set -eux; \
	apk add --no-cache imagemagick-dev; \
    # WARNING: imagick is likely not supported on Alpine: https://github.com/Imagick/imagick/issues/328
    # https://pecl.php.net/package/imagick
    # https://github.com/Imagick/imagick/commit/5ae2ecf20a1157073bad0170106ad0cf74e01cb6 (causes a lot of build failures, but strangely only intermittent ones 🤔)
    # see also https://github.com/Imagick/imagick/pull/641
    # this is "pecl install imagick-3.7.0", but by hand so we can apply a small hack / part of the above commit
	curl -fL -o imagick.tgz 'https://pecl.php.net/get/imagick-3.7.0.tgz'; \
	echo '5a364354109029d224bcbb2e82e15b248be9b641227f45e63425c06531792d3e *imagick.tgz' | sha256sum -c -; \
	tar --extract --directory /tmp --file imagick.tgz imagick-3.7.0; \
	grep '^//#endif$' /tmp/imagick-3.7.0/Imagick.stub.php; \
	test "$(grep -c '^//#endif$' /tmp/imagick-3.7.0/Imagick.stub.php)" = '1'; \
	sed -i -e 's!^//#endif$!#endif!' /tmp/imagick-3.7.0/Imagick.stub.php; \
	grep '^//#endif$' /tmp/imagick-3.7.0/Imagick.stub.php && exit 1 || :; \
	docker-php-ext-install /tmp/imagick-3.7.0; \
	rm -rf imagick.tgz /tmp/imagick-3.7.0

RUN php -i | grep imagick


#Create fpm pools
COPY pipeline/build/php/templates/php.conf /usr/local/etc/php-fpm.d/www.conf
COPY pipeline/build//php/templates/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY pipeline/build/php/templates/php.ini /usr/local/etc/php/php.ini

# Delete unused fpm pools
RUN rm /usr/local/etc/php-fpm.d/docker.conf
RUN rm /usr/local/etc/php/php.ini-production
RUN rm /usr/local/etc/php/php.ini-development


WORKDIR /opt/code
COPY . .

RUN composer update --quiet \
        && composer install --quiet \
            && php artisan config:clear \
                && php artisan cache:clear \
                    && rm -rf pipeline

RUN chown www-data:www-data -Rf /opt/code
WORKDIR /


