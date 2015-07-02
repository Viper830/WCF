<?php
namespace wcf\system\background;
use wcf\system\background\job\AbstractBackgroundJob;
use wcf\system\exception\LoggedException;
use wcf\system\exception\SystemException;
use wcf\system\SingletonFactory;
use wcf\system\WCF;

/**
 * Manages the background queue.
 *
 * @author	Tim Duesterhus
 * @copyright	2001-2015 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf
 * @subpackage	system.background.job
 * @category	Community Framework
 */
class BackgroundQueueHandler extends SingletonFactory {
	/**
	 * Enqueues the given job(s) for execution in the specified number of
	 * seconds. Defaults to "as soon as possible" (0 seconds).
	 * 
	 * @param	mixed	$jobs	Either an instance of \wcf\system\background\job\AbstractBackgroundJob or an array of these
	 * @param	int	$time	Minimum number of seconds to wait before performing the job.
	 * @see	\wcf\system\background\BackgroundQueueHandler::enqueueAt()
	 */
	public function enqueueIn($jobs, $time = 0) {
		return self::enqueueAt($jobs, TIME_NOW + $time);
	}
	
	/**
	 * Enqueues the given job(s) for execution at the given time.
	 * Note: The time is a minimum time. Depending on the size of
	 * the queue the job can be performed later as well!
	 *
	 * @param	mixed	$jobs	Either an instance of \wcf\system\background\job\AbstractBackgroundJob or an array of these
	 * @param	int	$time	Earliest time to consider the job for execution.
	 */
	public function enqueueAt($jobs, $time) {
		if ($time < TIME_NOW) {
			throw new SystemException("You may not schedule a job in the past (".$time." is smaller than the current timestamp ".TIME_NOW.").");
		}
		if (!is_array($jobs)) $jobs = [ $jobs ];
		foreach ($jobs as $job) {
			if (!($job instanceof AbstractBackgroundJob)) {
				throw new SystemException('$jobs contains an item that does not extend \wcf\system\background\job\AbstractBackgroundJob.');
			}
		}
		
		WCF::getDB()->beginTransaction();
		$sql = "INSERT INTO	wcf".WCF_N."_background_job
					(job, time)
			VALUES		(?, ?)";
		$statement = WCF::getDB()->prepareStatement($sql);
		foreach ($jobs as $job) {
			$statement->execute([
				serialize($job),
				$time
			]);
		}
		WCF::getDB()->commitTransaction();
	}
	
	/**
	 * Immediatly performs the given job.
	 * This method automatically handles requeuing in case of failure.
	 * 
	 * This method is used internally by performNextJob(), but it can
	 * be useful if you wish immediate execution of a certain job, but
	 * don't want to miss the automated error handling mechanism of the
	 * queue.
	 * 
	 * @param	\wcf\system\background\job\AbstractBackgroundJob	$job	The job to perform.
	 */
	public function performJob(AbstractBackgroundJob $job) {
		try {
			$job->perform();
		}
		catch (\Exception $e) {
			// gotta catch 'em all
			$job->fail();
			
			if ($job->getFailures() <= $job::MAX_FAILURES) {
				$this->enqueueIn($job, $job->retryAfter());
			}
			else {
				// job failed too often: log
				if ($e instanceof LoggedException) $e->getExceptionID();
			}
		}
	}
	
	/**
	 * Performs the (single) job that is due next.
	 * This method automatically handles requeuing in case of failure.
	 */
	public function performNextJob() {
		WCF::getDB()->beginTransaction();
		$commited = false;
		try {
			$sql = "SELECT		jobID, job
				FROM		wcf".WCF_N."_background_job
				WHERE		status = ?
					AND	time <= ?
				ORDER BY	time ASC, jobID ASC
				FOR UPDATE";
			$statement = WCF::getDB()->prepareStatement($sql, 1);
			$statement->execute([
				'ready',
				TIME_NOW
			]);
			$row = $statement->fetchSingleRow();
			if (!$row) {
				// nothing to do here
				return;
			}
			
			// lock job
			$sql = "UPDATE	wcf".WCF_N."_background_job
				SET	status = ?,
					time = ?
				WHERE		jobID = ?
					AND	status = ?";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute([
				'processing',
				TIME_NOW,
				$row['jobID'],
				'ready'
			]);
			if ($statement->getAffectedRows() != 1) {
				// somebody stole the job
				// this cannot happen unless MySQL violates it's contract to lock the row
				// -> silently ignore, there will be plenty of other oppurtunities to perform a job
				return;
			}
			WCF::getDB()->commitTransaction();
			$commited = true;
		}
		finally {
			if (!$commited) WCF::getDB()->rollbackTransaction();
		}

		$job = null;
		try {
			// no shut up operator, exception will be caught
			$job = unserialize($row['job']);
			if ($job) {
				$this->performJob($job);
			}
		}
		catch (\Exception $e) {
			// job is completely broken: log
			if ($e instanceof LoggedException) $e->getExceptionID();
		}
		finally {
			// remove entry of processed job
			$sql = "DELETE FROM	wcf".WCF_N."_background_job
				WHERE		jobID = ?";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute([ $row['jobID'] ]);
		}
	}
}