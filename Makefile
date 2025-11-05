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
	docker pull prestashop/prestashop:8
	@docker run --rm \
		--name phpunit \
		-e PS_DOMAIN=localhost \
		-e PS_ENABLE_SSL=0 \
		-e PS_DEV_MODE=1 \
		-v ${PWD}:/var/www/html/modules/ps_mbo \
		-w /var/www/html/modules/ps_mbo \
		prestashop/prestashop:8 \
		sh -c " \
			echo \"Testing module v\`cat config.xml | grep '<version>' | sed 's/^.*\[CDATA\[\(.*\)\]\].*/\1/'\`\n\" && \
			./vendor/bin/phpunit -c ./tests/phpunit.xml \
		      "
	@echo phpunit passed

phpunit-cleanup:
	-docker container rm -f test-phpunit
