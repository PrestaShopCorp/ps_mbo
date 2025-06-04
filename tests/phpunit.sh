#!/bin/bash
PS_VERSION=$1
PHP_VERSION=$2

set -e

# Docker images prestashop/prestashop may be used, even if the shop remains uninstalled
echo "Pull PrestaShop files (Tag ${PS_VERSION})"

docker rm -f temp-ps || true
docker volume rm -f ps-volume || true

docker run -tid --rm -v ps-volume:/var/www/html -v $PWD:/web/module --name temp-ps prestashop/prestashop:$PS_VERSION-$PHP_VERSION

# The nightly image needs more time to unzip the PrestaShop archive
while [[ -z "$(docker exec -t temp-ps ls)" ]]; do sleep 5; done

docker exec \
  -e _PS_ROOT_DIR_=/var/www/html \
  -w /web/module \
  test-phpunit \
  sh -c " \
    echo \"Testing module v\`cat config.xml | grep '<version>' | sed 's/^.*\[CDATA\[\(.*\)\]\].*/\1/'\`\n\" && \
    ./vendor/bin/phpunit -c ./tests/phpunit.xml \
  "
