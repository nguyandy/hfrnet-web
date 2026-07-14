<?php
 /**
  * Provides a consistent method of locking and unlocking files during
  * read/write operations.  Methods provided as static operations.
  *
  * @file Locker.php
  * @date 2010-02-01 14:43 HST
  * @author Paul Reuter
  * @version 1.1.2
  *
  * @modifications
  * 1.0.0 - 2009-02-13 - Created
  * 1.0.1 - 2009-03-02 - Minor layout change, no code changed.
  * 1.0.2 - 2009-03-26 - Win32 system uptime support.
  * 1.0.3 - 2009-03-26 - Added dependency on Filesystem::file_put_contents
  * 1.0.4 - 2009-05-19 - Added /proc/uptime test for system uptime.
  * 1.0.5 - 2009-07-11 - BugFix: getLockFilePath now returns "%s.lock"
  * 1.1.0 - 2009-10-08 - Modify: Added isLockStale, changed isLocked (reboot)
  * 1.1.1 - 2009-12-10 - BugFix: Speed up isLocked return.
  * 1.1.2 - 2010-02-01 - BugFix: lock must touch() file. Req. Filesystem::mkdir
  */


// Required includes:
require_once("Filesystem.php");


/**
 * Locking files is unreliable with flock(), so I made this class to
 * perform locks in a consistent manner using .lock files.
 *
 * NB: This class ain't for everybody, only the sexy people... who don't
 * use files ending in .lock for any purpose other than locking files.
 */
class Locker {

  /**
   * Attempt to obtain a lock for a file.
   *
   * @public
   * @static
   * @param {string} $fpath File to lock.
   * @param {int} $retries Number of retries to make.
   * @return {boolean} true if lock obtained, false otherwise.
   */
  function lock($fpath,$retries=3) {
    if( Locker::islocked($fpath,$retries) ) {
      return false;
    }

    $lock = Locker::m_getLockFilePath($fpath);
    
    if( !Filesystem::mkdir($lock,null,1) || !touch($lock) ) { 
      error_log("couldn't create lock.");
      return false;
    }

    return true;
  } // END: function lock($fpath,$retries=3)


  /**
   * Remove a lock on specified file.
   *
   * @public
   * @static
   * @param {string} $fpath The file to remove a lock on.
   * @return {boolean} success or failure.
   */
  function unlock($fpath) {
    if( !Locker::isLocked($fpath) ) {
      error_log("can't unlock a file that's not locked.");
      return false;
    }
    $lock = Locker::m_getLockFilePath($fpath);
    return unlink($lock);
  } // END: function unlock()


  /**
   * Test a file to check for the presence of a lock.
   *
   * @public
   * @static
   * @param string $fpath The file to test.
   * @param int $retries Number of times to retry
   * @return boolean true if locked, false otherwise.
   */
  function isLocked($fpath,$retries=0) { 
    $lock = Locker::m_getLockFilePath($fpath);
    if( !file_exists($lock) ) { 
      return false;
    }
    while( $retries > 0 ) { 
      $retries -= 1;
      usleep(rand(1,5) * 5e5);
      if( !file_exists($lock) ) { 
        return false;
      }
    }
    return true;
  } // END: function isLocked($fpath,$retries=0)


  /**
   * Test a file to determine it's been locked.
   *
   * @public
   * @static
   * @param string $fpath The file to test.
   * @return boolean true if lock file is older than reboot.
   */
  function isLockStale($fpath) {
    $lock = Locker::m_getLockFilePath($fpath);
    if( file_exists($lock) ) { 
      $boot = Locker::m_getSystemBootTime();
      if( filemtime($lock) < $boot ) { 
        return true;
      }     
    }
    return false;
  } // END: function isLockStale($fpath)



  // ------------------------------------------------------------------------
  //          Private methods
  // ------------------------------------------------------------------------



  /**
   * Returns the file path of the lock file.
   *
   * @private
   * @static
   * @param {string} $fpath The path to return a lock path for.
   * @return {string} Path to where lock file should exist if exist.
   */
  function m_getLockFilePath($fpath) {
    return sprintf("%s.lock",$fpath);
    /*
    $dname = dirname($fpath);
    $fname = basename($fpath);
    $ix = strrpos($fname,'.');
    if( $ix === false ) {
      return sprintf("%s.lock",$fpath);
    }
    return sprintf("%s/%s.lock",$dname,substr($fname,0,$ix));
    */
  } // END: function m_getLockFilePath($fpath)


  /**
   * Returns the epoch timestamp of when the machine was restarted.
   *
   * @private
   * @static
   * @return {int} Epoch timestamp (seconds since 1970-01-01 UTC) since boot.
   */
  function m_getSystemBootTime() {
    if( file_exists('/proc/uptime') ) { 
      list($up,$idle) = explode(' ',file_get_contents('/proc/uptime'));
      return (time()-$up);
    }
    if( file_exists("/proc/kmsg") ) { 
      return filemtime('/proc/kmsg');
    }
    if( file_exists("c:/Windows/bootstat.dat") ) { 
      return filemtime("c:/Windows/bootstat.dat");
    }
    return 0;
  } // END: function m_getSystemBootTime()


} // END: class Locker()


?>
