#!/bin/bash
mv ./.env ./.env_bkp
cp ./.env.test ./.env
php artisan config:clear
php artisan key:generate
echo "" > database/database-test.sqlite
php artisan migrate

if [ -z ${1+x} ]; then
	./vendor/bin/phpunit
else
	./vendor/bin/phpunit --filter $1
fi

mv ./.env_bkp ./.env
php artisan config:clear
