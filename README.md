docker_symfony4

Symfony4, Nginx, PHP 

* cd {PAHT}/docker_symfony4/docker

docker-compose build

docker-compose up

* acces to the page 

http://localhost:8000/

* acces to dataBase

port on your host : 8001 passeword : 123456

--------------------------------------------------------------

* to get a new skeleton
* docker exec -it docker_symfony4_php_1 /bin/bash
* cd /code

composer self-update

composer create-project symfony/skeleton [MON PROJET]
