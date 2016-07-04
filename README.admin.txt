Local setup example:

========================================================

1) HOST FILE SETUP (/etc/hosts)

127.0.0.1       dev.bookmarks.com

========================================================

2) VIRTUAL HOST SETUP (/etc/apache2/sites-enabled)

<VirtualHost *:80>
	ServerName dev.bookmarks.com
	DocumentRoot /var/www/bookmarks.com/web

	<Directory /var/www/bookmarks.com/web>
		DirectoryIndex /index.php
		FallbackResource /index.php
	</Directory>

        LogLevel notice
        ErrorLog /var/www/bookmarks.com/server.log
        CustomLog /var/www/bookmarks.com/access.log combined
</VirtualHost>

========================================================

3) DATABASE SETUP

CREATE DATABASE `bookmarks`;

CREATE TABLE `bookmark` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `uri` varchar(2048) NOT NULL,
  `comments` varchar(2048) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `comment` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `text` text NOT NULL,
  `ip` varchar(32) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

...and do not forget to update app.ini after