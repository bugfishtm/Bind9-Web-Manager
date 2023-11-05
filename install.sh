#!/bin/bash
# This script installs PHP 7.4, Nginx, and MariaDB and sets up a web interface for Bind9
# It creates a default user 'webuser' with the password 'webuser'
# Tested on Debian 12, the script can be run on a clean Debian installation
# The script is primarily for testing purposes and configures everything needed for the web interface
# Note: This setup uses Nginx as the web server and does not install Apache

# Install necessary packages
sudo apt install -y lsb-release apt-transport-https ca-certificates wget tar zip unzip

# Add the PHP repository from Ondřej Surý
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/php.list

# Update the package index
sudo apt update

# Install PHP 7.4 and required extensions
sudo apt install -y php7.4 php7.4-fpm php7.4-bcmath php7.4-bz2 php7.4-intl php7.4-gd \
php7.4-mbstring php7.4-zip php7.4-curl php7.4-mysql nginx mariadb-server

# Set up MariaDB user and database
sudo mysql -e "CREATE USER 'webuser'@'%' IDENTIFIED BY 'webuser';"
sudo mysql -e "CREATE DATABASE webuser;"
sudo mysql -e "GRANT ALL PRIVILEGES ON webuser.* TO 'webuser'@'%';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Create the web interface directory
sudo mkdir -p /var/www/web-interface
cd /var/www/web-interface

# Download and unzip the Bind9 Web Manager
sudo wget https://github.com/bugfishtm/Bind9-Web-Manager/archive/refs/tags/3.7.2.zip
sudo unzip -x 3.7.2.zip

# Set up the configuration file for Bind9 Web Manager
sudo mv /var/www/web-interface/Bind9-Web-Manager-3.7.2/_source/settings.sample.php /var/www/web-interface/Bind9-Web-Manager-3.7.2/_source/settings.php
sudo sed -i 's/DBUSER/webuser/g' /var/www/web-interface/Bind9-Web-Manager-3.7.2/_source/settings.php
sudo sed -i 's/DBPASS/webuser/g' /var/www/web-interface/Bind9-Web-Manager-3.7.2/_source/settings.php
sudo sed -i 's/DBNAME/webuser/g' /var/www/web-interface/Bind9-Web-Manager-3.7.2/_source/settings.php

# Configure Nginx to serve the web interface
sudo sed -i 's|root /var/www/html;|root /var/www/web-interface/Bind9-Web-Manager-3.7.2/_source;|' /etc/nginx/sites-available/default
sudo sed -i 's|index index.html index.htm index.nginx-debian.html;|index index.php;|' /etc/nginx/sites-available/default
sudo sed -i 's|#location ~ \\.php$ {|location ~ \\.php$ {|' /etc/nginx/sites-available/default
sudo sed -i 's|#\tinclude snippets/fastcgi-php.conf;|\tinclude snippets/fastcgi-php.conf;|' /etc/nginx/sites-available/default
sudo sed -i 's|#\tfastcgi_pass unix:/run/php/php7.4-fpm.sock;|\tfastcgi_pass unix:/run/php/php7.4-fpm.sock;|' /etc/nginx/sites-available/default
sudo sed -i '/fastcgi_pass unix:\/run\/php\/php7.4-fpm.sock;/a \}' /etc/nginx/sites-available/default

# Reload Nginx to apply the changes
sudo systemctl reload nginx
