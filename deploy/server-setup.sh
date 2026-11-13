#!/usr/bin/env bash
# ----------------------------------------------------------------------
# One-time EC2 setup for JobTrack (Ubuntu 24.04 LTS).
#
# Run ONCE on a fresh instance, as the default "ubuntu" user:
#   curl -fsSL https://raw.githubusercontent.com/asankasandz/jobtrack/main/deploy/server-setup.sh -o setup.sh
#   bash setup.sh
#
# Installs Apache + PHP + MySQL, creates the database and a dedicated DB
# user with a random password, writes config/database.php (which never
# goes into git), and points Apache's document root at public/ so that
# app/, config/, database/ and storage/ can never be reached by URL.
# ----------------------------------------------------------------------
set -euo pipefail

# Optional: DOMAIN=job-track.example.com bash setup.sh
# When set, Apache answers to that name and a free Let's Encrypt HTTPS
# certificate is installed (the DNS A record must already point here).
DOMAIN=${DOMAIN:-}

APP_DIR=/var/www/jobtrack
DB_NAME=jobtrack
DB_USER=jobtrack
DEPLOY_USER=${SUDO_USER:-$USER}

echo "==> Installing Apache, PHP and MySQL"
sudo apt-get update -y
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
  apache2 mysql-server rsync \
  php libapache2-mod-php php-mysql php-mbstring

echo "==> Creating app folders"
sudo mkdir -p "$APP_DIR"/{config,storage/resumes,public/assets/uploads/vacancies}
sudo chown -R "$DEPLOY_USER":www-data "$APP_DIR"
# Apache (www-data) must be able to write uploaded CVs and vacancy images.
sudo chmod -R 775 "$APP_DIR"/storage "$APP_DIR"/public/assets/uploads
# New files created inside inherit the www-data group.
sudo find "$APP_DIR" -type d -exec chmod g+s {} +

echo "==> Creating database and DB user"
DB_PASS=$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 24)
sudo mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "==> Writing config/database.php (server only, not in git)"
cat > "$APP_DIR/config/database.php" <<PHP
<?php

return [
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => '$DB_NAME',
    'username' => '$DB_USER',
    'password' => '$DB_PASS',
    'charset' => 'utf8mb4',
];
PHP
chmod 640 "$APP_DIR/config/database.php"
sudo chgrp www-data "$APP_DIR/config/database.php"

echo "==> Configuring Apache (document root = public/)"
sudo tee /etc/apache2/sites-available/jobtrack.conf >/dev/null <<APACHE
<VirtualHost *:80>
    ServerName ${DOMAIN:-localhost}
    DocumentRoot $APP_DIR/public
    DirectoryIndex index.php

    <Directory $APP_DIR/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Never execute scripts that were uploaded as "images".
    <Directory $APP_DIR/public/assets/uploads>
        php_admin_flag engine off
        <FilesMatch "\.(php|phar|phtml)$">
            Require all denied
        </FilesMatch>
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/jobtrack-error.log
    CustomLog \${APACHE_LOG_DIR}/jobtrack-access.log combined
</VirtualHost>
APACHE
sudo a2dissite 000-default >/dev/null
sudo a2ensite jobtrack >/dev/null
sudo systemctl reload apache2

echo "==> Production PHP settings (hide errors from visitors)"
PHP_INI=$(php -r 'echo "/etc/php/" . PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION . "/apache2/php.ini";')
sudo sed -i 's/^display_errors = .*/display_errors = Off/; s/^upload_max_filesize = .*/upload_max_filesize = 4M/; s/^post_max_size = .*/post_max_size = 8M/' "$PHP_INI"
sudo sed -i 's/^;\?session.cookie_httponly =.*/session.cookie_httponly = 1/' "$PHP_INI"
sudo systemctl restart apache2

if [ -n "$DOMAIN" ]; then
  echo "==> Installing HTTPS certificate for $DOMAIN"
  sudo apt-get install -y certbot python3-certbot-apache
  # --redirect: http://domain is sent to https://domain automatically.
  # Certbot also installs a timer that renews the certificate before it expires.
  sudo certbot --apache -d "$DOMAIN" --non-interactive --agree-tos \
    --register-unsafely-without-email --redirect
  # Session cookie only over HTTPS once the site has a certificate.
  sudo sed -i 's/^;\?session.cookie_secure =.*/session.cookie_secure = 1/' "$PHP_INI"
  sudo systemctl restart apache2
fi

cat <<DONE

==================================================================
 Server ready. Next steps:
 1. In GitHub, add the repository secrets EC2_HOST, EC2_USER, EC2_SSH_KEY
    and push to main (or run the "Deploy" workflow manually).
 2. The first deploy creates the tables automatically. Then create
    the HR account (once):
      php $APP_DIR/database/seed.php you@example.com 'StrongPassword' "HR Admin"
 3. Open ${DOMAIN:+https://$DOMAIN/ (or) }http://<your-ec2-public-ip>/
==================================================================
DONE
