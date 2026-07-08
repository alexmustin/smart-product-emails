<?php
/**
 * Persistent server-side state for a Woo Custom Emails import run.
 *
 * Progress is never trusted to the browser alone: it's written to a single
 * option after every batch so the wizard can detect an interrupted run
 * (dropped connection, closed tab, PHP timeout) and offer to resume, without
 * needing an explicit cursor - resuming is just "re-scan and keep going"
 * since every completed item is independently idempotent.
 *
 * @package SmartProductEmails
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persistent server-side state for a Woo Custom Emails import run.
 */
class SmartProductEmails_Import_Job {

	const OPTION_NAME = 'smartproductemails_import_job';

	/**
	 * Get the current job record, if any.
	 *
	 * @return array|null
	 */
	public function get() {
		$job = get_option( self::OPTION_NAME );

		return is_array( $job ) ? $job : null;
	}

	/**
	 * Start a new job, replacing any previous record.
	 *
	 * @param string $conflict_strategy 'overwrite' or 'skip'.
	 * @param int    $total_count Total number of product/status items to process.
	 * @return array The new job record.
	 */
	public function start( $conflict_strategy, $total_count ) {
		$now = current_time( 'mysql' );

		$job = array(
			'job_id'            => wp_generate_uuid4(),
			'status'            => 'running',
			'conflict_strategy' => in_array( $conflict_strategy, array( 'overwrite', 'skip' ), true ) ? $conflict_strategy : 'overwrite',
			'started_at'        => $now,
			'updated_at'        => $now,
			'processed_count'   => 0,
			'total_count'       => (int) $total_count,
			'totals'            => array(
				'created'  => 0,
				'updated'  => 0,
				'skipped'  => 0,
				'errors'   => 0,
				'conflict' => 0,
			),
		);

		update_option( self::OPTION_NAME, $job, false );

		return $job;
	}

	/**
	 * Merge a batch's results into the job record and refresh the heartbeat.
	 *
	 * @param array $batch_totals Partial totals to add: created, updated, skipped, errors, conflict.
	 * @param int   $items_processed How many items this batch processed.
	 * @return array The updated job record.
	 */
	public function record_batch( $batch_totals, $items_processed ) {
		$job = $this->get();
		if ( ! $job ) {
			return array();
		}

		foreach ( array( 'created', 'updated', 'skipped', 'errors', 'conflict' ) as $key ) {
			if ( isset( $batch_totals[ $key ] ) ) {
				$job['totals'][ $key ] += (int) $batch_totals[ $key ];
			}
		}

		$job['processed_count'] += (int) $items_processed;
		$job['updated_at']       = current_time( 'mysql' );

		update_option( self::OPTION_NAME, $job, false );

		return $job;
	}

	/**
	 * Mark the job complete.
	 *
	 * @return array The updated job record.
	 */
	public function complete() {
		return $this->set_status( 'completed' );
	}

	/**
	 * Mark the job cancelled (user stopped it after the in-flight batch).
	 *
	 * @return array The updated job record.
	 */
	public function cancel() {
		return $this->set_status( 'cancelled' );
	}

	/**
	 * Remove the job record entirely (e.g. after the user starts a fresh
	 * scan from the Report step).
	 */
	public function clear() {
		delete_option( self::OPTION_NAME );
	}

	/**
	 * Whether a job is currently running and still "fresh" (heartbeat within
	 * the stale-job timeout).
	 *
	 * @return bool
	 */
	public function is_active() {
		$job = $this->get();

		return $job && 'running' === $job['status'] && ! $this->is_stale( $job );
	}

	/**
	 * Whether a job exists, is marked running, but hasn't been touched
	 * recently - i.e. abandoned by a dropped connection or closed tab.
	 *
	 * @return bool
	 */
	public function is_abandoned() {
		$job = $this->get();

		return $job && 'running' === $job['status'] && $this->is_stale( $job );
	}

	/**
	 * Whether a job record's heartbeat is older than the stale-job timeout.
	 *
	 * @param array $job Job record.
	 * @return bool
	 */
	private function is_stale( $job ) {
		$timeout = apply_filters( 'smartproductemails_import_stale_job_timeout', 300 );
		$age     = time() - strtotime( $job['updated_at'] );

		return $age > $timeout;
	}

	/**
	 * Set the job's status and refresh its heartbeat.
	 *
	 * @param string $status New status value.
	 * @return array The updated job record.
	 */
	private function set_status( $status ) {
		$job = $this->get();
		if ( ! $job ) {
			return array();
		}

		$job['status']     = $status;
		$job['updated_at'] = current_time( 'mysql' );

		update_option( self::OPTION_NAME, $job, false );

		return $job;
	}
}
