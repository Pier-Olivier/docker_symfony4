docker_symfony4

Symfony4, Nginx, PHP, Redis, MariaDB

in order to access the html page :
* edit : {PAHT}/docker_symfony4/docker/nginx/default.conf
change :  root /code/[MON_PROJET]/public;

* cd {PAHT}/docker_symfony4/docker

docker-compose build

docker-compose up

* acces to dataBase

port on your host : 8001 passeword : 123456

--------------------------------------------------------------

* to get a new skeleton

solution 1
from your host (if you have composer installer) :
* cd {PAHT}/docker_symfony4/code
* composer self-update
* composer create-project symfony/skeleton [MON_PROJET]

solution 2
go into the countainer :
* docker exec -it docker_symfony4_php_1 /bin/bash
* cd /code
* composer self-update
* composer create-project symfony/skeleton [MON_PROJET]
* on the host give the access rigths


* acces to the page 
http://localhost:8000/
