# Install instructions

- clone the repo
- run `docker-compose up`
- run `mv ./.env.test ./.env`
- replace the `DB_HOST` in each .env file with the name of each MySQL container in Docker
- you can run all the tests on the separate test DB by running the `test.sh` bash script
