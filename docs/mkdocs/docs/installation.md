# DNSHTTP Installation

Suitefish-CMS can be installed through several methods including a graphical user interface (GUI) installer that simplifies the setup process for users with minimal technical experience. 

!!! danger "For your security, change the default password immediately after your first login."
	**Important:** To ensure the security of your account and system, it is strongly recommended to change the password right after your first login. Failing to do so may expose your system to potential security risks.
	
!!! info "Initial Username/Password for DNSHTTP"
	username: admin  
	password: changeme  
	
---

## Installation via Script

In the github repository's `_scripts` folder, you'll find an installation script designed to install the full version with all features on a root server. This script is intended for use only on freshly installed servers and may corrupt running services or operations. In the github repository's `_scripts` folder, you'll find an installation script designed to install the full version with all features on a root/kvm server. 

Execute the following Commands and navigate through the installation shell process to install dnshttp on a fresh server with full root-level access.

```bash
curl -o ./installer.sh https://raw.githubusercontent.com/bugfishtm/Bind9-Web-Manager/refs/heads/main/_scripts/installer.sh
chmod u+x ./installer.sh  
sh ./installer.sh install
```

---

## Installation via Docker

Here you can find the official Docker image related to this project. By clicking the link below, you'll be directed to the Docker Hub page where you can access and pull the image for use.

[DockerHUB](https://hub.docker.com/r/bugfishtm/dnshttp){.md-button}

---

## Manual Installation

What follows are instructions how to install this software and what is required to run it manually!

### Requirements

- PHP 8.4 Recommended (At least 8.2)
- Root Level Server Access (KVM / Root / VPS)
- Unrestricted access to a MySQL database with full permissions
- Open ports 53/953/853 (TCP/UDP) for DNS and 80/443 (TCP) for web access
 
Below are the requirements for the webserver. Additional default installed linux and php modules are required as openssl and more, usually they are already installed by default and so not listed here.

| Requirement | Minimum Value | Recommended Value |
|-------|----------|---|
| Server Root Access | Required | - |
| Linux Package: bind9 | Required |- |
| Linux Package: cron | Required |- |
| Linux Package: bind9-utils | Required |- |
| Linux Package: dnsutils | Required |- |
| Linux Package: php8.2(+) | Required |- |
| Apache2 Module: headers | Required |- |
| Apache2 Module: rewrite | Required |- |
| PHP Memory Limit | 128M |256M |
| PHP Max Post Size | 128M |256M |
| PHP Max Execution Time | 180s |600s |
| PHP Module: MySQLi | Enabled | - |
| PHP Module: JSON | Enabled | - |
| PHP Module: mbstring | Enabled | - |
| PHP Module: cURL | Enabled | - |
| PHP Module: intl | Enabled | - |
| PHP Module: session | Enabled | - |


### How to Install

1. Upload the files from the "_source" directory to your webspace.
2. Run the Frontend installer depending on your use case.
3. Login using the default credentials (admin/changeme).
4. Rename your named.conf file to named.conf.old.dnshttp. DNSHTTP relies on its own named.conf configuration, named.conf shall not exist in /etc/bind when dnshttp initializes first.
5. Set up the required cronjob (sync.php) to run every 2 hours.

### Install the Cronjob

Please ensure that both cronjobs are configured as the **root** user. To set up the cron job, run the following command as root to open the root crontab:

```bash
sudo crontab -e
```

Add the following line:

```
0 */2 * * * /path/to/DOCROOT/_cronjob/sync.php >/dev/null 2>&1
```

Save and exit. This schedules `sync.php` to run every 2 hours as the root user, which is required for BIND restart and permission management. To confirm the cron was saved correctly, run:

```bash
sudo crontab -l
```

Replace `/path/to/DOCROOT` with the actual path to your web root directory before saving.


### Rollback

As described in the installation steps, you changed file named.conf. You shall have made a backup like described in the Installation steps. If you replace your named.conf with your old initial backuped-file - All Changes made by DNSHTTP to your Bind9 Instance will be gone. This is a simple but trustfull way to "hard" deactivate and activate this software connection with bind. This may not work if you are using this software on a virtualmin environment as hybrid/master server. In this case you have to fix issues manually in case there are some. This script has been tested a long time in virtualmin. A rollback/fix on Virtualmin Servers will be less easy than a fix on other systems, but if you have some experience with DNS you should always been able to fix it yourself. You are free to ask on my forum or contact me if you have issues. If i have the possibility i will try to help you.


