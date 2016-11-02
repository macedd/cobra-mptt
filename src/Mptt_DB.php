<?php 
namespace Cobra_MPTT;

/**
 * Interface to implement the mptt database
 */
interface Mptt_DB {
    public function exec($sql);
    public function query($sql);
    public function insert_id();
}
