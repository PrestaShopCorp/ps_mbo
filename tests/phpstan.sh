#!/bin/bash
set -e

if [ $# -le 0 ]; then
  echo "No version provided. Use:"
  echo "tests/phpstan/phpstan.sh [PrestaShop_version]"
  exit 1
fi

PS_VERSION=$1
PHP_VERSION=$2
BASEDIR=$(dirname "$0")
MODULEDIR=$(cd $BASEDIR/.. && pwd)

if [ ! -f $MODULEDIR/tests/phpstan/phpstan-$PS_VERSION.neon ]; then
  echo "Configuration file for PrestaShop $PS_VERSION does not exist."
  echo "Please try another version."
  exit 2
fi

# Docker images prestashop/prestashop are used to get source files
echo "Pull PrestaShop files (Tag ${PS_VERSION})"

docker rm -f temp-ps || true
docker run -tid --rm -v ps-volume:/var/www/html -e DISABLE_MAKE=1 --name temp-ps prestashop/prestashop:$PS_VERSION-$PHP_VERSION

# Wait for docker initialization (it may be longer for containers based on branches since they must install dependencies)
until docker exec temp-ps ls /var/www/html/vendor/autoload.php 2> /dev/null; do
  echo Waiting for docker initialization...
  sleep 5
done


# Clear previous instance of the module in the PrestaShop volume
echo "Clear previous module and copy current one"
docker exec -t temp-ps rm -rf /var/www/html/modules/ps_mbo

echo "Run PHPStan using phpstan-${PS_VERSION}.neon file"
docker run --rm --volumes-from temp-ps \
       -v $PWD:/var/www/html/modules/ps_mbo \
       -e _PS_ROOT_DIR_=/var/www/html \
       -e DISABLE_MAKE=1 \
       --workdir=/var/www/html/modules/ps_mbo ghcr.io/phpstan/phpstan:1.10.45-php${PHP_VERSION} \
       analyse \
       --error-format=github \
       --configuration=/var/www/html/modules/ps_mbo/tests/phpstan/phpstan-${PS_VERSION}.neon
