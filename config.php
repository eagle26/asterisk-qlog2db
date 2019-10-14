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