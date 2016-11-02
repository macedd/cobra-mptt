<?php 
namespace Cobra_MPTT;

use PDO;

class Mptt_DB_PDO extends PDO implements Mptt_DB
{
    public function __construct($dsn, $username = null, $password = null, $driver_options = null)
    {
        parent::__construct($dsn, $username, $password, $driver_options);
        $this->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    }

    public function exec($sql)
    {
        return parent::exec($sql);
    }

    public function query($sql)
    {
        return parent::query($sql, PDO::FETCH_ASSOC);
    }

    public function insert_id()
    {
        return self::lastInsertId();
    }
}
