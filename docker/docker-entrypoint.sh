#!/usr/bin/env sh

# This script can likely be improved to support 'docker secret'


# Setup database credentials
if [ ! -s /var/www/html/sites/default/settings.php ]; then
  # Ensure we have values
  if [ -n "$MYSQL_USER" ] && [ -n "$MYSQL_PASSWORD" ] ; then
    # Start from the defaults, removing the empty database value
    cp /var/www/html/sites/default/default.settings.php /var/www/html/sites/default/settings.php
    sed -i "/$databases = array();/d" /var/www/html/sites/default/settings.php

    # Append our credentials into the default settings.php
    cat >> /var/www/html/sites/default/settings.php << EOF
\$databases['default']['default'] = array (
   'database' => 'icows_d9',
   'username' => '$MYSQL_USER',
   'password' => '$MYSQL_PASSWORD',
   'prefix' => '',
   'host' => 'icows_db',
   'port' => '3306',
   'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
   'driver' => 'mysql',
);
EOF

    # Append BaseURL if present in config
    if [ -n "$BASE_URL" ] ; then
      echo "\$base_url = '$BASE_URL';" >> /var/www/html/sites/default/settings.php
    fi

    # Remove most of the default 'sites' to prepare for links
    rm -rf /var/www/html/sites/all /var/www/html/sites/default/modules  /var/www/html/sites/default/themes /var/www/html/sites/default/libraries /var/www/html/sites/files

    # Replace with links
    ln -s /src/all /var/www/html/sites/all
    ln -s /src/default/modules /var/www/html/sites/default/modules
    ln -s /src/default/themes /var/www/html/sites/default/themes
    ln -s /src/default/libraries /var/www/html/sites/default/libraries
    ln -s /src/default/files /var/www/html/sites/default/files
  else
    echo "You must set the 'MYSQL_USER' and 'MYSQL_PASSWORD' values"
    exit -1
  fi
fi

# Startup server
apache2-foreground

