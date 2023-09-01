#!/bin/bash

# migration scripe
MIGRATION_FOLDER=[path to migration folder]
ENV_FILE=[path to .env file]
MYSQL_CONTAINER_NAME=[mysql container name]
DOCKER_NETWORK=docker_default

CURRENT_IP=$(sudo docker exec $MYSQL_CONTAINER_NAME cat /etc/hosts | tail -n 1 | cut -d$'\t' -f 1)
ENV_IP=$(more $ENV_FILE | grep DATABASE_HOST= | cut -d '=' -f 2)

echo 'Current Connection IP -> ' $CURRENT_IP
echo 'Env File IP -> ' $ENV_IP

sudo docker run -it --rm -v $MIGRATION_FOLDER:/usr/src/nasgrate/data --net=$DOCKER_NETWORK --env-file=$ENV_FILE -e DATABASE_HOST=$CURRENT_IP dlevsha/nasgrate "$@"