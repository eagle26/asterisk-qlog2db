#!/usr/bin/php
<?php
include_once ('config.php');
include_once ('db.php');


class qlog2db{
	private static $_DB;

	public function __construct(){
		self::$_DB = db::init();
	}

	public function run(){
		$pattern = config::QUEUES_LOG_PATH . '/' . 'queue_log*';
		$files = glob($pattern);
		if(!count($files))
			echo "Cannot find any queue log file on path: ". config::QUEUES_LOG_PATH."\n";

		foreach ($files as $filename){
			//If cannot open the file, try with the next
			if (!is_resource($handle = @fopen($filename, 'r'))){
				echo "Cannot open the file: {$filename}\n";
				continue ;
			}

			while (($line = fgets($handle)) !== false) {
				$line = trim($line);
				$line  = explode('|', $line);

				//As minimum must have 5 values (UNIX Timestamp|UNIQUE ID|QUEUE NAME|MEMBER CHANNEL|EVENT TYPE)
				//1366720340|1366720340.303267|MYQUEUE|SIP/8007|RINGNOANSWER|1000
				if(count($line) < 5)
					continue ;

				$event = $this->_parseEvent($line);
				//I think the following events are not needed at all, so, I will skip them
				if(in_array($event->event, ['CONFIGRELOAD', 'QUEUESTART']))
					continue ;

				//Add the event to MySQL
				$this->_saveEvent($event);
			}

			fclose($handle);
		}
	}

	/**
	 * Save the queues log event on MySQL
	 * @param stdClass $event
	 */
	private function _saveEvent(stdClass $event){
		$db = self::$_DB;

		$query = 'insert into `queues_log` (
					`time`,
					`callid`,
					`queuename`,
					`agent`,
					`event`,
					`data`,
					`data1`,
					`data2`,
					`data3`,
					`data4`,
					`data5`,
					`created`
			  ) values (?,?,?,?,?,?,?,?,?,?,?,?)';

		try{
			$db->startTransaction();
			$db->query(
					$query,
					$event->time,
					$event->uniqueid,
					$event->queue,
					$event->channel,
					$event->event,
					$event->data,
					$event->data1,
					$event->data2,
					$event->data3,
					$event->data4,
					$event->data5,
					$event->time
				);
			$db->endTransaction();
		}catch (Exception $e){
			$db->rollBack();
			die($e->getMessage()."\n");
		}
	}

	/**
	 * Convert each log line into an associative object
	 * @param $line
	 * @return object
	 */
	private function _parseEvent($line){
		list($time,$uniqueid,$queue,$channel,$event) =  $line;
		$extra_data = array_slice($line, 5);

		$channel = $this->_parseChannel($channel);

		$event = [
			'event' => $event,
			'time' => $time,
			'uniqueid' => $uniqueid,
			'queue' => $queue,
			'channel' => $channel,
			'data' => null,
			'data1' => null,
			'data2' => null,
			'data3' => null,
			'data4' => null,
			'data5' => null,
		];

		$x = 1;
		foreach ($extra_data as $data){
			$index = "data{$x}";
			$event[$index] = $data;
			$x++;
		}

		return (object) $event;
	}

	private function _parseChannel($channel){
		if(preg_match('/^(local|sip|iax2|pjsip|dahdi)\/(.*)@(.*)/i', $channel, $matches)){
			$channel = $matches[2];
		}

		return $channel;
	}
}

/* Starting to synchronize*/
$qlog2db = new qlog2db();
$qlog2db->run();