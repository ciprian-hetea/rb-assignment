# Install instructions

- clone the repo
- run `docker build -t rb-assignment .`
- run `docker-compose up`
- run `cp ./.env.test ./.env`
- replace the `DB_HOST` in each .env file with the name of each MySQL container in Docker
- inside the main docker image:
    - run `php artisan migrate`
    - set the correct permissions on the test script by running `chmod 0755 script.sh` in the command line
    - you can run all the tests on the separate test DB by running the `test.sh` bash script
