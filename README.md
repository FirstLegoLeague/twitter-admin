Twitter moderator interface
===========================

Introduction
------------

When showing tweets at a public event (e.g. twitter wall, or ticker tape), you probably don't want obscene or insulting text to show up, or maybe you want to filter out retweets.

This project listens for all tweets that match a configurable list of hashtags, words and/or users and stores them in a database. Using a web-interface, it is then possible to approve and dissapprove individual tweets, and even automatically (dis-)approve tweets from certain users.

Installation
------------

You'll need the following prerequisites:

* Webserver (e.g. Apache, but IIS should work too) with PHP5 for the moderator interface
* Commandline version of PHP5 for downloading new tweets
* Pear MDB2 module for PHP database access (e.g. php-mdb2-driver-mysql)
* Database (e.g. MySQL) for storing tweets
* Twitter account and Twitter application registration for connecting to Twitter API (see https://dev.twitter.com/apps for more info)

Quick installation howto:

* Clone this repository, and its submodule (phirehose). Preferably don't clone directly in your webserver's document root, see below. git clone --recurse-submodules https://github.com/FirstLegoLeague/twitter-admin.git
* Copy example.config.inc.php to config.inc.php and fill in details
* Create database (as configured in config.inc.php)
* Create required tables in database, based on schema.sql
* Setup your website to point to the www/ directory (e.g. create a symlink from your document root to it)
* Protect your moderator interface: copy and edit the www/example.htaccess and www/example.htpasswd files to your needs (i.e. rename to .htaccess and .htpasswd, edit the path in .htaccess, set a password using 'htpasswd .htpasswd admin')
* Start the poller (e.g. use the program 'screen' to keep it running in the background): ./poller.php
* Happy moderating :)

TODO
----

* Improve the installation howto with cut&pasteable instructions
* Describe how to get tweets out of the database (for now: "SELECT * FROM tweets WHERE approved=1")
