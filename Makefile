DOCKER = $(shell docker ps 2> /dev/null)
DOCKERFILE = ./docker/docker-compose.yml

help:
	@egrep "^#" Makefile

# install                   - install PHP container et install composer dependencies with dev dependencies
install:
	docker compose -f $(DOCKERFILE) run --rm php sh -c "composer install"

# create-shop							 - create a new shop
docker-up:
	cd docker && docker-compose up -d

docker-down:
	cd docker && docker-compose down -v

# install-prod              - install PHP container et install composer dependencies without dev dependencies
install-prod:
	docker compose -f $(DOCKERFILE) run --rm php sh -c "composer install --no-dev -a"

# build-zip                 - Build the zip of the module (will not work on windows)
build-zip:
	cp -Ra $(PWD) /tmp/ps_mbo
	rm -rf /tmp/ps_mbo/.env.test
	rm -rf /tmp/ps_mbo/.php_cs.*
	rm -rf /tmp/ps_mbo/composer.*
	rm -rf /tmp/ps_mbo/.gitignore
	rm -rf /tmp/ps_mbo/deploy.sh
	rm -rf /tmp/ps_mbo/.editorconfig
	rm -rf /tmp/ps_mbo/.git
	rm -rf /tmp/ps_mbo/.github
	rm -rf /tmp/ps_mbo/_dev
	rm -rf /tmp/ps_mbo/tests
	rm -rf /tmp/ps_mbo/docker-compose.yml
	rm -rf /tmp/ps_mbo/Makefile
	mv -v /tmp/ps_mbo $(PWD)/ps_mbo
	zip -r ps_mbo.zip ps_mbo
	rm -rf $(PWD)/ps_mbo

# build-zip-prod            - Build the zip of the module in production mode (will not work on windows)
build-zip-prod: install-prod build-zip

# phpunit                   - Start phpunit
phpunit: phpunit-cleanup

ifndef DOCKER
    $(error "DOCKER is unavailable on your system")
endif
	docker run --rm -d -e PS_DOMAIN=localhost -e PS_ENABLE_SSL=0 -e PS_DEV_MODE=1 -e _PS_ADMIN_DIR_=admin -e PS_ADMIN_DIR=admin -e PS_VERSION=8.0 --name mbo-phpunit prestashop/docker-internal-images:nightly
	-docker container exec -u www-data mbo-phpunit sh -c "sleep 1 && php -d memory_limit=-1 ./bin/console prestashop:module uninstall ps_mbo"
	docker cp . mbo-phpunit:/var/www/html/modules/ps_mbo
	docker container exec -u www-data mbo-phpunit sh -c "sleep 1 && php -d memory_limit=-1 ./bin/console prestashop:module install ps_mbo"
	@docker container exec -u www-data mbo-phpunit sh -c "echo \"Testing module v\`cat /var/www/html/modules/ps_mbo/config.xml | grep '<version>' | sed 's/^.*\[CDATA\[\(.*\)\]\].*/\1/'\`\n\""
	docker container exec -u www-data --workdir /var/www/html/ mbo-phpunit php -d date.timezone=UTC -d memory_limit=-1 ./modules/ps_mbo/vendor/phpunit/phpunit/phpunit -c modules/ps_mbo/tests/phpunit.xml
	@echo phpunit passed

phpunit-cleanup:
	-docker container rm -f mbo-phpunit
