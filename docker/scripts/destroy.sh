#!/bin/sh

echo "Destroying iCows development environment..."

# Load in iCows db config
echo "Loading icows.conf..."
if [ -e ../icows.conf ]; then
  IFS="="
  while read -r name value
  do
    if [ "$name" = "LOCAL_PORT" ]; then
      export LOCAL_PORT=$value
    fi
  done < ../icows.conf
else
  echo "Cannot find '../icows.conf'. Cannot continue..."
  exit -1
fi

echo "[LOCAL_PORT=$LOCAL_PORT]"

docker-compose -p icows down -v
