<?php

namespace Storm;

use M1\Vars\Vars;

class DB
{
	protected static $registry = array();

	# singleton pattern - only allow access through ::connect
	protected function __construct() { }
	protected function _clone() { }

	public static function connect($db_server, $db_name=null) {

		# determine registry key for server/name
		$db_key = ($db_name) ? "{$db_server}_{$db_name}" : $db_server;

		# if we're already connected to that db, return the cached db handle from the registry
		if (array_key_exists($db_key, self::$registry)) {
			return self::$registry[$db_key];
		}

		## otherwise, lets connect us to some databases

		# grab the config file from environment, or try $HOME if env is not available
		if (isset($_SERVER['STORM_DB_CONFIG'])) {
			$cfgfile = $_SERVER['STORM_DB_CONFIG'];
		} else {
			$cfgfile = $_SERVER['HOME']. '/.storm-db.yaml';
		}

		try {
			$dbcfg = new Vars($cfgfile, array('cache' => false));
		} catch (\InvalidArgumentException $e) {
			throw new \RuntimeException("Database configuration \"{$cfgfile}\" unreadable or not found!\n");
		}

		if (! isset($dbcfg[$db_key])) {
			throw new \InvalidArgumentException("Couldn't load config for {$db_key} database");
		}

		$dbuser = $dbcfg[$db_key]['username'];
		$dbpass = $dbcfg[$db_key]['password'];
		$dbhost = $dbcfg[$db_key]['host'];
		$dbname = $dbcfg[$db_key]['database'];

		if (isset($dbcfg[$db_key]['port'])) {
			$dbhost .= ":{$dbcfg[$db_key]['port']}";
		}

		$dbh = NewADOConnection($dbcfg[$db_key]['adapter']);

		# enable memcache instead of file based caching
		# if the server supports it
		if (function_exists('memcache_pconnect')) {
			$dbh->memCache = true;
			$dbh->memCacheHost = array('127.0.0.1');
			$dbh->memCachePort = 11211;
			$dbh->memCacheCompress = false;
		}

		if (! @$dbh->NConnect($dbhost, $dbuser, $dbpass, $dbname)) {
			throw new \RuntimeException("Cannot connect to database! host: {$dbhost}, user: {$dbuser}, db: {$dbname}, error: {$dbh->ErrorMsg()}");
		}

		# set associative fetch mode by default
		$dbh->SetFetchMode(ADODB_FETCH_ASSOC);

		# store the database handle for later re-use
		self::$registry[$db_key] = $dbh;

		return self::$registry[$db_key];
	}
}
