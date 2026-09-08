"""Deploy wpu-hostinger.zip to Hostinger over SSH/SFTP.

Required environment variables:
  HOSTINGER_SSH_HOST
  HOSTINGER_SSH_USER
  HOSTINGER_SSH_PASSWORD
  HOSTINGER_REMOTE_ROOT   e.g. /home/u899628465/domains/wpuhealth.online/public_html
  HOSTINGER_APP_URL       e.g. https://wpuhealth.online
  HOSTINGER_DB_NAME
  HOSTINGER_DB_USER
  HOSTINGER_DB_PASSWORD

Optional:
  HOSTINGER_SSH_PORT      (default: 65002)
  HOSTINGER_REMOTE_ZIP    (default: ~/wpu-hostinger.zip)
"""

from __future__ import annotations

import os
import re
import sys
import time
from pathlib import Path

import paramiko

ROOT = Path(__file__).resolve().parents[1]
LOCAL_ZIP = ROOT / "wpu-hostinger.zip"

DEFAULTS: dict[str, str] = {
    "HOSTINGER_SSH_HOST": "109.106.254.155",
    "HOSTINGER_SSH_PORT": "65002",
    "HOSTINGER_SSH_USER": "u899628465",
    "HOSTINGER_REMOTE_ROOT": "/home/u899628465/domains/wpuhealth.online/public_html",
    "HOSTINGER_REMOTE_ZIP": "/home/u899628465/wpu-hostinger.zip",
    "HOSTINGER_APP_URL": "https://wpuhealth.online",
    "HOSTINGER_DB_NAME": "u899628465_health_records",
    "HOSTINGER_DB_USER": "u899628465_wpu_health",
}


def env(name: str, default: str | None = None) -> str:
    value = os.environ.get(name) or default or DEFAULTS.get(name)
    if value is None or value == "":
        raise SystemExit(f"Missing required environment variable: {name}")

    return value


def config() -> dict[str, str | int]:
    ssh_user = env("HOSTINGER_SSH_USER")

    return {
        "host": env("HOSTINGER_SSH_HOST"),
        "port": int(env("HOSTINGER_SSH_PORT")),
        "user": ssh_user,
        "password": env("HOSTINGER_SSH_PASSWORD"),
        "remote_zip": env("HOSTINGER_REMOTE_ZIP"),
        "remote_root": env("HOSTINGER_REMOTE_ROOT"),
        "app_url": env("HOSTINGER_APP_URL").rstrip("/"),
        "db_name": env("HOSTINGER_DB_NAME"),
        "db_user": env("HOSTINGER_DB_USER"),
        "db_pass": env("HOSTINGER_DB_PASSWORD"),
    }


def connect(cfg: dict[str, str | int]) -> paramiko.SSHClient:
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(
        str(cfg["host"]),
        port=int(cfg["port"]),
        username=str(cfg["user"]),
        password=str(cfg["password"]),
        timeout=30,
        allow_agent=False,
        look_for_keys=False,
        banner_timeout=30,
    )

    return client


def run(client: paramiko.SSHClient, command: str, timeout: int = 120) -> tuple[int, str, str]:
    sys.stdout.buffer.write(f"$ {command[:180]}\n".encode())
    _stdin, stdout, stderr = client.exec_command(command, timeout=timeout)
    out = stdout.read().decode("utf-8", "replace")
    err = stderr.read().decode("utf-8", "replace")
    code = stdout.channel.recv_exit_status()
    if out.strip():
        sys.stdout.buffer.write(out.strip()[:4000].encode("utf-8", "replace"))
        sys.stdout.buffer.write(b"\n")
    if err.strip():
        sys.stdout.buffer.write(b"ERR: ")
        sys.stdout.buffer.write(err.strip()[:2000].encode("utf-8", "replace"))
        sys.stdout.buffer.write(b"\n")
    sys.stdout.buffer.write(f"exit {code}\n".encode())
    sys.stdout.flush()

    return code, out, err


def upload_zip(client: paramiko.SSHClient, cfg: dict[str, str | int]) -> None:
    remote_zip = str(cfg["remote_zip"])
    size = LOCAL_ZIP.stat().st_size
    print(f"Uploading {LOCAL_ZIP.name} ({size / (1024 * 1024):.1f} MB) -> {remote_zip}")
    sftp = client.open_sftp()
    last = [0.0]

    def cb(transferred: int, total: int) -> None:
        now = time.time()
        if now - last[0] >= 2 or transferred == total:
            last[0] = now
            pct = (transferred / total) * 100 if total else 0
            print(f"  {pct:5.1f}%  {transferred / (1024 * 1024):.1f}/{total / (1024 * 1024):.1f} MB")

    sftp.put(str(LOCAL_ZIP), remote_zip, callback=cb)
    sftp.close()
    print("Upload complete")


def write_env(client: paramiko.SSHClient, cfg: dict[str, str | int]) -> None:
    remote_root = str(cfg["remote_root"])
    env_body = f"""APP_NAME="WPU Health Services"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL={cfg["app_url"]}
APP_TIMEZONE=Asia/Manila

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE={cfg["db_name"]}
DB_USERNAME={cfg["db_user"]}
DB_PASSWORD="{cfg["db_pass"]}"

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=true
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync

CACHE_STORE=file

MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@wpuhealth.online"
MAIL_FROM_NAME="${{APP_NAME}}"

VITE_APP_NAME="${{APP_NAME}}"
"""
    sftp = client.open_sftp()
    env_path = f"{remote_root}/.env"
    with sftp.file(env_path, "w") as fh:
        fh.write(env_body)
    sftp.chmod(env_path, 0o600)
    sftp.close()
    print("Wrote production .env")


def fix_app_key(client: paramiko.SSHClient, cfg: dict[str, str | int]) -> None:
    remote_root = str(cfg["remote_root"])
    _, out, _ = run(client, f"cd {remote_root} && php artisan key:generate --show", timeout=60)
    key = out.strip().splitlines()[-1].strip()
    if not key.startswith("base64:"):
        raise RuntimeError(f"Unexpected key output: {out!r}")

    sftp = client.open_sftp()
    env_path = f"{remote_root}/.env"
    with sftp.file(env_path, "r") as fh:
        env_text = fh.read().decode("utf-8")
    if re.search(r"^APP_KEY=", env_text, flags=re.M):
        env_text = re.sub(r"^APP_KEY=.*$", f"APP_KEY={key}", env_text, count=1, flags=re.M)
    else:
        env_text = env_text.replace("APP_ENV=production", f"APP_KEY={key}\nAPP_ENV=production", 1)
    with sftp.file(env_path, "w") as fh:
        fh.write(env_text)
    sftp.chmod(env_path, 0o600)
    sftp.close()
    print("Set APP_KEY")


def db_test_php(cfg: dict[str, str | int]) -> str:
    db_user = str(cfg["db_user"])
    db_name = str(cfg["db_name"])
    db_pass = str(cfg["db_pass"]).replace("\\", "\\\\").replace("'", "\\'")

    return f"""<?php
$hosts = ['localhost', '127.0.0.1'];
$user = '{db_user}';
$db = '{db_name}';
$pass = '{db_pass}';

foreach ($hosts as $host) {{
    $c = @new mysqli($host, $user, $pass, $db);
    if ($c->connect_errno) {{
        echo "FAIL host=$host err=" . $c->connect_error . PHP_EOL;
        continue;
    }}
    echo "OK host=$host" . PHP_EOL;
    exit(0);
}}
exit(1);
"""


def upload_db_test(client: paramiko.SSHClient, cfg: dict[str, str | int]) -> str:
    remote_root = str(cfg["remote_root"])
    db_test_path = f"{remote_root}/storage/db_test.php"
    sftp = client.open_sftp()
    with sftp.file(db_test_path, "w") as fh:
        fh.write(db_test_php(cfg))
    sftp.close()

    return db_test_path


def verify_site(client: paramiko.SSHClient, cfg: dict[str, str | int]) -> None:
    host_header = str(cfg["app_url"]).replace("https://", "").replace("http://", "").strip("/")
    run(
        client,
        "curl -sS -o /tmp/wpu_home.html -w 'HTTP %{http_code} size %{size_download}\\n' "
        f"-H 'Host: {host_header}' https://127.0.0.1/ -k",
    )
    run(
        client,
        "curl -sS -o /tmp/wpu_login.html -w 'HTTP %{http_code} size %{size_download}\\n' "
        f"-H 'Host: {host_header}' https://127.0.0.1/login -k",
    )
    run(
        client,
        "php -r '$h=file_get_contents(\"/tmp/wpu_home.html\"); if(preg_match(\"/<title>(.*?)<\\/title>/si\",$h,$m)) echo $m[1],PHP_EOL;'",
    )


def main() -> None:
    if not LOCAL_ZIP.is_file():
        raise SystemExit(f"Missing deploy artifact: {LOCAL_ZIP}")

    cfg = config()
    remote_root = str(cfg["remote_root"])
    remote_zip = str(cfg["remote_zip"])

    client = connect(cfg)
    try:
        upload_zip(client, cfg)
        run(client, f"rm -f {remote_root}/default.php")
        run(
            client,
            f"cd {remote_root} && unzip -o -q {remote_zip} && rm -f {remote_zip} && echo unzip_ok && ls | wc -l",
            timeout=180,
        )
        write_env(client, cfg)
        run(
            client,
            " && ".join(
                [
                    f"cd {remote_root}",
                    "mkdir -p storage/app storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache unified_portal/logs",
                    "chmod -R ug+rwx storage bootstrap/cache unified_portal/logs",
                ]
            ),
        )
        fix_app_key(client, cfg)

        db_test_path = upload_db_test(client, cfg)
        code, _out, _err = run(client, f"php {db_test_path}", timeout=30)
        run(client, f"rm -f {db_test_path}")

        if code != 0:
            print("MySQL not ready yet; skipping migrations until database credentials work.")
        else:
            run(client, f"cd {remote_root} && php artisan migrate --force", timeout=180)
            run(client, f"cd {remote_root} && php artisan db:seed --class=AdminSeeder --force", timeout=60)

        run(
            client,
            f"cd {remote_root} && php artisan config:cache && php artisan route:cache && php artisan view:cache",
            timeout=60,
        )
        run(client, f"ls -la {remote_root} | head")
        verify_site(client, cfg)
    finally:
        client.close()


if __name__ == "__main__":
    main()
