<?php

// Language definitions used in install.php
$lang_install = array(

// Install Form
'Install S2'				=>	'Install S2 %s',
'Part 0'					=>	'Change installer language',
'Choose language help'		=>	'You can change the language of this install script if you find it easier to follow the instructions in your own language. Just choose your language from the list of installed ones below.',
'Installer language'		=>	'Installer language',
'Choose language legend'	=>	'Installer language',
'Choose language'			=>	'Change language',
'Part1'						=>	'Database setup',
'Part1 intro'				=>	'Please enter the requested information in order to setup your database for S2. You must know all the information asked for before proceeding with the installation. Contact your hosting support in case of difficulties.',
'Database type'				=>	'Database type',
'Database name'				=>	'Database name',
'Database server'			=>	'Database server',
'Database username'			=>	'Database username',
'Database password'			=>	'Database password',
'Database user pass'		=>	'Database username and password',
'Table prefix'				=>	'Table prefix',
'Database type info'		=>	'S2 currently supports MySQL, PostgreSQL and SQLite. If your database of choice is missing from the drop-down menu below, it means this PHP environment does not have support for that particular database. More information regarding support for particular versions of each database can be found in the documentation.',
'Mysql type info'			=>	'S2 has detected that your PHP environment supports two different ways of communicating with MySQL. The two options are called "<em>standard</em>" and "<em>improved</em>". If you are uncertain which one to use, start by trying improved and if that fails, try standard.',
'MySQL InnoDB info'			=>	'S2 has detected that your MySQL server might support <a href="http://dev.mysql.com/doc/refman/5.0/en/innodb-overview.html">InnoDB</a>. This would be a good choice if you are planning to run a large site. If you are uncertain, it is recommended to not use InnoDB.',
'Database server info'		=>	'Enter the address of the database server (example: <em>localhost</em>, <em>mysql1.example.com</em> or <em>208.77.188.166</em>). You can specify a custom port number if your database doesn\'t run on the default port (example: <em>localhost:3580</em>). For SQLite support, just enter anything or leave it at \'localhost\'.',
'Database name info'		=>	'Enter the name of the database that S2 will be installed into. The database must exist. For SQLite, this is the relative path to the database file. If the SQLite database file does not exist, S2 will attempt to create it.',
'Database username info'	=>	'Enter the username and password used for connecting to the selected database. Ignore for SQLite.',
'Table prefix info'			=>	'Optional - enter a database table prefix. By specifying a table prefix you can run multiple copies of S2 in the same database (example: <em>test_</em>).',
'Part1 legend'				=>	'Database information',
'Database type help'		=>	'Select database type.',
'Database server help'		=>	'The address of your database server. For SQLite enter anything.',
'Database name help'		=>	'Existing database S2 will be installed to',
'Database username help'	=>	'For database connection. Ignore for SQLite.',
'Database password help'	=>	'For database connection. Ignore for SQLite.',
'Table prefix help'			=>	'Optional database table prefix e.g. «test_».',
'Part2'						=>	'Administrator setup',
'Part2 legend'				=>	'Administrator\'s details',
'Part2 intro'				=>	'Please enter the requested information in order to setup an administrator account for your S2 installation. You can create more administrators and moderators in the control panel later.',
'Admin username'			=>	'Username',
'Admin username info'		=>	'Choose your username.',
'Admin password'			=>	'Password',
'Admin password info'		=>	'Think up a password. You can change it later.',
'Admin e-mail'				=>	'Admin\'s e-mail',
'Admin e-mail info'			=>	'If you specify <em>the e-mail of the administrator</em>, you will receive notifications about visitors\' comments. The e-mail of the administrator will never be published (however it will be displayed in the control panel to users with granted permissions). You can change this address later. Also, the value of this field will be given to the <em>webmaster e-mail</em>. Webmaster e-mail is used in RSS and as the sender e-mail in mailing comments to subscribers. Actually, spammers can get it. Webmaster e-mail can be changed later independently of the e-mails connected with accounts.',
'Username help'				=>	'Between 2 and 25 characters.',
'Password help'				=>	'Your password.',
'E-mail address help'		=>	'Your e-mail. See the information above.',
'Part3'						=>	'Site setup',
'Part3 legend'				=>	'Site information',
'Part3 intro'				=>	'Please enter the requested information about the site.',
'Base URL'					=>	'Base URL',
'Base URL info'				=>	'Please pay particular attention when entering your Base URL. You must set the correct Base URL or your site will not work properly. The Base URL is the URL (without trailing slash) of your site (example: <em>http://example.com</em> or <em>http://example.com/~myuser</em>). Please note that the preset value below is just an educated guess by S2.',
'Base URL help'				=>	'The URL (without trailing slash) of your S2 installation. Please read information above.',
'Default language'			=>	'Site language',
'Default language help'		=>	'If you are going to delete the current language pack (English), you must choose another one before deleting.',
'Start install'				=>	'Start installation', // Label for submit button
'Required'					=>	'(Required)',


// Install errors
'No database support'		=>	'This PHP environment does not have support for any of the databases that S2 supports. PHP needs to have support for either MySQL, PostgreSQL or SQLite in order for S2 to be installed.',
'Missing database name'		=>	'You must enter a database name. Please go back and correct.',
'Username too long'			=>	'Usernames must be no more than 25 characters long. Please go back and correct.',
'Username too short'		=>	'Usernames must be at least 2 characters long. Please go back and correct.',
'Pass too short'			=>	'Passwords must be at least 4 characters long. Please go back and correct.',
'Invalid email'				=>	'The administrator e-mail address you entered is invalid. Please go back and correct.',
'Missing base url'			=>	'You must enter a base URL. Please go back and correct.',
'No such database type'		=>	'\'%s\' is not a valid database type.',
'Invalid MySQL version'		=>	'You are running MySQL version %1$s. S2 requires at least MySQL %2$s to run properly. You must upgrade your MySQL installation before you can continue.',
'Invalid table prefix'		=>	'The table prefix \'%s\' contains illegal characters. The prefix may contain the letters a to z, any numbers and the underscore character. They must however not start with a number. Please choose a different prefix.',
'Too long table prefix'		=>	'The table prefix \'%s\' is too long. The maximum length is 40 characters. Please choose a different prefix.',
'SQLite prefix collision'	=>	'The table prefix \'sqlite_\' is reserved for use by the SQLite engine. Please choose a different prefix.',
'S2 already installed'		=>	'A table called "%1$susers" is already present in the database "%2$s". This could mean that S2 is already installed or that another piece of software is installed and is occupying one or more of the table names S2 requires. If you want to install multiple copies of S2 in the same database, you must choose a different table prefix.',
'Invalid language'			=>	'The language pack you have chosen doesn\'t seem to exist or is corrupt. Please recheck and try again.',

// Used in the install
'Site name'					=>	'Site powered by S2',
'Main Page'					=>	'Main page',
'Section example'			=>	'Section 1',
'Page example'				=>	'Page 1',
'Page text'					=>	'If you see this text, the install of S2 has been successfully completed. Now you can go directly to <script type="text/javascript">document.write(\'<a href="\' + document.location.href + \'---">the control panel</a>\');</script> and configure this site.',


// Installation completed form
'Success description'		=>	'Congratulations! S2 %s is successfully installing.',
'Success welcome'			=>	'Please follow the instructions below to finalize the installation.',
'Final instructions'		=>	'Final instructions',
'No write info 1'			=>	'<strong>Notice!</strong> To finalize the installation, you need to click on the button below to download a file called config.php. You then need to upload this file to the root directory of your S2 installation.',
'No write info 2'			=>	'Once you have uploaded config.php, S2 will be fully installed! You may then %s once config.php has been uploaded.',
'Go to index'				=>	'go to the main page',
'Warning'					=>	'Warning!',
'No cache write'			=>	'<strong>The cache directory is currently not writable!</strong> In order for S2 to function properly, the directory named <em>_cache</em> must be writable by PHP. Use chmod to set the appropriate directory permissions. If in doubt, chmod to 0777.',
'No pictures write'			=>	'<strong>The picture directory is currently not writable!</strong> If you want to upload pictures and other files you must check that the directory named <em>_pictures</em> is writable by PHP. Use chmod to set the appropriate directory permissions. If in doubt, chmod to 0777.',
'File upload alert'			=>	'<strong>File uploads appear to be disallowed on this server!</strong> If you want to upload pictures in the control panel, you have to enable the file_uploads configuration setting in PHP.',
'Download config'			=>	'Download config.php', // Label for submit button
'Write info'				=>	'S2 is completely installed! Now you may %s.',
);