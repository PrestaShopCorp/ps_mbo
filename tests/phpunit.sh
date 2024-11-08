#!/bin/sh

PS_VERSION=$1
docker rm -f test-phpunit || true
docker volume rm -f ps-volume || true

docker run -tid --rm -v ps-volume:/var/www/html -v ${PWD}:/var/www/html/modules/ps_mbo --name test-phpunit prestashop/prestashop:$PS_VERSION

until docker exec test-phpunit ls /var/www/html/vendor/autoload.php 2> /dev/null; do
  echo Waiting for docker initialization...
  sleep 5
done

docker exec \
  -e PS_DOMAIN=localhost \
  -e PS_ENABLE_SSL=0 \
  -e PS_DEV_MODE=1 \
  -w /var/www/html/modules/ps_mbo \
  test-phpunit \
  sh -c " \
    echo \"Testing module v\`cat config.xml | grep '<version>' | sed 's/^.*\[CDATA\[\(.*\)\]\].*/\1/'\`\n\" && \
    ./vendor/bin/phpunit -c ./tests/phpunit.xml \
        "
echo phpunit passed
