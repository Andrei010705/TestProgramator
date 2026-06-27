# TestProgramator - Product Video MVP

Aplicatie Laravel 12 pentru produse si candidati video YouTube. Scopul proiectului este un MVP scaffold curat: produse in MySQL, cautare/listare/filtrare/paginare, servicii mockabile pentru YouTube si AI, plus UI Blade simplu.

Frontendul si backendul ruleaza impreuna prin Laravel. Nu exista inca un frontend separat cu Vite/React/Vue.

## Ce Contine Proiectul

- `products` si `video_candidates` migrations
- modele Eloquent `Product` si `VideoCandidate`
- relatii Eloquent intre produse si candidati video
- `ProductController` pentru listare, cautare, filtrare si paginare
- `ProductYoutubeController` pentru `POST /products/{id}/search-youtube`
- servicii:
  - `YouTubeClient`
  - `AiVerifier`
  - `ProductVideoService`
  - `ProductSearchService`
- DTO-uri:
  - `YouTubeCandidateData`
  - `AiVerificationResult`
- Blade UI pentru tabel produse, filtre si candidati video
- setup scripts pentru Windows si macOS
- teste de baza pentru query-uri, parsare YouTube, parsare AI JSON, persistenta update si filtrul fara video

## Structura Importanta

```text
app/
  Contracts/
  DTO/
  Http/Controllers/
  Jobs/
  Models/
  Providers/
  Services/
config/
database/
  factories/
  migrations/
  seeders/
resources/views/
routes/
tests/
.env.example
composer.json
install-laragon-windows.py
install-macos.py
setup-windows.ps1
setup-macos.sh
```

## Cerinte

Pentru ambele sisteme:

- PHP 8.2+
- Composer
- MySQL 8 sau MariaDB
- Git optional, dar recomandat

Laravel este instalat ca dependinta Composer. Nu ai nevoie de comanda globala `laravel`.

## Instalare De La Zero Pe Windows 11

Recomandat: PowerShell + Laragon.

### 1. Instaleaza Laragon, PHP si Composer

Din folderul proiectului:

```powershell
cd C:\Users\artio\Desktop\TestProgramator
python .\install-laragon-windows.py
```

Scriptul incearca sa:

- instaleze/verifice Laragon
- gaseasca PHP din Laragon
- activeze extensiile PHP necesare
- instaleze/verifice Composer
- deschida o verificare cu `php -v` si `composer --version`

Daca PowerShell nu recunoaste `python`, instaleaza Python din Microsoft Store sau de pe `python.org`, apoi redeschide PowerShell.

### 2. Porneste MySQL Din Laragon

Deschide Laragon si apasa:

```text
Start All
```

Asigura-te ca MySQL/MariaDB este pornit.

### 3. Ruleaza Setup-ul Laravel

```powershell
powershell -ExecutionPolicy Bypass -File .\setup-windows.ps1
```

Scriptul face automat:

- verifica PHP si Composer
- creeaza `.env` din `.env.example` daca lipseste
- verifica MySQL
- creeaza baza `test_programator` daca lipseste
- ruleaza `composer install`
- ruleaza `php artisan key:generate`
- ruleaza migrations
- ruleaza seeders

Optiuni utile:

```powershell
powershell -ExecutionPolicy Bypass -File .\setup-windows.ps1 -SkipDatabase
powershell -ExecutionPolicy Bypass -File .\setup-windows.ps1 -SkipMigrate -SkipSeed
powershell -ExecutionPolicy Bypass -File .\setup-windows.ps1 -SkipDatabase -SkipMigrate -SkipSeed
```

### 4. Daca `php` Nu Este Recunoscut

Foloseste PHP-ul complet din Laragon:

```powershell
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan serve
```

Sau adauga temporar PHP in sesiunea curenta:

```powershell
$env:Path = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64;$env:Path"
```

Apoi:

```powershell
php artisan serve
```

## Instalare De La Zero Pe macOS

Pe macOS se foloseste Homebrew in loc de Laragon.

### 1. Instaleaza Homebrew

Daca nu ai Homebrew:

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

Inchide si redeschide Terminal dupa instalare.

### 2. Instaleaza PHP, Composer Si MySQL

Din folderul proiectului:

```bash
cd /path/to/TestProgramator
python3 install-macos.py
```

Scriptul verifica/instaleaza:

- PHP
- Composer
- MySQL
- porneste MySQL cu `brew services start mysql`

### 3. Ruleaza Setup-ul Laravel

```bash
chmod +x setup-macos.sh
./setup-macos.sh
```

Scriptul face automat:

- creeaza `.env` din `.env.example` daca lipseste
- creeaza baza `test_programator` daca lipseste
- ruleaza `composer install`
- ruleaza `php artisan key:generate`
- ruleaza migrations
- ruleaza seeders

Optiuni utile:

```bash
./setup-macos.sh --skip-database
./setup-macos.sh --skip-migrate --skip-seed
./setup-macos.sh --skip-database --skip-migrate --skip-seed
```

## Configurare `.env`

Fisierul `.env` este creat automat din `.env.example`. Valorile importante:

```env
APP_NAME="Product Video MVP"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=test_programator
DB_USERNAME=root
DB_PASSWORD=

YOUTUBE_API_KEY=
AI_API_KEY=
AI_API_ENDPOINT=https://api.openai.com/v1/chat/completions
AI_MODEL=gpt-4o-mini
```

Nu hardcoda chei API in cod. Pune `YOUTUBE_API_KEY` si `AI_API_KEY` doar in `.env` sau in secret manager.

## Pornire Aplicatie

Backendul si frontendul pornesc impreuna:

Windows:

```powershell
php artisan serve
```

macOS:

```bash
php artisan serve
```

Deschide:

```text
http://127.0.0.1:8000/products
```

Rute principale:

```text
GET  /products
POST /products/{id}/search-youtube
```

## Cum Vizualizezi Baza De Date

Baza de date implicita:

```text
test_programator
```

Tabele principale:

```text
products
video_candidates
```

### Windows Cu Laragon

Deschide Laragon si foloseste `Database` / `HeidiSQL`, daca este disponibil.

Conectare:

```text
Host: 127.0.0.1
Port: 3306
User: root
Password: gol, daca nu ai setat parola
Database: test_programator
```

### MySQL CLI

Windows:

```powershell
mysql -u root -p
```

macOS:

```bash
mysql -u root
```

SQL:

```sql
USE test_programator;
SHOW TABLES;
SELECT * FROM products;
SELECT * FROM video_candidates;
```

### Laravel Tinker

```bash
php artisan tinker
```

Comenzi utile:

```php
App\Models\Product::all();
App\Models\Product::first();
App\Models\Product::with('videoCandidates')->get();
App\Models\VideoCandidate::all();
```

## Date Demo, Migrations Si Reset

Ruleaza seed:

```bash
php artisan db:seed
```

Ruleaza migrations:

```bash
php artisan migrate
```

Reset complet baza + seed:

```bash
php artisan migrate:fresh --seed
```

Seederul actual creeaza produse placeholder. Importul real CSV/XLS este lasat ca TODO in `DatabaseSeeder`.

## Teste

```bash
composer test
```

Testele acopera:

- query builder pentru cautare produse
- parsare raspuns YouTube
- parsare stricta AI JSON
- persistenta update produs/video selectat
- filtru produse fara video

## Integrare YouTube Si AI

`YouTubeClient` si `AiVerifier` sunt pregatite pentru call-uri reale, dar contin TODO-uri pentru:

- request complet catre YouTube API
- request complet catre OpenAI/AI provider
- cache
- rate limiting
- error mapping/retry

`AiVerifier` asteapta strict JSON cu campurile:

```json
{
  "is_match": true,
  "selected_video_id": "abc123",
  "accuracy": 95,
  "reason": "Brand and model match."
}
```

## Troubleshooting Windows

`php is not recognized`:
Adauga folderul PHP din Laragon in PATH sau ruleaza cu path complet:

```powershell
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan serve
```

`vendor/autoload.php not found`:
Ruleaza:

```powershell
powershell -ExecutionPolicy Bypass -File .\setup-windows.ps1
```

sau:

```powershell
php .tools\composer\composer.phar install
```

`Can't connect to MySQL server on 127.0.0.1:3306`:
Deschide Laragon si apasa `Start All`. Verifica portul MySQL si actualizeaza `DB_PORT` in `.env` daca nu este `3306`.

`could not find driver`:
Ruleaza din nou:

```powershell
powershell -ExecutionPolicy Bypass -File .\setup-windows.ps1
```

Scriptul activeaza extensiile PHP uzuale: `openssl`, `curl`, `fileinfo`, `mbstring`, `mysqli`, `pdo_mysql`, `zip`.

`Access denied for user root`:
Seteaza corect in `.env`:

```env
DB_USERNAME=root
DB_PASSWORD=parola_ta
```

## Troubleshooting macOS

`brew: command not found`:
Instaleaza Homebrew si redeschide Terminal.

`mysql command not found`:

```bash
brew install mysql
brew services start mysql
```

`Could not connect to MySQL`:

```bash
brew services restart mysql
```

Verifica:

```bash
mysql -u root -e "SELECT 1;"
```

`Permission denied: ./setup-macos.sh`:

```bash
chmod +x setup-macos.sh
```

## Comenzi Rapide

Windows:

```powershell
powershell -ExecutionPolicy Bypass -File .\setup-windows.ps1
php artisan serve
```

macOS:

```bash
python3 install-macos.py
chmod +x setup-macos.sh
./setup-macos.sh
php artisan serve
```

Browser:

```text
http://127.0.0.1:8000/products
```
# test_programator
