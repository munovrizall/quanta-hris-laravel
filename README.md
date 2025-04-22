# Laravel Docker Setup

This project provides a Dockerized environment for a Laravel application using PHP 8.3 FPM, MySQL, and Nginx. It is designed to simplify the development and deployment process of Laravel applications.

## Project Structure

```
laravel-docker
├── .dockerignore          # Files and directories to ignore during the Docker build
├── Dockerfile             # Instructions to build the Docker image for the Laravel application
├── docker-compose.yml     # Defines the services for the application
├── docker                 # Contains configuration files for Nginx and PHP
│   ├── nginx
│   │   └── default.conf   # Nginx configuration for serving the Laravel application
│   └── php
│       └── php.ini       # PHP configuration settings for the PHP FPM service
├── entrypoint.sh         # Script executed when the Docker container starts
└── README.md             # Documentation for the project
```

## Getting Started

### Prerequisites

- Docker
- Docker Compose

### Installation

1. Clone the repository:

   ```bash
   git clone <repository-url>
   cd laravel-docker
   ```

2. Build the Docker images and start the containers:

   ```bash
   docker-compose up -d
   ```

3. Access the application:

   Open your web browser and navigate to `http://localhost`.

### Usage

- To run migrations, execute:

  ```bash
  docker-compose exec php php artisan migrate
  ```

- To seed the database, execute:

  ```bash
  docker-compose exec php php artisan db:seed
  ```

### Customization

- Modify the `docker/php/php.ini` file to customize PHP settings.
- Update the `docker/nginx/default.conf` file for Nginx configurations.

### Contributing

Contributions are welcome! Please submit a pull request or open an issue for any enhancements or bug fixes.

### License

This project is licensed under the MIT License. See the LICENSE file for details.