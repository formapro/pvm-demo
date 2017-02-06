FROM makasim/nginx-php-fpm:latest-all-exts

RUN apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests graphviz && \
    rm -rf /var/lib/apt/lists/*

RUN mkdir -p /var/pvm/session && chown www-data -R /var/pvm
