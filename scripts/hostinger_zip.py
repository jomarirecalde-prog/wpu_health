"""Build a Hostinger deploy zip from the project tree."""

from __future__ import annotations

import os
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ZIP_PATH = ROOT / "wpu-hostinger.zip"

EXCLUDE_DIR_NAMES = {
    ".git",
    ".github",
    ".netlify",
    ".idea",
    ".vscode",
    ".cursor",
    "node_modules",
    "netlify-dist",
    "dist-infinityfree",
    "tests",
    ".phpunit.cache",
}
EXCLUDE_FILE_NAMES = {
    ".env",
    ".env.backup",
    ".env.production",
    "wpu-infinityfree.zip",
    "wpu-hostinger.zip",
    "Homestead.json",
    "Homestead.yaml",
    "infinityfree-unzip.php",
    "infinityfree-install.php",
    ".DS_Store",
    "Thumbs.db",
}
SKIP_PATH_PARTS = {
    Path("storage") / "logs",
    Path("storage") / "framework" / "sessions",
    Path("storage") / "framework" / "views",
    Path("storage") / "framework" / "cache" / "data",
    Path("storage") / "framework" / "wpu_cache",
    Path("storage") / "pail",
}


def should_skip(rel: Path) -> bool:
    parts = rel.parts
    if any(p in EXCLUDE_DIR_NAMES for p in parts):
        return True
    if rel.name in EXCLUDE_FILE_NAMES:
        return True
    if rel.parent == Path("scripts") and rel.name.startswith("_"):
        return True
    if rel.suffix == ".log":
        return True
    for skip in SKIP_PATH_PARTS:
        try:
            rel.relative_to(skip)
            return True
        except ValueError:
            pass
    return False


def main() -> None:
    if ZIP_PATH.exists():
        ZIP_PATH.unlink()

    count = 0
    with zipfile.ZipFile(ZIP_PATH, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=6) as zf:
        for dirpath, dirnames, filenames in os.walk(ROOT):
            rel_dir = Path(dirpath).relative_to(ROOT)
            dirnames[:] = [d for d in dirnames if d not in EXCLUDE_DIR_NAMES]
            if any(p in EXCLUDE_DIR_NAMES for p in rel_dir.parts):
                continue
            for name in filenames:
                rel = rel_dir / name if rel_dir != Path(".") else Path(name)
                if should_skip(rel):
                    continue
                zf.write(ROOT / rel, rel.as_posix())
                count += 1

        placeholder_dirs = [
            "storage/logs",
            "storage/framework/sessions",
            "storage/framework/views",
            "storage/framework/cache/data",
            "bootstrap/cache",
            "unified_portal/logs",
        ]
        for d in placeholder_dirs:
            zf.writestr(f"{d}/.gitkeep", "")

    size_mb = ZIP_PATH.stat().st_size / (1024 * 1024)
    print(f"Wrote {count} files to {ZIP_PATH} ({size_mb:.1f} MB)")


if __name__ == "__main__":
    main()
