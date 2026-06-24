import os
import shutil
import subprocess
import sys
from pathlib import Path


PROJECT_ROOT = Path(__file__).resolve().parent
SHELL_PROFILE_MARKER_START = "# TestProgramator macOS PATH start"
SHELL_PROFILE_MARKER_END = "# TestProgramator macOS PATH end"


def run(command: list[str], check: bool = True) -> subprocess.CompletedProcess:
    print(f"> {' '.join(command)}")
    return subprocess.run(command, check=check)


def command_exists(command: str) -> bool:
    return shutil.which(command) is not None


def ensure_macos() -> None:
    if sys.platform != "darwin":
        print("This script is only for macOS.")
        sys.exit(1)


def install_homebrew_if_missing() -> None:
    if command_exists("brew"):
        print("Homebrew is already available.")
        return

    print("Homebrew is missing.")
    print("Install it from https://brew.sh, then reopen Terminal and rerun this script.")
    print("Official command:")
    print('/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"')
    sys.exit(1)


def brew_install_if_missing(command: str, formula: str) -> None:
    if command_exists(command):
        print(f"{command} is already available.")
        return

    print(f"Installing {formula} with Homebrew.")
    run(["brew", "install", formula])


def ensure_brew_shellenv() -> None:
    brew_prefix = subprocess.check_output(["brew", "--prefix"], text=True).strip()
    brew_bin = str(Path(brew_prefix) / "bin")

    if brew_bin not in os.environ.get("PATH", ""):
        os.environ["PATH"] = f"{brew_bin}:{os.environ.get('PATH', '')}"

    shell = Path(os.environ.get("SHELL", "")).name
    profile = Path.home() / (".zshrc" if shell == "zsh" else ".bash_profile")
    export_block = (
        f"{SHELL_PROFILE_MARKER_START}\n"
        f'eval "$({brew_bin}/brew shellenv)"\n'
        f"{SHELL_PROFILE_MARKER_END}\n"
    )

    current = profile.read_text(encoding="utf-8", errors="ignore") if profile.exists() else ""

    if SHELL_PROFILE_MARKER_START not in current:
        with profile.open("a", encoding="utf-8") as handle:
            if current and not current.endswith("\n"):
                handle.write("\n")
            handle.write("\n" + export_block)
        print(f"Added Homebrew shellenv to {profile}.")
    else:
        print(f"Homebrew shellenv already present in {profile}.")


def start_mysql_service() -> None:
    result = run(["brew", "services", "start", "mysql"], check=False)

    if result.returncode != 0:
        print("Could not start MySQL with brew services.")
        print("Try manually: brew services restart mysql")
        sys.exit(result.returncode)


def verify_laravel_project() -> None:
    if (PROJECT_ROOT / "artisan").exists() and (PROJECT_ROOT / "composer.json").exists():
        print("Laravel project scaffold found.")
        return

    print("Warning: this folder does not look like the Laravel project scaffold.")
    print(f"Expected artisan and composer.json in: {PROJECT_ROOT}")


def verify_commands() -> None:
    run(["php", "-v"])
    run(["composer", "--version"])
    run(["mysql", "--version"])


def main() -> None:
    ensure_macos()
    install_homebrew_if_missing()
    ensure_brew_shellenv()

    brew_install_if_missing("php", "php")
    brew_install_if_missing("composer", "composer")
    brew_install_if_missing("mysql", "mysql")

    start_mysql_service()
    verify_laravel_project()
    verify_commands()

    print("")
    print("macOS prerequisites are ready.")
    print("Next run:")
    print("./setup-macos.sh")


if __name__ == "__main__":
    main()
