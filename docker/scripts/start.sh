#!/bin/sh

echo "Starting up iCows development environment"

# Load in iCows db config
echo
echo "Loading icows.conf..."
if [ -e ../icows.conf ]; then
  IFS="="
  while read -r name value
  do
    if [ "$name" = "MYSQL_USER" ]; then
      export MYSQL_USER=$value
    elif [ "$name" = "MYSQL_PASSWORD" ]; then
      export MYSQL_PASSWORD=$value
    elif [ "$name" = "MYSQL_ROOT_PASSWORD" ]; then
      export MYSQL_ROOT_PASSWORD=$value
    elif [ "$name" = "LOCAL_PORT" ]; then
      export LOCAL_PORT=$value
    elif [ "$name" = "BASE_URL" ]; then
      export BASE_URL=$value
    elif [ "$name" = "PHP_ENV" ]; then
      export PHP_ENV=$value
    fi
  done < ../icows.conf
else
  echo "Cannot find '../icows.conf'. Please create your configuration file!!"
  exit -1;
fi

echo "[LOCAL_PORT=$LOCAL_PORT | MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD | MYSQL_USER=$MYSQL_USER | MYSQL_PASSWORD=$MYSQL_PASSWORD]"
if [ -n "$BASE_URL" ]; then
  echo "Overridden Drupal \$base_url [BASE_URL=$BASE_URL]"
fi
if [ -n "$PHP_ENV" ]; then
  echo "Specified PHP environment [PHP_ENV=$PHP_ENV]"
else
  export PHP_ENV=development
  echo "Defaulting to development PHP environment..."
fi

# Install the modules (if needed)
if [ $(find ../../src/all/modules/ -maxdepth 1 -type d -print| wc -l) -gt 1 ]; then 
  echo
  echo "** It looks like the required modules are already installed **"
  echo "If this is incorrect, remove all directories from '../../src/all/modules' and restart..."
  echo
else
  echo
  echo "Running 'install_deps.sh'..."
  ./install_deps.sh
  echo
fi

# Make sure we have a writeable files directory
mkdir -p ../../src/default/files/
chmod 777 ../../src/default/files/

# Startup docker compose
docker-compose -p icows up --build $1 $2 $3
