# Frontend

This project was generated using [Angular CLI](https://github.com/angular/angular-cli) version 20.3.10.

## Development server

To start a local development server, run:

```bash
ng serve
```

Once the server is running, open your browser and navigate to `http://localhost:4200/`. The application will automatically reload whenever you modify any of the source files.

## Code scaffolding

Angular CLI includes powerful code scaffolding tools. To generate a new component, run:

```bash
ng generate component component-name
```

For a complete list of available schematics (such as `components`, `directives`, or `pipes`), run:

```bash
ng generate --help
```

## Building

To build the project run:

```bash
ng build
```

This will compile your project and store the build artifacts in the `dist/` directory. By default, the production build optimizes your application for performance and speed.

## Running unit tests

To execute unit tests with the [Karma](https://karma-runner.github.io) test runner, use the following command:

```bash
ng test
```

## Running end-to-end tests

For end-to-end (e2e) testing, run:

```bash
ng e2e
```

Angular CLI does not come with an end-to-end testing framework by default. You can choose one that suits your needs.

## Docker

### Production Build

To run the application in production mode using Docker:

```bash
# Create the network (only needed once, if not already created by the API)
docker network create sembot-network

# Build and start the container
docker-compose up -d

# View logs
docker-compose logs -f frontend
```

The application will be available at `http://localhost:4200`.

To stop the container:

```bash
docker-compose down
```

### Development Mode with Hot Reload

To run the application in development mode with hot reload:

```bash
# Start the development container
docker-compose --profile dev up frontend-dev

# Or in detached mode
docker-compose --profile dev up -d frontend-dev
```

This will mount your local source code into the container and enable live reloading.

### Building the Docker Image

To build the Docker image manually:

```bash
docker build -t sembot-frontend .
```

### Docker Commands

```bash
# Rebuild the image
docker-compose build

# View running containers
docker-compose ps

# Stop and remove containers
docker-compose down

# Remove volumes (clean start)
docker-compose down -v
```

## Additional Resources

For more information on using the Angular CLI, including detailed command references, visit the [Angular CLI Overview and Command Reference](https://angular.dev/tools/cli) page.
