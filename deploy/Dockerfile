FROM formapro/nginx-php-fpm:latest-all-exts

ADD . /app
WORKDIR /app

RUN chmod a+rwX -R /app/var
RUN chmod a+rwX -R /app/examples/11-calculator/store
RUN chmod a+rwX -R /app/examples/8-store-to-file/store