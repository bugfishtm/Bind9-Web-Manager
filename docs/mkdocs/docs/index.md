# Proxmox-VMA2RAW

Convert Proxmox VMA backups to raw disk images with ease using Docker.

-----------

## Introduction

This repository provides the necessary files and a ready-to-use Docker image to convert Proxmox VMA backup files to raw disk images. These raw images can then be accessed or mounted with Windows software. This Docker image converts Proxmox VMA backup files to raw disk images. These raw images can then be accessed or mounted with Windows software.

### Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed on your machine.
- [DiskInternals Linux Reader](https://www.diskinternals.com/linux-reader/) for mounting raw disk images on Windows.

### Pull the Docker Image  

1. Pull the Docker image from Docker Hub:  
    ```sh
    docker pull bugfishtm/proxmox-vma2raw:latest
    ```  
2. Run the Docker container and specify a folder to put your vma files in:  
    ```sh
    docker run -it -v C:\path\to\your\files:/opt/files bugfishtm/proxmox-vma2raw:latest
    ```  

### Usage

1. Place your `.vma` files in the `files` folder specified at the docker image deployment.

2. Access the interactive shell of the running Docker container in bash mode:
    ```sh
    docker exec -it proxmox-vma2raw /bin/bash
    ```

3. Navigate to the `/opt/files` directory:
    ```sh
    cd /opt/files
    ```

4. Extract the `.vma` file to a raw disk image:
    ```sh
    vma extract ./sourcefile.vma ./extractdir
    ```
    - Ensure `./extractdir` does not exist before running the command.

5. The raw disk image will be available in the `./extractdir` directory inside the directory you placed your .vma file in.

6. Use [DiskInternals Linux Reader](https://www.diskinternals.com/linux-reader/) to mount and access the raw disk image.  

### For Developers

If you want to rebuild or change the image, in case dockerhub is not available or you want to modify the files.

1. Clone the repository:
    ```sh
    git clone https://github.com/bugfishtm/proxmox-vma2raw.git
    cd proxmox-vma2raw
    ```

2. Run the `create.bat` script to build and run the Docker container:
    ```sh
    ./_docker/build.bat
    ```


### Useful Links

- [Github](https://github.com/bugfishtm/proxmox-vma2raw)
- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- [DiskInternals Linux Reader](https://www.diskinternals.com/linux-reader/)
- [DockerHUB](https://hub.docker.com/r/bugfishtm/proxmox-vma2raw)


### Video Tutorial

The following documentation is intended for both end-users and developers.


| **Description**                                                       | **Link**                                                                                         |
|----------------------------------------------------------------------|-------------------------------------------------------------------------------------------------|
| A playlist or video related to this project. | [https://www.youtube.com/watch?v=AGllcgOKZDE](https://www.youtube.com/watch?v=AGllcgOKZDE)|
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