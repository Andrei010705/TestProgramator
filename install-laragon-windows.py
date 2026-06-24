import os
import shutil
import subprocess
import sys
from pathlib import Path
from winreg import CloseKey, HKEY_CURRENT_USER, OpenKey, QueryValueEx, SetValueEx, KEY_READ, KEY_SET_VALUE, REG_EXPAND_SZ


LARAGON_PHP_ROOT = Path(r"C:\laragon\bin\php")
USER_ENV_KEY = r"Environment"


def run(command: list[str], check: bool = True) -> subprocess.CompletedProcess:
    print(f"> {' '.join(command)}")
    return subprocess.run(command, check=check)


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


def add_php_to_user_path(php_dir: Path) -> None:
    php_path = str(php_dir)
    current_path = get_user_path()
    path_parts = [part.strip() for part in current_path.split(";") if part.strip()]

    if any(part.lower() == php_path.lower() for part in path_parts):
        print("PHP is already present in the user PATH.")
    else:
        new_path = ";".join(path_parts + [php_path])
        set_user_path(new_path)
        print("Added PHP to the user PATH.")

    os.environ["PATH"] = f"{php_path};{os.environ.get('PATH', '')}"


def verify_php_current_process() -> None:
    print("Verifying PHP in the current process:")
    run(["php", "-v"])


def open_powershell_verification() -> None:
    print("Opening a new PowerShell window to verify php -v.")
    subprocess.Popen(
        [
            "powershell.exe",
            "-NoExit",
            "-Command",
            "php -v; Write-Host ''; Write-Host 'If PHP is shown above, you can now run: .\\setup-windows.ps1' -ForegroundColor Green",
        ],
        creationflags=subprocess.CREATE_NEW_CONSOLE,
    )


def main() -> None:
    if os.name != "nt":
        print("This script is only for Windows.")
        sys.exit(1)

    install_laragon()
    php_dir = find_laragon_php_dir()
    add_php_to_user_path(php_dir)
    verify_php_current_process()
    open_powershell_verification()

    print("")
    print("Done. Close and reopen your project PowerShell, then run:")
    print(r".\setup-windows.ps1")


if __name__ == "__main__":
    main()
