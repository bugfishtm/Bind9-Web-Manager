# Bind9 Web Manager [DNSHTTP]

Bind9 Web Manager (DNSHTTP) is a web-based management interface for BIND9 DNS servers.

-----------

## 🚀 Introduction

Bind9 Web Manager (DNSHTTP) is a web-based management interface for BIND9 DNS servers. It centralizes domain and record management, server replication, security controls, and operational insights into a single platform — making DNS administration more straightforward for both single-server setups and multi-server architectures.

![Screenshot](./screenshots/site_domain_records_1.png)

DNSHTTP supports hybrid server configurations, where a single server acts as both a Master and a Slave simultaneously. This is useful for reducing infrastructure complexity while still maintaining proper replication across your DNS architecture.

To set up a hybrid environment, deploy the software on both servers. On the Master server, configure the Slave Server entry, and on the Slave server, configure the Master Server entry. Once both sides are configured, the servers can replicate with each other bidirectionally as needed.

![Screenshot](./screenshots/site_server_details_1.png)

DNSHTTP can operate as a dedicated DNS control panel alongside existing hosting panels such as Virtualmin, Plesk, ISPConfig, and others — managing your Bind9 DNS layer independently without interfering with your existing stack🔌. In Docker environments, only the standalone deployment is supported, which is also the case when installing via the setup script .

-----------

## 🔥 Features

Delivers the essential tools to effectively manage your site’s content, structure, and user roles—streamlined for simplicity, without unnecessary complexity. For a broad overview of its capabilities, refer to the feature list below.

### 📋 Domain and Record Management
 
The core of DNSHTTP is its domain and DNS record management. You can add, edit, and remove domains along with all of their associated DNS records directly through the web interface, without manually editing zone files or reloading services by hand.

### 🛡️ Access Control

The software features a robust user and group management system, enabling administrators to efficiently create, organize, and manage both users and user groups. With flexible permission controls, administrators can assign specific access rights to groups and individual users, ensuring secure and streamlined management of user privileges across the platform.

### 🔄 Slave Server Replication
 
DNSHTTP gives you direct control over the replication process between your Master and Slave DNS servers, with real-time status updates so you can monitor the state of your replication at a glance.
 
- The **Domains Section** displays both replicated slave domains and locally mastered domains side by side.
- **Conflicts** occur when the same domain exists as both a master and a slave entry. These are automatically detected, clearly highlighted, and can be resolved through the dedicated **Conflicts** section.

### 🚫 IP Blacklisting
 
DNSHTTP includes built-in IP blacklisting to help protect your DNS infrastructure from suspicious or unauthorized activity. When a threat is identified, the offending IP can be banned directly through the interface. Bans can be lifted manually at any time, or you can set up the `blacklist.php` cronjob to automate blacklisting resets on a scheduled basis.

### 📊 Replication Insights
 
Beyond basic replication controls, DNSHTTP provides detailed insights into the state of your replication and domain configuration. This gives you the visibility needed to make informed decisions, catch issues early, and proactively manage your DNS replication strategy across all connected servers.

### 🔌 External API
 
DNSHTTP exposes an API interface secured with tokens, allowing external systems and scripts to perform DNS operations programmatically. This makes it straightforward to integrate DNS management into your existing workflows, automation pipelines, or third-party tooling.

### 🖥️ Integrated Installer 

The installation process is simplified through a clear and intuitive graphical user interface (GUI), making setup quick and accessible for users with minimal technical experience. Additionally, advanced users can choose from alternative installation methods such as Docker containers or automated scripts, providing flexible deployment options to suit different environments and preferences.

-----------

### Tutorials

The following documentation is intended for both end-users and developers.


| **Description**                                                       | **Link**                                                                                         |
|----------------------------------------------------------------------|-------------------------------------------------------------------------------------------------|
| A playlist or video related to this project. | [https://www.youtube.com/playlist?list=PL6npOHuBGrpChSvani3MESZnzuKwwxz4o](hhttps://www.youtube.com/playlist?list=PL6npOHuBGrpChSvani3MESZnzuKwwxz4o)|
| If this repository contains a _videos folder, you can check that as well. | |

-----------

## Downloads  
The [Downloads Section](./download.html) provides all the necessary files to get started with the project, including the latest software versions and any related resources.

-----------

## Contributing  
Find out how you can contribute to the project by visiting the [Contributing Page](./contributing.html). Whether you want to report bugs, suggest features, or submit improvements, we welcome your involvement.

-----------

## Warranty  
Review the terms of our warranty on the [Warranty Information Page](./warranty.html). This page outlines the scope of support and any applicable guarantees.

-----------

## Support  
If you need assistance, visit the [Support Page](./support.html) to find the available channels for getting help with any issues or questions you might have.

-----------

## License  
Get the full details on licensing by checking out the [License Information Page](./license.html). This section includes the terms and conditions under which the project is distributed.