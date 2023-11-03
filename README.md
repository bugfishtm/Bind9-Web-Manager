![Bugfish](https://img.shields.io/badge/Bugfish-Software-orange)
![Status](https://img.shields.io/badge/Status-Finished-green)
![License](https://img.shields.io/badge/License-MIT-black)
![Version](https://img.shields.io/badge/Version-3.7.2-white)

# Bind9 Web Manager [DNSHTTP]

## Documentation

For complete documentation, see the file index.html in the docs folder. You can open it with any web browser.

- [Online Documentation](https://bugfishtm.github.io/Bind9-Web-Manager/)
- [GitHub Repository](https://github.com/bugfishtm/Bind9-Web-Manager)
- [General GitHub Project Page](https://bugfishtm.github.io)

## Overview

The Bind9 Web Manager [DNSHTTP] offers a comprehensive set of features for managing your BIND9 DNS servers. Here's an overview of the key functionalities:

### Slave Server Replication

Gain control over the replication process between your Slave and Master Servers. This feature provides real-time status updates and allows you to manage replication effectively.

### Slave/DNS Server Hybrid Support

Configure a server to serve as both a Master and a Slave Server. This hybrid approach simplifies replication interactions and offers resource-efficient management of your DNS architecture.

### User Management

Create, edit, and delete users, with granular permission assignment for a secure and efficient workflow tailored to your organizational structure.

### Rights Management

Establish precise controls over user activities within their designated operational areas, including "jailing" to restrict users to specific domains of operation.

### IP Blacklisting

Control IP blacklisting to mitigate security threats by identifying and addressing suspicious or unauthorized activities. Lift IP bans or use the daily.php cronjob for automated blacklisting resets.

### Replication Insights

Gain detailed insights into replication and domain information, enabling well-informed decisions and proactive management of your replication strategy.

### Installation

#### Requirements

Ensure you meet the following requirements:

- Elevated permissions (root) for cronjob execution
- Apache2 web server with robust PHP 7/8 support
- Apache2 Modules: rewrite, headers
- PHP Modules: mysql, curl, intl, mbstring, zip, gd
- Unrestricted access to a MySQL database with full permissions
- Open ports 53/953 (TCP/UDP) for DNS and 80/442 (TCP) for web access

#### Installation Steps

1. Upload the files from the "_source" directory to your webspace.
2. Modify the Settings.sample.php file with valid MySQL user information and rename it to settings.php in your website's document root folder.
3. Login using the default credentials (admin/changeme).
4. Set up the required cronjobs (daily.php, notify.php, and sync.php) as outlined in the instructions.
5. Delete your named.conf file (make backup in case you need to rollback, /etc/bind/named.conf needs to be deleted. Cronjob sync.php will install a new one.).

You're now ready to use the software.

### Domains, Replications, and Conflicts

- The Domains Section shows replicated domains and master domains.
- Conflicts can arise when domains exist as both master and slave domains; these conflicts are highlighted and can be resolved in the dedicated "Conflicts" section.

### Types of Server Connections

- For systems with Master Domains and Slave Servers, deploy the software on both servers. Configure the Slave Server on the Master server and vice versa.
- The software accommodates "hybrid" connections, allowing servers to function as both master and slave servers simultaneously.

## Web Interface Login

Change the default login credentials immediately after installation:

- Username: admin
- Password: changeme

## Support and Assistance

If you encounter any issues or require assistance, please visit [bugfish.eu/forum](https://www.bugfish.eu/forum) for additional resources. You can also contact us at [request@bugfish.eu](mailto:request@bugfish.eu), and we will do our best to assist you.

This Android WebApp Example project offers a convenient way to deploy customized apps related to your website, enhancing your online presence and user experience.

## License Information

The license details for this Bind9-Web-Manager project can be found in the "license.md" file within the project repository. Please review this file to understand the terms and conditions of use and distribution. It is essential to comply with the project's license to ensure legal and ethical usage of the provided resources.