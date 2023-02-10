# LAV45 / Mock-Server

### Installing

```shell
# Build it into you project
~$ composer require lav45/mock-server
~$ php vendor/bin/mock-server --host=0.0.0.0 --port=8080 --mocks=./mocks

# OR just pul docker image
~$ docker pull lav45/mock-server:latest
~$ docker run --rm -i -v $(pwd)/mocks:/mocks -p 8080:8080 lav45/mock-server:latest
```
