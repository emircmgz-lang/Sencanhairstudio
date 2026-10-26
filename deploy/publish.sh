#!/usr/bin/env bash
# Usage: bash deploy/publish.sh /absolute/domain-directory [--include-setup]
set -euo pipefail
umask 027
repo=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd -P)
if [[ $# -lt 1 || $# -gt 2 || "$1" != /* || "$1" == / ]]; then
  echo 'Usage: bash deploy/publish.sh /absolute/domain-directory [--include-setup]' >&2; exit 2
fi
if [[ $# == 2 && "$2" != --include-setup ]]; then echo 'Unknown option' >&2; exit 2; fi
for dependency in rsync flock; do command -v "$dependency" >/dev/null || { echo "Required: $dependency" >&2; exit 1; }; done
[[ -f "$repo/public_html/index.html" && -f "$repo/private/bootstrap.php" ]] || exit 1
mkdir -p -- "$1"
target=$(cd -- "$1" && pwd -P)
# Keep the Git checkout outside the public directory.
case "$repo/" in "$target/htdocs/"*) echo 'Git checkout must be outside htdocs.' >&2; exit 1;; esac
exec 9>"$target/.sencan-deploy.lock"
flock -n 9 || { echo 'Another deployment is running.' >&2; exit 1; }
mkdir -p -- "$target/htdocs" "$target/private"
[[ ! -L "$target/htdocs" && ! -L "$target/private" ]] || { echo 'Target directories must not be symlinks.' >&2; exit 1; }
# No --delete: preserve server config, backups, uploads and runtime state.
rsync -rt --chmod=D750,F640 --exclude=config.php --exclude=backups/ --exclude=media/ --exclude=cron-status.json --exclude=.cron.lock -- "$repo/private/" "$target/private/"
public_options=(--exclude=setup.php)
if [[ ${2:-} == --include-setup ]]; then public_options=(); fi
rsync -rt --chmod=D755,F644 "${public_options[@]}" -- "$repo/public_html/" "$target/htdocs/"
echo 'Published. Web root: htdocs; private config and runtime files preserved.'
