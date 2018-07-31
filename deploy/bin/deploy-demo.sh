#!/usr/bin/env bash

set -x
set -e

DROPLET_NAME="pvm.demo"

scp "deploy/docker-compose.yml" "root@$DROPLET_NAME:/pvm/docker-compose.yml"
scp "deploy/.env" "root@$DROPLET_NAME:/pvm/.env"
scp "deploy/traefik.toml" "root@$DROPLET_NAME:/pvm/traefik.toml"
ssh "root@$DROPLET_NAME" "cd /pvm && docker stack deploy --compose-file docker-compose.yml pvm_demo"