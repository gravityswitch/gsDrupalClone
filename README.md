# gsDrupalClone

## Introduction
The process for copying a Drupal site is anything but straightforward.  Despite numerous suggestions and guides out there it is difficult to find a complete workflow that actually works reliably for getting a site that's out there live copied into a local development environment where is can be accessed with a local domain name (i.e. - drupal.com -> drupal.dev).

## Installation
  1. Create a directory called `.gs_drupal_clone` in your home directory.
  2. Clone the github repo into the directory you just created:
  ```
  git clone https://github.com/gravityswitch/gsDrupalClone.git
  ```
  3. Make the file `gsDrupalClone` in the `bin/` directory executable:
  ```
  chmod 755 bin/gsDrupalClone
  ```
  4. You can either add the `.gs_drupal_clone/bin` director to your path or simply run it from within that directory, up to you.

## Config Setup

### General Config
Overall configuration for the gsDrupalClone is set through the file `~/.gs_drupal_clone/conf/clone.conf.php`.  You will need to copy the `clone.conf.php.default` file over to `clone.conf.php` and then edit it to set the options for your local environment.

* `dbLocalServerName`: This should be your local MySQL database server, most likely `localhost`.

* `dbRootUsername`: This should be the admin MySQL user (or a user with CREATE permissions).
  
* `dbRootPassword`: The password for the above user.
    
* `wwwroot`: This is the subdir within each of your local dev sites that contains the root for your web server to point to, for instance if you have a local site called *widgets.dev* the local directory may be at:
  ```
  ~/Sites/widgets/
  ```
  and within that structure your wwwroot directory might be at:
  ```
  ~/Sites/widgets/htdocs
  ```
  In this case `wwwroot` should be set to `htdocs`.
    
* `localDevTLD`: This is the pseudo Top Level Directory of your local dev env, so if you access your sites at <sitename>.dev then `.dev` is what this should be set to.
    
* `baseDir`: This is the base of where to find your local dev sites, so in the above example (for `wwwroot`) the `baseDir` would be `$_SERVER["HOME"] . "/Sites/"`.
    
* `drush`: This is the full path to the drush command on your system.


### Site Specific Config
Each site you will be replicating needs a config file.  The file should be located in `~/.gs_drupal_clone/conf` and should be named `<sitename>.conf.php`.

## Running the Script
```
$ gsDrupalClone <sitename>
```

## The Process
This script attempts to make that process simple and repeatable and in doing so performs the following steps:

  1. Checks for the existance of the local dev directory (as specified in the config file) and creates it if needed.
  2. Generates a Drush Alias file and places it in your local .drush directory.
  3. Uses Drush (utilizing the alias file created in step 2) to rsync files, including config, from the server.
  4. Creates a local database with identical name and credentials to the server.
  5. Pulls down the database from the server.
  6. Does a search and replace within the database dump to change all references to the domain name to the new local domain name.
  7. Populates the local DB with the modified DB dump.
  8. Turns off clean URLs (on the local site only) and then turns them back on.

This process allows sites to be cleanly, repeatably copied to a local envrionment with minimal effort required.
