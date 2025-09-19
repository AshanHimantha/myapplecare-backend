# My Apple Care - Backend API ⚙️

[![PHP](https://img.shields.io/badge/PHP-8.0+-8892BF.svg?logo=php)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20.svg?logo=laravel)](https://laravel.com)


This repository contains the Laravel backend API that powers the **My Apple Care** web application. It handles user authentication, data management, and the core business logic for the service.

**🖥️ View the React Frontend:** [AshanHimantha/myapplecare-frontend](https://github.com/AshanHimantha/myapplecare-frontend)

---

## ✨ Features

- **RESTful API:** A well-structured API for all frontend operations.
- **JWT Authentication:** Secure, token-based authentication using the Laravel Sanctum.
- **Role-Based Access Control:** Custom middleware (`isAdmin`) protects admin-only routes, separating user and administrator privileges.
- **Eloquent ORM:** Leverages Laravel's powerful Object-Relational Mapper for clean and safe database interactions.

---

## 🛠️ Tech Stack

- **Framework:** [Laravel 11](https://laravel.com/)
- **Language:** [PHP](https://www.php.net/)
- **Authentication:** Laravel Sanctum for JSON Web Tokens.
- **Database:** [Eloquent ORM](https://laravel.com/docs/11.x/eloquent) with a relational database (e.g., MySQL).
- **Dependency Manager:** [Composer](https://getcomposer.org/)

---

## 🚀 Getting Started

Follow these instructions to get the backend server up and running on your local machine.

### Prerequisites

- **PHP:** Version 8.0 or higher
- **Composer:** [PHP Dependency Manager](https://getcomposer.org/download/)
- **Database:** A running instance of MySQL or another compatible database.
- **Git:** [Version Control System](https://git-scm.com/)

### Installation & Setup

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/AshanHimantha/myapplecare-backend.git
    cd myapplecare-backend
    ```

2.  **Install PHP dependencies:**
    ```bash
    composer install
    ```

3.  **Configure your environment:**
    -   Copy the example environment file.
        ```bash
        cp .env.example .env
        ```
    -   Generate your unique application key and JWT secret.
        ```bash
        php artisan key:generate
        php artisan jwt:secret
        ```

4.  **Set up the database:**
    -   Create a new database for the project (e.g., `myapplecare_db`).
    -   Open the newly created `.env` file and update the `DB_*` variables with your database credentials.
        ```env
        DB_CONNECTION=mysql
        DB_HOST=127.0.0.1
        DB_PORT=3306
        DB_DATABASE=myapplecare_db
        DB_USERNAME=your_db_user
        DB_PASSWORD=your_db_password
        ```

5.  **Run database migrations:**
    -   This command will create all the necessary tables (`users`, `posts`, etc.) in your database.
        ```bash
        php artisan migrate
        ```

6.  **Run the development server:**
    ```bash
    php artisan serve
    ```
    The API server should now be running, typically at `http://localhost:8000`.

---

## 📝 API Endpoints Overview

All endpoints are prefixed with `/api`.

| Method   | Endpoint                  | Description                               | Protected | Role      |
| :------- | :------------------------ | :---------------------------------------- | :-------- | :-------- |
| `POST`   | `/auth/register`          | Register a new user.                      | No        | -         |
| `POST`   | `/auth/login`             | Authenticate a user and get a JWT.        | No        | -         |
| `POST`   | `/auth/logout`            | Log out the authenticated user.           | Yes       | Any       |
| `GET`    | `/auth/user-profile`      | Get the profile of the logged-in user.    | Yes       | Any       |
| `GET`    | `/users`                  | Get a list of all users.                  | Yes       | Admin     |
| `DELETE` | `/users/:id`              | Delete a user by ID.                      | Yes       | Admin     |
| `POST`   | `/posts`                  | Create a new repair request (post).       | Yes       | Any       |
| `GET`    | `/posts`                  | Get all repair requests.                  | Yes       | Any       |
| `PUT`    | `/posts/:id`              | Update the status of a repair request.    | Yes       | Admin     |

> **Note:** Endpoints marked as **Protected** require a valid Bearer Token in the `Authorization` header.

---

## 🤝 Contributing

Contributions are welcome! If you have suggestions or want to improve the code, please feel free to fork the repo and create a pull request.

1.  **Fork** the Project
2.  Create your Feature Branch (`git checkout -b feature/NewFeature`)
3.  Commit your Changes (`git commit -m 'Add some NewFeature'`)
4.  Push to the Branch (`git push origin feature/NewFeature`)
5.  Open a **Pull Request**

---
