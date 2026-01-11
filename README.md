# Quanta HRIS

Quanta HRIS is a complete system for managing employees. It helps companies track attendance and calculate salaries easily. This system handles Indonesian tax rules (PPh 21 TER) automatically.

### About This Repository

This repository contains the **Backend API** and the **Web CMS (Admin Panel)**.
If you are looking for the mobile application code, please check:
[Quanta HRIS Mobile App Repository](https://github.com/munovrizall/quanta-hris-flutter)

## Key Features

-   **Attendance (Absensi)**

    -   **Location Check:** Employees can only clock in if they are near the office.
    -   **Anti-Fake GPS:** The system calculates real distances to prevent cheating.
    -   **Selfie Verification:** Employees must take a photo when clocking in or out.
    -   **Automatic Status:** The system knows if an employee is On Time, Late, or Leaving Early.

-   **Payroll (Gaji)**

    -   **Tax Calculation:** Automatically calculates PPh 21 taxes using the TER method.
    -   **BPJS:** Calculates BPJS Kesehatan and Ketenagakerjaan cuts.
    -   **Automatic Deductions:** Salary is automatically cut if an employee is late or absent without notice.
    -   **Payslips:** Creates PDF payslips for employees.

-   **Employee Management**
    -   **Leave & Permits:** Employees can request time off (Cuti/Izin) easily.
    -   **Overtime:** The system detects overtime work automatically.

## Tech Stack

-   **Framework:** Laravel 12
-   **Database:** MySQL
-   **Server:** Nginx
-   **Deployment:** Docker & Docker Compose

## Local Development Setup

Follow these steps to run the project on your computer:

1.  **Clone the Repository**

    ```bash
    git clone https://github.com/your-repo/quanta-hris-laravel.git
    cd quanta-hris-laravel
    ```

2.  **Setup Environment**
    Copy the `.env` file and set up your database connection.

    ```bash
    cp .env.example .env
    ```

3.  **Install Libraries**

    ```bash
    composer install
    npm install
    ```

4.  **Generate Key**

    ```bash
    php artisan key:generate
    ```

5.  **Setup Database**
    Make sure your MySQL is running, then run this command to create tables and dummy users:

    ```bash
    php artisan migrate --seed
    ```

6.  **Build Assets**

    ```bash
    npm run build
    ```

7.  **Run Server**
    ```bash
    php artisan serve
    ```

### Login Credentials (Default)

After running `migrate --seed`, you can use these accounts to log in:

| Role            | Email                       | Password        |
| :-------------- | :-------------------------- | :-------------- |
| **Admin**       | `admin@smartcool.id`        | `admin123`      |
| **CEO**         | `ceo@smartcool.id`          | `ceo123`        |
| **Manager HRD** | `manager.hrd1@smartcool.id` | `managerhrd123` |
| **Staff HRD**   | `staff.hrd1@smartcool.id`   | `staffhrd123`   |
| **User (IT)**   | `rizal@smartcool.id`        | `rizal123`      |

## Production Deployment (Docker)

If you want to deploy to a live server using Docker:

1.  **Start the App**

    ```bash
    docker-compose up -d --build
    ```

2.  **Run Setup Commands**

    ```bash
    docker-compose exec app composer install --optimize-autoloader --no-dev
    docker-compose exec app php artisan migrate --force
    docker-compose exec app php artisan config:cache
    docker-compose exec app php artisan route:cache
    docker-compose exec app php artisan view:cache
    ```

3.  **Fix Permissions**
    ```bash
    docker-compose exec app chmod -R 775 storage bootstrap/cache
    ```

## Important Configuration

For the app to work correctly (especially images and links), change these lines in your `.env` file:

```ini
APP_URL=https://your-domain.com
ASSET_URL=https://your-domain.com
FILESYSTEM_DISK=public
```
