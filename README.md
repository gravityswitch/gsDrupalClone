# gsDrupalClone

## Introduction
The process for copying a Drupal site is anything but strightforward.  Despite numerous suggestions and guides out there it is difficult to find a complete workflow that actually works reliably for getting a site that's out there live copied into a local development environment where is can be accessed with a local domain name (i.e. - drupal.com -> drupal.dev).

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
