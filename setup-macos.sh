#!/usr/bin/env bash
set -euo pipefail

SKIP_DATABASE=0
SKIP_MIGRATE=0
SKIP_SEED=0

for arg in "$@"; do
    case "$arg" in
        --skip-database) SKIP_DATABASE=1 ;;
        --skip-migrate) SKIP_MIGRATE=1 ;;
        --skip-seed) SKIP_SEED=1 ;;
        *)
            echo "Unknown option: $arg"
            echo "Supported: --skip-database --skip-migrate --skip-seed"
            exit 1
            ;;
    esac
done

require_command() {
    local name="$1"
    local hint="$2"

    if ! command -v "$name" >/dev/null 2>&1; then
        echo "Missing required command: $name"
        echo "$hint"
        exit 1
    fi
}

dotenv_value() {
    local name="$1"
    local default_value="${2:-}"

    if [ ! -f ".env" ]; then
        printf "%s" "$default_value"
        return
    fi

    local line
    line="$(grep -E "^[[:space:]]*${name}[[:space:]]*=" .env | head -n 1 || true)"

    if [ -z "$line" ]; then
        printf "%s" "$default_value"
        return
    fi

    local value="${line#*=}"
    value="${value#"${value%%[![:space:]]*}"}"
    value="${value%"${value##*[![:space:]]}"}"
    value="${value%\"}"
    value="${value#\"}"
    value="${value%\'}"
    value="${value#\'}"
    printf "%s" "$value"
}

mysql_exec() {
    local host="$1"
    local port="$2"
    local user="$3"
    local password="$4"
    local sql="$5"

    if [ -n "$password" ]; then
        mysql --host="$host" --port="$port" --user="$user" --password="$password" --execute="$sql"
    else
        mysql --host="$host" --port="$port" --user="$user" --execute="$sql"
    fi
}

assert_laravel_project() {
    if [ ! -f "artisan" ] || [ ! -f "composer.json" ]; then
        echo "This folder does not look like the Laravel project scaffold."
        echo "Run this script from the project root."
        exit 1
    fi
}

ensure_mysql_service() {
    if [ "$SKIP_DATABASE" -eq 1 ]; then
        echo "Skipping database creation because --skip-database was passed."
        return
    fi

    local db_connection
    db_connection="$(dotenv_value DB_CONNECTION mysql)"

    if [ "$db_connection" != "mysql" ]; then
        echo "Skipping database creation because DB_CONNECTION is '$db_connection'."
        return
    fi

    if command -v brew >/dev/null 2>&1; then
        brew services start mysql >/dev/null 2>&1 || true
    fi

    local db_host db_port db_name db_user db_password
    db_host="$(dotenv_value DB_HOST 127.0.0.1)"
    db_port="$(dotenv_value DB_PORT 3306)"
    db_name="$(dotenv_value DB_DATABASE test_programator)"
    db_user="$(dotenv_value DB_USERNAME root)"
    db_password="$(dotenv_value DB_PASSWORD "")"

    if ! [[ "$db_name" =~ ^[A-Za-z0-9_]+$ ]]; then
        echo "DB_DATABASE '$db_name' contains unsupported characters for automatic creation."
        echo "Use letters, numbers, and underscores, or create the database manually."
        exit 1
    fi

    echo "Checking MySQL connection to ${db_host}:${db_port} as ${db_user}..."

    if ! mysql_exec "$db_host" "$db_port" "$db_user" "$db_password" "SELECT 1;" >/dev/null; then
        if [ "$db_host" = "127.0.0.1" ] && mysql_exec "localhost" "$db_port" "$db_user" "$db_password" "SELECT 1;" >/dev/null; then
            db_host="localhost"
        else
            echo "Could not connect to MySQL."
            echo "Try: brew services restart mysql"
            echo "If your MySQL user has a password, set DB_PASSWORD in .env and rerun."
            exit 1
        fi
    fi

    echo "Creating database '$db_name' if it does not exist..."
    mysql_exec "$db_host" "$db_port" "$db_user" "$db_password" "CREATE DATABASE IF NOT EXISTS ${db_name} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >/dev/null
    echo "Database '$db_name' is ready."
}

echo "Checking macOS Laravel prerequisites..."

assert_laravel_project
require_command php "Install PHP with: brew install php"
require_command composer "Install Composer with: brew install composer"
require_command mysql "Install MySQL with: brew install mysql"

echo "PHP:"
php -v | head -n 1

echo "Composer:"
composer --version || true

if [ ! -f ".env" ]; then
    cp .env.example .env
    echo "Created .env from .env.example"
else
    echo ".env already exists; leaving it unchanged."
fi

ensure_mysql_service

echo "Installing Composer dependencies..."
composer install

echo "Generating Laravel app key..."
php artisan key:generate

if [ "$SKIP_MIGRATE" -eq 0 ]; then
    echo "Running migrations..."
    php artisan migrate
fi

if [ "$SKIP_SEED" -eq 0 ]; then
    echo "Running seeders..."
    php artisan db:seed
fi

echo ""
echo "Setup complete."
echo "Run the app with: php artisan serve"
echo "Then open: http://127.0.0.1:8000/products"
