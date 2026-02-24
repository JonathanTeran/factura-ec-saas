.PHONY: help up down restart logs shell migrate seed fresh test clear install horizon

# Variables
DOCKER_COMPOSE = docker-compose -f docker/docker-compose.yml
APP_CONTAINER = factura-ec-app

help: ## Mostrar ayuda
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# Docker commands
up: ## Iniciar todos los contenedores
	$(DOCKER_COMPOSE) up -d

down: ## Detener todos los contenedores
	$(DOCKER_COMPOSE) down

restart: ## Reiniciar contenedores
	$(DOCKER_COMPOSE) restart

logs: ## Ver logs de todos los contenedores
	$(DOCKER_COMPOSE) logs -f

logs-app: ## Ver logs solo de la app
	$(DOCKER_COMPOSE) logs -f app

shell: ## Acceder al shell del contenedor de la app
	$(DOCKER_COMPOSE) exec app sh

# Laravel commands
migrate: ## Ejecutar migraciones
	$(DOCKER_COMPOSE) exec app php artisan migrate

seed: ## Ejecutar seeders
	$(DOCKER_COMPOSE) exec app php artisan db:seed

fresh: ## Resetear base de datos
	$(DOCKER_COMPOSE) exec app php artisan migrate:fresh --seed

test: ## Ejecutar tests
	$(DOCKER_COMPOSE) exec app php artisan test

clear: ## Limpiar toda la cache
	$(DOCKER_COMPOSE) exec app php artisan cache:clear
	$(DOCKER_COMPOSE) exec app php artisan config:clear
	$(DOCKER_COMPOSE) exec app php artisan route:clear
	$(DOCKER_COMPOSE) exec app php artisan view:clear

cache: ## Generar cache
	$(DOCKER_COMPOSE) exec app php artisan config:cache
	$(DOCKER_COMPOSE) exec app php artisan route:cache
	$(DOCKER_COMPOSE) exec app php artisan view:cache

horizon: ## Ver estado de Horizon
	$(DOCKER_COMPOSE) exec app php artisan horizon:status

# Installation
install: ## Instalación completa del proyecto
	cp backend/.env.example backend/.env
	cd backend && composer install
	$(DOCKER_COMPOSE) up -d
	sleep 10
	$(DOCKER_COMPOSE) exec app php artisan key:generate
	$(DOCKER_COMPOSE) exec app php artisan migrate --seed
	$(DOCKER_COMPOSE) exec app php artisan storage:link
	@echo "Instalación completada!"
	@echo "App: http://localhost:8000"
	@echo "Mailpit: http://localhost:8025"
	@echo "MinIO: http://localhost:9001"

# Development
dev: ## Modo desarrollo
	$(DOCKER_COMPOSE) exec app php artisan serve --host=0.0.0.0 --port=8000

queue: ## Procesar cola manualmente
	$(DOCKER_COMPOSE) exec app php artisan queue:work --queue=sri-send,email,default

tinker: ## Abrir tinker
	$(DOCKER_COMPOSE) exec app php artisan tinker

# Mobile
flutter-run: ## Ejecutar app Flutter
	cd mobile && flutter run

flutter-build-apk: ## Compilar APK
	cd mobile && flutter build apk --release

flutter-build-ios: ## Compilar iOS
	cd mobile && flutter build ios --release

# Database
db-backup: ## Hacer backup de la base de datos
	$(DOCKER_COMPOSE) exec mysql mysqldump -u root -prootsecret factura_ec > backup_$(shell date +%Y%m%d_%H%M%S).sql

db-restore: ## Restaurar backup (usar: make db-restore FILE=backup.sql)
	$(DOCKER_COMPOSE) exec -T mysql mysql -u root -prootsecret factura_ec < $(FILE)
