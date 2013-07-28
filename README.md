TYPO3CronFinder
===============

Searches local TYPO3 Instances and writes cronfile

What it does
------------

This small script is looking for local TYPO3 instances and automatically create cronjob files if requested. It is PHP based and very easy to extend. You are also able to run this script as a cronjob to prevent skipped TYPO3 instances.

How to use it
-------------

General Usage:

    $ ./find.php path depth everyMinutes [cronPath] [cronUser] [quiet]

### Params

##### Path

The path where the search should start. For example `/var/www` or `home`

##### Depth

The depth of your architecture. For example:

`$path = '/var/www/'` and TYPO3 instances in `/var/www/$DOMAIN/htdocs/` then use `2`.

    /var/www/ => PATH
    $DOMAIN/  => Level 1
    htdocs/   => Level 2

##### everyMinutes

This value will be used to setup the distance between each call. Technically this is `*/$value * * * *` in the crobtabs timer. So do't values bigger equals 59.
If there will be the request for bigger values open a new issue.

##### cronPath

This variable describes the path to the cronjob files. It has to eigther be a local file ( with existing parent directory ) or `NONE`. If `NONE` is set the content will be returned to CLI.

##### cronUser

This variable will set the user for each call of the cronrun. It is possible to set this to `owner` or a local user. `owner` will use the owner of the folder which contains typo3conf.

##### quiet

If one param equals `-q` only errors will be posted to your shell. Perfect for cronjobs.

Troubleshooting
---------------

### MoreComplex vhost structure

If you are currently using a more complex structure of your vhost structure ( for example if you are using Plesk as hosting software ), than you are not able to use a single run to fetch all TYPO3 instances. In this case there is the solution to run this script two times.
    
    $ ./find.php /var/www/vhosts/ 2 5 /etc/cron.d/TYPO3_Cron_1
    $ ./find.php /var/www/vhosts/ 4 5 /etc/cron.d/TYPO3_Cron_2
    
This would fetch:

    /var/www/vhosts/default.com/htdocs/typo3conf
    /var/www/vhosts/default.com/subdomains/test/htdocs/typo3conf
    

How to contribute
-----------------
The TYPO3 Community lives from your contribution!

You wrote a feature? - Start a pull request!

You found a bug? - Write an issue report!

You are in the need of a feature? - Write a feature request!

You have a problem with the usage? - Ask!
