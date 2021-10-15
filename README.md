# Install instructions

- clone the repo
- run `docker build -t rb-assignment .`
- run `docker-compose up`
- run `cp ./.env.test ./.env`
- replace the `DB_HOST` in each .env file with the name of each MySQL container in Docker
- inside the main docker image:
    - run `php artisan migrate`
    - set the correct permissions on the test script by running `chmod 0755 test.sh` in the command line
    - you can run all the tests on the separate test DB by running the `test.sh` bash script
# Documentation
You can find the API documentation [here](https://documenter.getpostman.com/view/13137814/UV5WCHZj).

Just click the "Run in Postman" button to test it out.
