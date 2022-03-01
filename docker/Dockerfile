# Make sure to rebuild your images if a security update is released on the drupal/7.x branch
FROM drupal:9.3
ENV OPCACHE_CONFIG /usr/local/etc/php/conf.d/opcache-recommended.ini

COPY docker-entrypoint.sh /docker-entrypoint.sh

# Remove opcache 60 second delay
RUN sed -i "s/opcache.revalidate/\;opcache.revalidate/" ${OPCACHE_CONFIG} \
  && echo opcache.revalidate_freq=0 >> ${OPCACHE_CONFIG}

# Create concrete php config file
ARG PHP_ENV=${PHP_ENV}
RUN cp /usr/local/etc/php/php.ini-${PHP_ENV} /usr/local/etc/php/php.ini

# Add phpinfo page for debugging purposes
RUN cd /var/www/html && touch phpinfo.php \
    && echo "<?php print phpinfo() ?>" >> phpinfo.php

CMD /docker-entrypoint.sh

RUN echo 'adding php mailer'
RUN composer require phpmailer/phpmailer