import os
import re
import shutil
import subprocess
import sys
import urllib.request
from pathlib import Path
from winreg import CloseKey, HKEY_CURRENT_USER, OpenKey, QueryValueEx, SetValueEx, KEY_READ, KEY_SET_VALUE, REG_EXPAND_SZ


LARAGON_PHP_ROOT = Path(r"C:\laragon\bin\php")
USER_ENV_KEY = r"Environment"
LOCAL_COMPOSER_PATH = Path(".tools") / "composer"
COMPOSER_PATHS = [
    LOCAL_COMPOSER_PATH,
    Path(r"C:\ProgramData\ComposerSetup\bin"),
    Path(os.environ.get("APPDATA", "")) / "Composer" / "vendor" / "bin",
    Path(os.environ.get("LOCALAPPDATA", "")) / "Programs" / "Composer" / "bin",
]


def run(command: list[str], check: bool = True) -> subprocess.CompletedProcess:
    print(f"> {' '.join(command)}")
    return subprocess.run(command, check=check)


def run_composer(args: list[str], check: bool = True) -> subprocess.CompletedProcess:
    local_composer = LOCAL_COMPOSER_PATH / "composer.phar"

    if local_composer.exists():
        return run(["php", str(local_composer), *args], check=check)

    composer_bat = shutil.which("composer.bat")

    if composer_bat:
        return run(["cmd.exe", "/c", composer_bat, *args], check=check)

    composer_cmd = shutil.which("composer")

    if composer_cmd:
        return run(["cmd.exe", "/c", composer_cmd, *args], check=check)

    raise FileNotFoundError("Composer was not found.")


def command_exists(command: str) -> bool:
    return shutil.which(command) is not None


def install_laragon() -> None:
    laragon_exe = Path(r"C:\laragon\laragon.exe")

    if laragon_exe.exists():
        print("Laragon already appears to be installed at C:\\laragon.")
        return

    if not command_exists("winget"):
        print("winget was not found.")
        print("Install App Installer from Microsoft Store, then rerun this script.")
        sys.exit(1)

    print("Installing Laragon with winget. A Windows/UAC confirmation may appear.")
    result = run(
        [
            "winget",
            "install",
            "--id",
            "LeNgocKhoa.Laragon",
            "--exact",
            "--accept-package-agreements",
            "--accept-source-agreements",
        ],
        check=False,
    )

    if result.returncode != 0:
        print("Laragon installation failed or was cancelled.")
        print("You can install Laragon manually, then rerun this script.")
        sys.exit(result.returncode)


def install_composer() -> None:
    if (LOCAL_COMPOSER_PATH / "composer.phar").exists() or command_exists("composer") or command_exists("composer.bat"):
        print("Composer is already available.")
        add_existing_command_path("composer.bat", COMPOSER_PATHS)
        return

    add_existing_command_path("composer.bat", COMPOSER_PATHS)

    if command_exists("composer") or command_exists("composer.bat"):
        print("Composer was found after refreshing PATH.")
        return

    if not command_exists("winget"):
        print("Composer is missing and winget was not found. Falling back to local Composer installer.")
        install_local_composer()
        return

    print("Installing Composer with winget.")
    result = run(
        [
            "winget",
            "install",
            "--id",
            "Composer.Composer",
            "--exact",
            "--accept-package-agreements",
            "--accept-source-agreements",
        ],
        check=False,
    )

    if result.returncode != 0:
        print("winget could not install Composer. Falling back to local Composer installer.")
        install_local_composer()
        return

    add_existing_command_path("composer.bat", COMPOSER_PATHS)

    if not command_exists("composer"):
        print("Composer was installed, but this process cannot see it yet. Falling back to local Composer installer.")
        install_local_composer()


def install_local_composer() -> None:
    if not command_exists("php"):
        print("Cannot install Composer locally because PHP is missing.")
        sys.exit(1)

    LOCAL_COMPOSER_PATH.mkdir(parents=True, exist_ok=True)

    installer_path = Path(os.environ.get("TEMP", ".")) / "composer-setup.php"
    composer_phar = LOCAL_COMPOSER_PATH / "composer.phar"
    composer_bat = LOCAL_COMPOSER_PATH / "composer.bat"

    print("Downloading Composer installer from getcomposer.org.")
    urllib.request.urlretrieve("https://getcomposer.org/installer", installer_path)

    print(f"Installing Composer locally into {LOCAL_COMPOSER_PATH}.")
    run(["php", str(installer_path), f"--install-dir={LOCAL_COMPOSER_PATH}", "--filename=composer.phar"])

    if not composer_phar.exists():
        print("Local Composer installation failed: composer.phar was not created.")
        sys.exit(1)

    composer_bat.write_text('@php "%~dp0composer.phar" %*\n', encoding="ascii")
    os.environ["PATH"] = f"{LOCAL_COMPOSER_PATH.resolve()};{os.environ.get('PATH', '')}"

    print(f"Local Composer is ready at {composer_bat}.")


def find_laragon_php_dir() -> Path:
    if not LARAGON_PHP_ROOT.exists():
        print(f"Could not find {LARAGON_PHP_ROOT}.")
        print("Open Laragon once, make sure PHP is installed, then rerun this script.")
        sys.exit(1)

    php_dirs = sorted(
        [path for path in LARAGON_PHP_ROOT.iterdir() if path.is_dir() and (path / "php.exe").exists()],
        key=lambda path: path.name,
        reverse=True,
    )

    if not php_dirs:
        print("Laragon is installed, but no PHP folder with php.exe was found.")
        print(f"Expected something like: {LARAGON_PHP_ROOT}\\php-8.3.x\\php.exe")
        sys.exit(1)

    php_dir = php_dirs[0]
    print(f"Using PHP from: {php_dir}")
    return php_dir


def enable_php_extensions(php_dir: Path) -> None:
    php_ini = php_dir / "php.ini"
    php_ini_development = php_dir / "php.ini-development"
    php_ini_production = php_dir / "php.ini-production"
    ext_dir = php_dir / "ext"

    if not php_ini.exists():
        if php_ini_development.exists():
            shutil.copyfile(php_ini_development, php_ini)
            print("Created php.ini from php.ini-development.")
        elif php_ini_production.exists():
            shutil.copyfile(php_ini_production, php_ini)
            print("Created php.ini from php.ini-production.")
        else:
            print(f"Could not find a php.ini template in {php_dir}.")
            return

    lines = php_ini.read_text(encoding="utf-8", errors="ignore").splitlines()
    extensions = ["openssl", "curl", "fileinfo", "mbstring", "mysqli", "pdo_mysql", "zip"]
    available_extensions = [
        extension for extension in extensions if (ext_dir / f"php_{extension}.dll").exists()
    ]
    new_lines: list[str] = []
    extension_dir_written = False
    enabled_extensions: set[str] = set()

    for line in lines:
        stripped = line.strip()

        if stripped.startswith("extension_dir") or stripped.startswith(";extension_dir"):
            if not extension_dir_written:
                new_lines.append(f'extension_dir = "{ext_dir}"')
                extension_dir_written = True
            continue

        matched_extension = None

        for extension in available_extensions:
            pattern = rf"^;?\s*extension\s*=\s*(php_)?{re.escape(extension)}(\.dll)?\s*$"

            if re.match(pattern, stripped):
                matched_extension = extension
                break

        if matched_extension:
            if matched_extension not in enabled_extensions:
                new_lines.append(f"extension={matched_extension}")
                enabled_extensions.add(matched_extension)
            continue

        new_lines.append(line)

    if not extension_dir_written:
        new_lines.append(f'extension_dir = "{ext_dir}"')

    for extension in available_extensions:
        if extension not in enabled_extensions:
            new_lines.append(f"extension={extension}")

    php_ini.write_text("\n".join(new_lines) + "\n", encoding="ascii", errors="ignore")
    print(f"Enabled PHP extensions in {php_ini}.")


def get_user_path() -> str:
    try:
        key = OpenKey(HKEY_CURRENT_USER, USER_ENV_KEY, 0, KEY_READ)
        try:
            value, _ = QueryValueEx(key, "Path")
            return value
        finally:
            CloseKey(key)
    except FileNotFoundError:
        return ""


def set_user_path(value: str) -> None:
    key = OpenKey(HKEY_CURRENT_USER, USER_ENV_KEY, 0, KEY_SET_VALUE)
    try:
        SetValueEx(key, "Path", 0, REG_EXPAND_SZ, value)
    finally:
        CloseKey(key)


def add_directory_to_user_path(directory: Path, label: str) -> None:
    directory_path = str(directory)
    current_path = get_user_path()
    path_parts = [part.strip() for part in current_path.split(";") if part.strip()]

    if any(part.lower() == directory_path.lower() for part in path_parts):
        print(f"{label} is already present in the user PATH.")
    else:
        new_path = ";".join(path_parts + [directory_path])
        set_user_path(new_path)
        print(f"Added {label} to the user PATH.")

    os.environ["PATH"] = f"{directory_path};{os.environ.get('PATH', '')}"


def add_existing_command_path(command_name: str, candidate_paths: list[Path]) -> bool:
    if command_exists(command_name):
        return True

    for candidate_path in candidate_paths:
        if candidate_path and (candidate_path / command_name).exists():
            resolved_path = candidate_path.resolve()
            os.environ["PATH"] = f"{resolved_path};{os.environ.get('PATH', '')}"
            print(f"Found {command_name} in {resolved_path} and added it for this process.")
            return True

    return False


def verify_laravel_project() -> None:
    artisan = Path("artisan")
    composer_json = Path("composer.json")

    if artisan.exists() and composer_json.exists():
        print("Laravel project scaffold found in the current folder.")
        return

    print("Warning: current folder does not look like this Laravel project scaffold.")
    print("Run this script from C:\\Users\\artio\\Desktop\\TestProgramator for the final setup step.")


def verify_php_current_process() -> None:
    print("Verifying PHP in the current process:")
    run(["php", "-v"])


def verify_composer_current_process() -> None:
    print("Verifying Composer in the current process:")
    run_composer(["--version"])


def open_powershell_verification() -> None:
    print("Opening a new PowerShell window to verify php -v.")
    local_composer = (LOCAL_COMPOSER_PATH / "composer.phar").resolve()
    composer_command = f"php '{local_composer}' --version" if local_composer.exists() else "composer --version"
    subprocess.Popen(
        [
            "powershell.exe",
            "-NoExit",
            "-Command",
            f"php -v; {composer_command}; Write-Host ''; Write-Host 'If PHP and Composer are shown above, you can now run: .\\setup-windows.ps1' -ForegroundColor Green",
        ],
        creationflags=subprocess.CREATE_NEW_CONSOLE,
    )


def main() -> None:
    if os.name != "nt":
        print("This script is only for Windows.")
        sys.exit(1)

    install_laragon()
    php_dir = find_laragon_php_dir()
    add_directory_to_user_path(php_dir, "PHP")
    enable_php_extensions(php_dir)
    install_composer()
    verify_laravel_project()
    verify_php_current_process()
    verify_composer_current_process()
    open_powershell_verification()

    print("")
    print("Done. Close and reopen your project PowerShell, then run:")
    print(r".\setup-windows.ps1")


if __name__ == "__main__":
    main()
