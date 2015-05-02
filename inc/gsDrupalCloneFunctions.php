<?php
/**
 * File gsDrupalCloneFunctions.php
 *
 * Include file containing all of the functions for the gsDrupalClone
 * script.
 *
 * PHP version 5
 *
 * @category  Drupal_Scripts
 * @package   GSDrupalClone
 * @author    Chris Slack <chris@gravityswitch.com>
 * @copyright 2015 Gravity Switch
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://www.gravityswitch.com
 */

/**
 * Function setupDirs
 *
 * Ensures that the local directories needed exist and if they do not it
 * creates them.
 *
 * @param array $CLONE_CONF Overall config array
 * @param array $SITE_CONF  Site specific config array
 *
 * @return none
 */
function setupDirs($CLONE_CONF, $SITE_CONF)
{

    $baseDir = $CLONE_CONF['baseDir'] . $SITE_CONF['localSite'];
    $wwwroot = $CLONE_CONF['wwwroot'];

    if (!file_exists($baseDir)) {
        mkdir($baseDir);
        echo "Created Directory " . $baseDir . " [OK]\n";
    } else {
        print "Directory $baseDir already exists [OK]\n";
    }

    if (!file_exists("$baseDir/$wwwroot")) {
        mkdir("$baseDir/$wwwroot");
        echo "Created Directory " . $baseDir . "/" . $wwwroot . " [OK]\n";
    } else {
        print "Directory $baseDir/$wwwroot already exists [OK]\n";
    }
}


/**
 * Function setupAliasFile
 *
 * Creates and sets up the drush alias file for the remote and local envs in the
 * ~/.drush directory
 *
 * @param array $CLONE_CONF Overall config array
 * @param array $SITE_CONF  Site specific config array
 *
 * @return none
 */
function setupAliasFile($CLONE_CONF, $SITE_CONF)
{

    //global $aliases;

    $remoteSite = $SITE_CONF['remoteSite'];
    $remoteUser = $SITE_CONF['remoteUser'];
    $localSite = $SITE_CONF['localSite'];
    $remoteDir = $SITE_CONF['remoteDir'];
    $localDevTLD = $CLONE_CONF['localDevTLD'];
    $baseDir = $CLONE_CONF['baseDir'];
    $wwwroot = $CLONE_CONF['wwwroot'];
    $drush = $CLONE_CONF['drush'];

    $aliasFile = $_SERVER["HOME"] . "/.drush/$localSite.aliases.drushrc.php";

    shell_exec(
        "ssh $remoteUser@$remoteSite \"drush --root=\\\"$remoteDir\\\" \
        site-alias --with-db --show-passwords --with-optional @self\" > \
        $aliasFile.tmp"
    );

    shell_exec(
        "echo '<?php' > .$localSite.alias.tmp && cat $aliasFile.tmp >> \
        .$localSite.alias.tmp"
    );

    include ".$localSite.alias.tmp";

    // Delete the temp files
    // unlink("$aliasFile.tmp");
    // unlink(".$localSite.alias.tmp");
    $siteKey = array_keys($aliases);

    $aliases["$remoteSite"] = $aliases[$siteKey[0]];
    $aliases["$localSite.$localDevTLD"] = $aliases["$remoteSite"];

    // Update the array with remote information
    $aliases["$remoteSite"]["remote-host"] = $remoteSite;
    $aliases["$remoteSite"]["remote-user"] = $remoteUser;

    // Update the array with local information
    $aliases["$localSite.$localDevTLD"]['root'] = $baseDir .
        $localSite . "/$wwwroot";

    $aliases["$localSite.$localDevTLD"]['path-aliases']['%drush'] = $drush;
    
    // Write the array back out to the alias file in PHP format
    $results = "<?php \$aliases[\"$remoteSite\"] = " .
        var_export($aliases["$remoteSite"], true) . ";\n";

    $results .= "\$aliases[\"$localSite.$localDevTLD\"] = " .
        var_export($aliases["$localSite.$localDevTLD"], true) . ";\n";

    file_put_contents($aliasFile, $results);

    echo "Setup alias file " . $aliasFile . " [OK]\n";

}


/**
 * Function setupSettingsFile
 *
 * Creates and sets up the drush alias file for the remote and local envs in the
 * ~/.drush directory
 *
 * @param array $CLONE_CONF Overall config array
 * @param array $SITE_CONF  Site specific config array
 *
 * @return none
 */
function setupSettingsFile(
    $CLONE_CONF,
    $SITE_CONF,
    $dbLocalName,
    $dbLocalUser,
    $dbLocalPassword
) {

    //global $aliases;
    $localSite = $SITE_CONF['localSite'];
    $localDevTLD = $CLONE_CONF['localDevTLD'];
    $baseDir = $CLONE_CONF['baseDir'];
    $wwwroot = $CLONE_CONF['wwwroot'];

    $dbLocalServername = $CLONE_CONF['dbLocalServerName'];
    $dbRootUsername = $CLONE_CONF['dbRootUsername'];
    $dbRootPassword = $CLONE_CONF['dbRootPassword'];

    $settingsFile = $baseDir . $localSite . "/" . $wwwroot .
        "/sites/default/settings.php";
    
    // Write the array back out to the alias file in PHP format
    $databases = array();

    $databases['default'] = array();
    $databases['default']['default'] = array();

    $databases['default']['default']['database'] = $dbLocalName;
    $databases['default']['default']['username'] = $dbLocalUser;
    $databases['default']['default']['password'] = $dbLocalPassword;
    $databases['default']['default']['host'] = "localhost";
    $databases['default']['default']['port'] = "";
    $databases['default']['default']['driver'] = "mysql";
    $databases['default']['default']['prefix'] = "";

    $results = "<?php\n\$databases = " .
        var_export($databases, true) . ";\n";

    file_put_contents($settingsFile, $results);

    echo "Created settings.php file " . $settingsFile . " [OK]\n";

}


/**
 *Function syncFiles
 *
 * Uses drush to rsync files from the remote server to the local env
 *
 * @param array $CLONE_CONF Overall config array
 * @param array $SITE_CONF  Site specific config array
 *
 * @return none
 */
function syncFiles($CLONE_CONF, $SITE_CONF)
{

    $remoteSite = $SITE_CONF['remoteSite'];
    $localSite = $SITE_CONF['localSite'];
    $localDevTLD = $CLONE_CONF['localDevTLD'];
    $wwwroot = $CLONE_CONF['wwwroot'];
    $baseDir = $CLONE_CONF['baseDir'] . "$localSite";


    // Copy all of the files from the server with rsync/scp
    echo "Syncing files, this may take a while...\n";
    // shell_exec(
    //     "drush -y rsync --include-conf @$remoteSite \
    //     @$localSite.$localDevTLD"
    // );
    shell_exec(
        "drush -y rsync  @$remoteSite \
        @$localSite.$localDevTLD"
    );
    // Need to make sure files are readable by local web server
    shell_exec("chmod -R 755 $baseDir/*");

    echo "Files synced [OK]\n";

}


/**
 * Function setupDB
 *
 * Creates the local database required and grants permission to the specified
 * user.  NOTE: This will DELETE any database with the same name.
 *
 * @param array  $CLONE_CONF      Overall config array
 * @param string $dbLocalName     MySQL site database name
 * @param string $dbLocalUser     MySQL site user name
 * @param string $dbLocalPassword Password for mysql site user
 *
 * @return none
 */
function setupDB($CLONE_CONF, $dbLocalName, $dbLocalUser, $dbLocalPassword)
{

    $dbLocalServername = $CLONE_CONF['dbLocalServerName'];
    $dbRootUsername = $CLONE_CONF['dbRootUsername'];
    $dbRootPassword = $CLONE_CONF['dbRootPassword'];

    // Create connection
    $localDB = new mysqli($dbLocalServername, $dbRootUsername, $dbRootPassword);

    // Check connection
    if ($localDB->connect_error) {
        die("Connection failed: " . $localDB->connect_error);
    }

    // If the DB already exists it needs to be deleted
    $sql = "DROP DATABASE $dbLocalName";
    $localDB->query($sql);

    // Create database
    $sql = "CREATE DATABASE $dbLocalName";
    if ($localDB->query($sql) === true) {
        echo "Database created successfully [OK]\n";
    } else {
        echo "Error creating database: " . $localDB->error . "\n";
    }

    // Create user and grant appropriate permissions
    $sql = "GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, 
        DROP, INDEX, ALTER, CREATE TEMPORARY TABLES ON 
        $dbLocalName.* TO \"$dbLocalUser\"@'localhost' 
        IDENTIFIED BY \"$dbLocalPassword\"";

    if ($localDB->query($sql) === true) {
        echo "Database user created successfully [OK]\n";
    } else {
        echo "Error creating database user: " . $localDB->error . "\n";
    }

    // Close the DB connection
    $localDB->close();
}


/**
 * Function populateDB
 *
 * Fills the local database with the contents of the remote database changing
 * any hard coded URLs to reflect the URL change from the remote to local env.
 *
 * @param array  $CLONE_CONF  Overall config array
 * @param array  $SITE_CONF   Site specific config array
 * @param string $dbLocalName The name of the local Database
 *
 * @return none
 */
function populateDB($CLONE_CONF, $SITE_CONF, $dbLocalName)
{

    $remoteSite = $SITE_CONF['remoteSite'];
    $localSite = $SITE_CONF['localSite'];
    $localDevTLD = $CLONE_CONF['localDevTLD'];
    $dbRootUsername = $CLONE_CONF['dbRootUsername'];
    $dbRootPassword = $CLONE_CONF['dbRootPassword'];

    $remoteDumpFile = "/tmp/$remoteSite.sql.tmp";
    $file = "/tmp/$localSite.sql.tmp";

    // Build the search string with the dots escaped
    $remoteSiteSearchStr = preg_replace('/\./', '\\.', $remoteSite);

    echo "Dumping remote database, this may take some time...\n";

    // Dump DB from server
    // $remoteDump = shell_exec("drush @$remoteSite sql-dump");
    shell_exec("drush @$remoteSite sql-dump > $remoteDumpFile");

    // Modify the DB dump from server to reflect the local env
    // Equiv of the following sed command:
    // LANG=C sed 's/stiebel\.gravityswitch\.com/stiebel\.dev/g' stiebel.sql > stiebel.dev.sql

    shell_exec("/bin/bash -c \"LANG=C sed 's/$remoteSiteSearchStr/$localSite.$localDevTLD/g' $remoteDumpFile > $file\"");

    // Populate the local DB with the modified DB Dump
    shell_exec(
        "mysql -u $dbRootUsername \
        -p$dbRootPassword $dbLocalName < $file > /dev/null 2>&1"
    );

    // Delete the file created by the SQL dump
    unlink($remoteDumpFile);
    unlink($file);
}


/**
 * Function resetCleanURLs
 *
 * When a migration takes place from a site with clean URLs turned on the
 * clean_url variable must be turned off on the local site to reset the
 * encoded URLs in order to work with a new base URL.  Once the clean_url
 * is turned off it is turned back on again so no site behavior is different
 * from remote to local, but the toggle is neccesary for reset purposes.
 *
 *  @param array $CLONE_CONF Pass in the overall config array
 *  @param array $SITE_CONF  Pass in the site specific config array
 *
 *  @return none
 */
function resetCleanURLs($CLONE_CONF, $SITE_CONF)
{

    $localSite = $SITE_CONF['localSite'];
    $baseDir = $CLONE_CONF['baseDir'] . "$localSite";
    $wwwroot = $CLONE_CONF['wwwroot'];

    // Need to reset clean URLs
    shell_exec("cd $baseDir/$wwwroot; drush vset clean_url 0 --yes");
    shell_exec("cd $baseDir/$wwwroot; drush cache-clear all");
    shell_exec("cd $baseDir/$wwwroot; drush vset clean_url 1 --yes");
}
