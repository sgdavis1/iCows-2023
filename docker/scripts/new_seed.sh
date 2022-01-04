#!/bin/sh

echo "Creating a new iCows seed"

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
    fi
  done < ../icows.conf
else
  echo "Cannot find '../icows.conf'. Please create your configuration file!!"
  exit -1;
fi

echo "[LOCAL_PORT=$LOCAL_PORT | MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD | MYSQL_USER=$MYSQL_USER | MYSQL_PASSWORD=$MYSQL_PASSWORD]"

cd ..
docker exec icows_db mysqldump -u$MYSQL_USER -p$MYSQL_PASSWORD icows_d9 > seed.tmp.sql
cat seed.tmp.sql | grep -v "INSERT INTO \`cache" > seed.sql
rm seed.tmp.sql
