#!/bin/sh

########################################################################################################################################################
# Startup Procedure
########################################################################################################################################################

############################################################################
# Define Color Codes
############################################################################
GREEN=$(tput setaf 2)
YELLOW=$(tput setaf 3)
RED=$(tput setaf 1)
RESET=$(tput sgr0)

############################################################################
# Check Interpreter
############################################################################
if [ "$BASH_VERSION" ] ; then
    echo 
	echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
	echo "   DNSHTTP Installation Script"
	echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
    echo 
    echo " Use the following command to start the script."
    echo " Command: sh $0"
    echo 
    echo " ${RED}Error${RESET}: Please do not use bash interpreter to run this script ($0)"
    echo " ${RED}Error${RESET}: Execution aborted."
    echo 
    echo 
    exit 1
fi

############################################################################
# Check for Root Script Access
############################################################################
if [ "$(id -u)" -ne 0 ]; then
    echo 
	echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
	echo "   DNSHTTP Installation Script"
	echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
	echo 
	echo " ${RED}Error${RESET}: This script must be run as root."
	echo " ${RED}Error${RESET}: Execution aborted."
	echo 
	exit 1
fi

############################################################################
# Check if OS is valid
############################################################################
#if [ -f /etc/issue ]; then
#	VERSION_INFO=$(cat /etc/issue)
#	if echo "$VERSION_INFO" | grep -q "Ubuntu" && echo "$VERSION_INFO" | grep -q "24"; then
#		echo -n ""
#	else
#		echo 
#		echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
#		echo "   DNSHTTP Installation Script"
#		echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
#		echo
#		echo " ${RED}Error${RESET}: This script only works on Ubuntu 24, you are using a different OS."
#		echo " ${RED}Error${RESET}: Execution aborted."
#		echo 
#		exit 1
#	fi
#else
#	echo 
#	echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
#	echo "   DNSHTTP Installation Script"
#	echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
#	echo
#	echo " ${RED}Error${RESET}: /etc/issue file not found. Cannot determine OS version."
#	echo " ${RED}Error${RESET}: Execution aborted."
#	echo 
#	exit 1
#fi

########################################################################################################################################################
# Functions
########################################################################################################################################################

############################################################################
# Loading Spinner Function
############################################################################
spinner() {
    local pid="${1:-$!}"
    local i=1
    while kill -0 $pid 2>/dev/null; do
        dots=$(printf "%-${i}s" "" | tr ' ' '.')
        printf "\r Loading${dots} "
        i=$((i + 1))
        if [ $i -gt 5 ]; then
            i=1 
        fi
        sleep 0.5 
    done
    printf "\r"
}

############################################################################
# Install Package check by Command
############################################################################
check_and_install() {
    CMD="$1"
    PKG="$2"
	if [ "$current_script_install_everything" -eq 1 ] || [ "$current_script_install_dependencies" -eq 1 ] ; then
		DEBIAN_FRONTEND=noninteractive APT_PAGER=cat apt install -y "$PKG" >/dev/null 2>&1
		if ! command -v "$CMD" >/dev/null 2>&1; then
			if command -v "$CMD" >/dev/null 2>&1; then
				echo " ${GREEN}Package available${RESET}: $CMD"
			else
				echo " ${RED}Error${RESET}: Package '$CMD' could not be installed."
				echo " ${RED}Error${RESET}: Please install '$PKG' manually using: apt install $PKG."
				echo " ${RED}Error${RESET}: Execution aborted."
				echo 
				exit 1
			fi
		else
			 echo " ${GREEN}Package available${RESET}: $CMD"
		fi
    fi
	if [ "$current_script_show_info" -eq 1 ]; then
        if command -v "$CMD" >/dev/null 2>&1; then
            echo " ${GREEN}Package available${RESET}: $CMD"
        else
			echo " ${RED}Package missing${RESET}: $CMD"
        fi		
	fi
}

############################################################################
# Install Package with Check
############################################################################
check_and_install_pkg() {
    PKG="$1"
	if [ "$current_script_install_everything" -eq 1 ] || [ "$current_script_install_dependencies" -eq 1 ] ; then
		DEBIAN_FRONTEND=noninteractive APT_PAGER=cat apt install -y "$PKG" >/dev/null 2>&1
		if ! dpkg -l | grep -qw "$PKG"; then
			if dpkg -l | grep -qw "$PKG"; then
				echo " ${GREEN}Package available${RESET}: $PKG"
			else
				echo " ${RED}Error${RESET}: Package '$PKG' could not be installed."
				echo " ${RED}Error${RESET}: Please install '$PKG' manually using: apt install $PKG."
				echo " ${RED}Error${RESET}: Execution aborted."
				echo 
				exit 1
			fi
		else
			echo " ${GREEN}Package available${RESET}: $PKG"
		fi
    fi
	if [ "$current_script_show_info" -eq 1 ]; then
        if dpkg -l | grep -qw "$PKG"; then
            echo " ${GREEN}Package available${RESET}: $PKG"
        else
			echo " ${RED}Package missing${RESET}: $PKG"
        fi		
	fi
}

############################################################################
# Install Package with Custom Check Command
############################################################################
check_and_install_pkg_cname() {
    PKG="$1"
    PKG2="$2"
	if [ "$current_script_install_everything" -eq 1 ] || [ "$current_script_install_dependencies" -eq 1 ] ; then
		DEBIAN_FRONTEND=noninteractive APT_PAGER=cat apt install -y "$PKG" >/dev/null 2>&1
		if ! dpkg -l | grep -qw "$PKG2"; then
			if dpkg -l | grep -qw "$PKG"; then
				echo " ${GREEN}Package available${RESET}: $PKG"
			else
				echo " ${RED}Error${RESET}: Package '$PKG' could not be installed."
				echo " ${RED}Error${RESET}: Please install '$PKG' manually using: apt install $PKG."
				echo " ${RED}Error${RESET}: Execution aborted."
				echo 
				exit 1
			fi
		else
			echo " ${GREEN}Package available${RESET}: $PKG"
		fi
    fi
	if [ "$current_script_show_info" -eq 1 ]; then
        if dpkg -l | grep -qw "$PKG"; then
            echo " ${GREEN}Package available${RESET}: $PKG"
        else
			echo " ${RED}Package missing${RESET}: $PKG"
        fi		
	fi
}

############################################################################
# Enable Apache2 Module
############################################################################
enable_apache_module() {
    MODULE="$1"
	if [ "$current_script_install_everything" -eq 1 ]; then
		a2enmod "$MODULE" > /dev/null 2>&1 
		if [ $? -eq 0 ]; then
			echo " ${GREEN}Apache2-Module enabled${RESET}: $MODULE"
		else
			echo " ${RED}Error${RESET}: Apache module '$MODULE' could not be enabled."
			echo " ${RED}Error${RESET}: Please enable it manually using: a2enmod $MODULE."
			echo " ${RED}Error${RESET}: Execution aborted."
			echo 
			exit 1
		fi
	else
        if apache2ctl -M 2>/dev/null | grep -q "^ $MODULE"; then
            echo " ${GREEN}Apache2-Module enabled${RESET}: $MODULE"
        else
			echo " ${RED}Apache2-Module disabled${RESET}: $MODULE"
        fi		
	fi
}

############################################################################
# Disable Apache2 Module
############################################################################
disable_apache_module() {
    MODULE="$1"
	if [ "$current_script_install_everything" -eq 1 ]; then
		a2dismod "$MODULE" > /dev/null 2>&1 
		if [ $? -eq 0 ]; then
			echo " ${GREEN}Apache2-Module disabled${RESET}: $MODULE"
		else
			echo " ${RED}Error${RESET}: Apache module '$MODULE' could not be disabled."
			echo " ${RED}Error${RESET}: Please disable it manually using: a2dismod $MODULE."
			echo " ${RED}Error${RESET}: Execution aborted."
			echo 
			exit 1
		fi
	else
        if apache2ctl -M 2>/dev/null | grep -q "^ $MODULE"; then
            echo " ${RED}Apache2-Module enabled${RESET}: $MODULE"
        else
			echo " ${GREEN}Apache2-Module disabled${RESET}: $MODULE"
        fi		
	fi
}

############################################################################
# Create Missing Folders
############################################################################
create_folder_if_missing() {
    DIR="$1"
	if [ "$current_script_install_everything" -eq 1 ]; then
		if [ ! -d "$DIR" ]; then
			mkdir -p "$DIR" > /dev/null 2>&1 
			if [ $? -eq 0 ]; then
				echo " ${GREEN}Folder exists${RESET}: $DIR"
			else
				echo " ${RED}Error${RESET}: Failed to create folder '$DIR'."
				echo " ${RED}Error${RESET}: Execution aborted."
				echo 
				exit 1
			fi
		else
			echo " ${GREEN}Folder exists${RESET}: $DIR"
		fi
	else
        if [ -d "$DIR" ]; then
            echo " ${GREEN}Folder exists${RESET}: $DIR"
        else
			echo " ${RED}Folder missing${RESET}: $DIR"
        fi		
	fi
}

########################################################################################################################################################
# Output - No Parameters Provided
########################################################################################################################################################
if [ ! "$1" = "server-check" ] && [ ! "$1" = "install-dependencies" ] && [ ! "$1" = "install" ]; then
	echo 	
	echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
	echo "   DNSHTTP Installation Script"
	echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
	echo 
    echo " Usage: $0 <parameter>"
	echo 
    echo " Possible parameters:"
    echo " [ install ]              Install full dnshttp dns server."
	echo 
	exit 1
fi

########################################################################################################################################################
# Sorting Variable for If-Loops
########################################################################################################################################################
current_script_show_info=0
current_script_install_dependencies=1
current_script_install_everything=1

########################################################################################################################################################
# [ install ]
########################################################################################################################################################
if [ "$1" = "install" ]; then
	echo 
	echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
	echo "   DNSHTTP Installation Script 4.1.0"
	echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
	echo " Executing: install"
	echo 
	echo " ${GREEN}Thank you for choosing Bind9 Web Manager.${RESET}"
	echo " This script will guide you through the complete"
	echo " server installation process."
	echo 
	echo " This section will automatically install all required"
	echo " system, server, and development dependencies for"
	echo " DNSHTTP, including web server, database, PHP, and"
	echo " related libraries and tools."
	echo
	echo " It checks for each package and installs it if missing,"
	echo " ensuring a consistent environment for DNSHTTP."
	echo
	echo " ${YELLOW}Warning: It is NOT recommended to run this script on"
	echo " systems that already have running services or existing"
	echo " configurations. This process may install, upgrade,"
	echo " or reconfigure core components such as Apache, MariaDB,"
	echo " Redis, and PHP, which can disrupt existing web"
	echo " applications, databases, or custom service setups."
	echo " Script is für Ubuntu and Debian 20+, do not use on other systems.${RESET}"
	echo
	echo " ${RED}Warning: Do never use this script on a running plesk/virtualmin"
	echo " or other hosting system which is already initialized, just on fresh servers.${RESET}"
	echo 
	echo " This script is licensed under the GNU GPLv3."
	echo " ${RED}Use it at your own risk.${RESET}"
	echo 
	current_script_show_info=0
	current_script_install_dependencies=1
	current_script_install_everything=1
fi

########################################################################################################################################################
# Initial Confirmation
########################################################################################################################################################
echo " ${YELLOW}Notice${RESET}: User input required... (see below)"
read -p " Do you want to continue? (y/n): " answer
if [ "$answer" != "y" ] && [ "$answer" != "Y" ]; then
	echo 
	echo " Execution aborted by user."
	echo " Exiting script now."
	echo 
	exit 1
fi

########################################################################################################################################################
# Update Source-List
########################################################################################################################################################
if [ "$current_script_install_dependencies" -eq 1 ]; then
	echo
	echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
	echo "   Linux: Update Source-List"
	echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
	DEBIAN_FRONTEND=noninteractive APT_PAGER=cat apt update > /dev/null 2>&1 
	echo " ${GREEN}Ok${RESET}: Source list updated."
fi

########################################################################################################################################################
# Upgrade System
########################################################################################################################################################
if [ "$current_script_install_everything" -eq 1 ]; then
	echo
	echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
	echo "   Linux: Upgrade"
	echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
	DEBIAN_FRONTEND=noninteractive APT_PAGER=cat apt upgrade -y > /dev/null 2>&1 
	echo " ${GREEN}Ok${RESET}: System packages upgraded."
fi

########################################################################################################################################################
# Package Installation
########################################################################################################################################################
echo
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Linux: Default Packages"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
check_and_install_pkg apache2
check_and_install_pkg software-properties-common
check_and_install_pkg nano
check_and_install_pkg git
check_and_install_pkg gcc
check_and_install_pkg make
check_and_install_pkg autoconf
check_and_install_pkg pkg-config
check_and_install_pkg imagemagick
check_and_install_pkg openssl
check_and_install_pkg curl
check_and_install_pkg cron
check_and_install_pkg tzdata
check_and_install_pkg zip
check_and_install_pkg htop
check_and_install_pkg unzip
check_and_install_pkg tmux
check_and_install_pkg wget
check_and_install_pkg iputils-ping
check_and_install_pkg apache2-suexec-custom
check_and_install_pkg jq
check_and_install_pkg sshpass
check_and_install_pkg gzip
check_and_install_pkg tar
check_and_install_pkg python3
check_and_install_pkg perl	
check_and_install_pkg mariadb-server
check_and_install_pkg mariadb-client
check_and_install_pkg supervisor
echo
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Linux: Development Packages"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
check_and_install_pkg libc-dev
check_and_install_pkg libonig-dev
check_and_install_pkg libpng-dev
check_and_install_pkg zlib1g-dev
check_and_install_pkg libcurl4-openssl-dev
check_and_install_pkg libicu-dev
check_and_install_pkg libxml2-dev
check_and_install_pkg libzip-dev
check_and_install_pkg libsodium-dev
check_and_install_pkg libmemcached-dev
check_and_install_pkg libssl-dev
check_and_install_pkg libtidy-dev
check_and_install_pkg libkrb5-dev
check_and_install_pkg libssh2-1-dev
#check_and_install_pkg libc-client-dev (mask by libc-client2007e-dev)
check_and_install_pkg libc-client2007e-dev
check_and_install_pkg libbz2-dev
check_and_install_pkg libmagickwand-dev
check_and_install_pkg libldap2-dev
check_and_install_pkg_cname libfreetype-dev libfreetype6-dev
check_and_install_pkg libjpeg-dev
echo
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Linux: Server Packages"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
check_and_install_pkg jailkit
check_and_install_pkg fail2ban
check_and_install_pkg ufw
check_and_install_pkg apt-transport-https
check_and_install_pkg ca-certificates
check_and_install_pkg certbot
check_and_install_pkg bind9
check_and_install_pkg bind9-utils
check_and_install_pkg dnsutils

########################################################################################################################################################
# PHP Installation
########################################################################################################################################################	
echo
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   PHP-8.4: Installation"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
if [ "$current_script_install_everything" -eq 1 ] || [ "$current_script_install_dependencies" -eq 1 ]; then
	if ! command -v php8.4 > /dev/null 2>&1 || ! php -v | grep -q "8.4"; then
		if ! grep -q "packages.sury.org" /etc/apt/sources.list.d/*; then
			add-apt-repository -y ppa:ondrej/php > /dev/null 2>&1 
			DEBIAN_FRONTEND=noninteractive APT_PAGER=cat apt update > /dev/null 2>&1 
			echo " ${GREEN}Ok${RESET}: PHP Sury Repository added to sources lists."
		fi
		DEBIAN_FRONTEND=noninteractive APT_PAGER=cat apt install -y php8.4 > /dev/null 2>&1 
		if [ $? -eq 0 ]; then
			echo " ${GREEN}Package available${RESET}: PHP 8.4"
		else
			echo " ${RED}Error${RESET}: Package 'PHP 8.4' could not be installed."
			echo " ${RED}Error${RESET}: Please install it manually using: apt install -y php8.4."
			echo " ${RED}Error${RESET}: Execution aborted."
			echo 
			exit 1
		fi
	else
		echo " ${GREEN}Package available${RESET}: PHP 8.4"
	fi
else
	check_and_install_pkg php8.4
fi
echo
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   PHP-8.4 Modules"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
#check_and_install_pkg php8.4-session
check_and_install_pkg php8.4-cli
check_and_install_pkg_cname php8.4-mysql php8.4-mysqli
check_and_install_pkg php8.4-xml
check_and_install_pkg php8.4-mbstring
check_and_install_pkg php8.4-curl
check_and_install_pkg php8.4-zip
#check_and_install_pkg php8.4-filter
#check_and_install_pkg php8.4-ctype
#check_and_install_pkg php8.4-fileinfo
check_and_install_pkg php8.4-intl
check_and_install_pkg php8.4-common
check_and_install_pkg php8.4-soap
check_and_install_pkg php8.4-opcache
check_and_install_pkg php8.4-gd
check_and_install_pkg php8.4-bcmath
check_and_install_pkg php8.4-bz2
check_and_install_pkg php8.4-imap
check_and_install_pkg php8.4-tidy
check_and_install_pkg php8.4-ssh2
check_and_install_pkg php8.4-imagick
check_and_install_pkg php8.4-sqlite3
check_and_install_pkg php8.4-ldap
check_and_install_pkg php8.4-memcached
check_and_install_pkg php8.4-fpm

########################################################################################################################################################
# Apache2 Module Configuration
########################################################################################################################################################	
echo
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Apache2: Default Modules"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
enable_apache_module ssl
enable_apache_module rewrite
enable_apache_module headers
enable_apache_module cgi
enable_apache_module cgid
enable_apache_module remoteip
enable_apache_module deflate
enable_apache_module http2
enable_apache_module proxy
enable_apache_module proxy_http
enable_apache_module proxy_ftp
enable_apache_module proxy_fcgi
enable_apache_module proxy_balancer
enable_apache_module suexec	
echo
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Apache2: HTTP2 Setup"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
disable_apache_module php8.4
disable_apache_module mpm_prefork
enable_apache_module mpm_event	


########################################################################################################################################################
# Folders Initialization
########################################################################################################################################################
echo	
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Initialization: Folders"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
create_folder_if_missing /dnshttp
create_folder_if_missing /dnshttp/log
create_folder_if_missing /dnshttp/web
create_folder_if_missing /dnshttp/cache
create_folder_if_missing /dnshttp/backup
create_folder_if_missing /dnshttp/ssl	

########################################################################################################################################################
# Backup old Settings
########################################################################################################################################################
echo	
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Initialization: Backup"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
TIMESTAMP=$(date +"%Y%m%d%H%M%S")
BACKUPFOLDER="/dnshttp/backup/$TIMESTAMP"
BACKUPFOLDER_SUPERVISOR="/dnshttp/backup/$TIMESTAMP/supervisor"
BACKUPFOLDER_APACHE="/dnshttp/backup/$TIMESTAMP/apache2"
BACKUPFOLDER_SF="/dnshttp/backup/$TIMESTAMP/web"
BACKUPFOLDER_SSL="/dnshttp/backup/$TIMESTAMP/ssl"

if [ ! -d "$BACKUPFOLDER_SUPERVISOR" ]; then
	mkdir -p "$BACKUPFOLDER_SUPERVISOR" > /dev/null 2>&1
	if [ ! $? -eq 0 ]; then
		echo " ${RED}Error${RESET}: Failed to create folder '$BACKUPFOLDER_SUPERVISOR'."
		echo " ${RED}Error${RESET}: Execution aborted."
		echo 
		exit 1
	fi
else
	echo " ${GREEN}Ok${RESET}: Folder '$BACKUPFOLDER_SUPERVISOR' already exists."
fi
cp -r /etc/supervisor/* "$BACKUPFOLDER_SUPERVISOR/" > /dev/null 2>&1 
if [ $? -eq 0 ]; then
	echo " ${GREEN}Ok${RESET}: Supervisor configuration backup to '$BACKUPFOLDER_SUPERVISOR'."
else
	echo " ${RED}Error${RESET}: Failed to backup Supervisor configuration."
	echo " ${RED}Error${RESET}: Execution aborted."
	echo 
	exit 1
fi	
if [ ! -d "$BACKUPFOLDER_APACHE" ]; then
	mkdir -p "$BACKUPFOLDER_APACHE" > /dev/null 2>&1
	if [ ! $? -eq 0 ]; then
		echo " ${RED}Error${RESET}: Failed to create folder '$BACKUPFOLDER_APACHE'."
		echo " ${RED}Error${RESET}: Execution aborted."
		echo 
		exit 1
	fi
else
	echo " ${GREEN}Ok${RESET}: Folder '$BACKUPFOLDER_APACHE' already exists."
fi
cp -r /etc/apache2/* "$BACKUPFOLDER_APACHE/" > /dev/null 2>&1 
if [ $? -eq 0 ]; then
	echo " ${GREEN}Ok${RESET}: Apache2 configuration backup to '$BACKUPFOLDER_APACHE'."
else
	echo " ${RED}Error${RESET}: Failed to backup Apache2 configuration."
	echo " ${RED}Error${RESET}: Execution aborted."
	echo 
	exit 1
fi	
if [ ! -d "$BACKUPFOLDER_SF" ]; then
	mkdir -p "$BACKUPFOLDER_SF" > /dev/null 2>&1
	if [ ! $? -eq 0 ]; then
		echo " ${RED}Error${RESET}: Failed to create folder '$BACKUPFOLDER_SF'."
		echo " ${RED}Error${RESET}: Execution aborted."
		echo
		exit 1
	fi
else
	echo " ${GREEN}Ok${RESET}: Folder '$BACKUPFOLDER_SF' already exists."
fi
mv /dnshttp/web "$BACKUPFOLDER_SF/" > /dev/null 2>&1 
if [ $? -eq 0 ]; then
	echo " ${GREEN}Ok${RESET}: Source Code backup to '$BACKUPFOLDER_SF'."
else
	echo " ${RED}Error${RESET}: Failed to backup dnshttp Source."
	echo " ${RED}Error${RESET}: Execution aborted."
	echo
	exit 1
fi	
if [ ! -d "$BACKUPFOLDER_SSL" ]; then
	mkdir -p "$BACKUPFOLDER_SSL" > /dev/null 2>&1 
	if [ ! $? -eq 0 ]; then
		echo " ${RED}Error${RESET}: Failed to create folder '$BACKUPFOLDER_SSL'."
		echo " ${RED}Error${RESET}: Execution aborted."
		echo 
		exit 1
	fi
else
	echo " ${GREEN}Ok${RESET}: Folder '$BACKUPFOLDER_SF' already exists."
fi
mv /dnshttp/ssl "$BACKUPFOLDER_SSL/" > /dev/null 2>&1 
if [ $? -eq 0 ]; then
	echo " ${GREEN}Ok${RESET}: Current SSL Certificate backup to '$BACKUPFOLDER_SSL'."
else
	echo " ${RED}Error${RESET}: Failed to backup Current SSL Certificate."
	echo " ${RED}Error${RESET}: Execution aborted."
	echo 
	exit 1
fi	
if [ ! -d "/dnshttp/web" ]; then
	mkdir -p "/dnshttp/web" > /dev/null 2>&1 
	if [ ! $? -eq 0 ]; then
		echo " ${RED}Error${RESET}: Failed to create folder '/dnshttp/web'."
		echo " ${RED}Error${RESET}: Execution aborted."
		echo 
		exit 1
	fi
	echo " ${GREEN}Ok${RESET}: Folder '/dnshttp/web' created."
else
	echo " ${GREEN}Ok${RESET}: Folder '/dnshttp/web' already exists."
fi	

########################################################################################################################################################
# Download current repository Package
########################################################################################################################################################
echo
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Download: DNSHTTP"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
if [ -f "/dnshttp/cache/release.zip" ]; then
	unlink /dnshttp/cache/release.zip
	echo " ${YELLOW}Notice${RESET}: Existing release file removed: /dnshttp/cache/release.zip"
fi
wget -q "https://github.com/bugfishtm/Bind9-Web-Manager/archive/refs/heads/main.zip" -O "/dnshttp/cache/release.zip" 
if [ $? -eq 0 ]; then
	echo " ${GREEN}Ok${RESET}: Download of dnshttp successfull."
else
	echo " ${RED}Error${RESET}: Failed to download the dnshttp release file by url:"
	echo " ${RED}Error${RESET}: https://github.com/bugfishtm/Bind9-Web-Manager/archive/refs/heads/main.zip."
	echo " ${RED}Error${RESET}: Execution aborted."
	echo 
	exit 1
fi
if [ -d "/dnshttp/cache/Bind9-Web-Manager-main" ]; then
	rm -rf "/dnshttp/cache/Bind9-Web-Manager-main" 
	echo " ${YELLOW}Notice${RESET}: Existing extracted folder removed: /dnshttp/cache/Bind9-Web-Manager-main"
fi
if [ -f "/dnshttp/cache/release.zip" ]; then
	unzip -q "/dnshttp/cache/release.zip" -d "/dnshttp/cache/" 
	if [ $? -eq 0 ]; then
		echo " ${GREEN}Ok${RESET}: Extraction of dnshttp release successfull."
	else
		echo " ${RED}Error${RESET}: Failed to extract the dnshttp release file."
		echo " ${RED}Error${RESET}: Execution aborted."
		echo 
		exit 1
	fi
else
	echo " ${RED}Error${RESET}: dnshttp release file '/dnshttp/cache/release.zip' not found."
	echo " ${RED}Error${RESET}: Execution aborted."
	echo 
	exit 1
fi		

########################################################################################################################################################
# Certificate Setup (Letsencrypt)
########################################################################################################################################################
SF_SSL_PATH_CERT=""
SF_SSL_PATH_KEY=""
echo
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Setup: SSL Certificate"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
echo " ${YELLOW}Notice${RESET}: User input required... (see below)"
SF_LE_ENABLE=""
while true; do
  printf " Do you want to use LetsEncrypt to issue a certificate? (y/n): "
  read SF_LE_ENABLE
  case "$SF_LE_ENABLE" in
    [yY]|[nN]) break ;;
    *) echo " Do you want to use LetsEncrypt to issue a certificate? (y/n): " ;;
  esac
done
if [ "$SF_LE_ENABLE" = "y" ] || [ "$SF_LE_ENABLE" = "Y" ]; then
	printf " Enter your public website domain (example.domain): "
	read SF_LE_DOMAIN
	printf " Enter the LetsEncrypt account mail (example@localhost): "
	read SF_LE_MAIL
	systemctl stop apache2 > /dev/null 2>&1
	echo " ${GREEN}Ok${RESET}: Terminating apache2 process."
	echo " ${GREEN}Ok${RESET}: Starting LetsEncrypt SSl-Certificate Generation."
	certbot certonly --standalone \
		--non-interactive \
		--agree-tos \
		--email $SF_LE_MAIL \
		-d $SF_LE_DOMAIN > /dev/null 2>&1 
	SF_SSL_PATH_CERT="/etc/letsencrypt/live/$SF_LE_DOMAIN/cert.pem"
	SF_SSL_PATH_KEY="/etc/letsencrypt/live/$SF_LE_DOMAIN/privkey.pem"
	if [ -f "$SF_SSL_PATH_CERT" ] && [ -f "$SF_SSL_PATH_KEY" ]; then
		echo " ${GREEN}Ok${RESET}: Using Let's Encrypt certificate and key."
	else
		echo " ${RED}Error${RESET}: Error while creating Let's Encrypt certificate and key."
		echo " ${YELLOW}Notice${RESET}: Fallback to Custom Certificate creation."
		SF_LE_ENABLE="n"
		echo " ${GREEN}Ok${RESET}: Starting Custom Certificate creation."
		if [ ! -d "/dnshttp/ssl" ]; then
			mkdir -p "/dnshttp/ssl" > /dev/null 2>&1
			if [ ! $? -eq 0 ]; then
				echo " ${RED}Error${RESET}: Failed to create folder '/dnshttp/ssl'."
				echo " ${RED}Error${RESET}: Execution aborted."
				echo
				exit 1
			fi
			echo " ${GREEN}Ok${RESET}: Folder '/dnshttp/ssl' created."
		else
			echo " ${GREEN}Ok${RESET}: Folder '/dnshttp/ssl' already exists."
		fi
		openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
		 -keyout /dnshttp/ssl/privkey.pem \
		 -out /dnshttp/ssl/cert.pem \
		 -subj "/C=US/ST=State/L=City/O=Organization/OU=OrgUnit/CN=example.com" > /dev/null 2>&1 
		if [ ! $? -eq 0 ]; then
			echo " ${RED}Error${RESET}: Failed to create ssl certificate."
			echo " ${RED}Error${RESET}: Execution aborted."
			echo 
			exit 1
		fi
		echo " ${GREEN}Ok${RESET}: Self-signed ssl certificate created."
		SF_SSL_PATH_CERT="/dnshttp/ssl/cert.pem"
		SF_SSL_PATH_KEY="/dnshttp/ssl/privkey.pem"	  
	fi
fi

########################################################################################################################################################
# Certificate Setup (Custom)
########################################################################################################################################################
if [ "$SF_LE_ENABLE" = "n" ] || [ "$SF_LE_ENABLE" = "N" ]; then
	echo " ${GREEN}Ok${RESET}: Starting Custom Certificate creation."
	if [ ! -d "/dnshttp/ssl" ]; then
		mkdir -p "/dnshttp/ssl" > /dev/null 2>&1
		if [ ! $? -eq 0 ]; then
			echo " ${RED}Error${RESET}: Failed to create folder '/dnshttp/ssl'."
			echo " ${RED}Error${RESET}: Execution aborted."
			echo
			exit 1
		fi
		echo " ${GREEN}Ok${RESET}: Folder '/dnshttp/ssl' created."
	else
		echo " ${GREEN}Ok${RESET}: Folder '/dnshttp/ssl' already exists."
	fi
	openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
	 -keyout /dnshttp/ssl/privkey.pem \
	 -out /dnshttp/ssl/cert.pem \
	 -subj "/C=US/ST=State/L=City/O=Organization/OU=OrgUnit/CN=example.com" > /dev/null 2>&1 
	if [ ! $? -eq 0 ]; then
		echo " ${RED}Error${RESET}: Failed to create ssl certificate."
		echo " ${RED}Error${RESET}: Execution aborted."
		echo 
		exit 1
	fi
	echo " ${GREEN}Ok${RESET}: Self-signed ssl certificate created."
	SF_SSL_PATH_CERT="/dnshttp/ssl/cert.pem"
	SF_SSL_PATH_KEY="/dnshttp/ssl/privkey.pem"
fi

########################################################################################################################################################
# MySQL Setup
########################################################################################################################################################
echo
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Setup: MySQL"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
echo " ${YELLOW}Notice${RESET}: User input required... (see below)"
# Initial Data
printf " Enter MySQL database name to be created: "
read DB_NAME
printf " Enter MySQL username to be created: "
read DB_USER
printf " Enter new password for MySQL user '$DB_USER': "
read DB_PASS
# Check for Root Password
mysql -u root -e "SELECT 1;" > /dev/null 2>&1
if [ $? -eq 0 ]; then
	echo " ${YELLOW}Warning${RESET}: MySQL root account has NO password set."
	echo " ${YELLOW}Warning${RESET}: User input required... (see below)"
	printf " Enter new MySQL root password: "
	read ROOT_PASS
	mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '$ROOT_PASS';" > /dev/null 2>&1
	echo " ${GREEN}Ok${RESET}: MySQL Root Account Password changed."
else
	echo " ${YELLOW}Warning${RESET}: MySQL root account has a password set."
	echo " ${YELLOW}Warning${RESET}: User input required... (see below)"
	printf " Enter the current MySQL root password: "
	read ROOT_PASS
	mysql -u root -p"$ROOT_PASS" -e "SELECT 1;" > /dev/null 2>&1
	if [ $? -eq 0 ]; then
		echo " ${GREEN}Ok${RESET}: Correct MySQL root password provided."
	else
		echo " ${RED}Error${RESET}: Incorrect MySQL root password or authentication failed."
		echo " ${RED}Error${RESET}: Execution aborted."
		echo
		exit 1
	fi	
fi
# Check if database already exists
DB_EXISTS=$(mysql -u root -p"$ROOT_PASS" -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$DB_NAME';" -N -B 2>/dev/null)
if [ "$DB_EXISTS" = "$DB_NAME" ]; then
	echo " ${YELLOW}Warning${RESET}: Database '$DB_NAME' already exists."
	echo " ${YELLOW}Warning${RESET}: User input required... (see below)"
	printf " Do you want to delete the current database '$DB_NAME' and create a new one? (y/N): "
	read CONFIRM
	case "$CONFIRM" in
		y|Y)
			mysql -u root -p"$ROOT_PASS" -e "DROP DATABASE $DB_NAME;" > /dev/null 2>&1
			if [ $? -eq 0 ]; then
				echo " ${GREEN}Ok${RESET}: Database '$DB_NAME' has been deleted."
			else
				echo " ${RED}Error${RESET}: Failed to delete database '$DB_NAME'."
				echo " ${RED}Error${RESET}: Execution aborted."
				echo
				exit 1
			fi
			;;
		*)
			echo " ${RED}Error${RESET}: Execution aborted."
			echo
			exit 1
			;;
	esac
fi
# Create Database
mysql -u root -p"$ROOT_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;" > /dev/null 2>&1
if [ $? -eq 0 ]; then
	echo " ${GREEN}Ok${RESET}: Database '$DB_NAME' created successfully."
else
	echo " ${RED}Error${RESET}: Failed to create database '$DB_NAME'."
	echo " ${RED}Error${RESET}: Execution aborted."
	echo
	exit 1
fi
# Create the user and grant privileges
mysql -u root -p"$ROOT_PASS" -e "CREATE USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';" > /dev/null 2>&1
mysql -u root -p"$ROOT_PASS" -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';" > /dev/null 2>&1
mysql -u root -p"$ROOT_PASS" -e "FLUSH PRIVILEGES;" > /dev/null 2>&1
if [ $? -eq 0 ]; then
	echo " ${GREEN}Ok${RESET}: User '$DB_USER' created and granted full privileges on '$DB_NAME'."
else
	echo " ${RED}Error${RESET}: Failed to create user or grant privileges."
	echo " ${RED}Error${RESET}: Execution aborted."
	echo
	exit 1
fi  

########################################################################################################################################################
# Cronjob Setup
########################################################################################################################################################
echo
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Setup: Cronjobs"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
CRON_JOB="0 * * * * root flock -n /tmp/dnshttp_sync.lock /usr/bin/php8.4 /dnshttp/web/_cronjob/sync.php 2>&1"
CRON_FILE="/etc/cron.d/dnshttp"
if [ ! -f "$CRON_FILE" ]; then
	touch "$CRON_FILE"
fi
grep -Fxq "$CRON_JOB" "$CRON_FILE" 2>/dev/null
if [ $? -ne 0 ]; then
	echo "$CRON_JOB" >> "$CRON_FILE"
	echo " ${GREEN}Ok${RESET}: Cron job added successfully for user 'root'."
else
	unlink "$CRON_FILE"
	echo " ${YELLOW}Warning${RESET}: Cron job already exists. File has been removed."
	echo "$CRON_JOB" >> "$CRON_FILE"
	echo " ${GREEN}Ok${RESET}: Cron job added successfully for user 'root'."
fi
echo " ${GREEN}Ok${RESET}: Setup Chmod 0770 on: $CRON_FILE";
chmod 0770 "$CRON_FILE"
echo " ${GREEN}Ok${RESET}: Activate Cronjob File: $CRON_FILE";
crontab "$CRON_FILE"	
CRON_JOB="0 0 */14 * * root certbot renew --quiet --renew-hook 'systemctl restart apache2' >> /var/log/letsencrypt/renew.log 2>&1"
CRON_FILE="/etc/cron.d/certbot"
if [ ! -f "$CRON_FILE" ]; then
	touch "$CRON_FILE"
fi
grep -Fxq "$CRON_JOB" "$CRON_FILE" 2>/dev/null
if [ $? -ne 0 ]; then
	echo "$CRON_JOB" >> "$CRON_FILE"
	echo " ${GREEN}Ok${RESET}: Cron job added successfully for user 'root'."
else
	unlink "$CRON_FILE"
	echo " ${YELLOW}Warning${RESET}: Cron job already exists. File has been removed."
	echo "$CRON_JOB" >> "$CRON_FILE"
	echo " ${GREEN}Ok${RESET}: Cron job added successfully for user 'root'."
fi
echo " ${GREEN}Ok${RESET}: Setup Chmod 0770 on: $CRON_FILE";
chmod 0770 "$CRON_FILE"
echo " ${GREEN}Ok${RESET}: Activate Cronjob File: $CRON_FILE";
crontab "$CRON_FILE"	


########################################################################################################################################################
# Apache2 Setup
########################################################################################################################################################
echo
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Setup: Apache2"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
CONF_DIR="/etc/apache2/sites-available"
CONF_FILE="dnshttp.conf"
CONF_PATH="$CONF_DIR/$CONF_FILE"
if [ ! -d "$CONF_DIR" ]; then
	mkdir -p "$CONF_DIR" > /dev/null 2>&1
	if [ $? -eq 0 ]; then
		echo " ${GREEN}Ok${RESET}: Directory '$CONF_DIR' created."
	else
		echo " ${RED}Error${RESET}: Failed to create directory '$CONF_DIR'."
		echo " ${RED}Error${RESET}: Execution aborted."
		echo
		exit 1
	fi
fi
a2dissite 000-default > /dev/null 2>&1
echo " ${GREEN}Ok${RESET}: Disabled Default 000-default apache2 website."
a2dissite default-ssl > /dev/null 2>&1
echo " ${GREEN}Ok${RESET}: Disabled Default default-ssl apache2 website."
unlink "$CONF_DIR/$CONF_FILE" > /dev/null 2>&1	
echo " ${GREEN}Ok${RESET}: Deleting $CONF_PATH."
cat > "$CONF_PATH" <<EOL
<VirtualHost *:80>

	# Set Document Root
	DocumentRoot /dnshttp/web

	# Logs for Apache
	ErrorLog /dnshttp/log/apache2_error.log
	CustomLog /dnshttp/log/apache2_access.log combined
	
	## No Indexes but index.php
	DirectoryIndex disabled
	DirectoryIndex index.php index.html

	# Run this VirtualHost as 'dnshttp'
	SuexecUserGroup dnshttp dnshttp
	
	# Deflate Module
	<IfModule mod_deflate.c>
		AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/x-javascript application/json application/xml
		AddOutputFilterByType DEFLATE image/svg+xml application/x-font-ttf application/x-font-opentype
		BrowserMatch ^Mozilla/4 gzip-only-text/html
		BrowserMatch ^Mozilla/4\.0[678] no-gzip
		BrowserMatch \bMSIE !no-gzip !gzip-only-text/html
	</IfModule>
 
    # Security Headers
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains"
    Header set X-XSS-Protection "1; mode=block" 
    Header set X-Content-Type-Options nosniff
    Header set X-Frame-Options "sameorigin"
    Header set Referrer-Policy "same-origin" 

	## HTTP2
	Protocols h2 http/1.1
	
	## PHP Handler
	<FilesMatch \.php$>
		SetHandler "proxy:unix:/var/run/php/dnshttp.sock|fcgi://localhost"
	</FilesMatch>
	
	## Directory Preferences
	<Directory /dnshttp/web>
		Options -Indexes
		AllowOverride All
		Require all granted
	</Directory>

</VirtualHost>
<VirtualHost *:443>

	# Set Document Root
	DocumentRoot /dnshttp/web

	# Logs for Apache
	ErrorLog /dnshttp/log/apache2_error.log
	CustomLog /dnshttp/log/apache2_access.log combined
	
	## No Indexes but index.php
	DirectoryIndex disabled
	DirectoryIndex index.php index.html

	# Run this VirtualHost as 'dnshttp'
	SuexecUserGroup dnshttp dnshttp
	
	# Deflate Module
	<IfModule mod_deflate.c>
		AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/x-javascript application/json application/xml
		AddOutputFilterByType DEFLATE image/svg+xml application/x-font-ttf application/x-font-opentype
		BrowserMatch ^Mozilla/4 gzip-only-text/html
		BrowserMatch ^Mozilla/4\.0[678] no-gzip
		BrowserMatch \bMSIE !no-gzip !gzip-only-text/html
	</IfModule>
 
    # Security Headers
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains"
    Header set X-XSS-Protection "1; mode=block" 
    Header set X-Content-Type-Options nosniff
    Header set X-Frame-Options "sameorigin"
    Header set Referrer-Policy "same-origin" 

	## HTTP2
	Protocols h2 http/1.1
	
	## PHP Handler
	<FilesMatch \.php$>
		SetHandler "proxy:unix:/var/run/php/dnshttp.sock|fcgi://localhost"
	</FilesMatch>

	## Directory Preferences
	<Directory /dnshttp/web>
		Options -Indexes
		AllowOverride All
		Require all granted
	</Directory>
	
	# SSL Certificate Files
	SSLEngine on
	SSLCertificateFile $SF_SSL_PATH_CERT
	SSLCertificateKeyFile $SF_SSL_PATH_KEY

</VirtualHost>
EOL
# Check if the file was created successfully
if [ -f "$CONF_PATH" ]; then
	echo " ${GREEN}Ok${RESET}: Apache VirtualHost configuration file created at '$CONF_PATH'."
else
	echo " ${RED}Error${RESET}: Failed to create Apache VirtualHost configuration file."
	echo " ${RED}Error${RESET}: Execution aborted."
	echo
	exit 1
fi
# Enable the site and restart Apache
a2ensite "$CONF_FILE" > /dev/null 2>&1
if [ $? -eq 0 ]; then
	echo " ${GREEN}Ok${RESET}: VirtualHost '$CONF_FILE' enabled."
else
	echo " ${RED}Error${RESET}: Failed to entable 'dnshttp' virtualhost "
	echo " ${RED}Error${RESET}: config file on apache2, try using: a2ensite $CONF_FILE."
	echo " ${RED}Error${RESET}: Execution aborted."
	echo
	exit 1
fi	

########################################################################################################################################################
# Users & Groups Setup
########################################################################################################################################################
echo
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Setup: Users & Groups"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
USER="dnshttp"
GROUP="dnshttp"
if ! getent group "$GROUP" >/dev/null 2>&1; then
	groupadd "$GROUP"
	echo " ${GREEN}Ok${RESET}: Group '$GROUP' created."
else
	echo " ${YELLOW}Notice${RESET}: Group '$GROUP' already exists."
fi
if ! id "$USER" >/dev/null 2>&1; then
	useradd -g "$GROUP" -s /usr/sbin/nologin -M -d /dnshttp "$USER" > /dev/null 2>&1
	if [ $? -eq 0 ]; then
		echo " ${GREEN}Ok${RESET}: User '$USER' created and linked to group '$GROUP'."
	else
		echo " ${RED}Error${RESET}: Failed to create user '$USER'."
		echo " ${RED}Error${RESET}: Execution aborted."
		echo
		exit 1
	fi
else
	echo " ${YELLOW}Warning${RESET}: User '$USER' already exists."
fi
passwd -l "$USER" > /dev/null 2>&1
echo " ${GREEN}Ok${RESET}: Disabled password login for user '$USER'."	
APACHE_USER="www-data"
GROUP_NAME="dnshttp"
WEB_ROOT="/dnshttp/web"
sudo usermod -a -G "$GROUP_NAME" "$APACHE_USER"
echo " ${GREEN}Ok${RESET}: Linked Apache user ($APACHE_USER) to group ($GROUP_NAME)..."

########################################################################################################################################################
# dnshttp Folder Setup
########################################################################################################################################################
echo
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Setup: dnshttp Folder"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
SRC_DIR="/dnshttp/cache/Bind9-Web-Manager-main/_source"
DEST_DIR="/dnshttp/web"
cp -r "$SRC_DIR"/* "$DEST_DIR"/ > /dev/null 2>&1 
if [ $? -eq 0 ]; then
	echo " ${GREEN}Ok${RESET}: Files copied from '$SRC_DIR' to '$DEST_DIR' successfully."
else
	echo " ${RED}Error${RESET}: Failed to copy files."
	echo " ${RED}Error${RESET}: Execution aborted."
	echo
	exit 1
fi

########################################################################################################################################################
# PHP Setup
########################################################################################################################################################
echo
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Setup: PHP"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
# Variables
POOL_NAME="dnshttp"
POOL_USER="dnshttp"
POOL_GROUP="dnshttp"
POOL_CONFIG="/etc/php/8.4/fpm/pool.d/${POOL_NAME}.conf"
SOCKET_PATH="/var/run/php/${POOL_NAME}.sock"
# Check if pool config already exists
if [ -f "$POOL_CONFIG" ]; then
   unlink "$POOL_CONFIG"
   echo " ${YELLOW}Warning${RESET}: Pool configuration overwritten: $POOL_CONFIG."
fi
# Create FPM pool configuration
cat << EOF > "$POOL_CONFIG"
[$POOL_NAME]
user = $POOL_USER
group = $POOL_GROUP
listen = $SOCKET_PATH
listen.owner = $POOL_USER
listen.group = $POOL_GROUP
pm = dynamic
pm.max_children = 75
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.process_idle_timeout = 10s
php_admin_flag[log_errors] = on
php_admin_value[error_log] = /dnshttp/log/php_error.log
php_admin_flag[display_errors] = off
php_admin_value[memory_limit] = 256M
php_admin_value[post_max_size] = 256M
php_admin_value[max_execution_time] = 900
EOF
echo " ${GREEN}Ok${RESET}: FPM pool config for $POOL_NAME created at $POOL_CONFIG"

########################################################################################################################################################
# PHP Setup
########################################################################################################################################################
echo " ${GREEN}Ok${RESET}: Rewriting General PHP Configuration"
echo "memory_limit = 256M" >> /etc/php/8.4/apache2/php.ini
echo "post_max_size = 256M" >> /etc/php/8.4/apache2/php.ini
echo "max_execution_time = 900" >> /etc/php/8.4/apache2/php.ini

########################################################################################################################################################
# Permissions Setup
########################################################################################################################################################
echo	
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Setup: Permissions"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
echo " ${GREEN}Ok${RESET}: Owner set to 'dnshttp' on: /dnshttp/*";
chown dnshttp:dnshttp /dnshttp -R > /dev/null 2>&1
echo " ${GREEN}Ok${RESET}: Chmod set to 0770 on: /dnshttp/*";
chmod 0770 /dnshttp -R > /dev/null 2>&1

########################################################################################################################################################
# Timezone Setup
########################################################################################################################################################
echo	
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Setup: Timezone"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
echo " ${YELLOW}Warning${RESET}: User input required... (see below)"
TIMEZONE="Europe/Berlin"

while true; do
  echo -n " Please enter the timezone (e.g., Europe/Berlin): "
  read TIMEZONE
  # Check if the timezone is valid
  if timedatectl list-timezones | grep -Fxq "$TIMEZONE"; then
    echo "Setting system timezone to $TIMEZONE..."
    timedatectl set-timezone "$TIMEZONE"
    if [ $? -eq 0 ]; then
      echo " ${GREEN}Ok${RESET}: Timezone successfully set to $TIMEZONE."
      break
    else
      echo " ${RED}Error${RESET}: Failed to set timezone. Please try again."
    fi
  else
    echo " ${RED}Error${RESET}: Invalid timezone. Please enter a valid timezone."
  fi
done

########################################################################################################################################################
# Restart Services
########################################################################################################################################################
echo	
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Setup: Services"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
systemctl enable apache2 > /dev/null 2>&1
systemctl restart apache2 > /dev/null 2>&1
echo " ${GREEN}Ok${RESET}: Restart and enable apache2."
systemctl enable mariadb > /dev/null 2>&1
systemctl restart mariadb > /dev/null 2>&1
echo " ${GREEN}Ok${RESET}: Restart and enable mariadb."
systemctl enable cron > /dev/null 2>&1
systemctl restart cron > /dev/null 2>&1
echo " ${GREEN}Ok${RESET}: Restart and enable cron."
systemctl enable bind9 > /dev/null 2>&1
systemctl stop bind9 > /dev/null 2>&1
echo " ${GREEN}Ok${RESET}: Stop and enable bind9."
systemctl restart php8.4-fpm > /dev/null 2>&1
echo " ${GREEN}Ok${RESET}: Restart and enable php8.4-fpm."
supervisorctl reread > /dev/null 2>&1
supervisorctl update > /dev/null 2>&1
supervisorctl stop dnshttp > /dev/null 2>&1
supervisorctl start dnshttp > /dev/null 2>&1
echo " ${GREEN}Ok${RESET}: Starting supervisor services."

########################################################################################################################################################
# Delete Bind Named Conf (Cronjob will Regenerate)
########################################################################################################################################################
echo	
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   Setup: Deleting default named.conf file"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
mv /etc/bind/named.conf /etc/bind/named.conf.dnshttp.backup.overwritten > /dev/null 2>&1
echo " ${GREEN}Ok${RESET}: Done."

########################################################################################################################################################
# Finish message
########################################################################################################################################################
echo
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   ${GREEN} Installation Finished!${RESET}"
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
echo
echo " The installation is finished."
echo " You can now login at: https://your-ip-adr/"
echo
echo " Webinterface Login"
echo " Username: admin"
echo " Password: changeme"
echo
echo " MySQL Data for Installation"
echo " Hostname: 127.0.0.1 (localhost)"
echo " Username: $DB_USER"
echo " Password: $DB_PASS"
echo " Database: $DB_NAME"
echo
echo " ${RED}Please note the MySQL and Interface Login data"
echo " You will need this for the DNSHTTP Installation in the Frontend.${RESET}"
echo
echo " ${YELLOW}Do not forget to change your password after login!${RESET}"

########################################################################################################################################################
# End of Script
########################################################################################################################################################
echo	
echo " ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "   End of 'install' script."
echo "   Thank you for using DNSHTTP."
echo " ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"
echo	
exit 1      