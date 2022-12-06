PHOUNDATION

This project is a PHP website development framework.

The 4.0 version is a new class based version that has been written from the ground up and is currently in heavy development. 

This project will be intended for production use, but right now is not ready for that. 

The web pages included in this project are built with the open source version of MDB UI kit, see https://mdbootstrap.com/general/license/#license-free

MIT license is compatible with GPL license:
https://technical-qa.com/can-i-use-mit-licensed-code-in-my-gpl-licensed-project/

=========================================================================

Current features planned for v4.* tree:

* Web, API & CLI interfaces.
  The system will have a web interface, multiple API interfaces (REST and GraphQL) and CLI interface through which basic system tasks can be mananged. At the same time, these system interfaces serve as a basis for expansion 
* Easy expandable CLI interface.
  Adding new commands to the CLI interface is as easy as creating new directories and PHP files under the /scripts/ directory
* Forced input data validation through easy validation functions.
  All variables received by this framework, be it through command line arguments, GET, POST or other API type requests all are hidden until validated by the developer. This ensures that all data is always validated before use.
* Simple environment based configuration Yaml formatted files. 
  With this system, many things can be configured, but almost no configuration is required to start the system. Also, different environments can run with different configurations safely and easily.  
* Automated per-library initialization system that can safely update your system in incremental steps
   
* Support for MySQL, Mongo, Redis, Memcached, and Elastic search databases is built in
   
* Support for multiple database connections per process.

* Full data synchronization between different environments.
    
* Users / Roles / Rights management
  
* Safe file access with restrictions
  
* Built in and fully customizable routing with automated scanning for malicious requests
  
* Safe process & commands handling
  
* Worker handling where hundreds of child processes can simultaneously execute commands in parrallel
  
* Built in notifications system in case of issues
  
* Support for templates, template for open source version of MDBootstrap included
  
* Supporting user managed plugins
  
* Auto CSS & JS minification, including code generated on the fly
  
* Auto CSS & JS bundling, including code generated on the fly
  
* Integrated incremental backups system using rsync and btrfs
  
* Basic hardware management to manage printer, scanner and fingerprint devices
  
* Integrated API manager to easily build REST and GraphQL API clients and servers
  
* GEO library that can automatically download and install datasets from
* Everything unit tested
* 
* Automatic image optimization for web pages with size, content and format changes supported
* Multilingual system with translation interface
* Fully automated deployment system that checks code, minifies CSS / JS, translates, sends code to remote server, and updates all file mode settings
* Servers management
* Devices management over multiple servers
  