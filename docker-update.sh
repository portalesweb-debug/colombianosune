#!/bin/bash

# actualizar código
git pull

# detener contenedor
docker stop colombianosune_app

# eliminar contenedor
docker rm colombianosune_app

# levantar con docker compose
docker compose up -d --build --force-recreate