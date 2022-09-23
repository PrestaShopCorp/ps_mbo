DOCKER = $(shell docker ps 2> /dev/null)

help:
	@egrep "^#" Makefile

# target: docker-build|db               - Setup/Build PHP & (node)JS dependencies
db: docker-build
docker-build: build-back

build-back:
	docker-compose run --rm php sh -c "composer install"

build-back-prod:
	docker-compose run --rm php sh -c "composer install --no-dev -o"

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

# target: build-zip-prod                   - Launch prod zip generation of the module (will not work on windows)
build-zip-prod: build-back-prod build-zip

# target: phpunit                                - Start phpunit
phpunit: phpunit-cleanup
ifndef DOCKER
    $(error "DOCKER is unavailable on your system")
endif
	docker run --rm -d -e PS_DOMAIN=localhost -e PS_ENABLE_SSL=0 -e PS_DEV_MODE=1 -e _PS_ADMIN_DIR_=admin -e PS_ADMIN_DIR=admin -e PS_VERSION=8.0 --name test-phpunit prestashop/docker-internal-images:nightly
	-docker container exec -u www-data test-phpunit sh -c "sleep 1 && php -d memory_limit=-1 ./bin/console prestashop:module uninstall ps_mbo"
	docker cp . test-phpunit:/var/www/html/modules/ps_mbo
	docker container exec -u www-data test-phpunit sh -c "sleep 1 && php -d memory_limit=-1 ./bin/console prestashop:module install ps_mbo"
	@docker container exec -u www-data test-phpunit sh -c "echo \"Testing module v\`cat /var/www/html/modules/ps_mbo/config.xml | grep '<version>' | sed 's/^.*\[CDATA\[\(.*\)\]\].*/\1/'\`\n\""
	docker container exec -u www-data --workdir /var/www/html/ test-phpunit php -d date.timezone=UTC -d memory_limit=-1 ./modules/ps_mbo/vendor/phpunit/phpunit/phpunit -c modules/ps_mbo/tests/phpunit.xml
	@echo phpunit passed

phpunit-cleanup:
	-docker container rm -f test-phpunit
