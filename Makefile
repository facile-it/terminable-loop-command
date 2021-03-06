pre-commit-check: cs-fix psalm phpstan tests

cs-fix:
	vendor/bin/php-cs-fixer fix --verbose --ansi

cs-dry-run:
	vendor/bin/php-cs-fixer fix --verbose --ansi --diff --dry-run

psalm:
	vendor/bin/psalm

phpstan:
	vendor/bin/phpstan analyse

tests:
	vendor/bin/phpunit --verbose

.PHONY: tests
