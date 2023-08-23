# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## Unreleased
 * ...

## 1.3.0 [2023-08-23]
* Adds support for SIGQUIT signal (needed to support php-fpm-alpine docker images which overrides STOPSIGNAL ([Official Dockerfile](https://github.com/docker-library/php/blob/master/8.2/alpine3.18/fpm/Dockerfile#L259)))

## 1.2.0 [2023-04-03]
 * Drop support to PHP 7.3
 * Drop support to EOL Symfony versions (3.x, 4.0 to 4.3, 5.0 to 5.3)

## 1.1.1 [2023-04-03]
 * Drop `phpspec/prophecy-phpunit` as a dependency, move it to `require-dev`

## 1.1.0 [2021-03-04]
 * Bump minimum PHP required version to 7.3 (#1)
 * Add PHP 8 support (#6)  
 * Add Symfony 5 support (#4)
 * Add Psalm (level 1) static analysis (#5)
 * Fix possible `null` when command name is not set, use FQCN of command as fallback (#5)

## 1.0.0 [2019-05-06]
First stable release (no notable changes).

## 0.1.0 [2019-04-05]
First release.
