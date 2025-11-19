# Sembot Helpdesk Lite

A modern helpdesk application built with Laravel 11 (API) and Angular 20 (Frontend).

## Quick Start

### Prerequisites

- Docker and Docker Compose
- Git

### Running the Application

#### Production Mode

Start both API and Frontend services:

```bash
./start.sh
```

This will:
- Create the shared Docker network
- Start the Laravel API (via Sail) on `http://localhost:80`
- Start the Angular Frontend on `http://localhost:4200`

Stop all services:

```bash
./stop.sh
```

Restart all services:

```bash
./restart.sh
```

#### Development Mode (with Hot Reload)

Start both services in development mode:

```bash
./start-dev.sh
```

This will start the frontend with hot reload enabled for development.

Stop development services:

```bash
./stop-dev.sh
```

## Project Structure

```
├── api/              # Laravel 11 API (with Sail)
├── frontend/         # Angular 20 Application
├── start.sh          # Start all services (production)
├── stop.sh           # Stop all services
├── restart.sh        # Restart all services
├── start-dev.sh      # Start all services (development mode)
└── stop-dev.sh       # Stop development services
```

## Services

- **API**: Laravel 11 REST API running on port 80
- **Frontend**: Angular 20 SPA running on port 4200

## Manual Management

### API (Laravel Sail)

```bash
cd api
./vendor/bin/sail up -d    # Start
./vendor/bin/sail down     # Stop
./vendor/bin/sail logs -f  # View logs
```

### Frontend (Docker Compose)

Production mode:
```bash
cd frontend
docker-compose up -d         # Start
docker-compose down          # Stop
docker-compose logs -f       # View logs
```

Development mode:
```bash
cd frontend
docker-compose --profile dev up frontend-dev     # Start with hot reload
docker-compose --profile dev down                # Stop
```

## Development

See individual README files in `api/` and `frontend/` directories for detailed development instructions.
