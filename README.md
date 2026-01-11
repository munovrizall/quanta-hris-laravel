# Quanta HRIS

Quanta HRIS is a comprehensive Human Resource Information System designed to streamline workforce management. It integrates secure, geolocation-based attendance tracking with a complex, automated payroll system tailored for Indonesian tax regulations (PPh 21 TER). Built with high performance and scalability in mind, it serves as a central hub for managing employees, attendance, leaves, and financial reporting.

## Key Features

Based on the architectural analysis, the system includes the following core modules:

-   **Attendance Management**

    -   **Geolocation Validation:** Ensures employees clock in/out within a specific radius of assigned office branches.
    -   **Anti-Fake GPS:** Calculates precise distances using the Haversine formula to validate location authenticity.
    -   **Selfie Verification:** Requires photo evidence for both clock-in and clock-out actions.
    -   **Smart Status Tracking:** Automatically categorizes attendance as On Time, Late, or Early Departure based on shift schedules.
    -   **Multi-Branch Support:** Dynamically assigns attendance records to the nearest valid company branch.

-   **Payroll System (Indonesian Compliance)**

    -   **Automated Tax Calculation:** Features a dedicated engine for PPh 21 calculations using the Average Effective Rate (TER) method.
    -   **BPJS Integration:** Automatically calculates breakdowns for BPJS Kesehatan and BPJS Ketenagakerjaan (JHT, JP).
    -   **Dynamic Deductions:** processes deductions for lateness and unexcused absences (Alfa) automatically affecting the final take-home pay.
    -   **Payslip Generation:** Generates detailed, professional PDF payslips for individuals or bulk periods.

-   **Employee Management**
    -   **Leave & Permits:** Integrated workflow for requesting and approving annual leaves (Cuti) and special permissions (Izin).
    -   **Overtime (Lembur):** intelligent detection of overtime eligibility based on attendance duration and shift rules.
    -   **Performance Reports:** Modules for generating performance insights and financial summaries.

## Tech Stack

-   **Framework:** Laravel 12
-   **Database:** MySQL
-   **Web Server:** Nginx
-   **Containerization:** Docker & Docker Compose
-   **PDF Engine:** dompdf

## Local Development Setup

Follow these steps to set up the project locally:

1.  **Clone the Repository**

    ```bash
    git clone https://github.com/your-repo/quanta-hris-laravel.git
    cd quanta-hris-laravel
    ```

2.  **Environment Setup**
    Copy the example environment file and configure your database credentials.

    ```bash
    cp .env.example .env
    ```

3.  **Install Dependencies**

    ```bash
    composer install
    npm install
    ```

4.  **Generate Application Key**

    ```bash
    php artisan key:generate
    ```

5.  **Database Migration & Seeding**
    Ensure your local MySQL server is running, then execute:

    ```bash
    php artisan migrate --seed
    ```

6.  **Build Assets**

    ```bash
    npm run build
    ```

7.  **Run Development Server**
    ```bash
    php artisan serve
    ```

## Production Deployment (Docker)

This project is optimized for deployment using Docker and Nginx.

1.  **Build and Start Containers**
    Run the following command to build the image and start the services in detached mode:

    ```bash
    docker-compose up -d --build
    ```

2.  **Execute Post-Deployment Commands**
    Once the containers are running, install dependencies and run migrations inside the container:

    ```bash
    docker-compose exec app composer install --optimize-autoloader --no-dev
    docker-compose exec app php artisan migrate --force
    docker-compose exec app php artisan config:cache
    docker-compose exec app php artisan route:cache
    docker-compose exec app php artisan view:cache
    ```

3.  **File Permissions**
    Ensure the storage directory is writable:
    ```bash
    docker-compose exec app chmod -R 775 storage bootstrap/cache
    ```

## Environment Configuration

For the application to function correctly in production, especially for asset loading and secure redirection, you must configure the following variables in your `.env` file:

-   **APP_URL**: Must be set to your actual HTTPS domain (e.g., `https://hris.quanta.id`).
-   **ASSET_URL**: Must also be set to the HTTPS domain to ensure mixed content errors do not occur.
-   **FILESYSTEM_DISK**: Configure to `public` or `s3` depending on your storage needs for attendance photos.

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
ASSET_URL=https://your-domain.com
```
