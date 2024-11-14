#!/bin/sh

PS_VERSION=$1

# Clean container
docker rm -f test-phpunit || true
docker volume rm -f ps-volume || true

docker run -e DISABLE_MAKE=1 -tid --rm -v ps-volume:/var/www/html -v $PWD:/web/module --name test-phpunit prestashop/prestashop:$PS_VERSION

until docker exec test-phpunit ls /var/www/html/vendor/autoload.php 2> /dev/null; do
  echo Waiting for docker initialization...
  sleep 5
done

docker exec \
  -e _PS_ROOT_DIR_=/var/www/html \
  -w /web/module \
  test-phpunit \
  sh -c " \
    echo \"Testing module v\`cat config.xml | grep '<version>' | sed 's/^.*\[CDATA\[\(.*\)\]\].*/\1/'\`\n\" && \
    ./vendor/bin/phpunit -c ./tests/phpunit.xml \
  "
