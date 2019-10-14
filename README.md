# asterisk-qlog2db
Migrates asterisk queue logs from plain text files to a MySQL database.

#How to Use it

- Clone or download the repo
```bash
git clone https://github.com/eagle26/asterisk-qlog2db.git
```

- Go to the folder generated after cloning the repo
- Mount the SQL File
```bash
mysql -uroot < sql/asterisk.sql
```
- Edit the config file according to your needs
```php
<?php
class config{
	//Database Configurations (MySQL)
	const DB_NAME = 'asterisk';
	const DB_HOST = 'localhost';
	const DB_PORT = 3306;
	const DB_USER = 'root';
	const DB_PASSWORD = null;

	//Queues Log Table
	const QUEUES_LOG_TBL = 'queues_log';

	//Log Path
	const QUEUES_LOG_PATH = "/var/log/asterisk";
}
```
- Run the script
```bash
php qlog2db.php
```