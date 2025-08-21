# be_login

A backend authentication system built with Laravel, designed to handle user registration, login, and user management. The **main branch** is for production-ready code and the **dev branch** is for local testing and development. It includes user registration with email and password, user login and authentication, password hashing for security, session management, and environment-based configuration via `.env`.

To install and set up the project, follow these steps:

1. **Clone the repository and enter the folder**:  
   `git clone https://github.com/NANB-an/be_login.git && cd be_login`

2. **Install PHP dependencies**:  
   `composer install`

3. **Copy the environment file**:  
   `cp .env.example .env`

4. **Configure your `.env` file**:  
   - For **local testing**, use SQLite by setting:  
     `DB_CONNECTION=sqlite`  
     Comment out the other DB_* settings:  
     `# DB_HOST=localhost`  
     `# DB_PORT=5432`  
     `# DB_DATABASE=<your-database-name>`  
     `# DB_USERNAME=<your-username>`  
     `# DB_PASSWORD=<your-password>`  
     Set `SANCTUM_STATEFUL_DOMAINS=localhost`
     
   - For **production**, use PostgreSQL by setting:  
     `DB_CONNECTION=pgsql`  
     `DB_HOST=<your-production-db-host>`  
     `DB_PORT=5432`  
     `DB_DATABASE=<your-database-name>`  
     `DB_USERNAME=<your-username>`  
     `DB_PASSWORD=<your-password>`  
     Set `SANCTUM_STATEFUL_DOMAINS=<your-frontend-domain>`

5. **Generate the application key**:  
   `php artisan key:generate`

6. **Run database migrations**:  
   `php artisan migrate`

7. **Start the Laravel development server**:  
   `php artisan serve`  
   The backend will be accessible at `http://127.0.0.1:8000`.

**API Endpoints**:  
- `POST /api/register` – Register a new user  
- `POST /api/login` – Login a user  
- `POST /api/logout` – Logout the authenticated user  
- `GET /api/users` – List users (protected route)  



