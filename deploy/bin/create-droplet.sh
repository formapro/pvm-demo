#!/usr/bin/env bash

set -x
set -e

if (( "$#" != 1 ))
then
    echo "The droplet name has to be provided"
    exit 1
fi
DROPLET_NAME="$1"

if [[ -z "$DOTOKEN" ]]; then
    # get it from https://cloud.digitalocean.com/settings/security
    echo "Must provide DOTOKEN in environment" 1>&2
    exit 1
fi

if [[ -z "$SSH_KEY_FINGERPRINT" ]]; then
    # get it from https://cloud.digitalocean.com/settings/security
    echo "Must provide SSH_KEY_FINGERPRINT in environment" 1>&2
    exit 1
fi

docker-machine create \
    --driver digitalocean \
    --digitalocean-image ubuntu-16-04-x64 \
    --digitalocean-access-token "$DOTOKEN" \
    --digitalocean-region "ams3" \
    --digitalocean-size "s-1vcpu-1gb" \
    --digitalocean-ssh-key-fingerprint "$SSH_KEY_FINGERPRINT" \
    "$DROPLET_NAME"

docker-machine ssh "$DROPLET_NAME" \
"docker swarm init --advertise-addr `docker-machine ip $DROPLET_NAME`";

docker-machine ssh "$DROPLET_NAME" "mkdir -p /pvm"