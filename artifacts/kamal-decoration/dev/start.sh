#!/usr/bin/env bash
# Dev harness for Replit preview ONLY.
# The deliverable (what gets uploaded to Namecheap) is the ./site folder — pure PHP + MySQL.
set -euo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SITE="$DIR/site"
DEVDB="$DIR/.devdb"
DATADIR="$DEVDB/data"
SOCK="/tmp/kamal-mysql.sock"
DBPORT=3307
DBNAME="kamal_decoration"
DBUSER="kamal"
DBPASS="kamal_dev_pass"

mkdir -p "$DEVDB"

# 1) Initialize MariaDB data dir on first run
if [ ! -d "$DATADIR/mysql" ]; then
  echo "[dev] initializing MariaDB data directory..."
  mariadb-install-db --datadir="$DATADIR" \
    --auth-root-authentication-method=normal --skip-test-db >/dev/null 2>&1
fi

# 2) Start MariaDB if not already running
if ! mariadb-admin --socket="$SOCK" ping >/dev/null 2>&1; then
  rm -f "$SOCK"
  echo "[dev] starting MariaDB..."
  mariadbd --datadir="$DATADIR" --socket="$SOCK" \
    --port="$DBPORT" --bind-address=127.0.0.1 --skip-name-resolve \
    --innodb_buffer_pool_size=64M \
    --pid-file="$DEVDB/mariadb.pid" >>"$DEVDB/mariadb.log" 2>&1 &
  ok=0
  for _ in $(seq 1 120); do
    if mariadb-admin --socket="$SOCK" ping >/dev/null 2>&1; then ok=1; break; fi
    sleep 0.5
  done
  if [ "$ok" != "1" ]; then
    echo "[dev] MariaDB failed to start; tail of log:" >&2
    tail -n 40 "$DEVDB/mariadb.log" >&2 || true
    exit 1
  fi
fi

# 3) Ensure database + user
mariadb --socket="$SOCK" -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`$DBNAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DBUSER'@'127.0.0.1' IDENTIFIED BY '$DBPASS';
CREATE USER IF NOT EXISTS '$DBUSER'@'localhost' IDENTIFIED BY '$DBPASS';
GRANT ALL PRIVILEGES ON \`$DBNAME\`.* TO '$DBUSER'@'127.0.0.1';
GRANT ALL PRIVILEGES ON \`$DBNAME\`.* TO '$DBUSER'@'localhost';
FLUSH PRIVILEGES;
SQL

# 4) Import schema + sample data on first run
TABLES=$(mariadb --socket="$SOCK" -u root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DBNAME'")
if [ "$TABLES" -eq 0 ] && [ -f "$SITE/database.sql" ]; then
  echo "[dev] importing database.sql..."
  mariadb --socket="$SOCK" -u root "$DBNAME" < "$SITE/database.sql"
fi

# 5) Dev config + idempotent dev seed (admin user, site URL, QR/barcode files)
if [ ! -f "$SITE/config/config.php" ]; then
  php -c "$DIR/dev/php.ini" "$SITE/tools/make-dev-config.php" "$DBNAME" "$DBUSER" "$DBPASS" "127.0.0.1" "$DBPORT"
fi
php -c "$DIR/dev/php.ini" "$SITE/tools/dev-seed.php" || echo "[dev] seed step reported a problem (continuing)"

# 6) Serve the site (router emulates .htaccess clean URLs)
# php -S is single-threaded by default; browsers fetch CSS/JS/images in parallel,
# so give it workers or asset requests can time out behind the preview proxy.
export PHP_CLI_SERVER_WORKERS=8
echo "[dev] serving on port ${PORT:?PORT env var is required}"
exec php -c "$DIR/dev/php.ini" -S 0.0.0.0:"$PORT" -t "$SITE" "$DIR/dev/router.php"
