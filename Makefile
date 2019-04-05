build: cs-fix phpstan tests

cs-fix:
	vendor/bin/php-cs-fixer fix --verbose --ansi

cs-dry-run:
	vendor/bin/php-cs-fixer fix --verbose --ansi --diff --dry-run

phpstan:
	vendor/bin/phpstan analyse

tests:
	vendor/bin/phpunit --verbose

.PHONY: tests
