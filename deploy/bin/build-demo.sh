#!/usr/bin/env bash

set -x
set -e

if (( "$#" != 1 ))
then
    echo "Tag has to be provided"
    exit 1
fi

DROPLET_NAME="pvm.demo"
IMAGE_NAME="formapro/pvm-demo"

mkdir -p /tmp/pvm-demo
rm -rf /tmp/pvm-demo/*

cp -a ./* /tmp/pvm-demo
rm -rf /tmp/pvm-demo/vendor
rm -rf /tmp/pvm-demo/var/cache
rm -rf /tmp/pvm-demo/var/logs
rm -rf /tmp/pvm-demo/tests
rm -rf /tmp/pvm-demo/deploy

mkdir -p /tmp/pvm-demo/var/cache/prod
mkdir -p /tmp/pvm-demo/var/logs
chmod -R a+rwX /tmp/pvm-demo/var

(cd /tmp/pvm-demo; composer install --no-dev --prefer-dist --ignore-platform-reqs --no-scripts --optimize-autoloader --no-interaction)

git rev-parse HEAD > /tmp/pvm-demo/config/version.html
date '+%Y-%m-%d %H:%M:%S' > /tmp/pvm-demo/config/build.html

cat /tmp/pvm-demo/config/version.html;
cat /tmp/pvm-demo/config/build.html;

cp -f deploy/.env /tmp/pvm-demo

(cd /tmp/pvm-demo; docker run --rm -it --env-file "/tmp/pvm-demo/.env" -v "/tmp/pvm-demo:/app" -w "/app" "formapro/nginx-php-fpm:latest-all-exts" bin/console cache:warmup)

cp deploy/Dockerfile /tmp/pvm-demo
(cd /tmp/pvm-demo; docker build --rm --pull --force-rm --tag "$IMAGE_NAME:$1" .)

docker login --username="$DOCKER_USER" --password="$DOCKER_PASSWORD"
docker push "$IMAGE_NAME:$1"
