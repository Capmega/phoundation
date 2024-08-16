# PHOUNDATION

This project is a PHP website development framework.

The 4.10 version is a new class based version that has been written from the ground up and is currently in heavy development.

This project will be intended for production use, but right now is still Alpha version quality

The web pages included in this project are built with the open source version of MDB UI kit, see https://mdbootstrap.com/general/license/#license-free

MIT license is compatible with GPL license:
https://technical-qa.com/can-i-use-mit-licensed-code-in-my-gpl-licensed-project/

=========================================================================

Current features planned for v4.* tree:

* (WIP)  Kubernetes / Docker interface libraries with management through CLI (partially available already) and Web 

* (100%) Web, API & CLI interfaces. The system will have a web interface, multiple API interfaces (REST and GraphQL) and CLI interface through which basic system tasks can be mananged. At the same time, these system interfaces serve as a basis for expansion

* (100%) Easy expandable CLI interface. Adding new commands to the CLI interface is as easy as creating new directories and PHP files under the /commands/ directory, or adding them in your library under LIBRARYDIRECTORY/Library/commands/....

* (100%) Forced input data validation through easy validation functions. All variables received by this framework, be it through command line arguments in $argv, GET, POST or other API type requests all are hidden until validated by the developer. This ensures that all data is always validated before use.

* (100%) Simple environment based configuration Yaml formatted files. With this system, many things can be configured, but almost no configuration is required to start the system. Also, different environments can run with different configurations safely and easily.

* (100%) Automated per-library initialization system that can safely update your system in incremental steps

* (100%) Support for multiple database connections per process.

* (100%) DataEntry objects that, with simple table definitions, allow for easy basic CRUD operations

* (100%) DataEntry objects track changes using meta_id, allowing for change auditing

* (100%) RBAC Role Based Access Control system, with users / roles / rights management. Users can have multiple roles assigned. Each role will give the user certain rights which will give the user access to the different pages

* (100%) Safe process & commands handling

* (100%) Process worker handling where hundreds of child processes can simultaneously execute commands in parallel

* (100%) Built in notifications system that can send notifications to users or roles, where all users having that role will receive the notification

* (100%) Support for multiple HTML templates, template for open source version of MDBootstrap and AdminLTE included.

* (100%) GeoIP library that can automatically download and install datasets from maxmind

* (100%) Servers management

* (100%) All file access requires restrictions to ensure files can only be accessed safely

* (95%) Built in and fully customizable routing with automated scanning for malicious requests

* (100%) Support for vendor grouped plugins

* (80%) Support for MySQL, Mongo, Redis, Memcached, and Elastic search databases is built in

* (50%) Auto CSS & JS minification, including code generated on the fly

* (50%) Auto CSS & JS bundling, including code generated on the fly

* (0%) Integrated incremental backups system using rsync and btrfs

* (80%) Basic hardware management to manage printer, scanner and fingerprint devices

* (5%) Integrated API manager to easily build REST and GraphQL API clients and servers

* (70%) Geo library that can automatically download and install datasets from geonames

* (5%) Everything unit tested

* (40%) Automatic image optimization for web pages with size, content and format changes supported

* (20%) Multilingual system with translation interface

* (50%) Fully automated deployment system that checks code, minifies CSS / JS, translates, sends code to remote server, and updates all file mode settings, allows data synchronization from production server

* (30%) Devices management over multiple servers


### Example CLI commands
./pho -Z                                  # Rebuilds the commands cache
./pho cache system rebuild all            # Rebuilds all system caches (web, commands, hooks, and tests)
./pho info                                # Displays general system information   
./pho libraries stats                     # Displays information about all available libraries and plugins   
./pho system deploy to production         # Deploys current installation to the production environment
./pho system sync from production         # Synchronizes all databases and data files from the production environment to the local environment
./pho system project update               # Updates the core libraries of this project from Phoundation repositories   
./pho devops docker build                 # Builds a docker image of this project   
./pho devops kubernetes create deployment # Creates a new deployment in Kubernetes
./pho system geo ip import                # Downloads and imports the GeoIP data


### Most important static objects:

Route    This class is only used in web/index.php to route HTTP requests to your pages

Request  Contains various methods to manage the request made by the HTTP client

Response Contains various methods to manage the response to the HTTP client

Session  The Session object contains information about the current HTTP session

Core     Contains basic system information

Log      The system logger, contains various methods to log notices, warnings, errors, and successes to the log files
