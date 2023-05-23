DOCKER = $(shell docker ps 2> /dev/null)
DOCKERFILE = ./docker/docker-compose.yml

help:
	@egrep "^#" Makefile

# install                   - install PHP container et install composer dependencies with dev dependencies
install:
	docker-compose -f $(DOCKERFILE) run --rm php sh -c "composer install"

# install-prod              - install PHP container et install composer dependencies without dev dependencies
install-prod:
	docker compose -f $(DOCKERFILE) run --rm php sh -c "composer install --no-dev -a"

# create-shop							 - create a new shop
docker-up:
	docker-compose -f $(DOCKERFILE) up -d

docker-down:
	docker-compose -f $(DOCKERFILE) down -v

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
	docker pull prestashop/docker-internal-images:nightly
	@docker run --rm \
		--name phpunit \
		-e PS_DOMAIN=localhost \
		-e PS_ENABLE_SSL=0 \
		-e PS_DEV_MODE=1 \
		-e XDEBUG_MODE=coverage \
		-e XDEBUG_ENABLED=1 \
		-v ${PWD}:/var/www/html/modules/ps_mbo \
		-w /var/www/html/modules/ps_mbo \
		prestashop/docker-internal-images:nightly \
		sh -c " \
			service mariadb start && \
			service apache2 start && \
			docker-php-ext-enable xdebug && \
			../../bin/console prestashop:module install ps_mbo && \
			echo \"Testing module v\`cat config.xml | grep '<version>' | sed 's/^.*\[CDATA\[\(.*\)\]\].*/\1/'\`\n\" && \
			chown -R www-data:www-data ../../var/logs && \
			chown -R www-data:www-data ../../var/cache && \
			./vendor/bin/phpunit -c ./tests/phpunit.xml \
		      "
	@echo phpunit passed

phpunit-cleanup:
	-docker container rm -f mbo-phpunit
