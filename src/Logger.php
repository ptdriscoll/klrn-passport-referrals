<?php
namespace PassportReferrals;

/**
 * Simple file-based logging utility.
 *
 * Handles appending messages to a log file with timestamps and 
 * message levels (INFO, ERROR, etc.). Designed for immediate 
 * writes per iteration, so logs are preserved even if execution 
 * stops unexpectedly.
 */
class Logger {
    private $logFile;
    private $maxSize;

    /**
     * @param string $filePath  
     *   Full path to the log file (e.g., ../logs/api.log).
     * @param int $maxSize  
     *   Maximum file size in bytes before truncation (default 1 MB).
     */
    public function __construct($filePath, $maxSize=1048576) {
        $this->logFile = $filePath;
        $this->maxSize = $maxSize;
        $this->ensureLogDirExists();
    }

    /**
     * Writes general log entry with timestamp.
     *
     * @param string $level  
     *   Log level such as 'INFO', 'ERROR', or 'DEBUG'.
     * @param string $message  
     *   Message to write to the log file.
     */
    public function log($level, $message) {
        $this->rotateIfNeeded();
        $timestamp = date('Y-m-d H:i:s');
        $entry = sprintf("[%s] [%s] %s" . PHP_EOL, $timestamp, strtoupper($level), $message);
        file_put_contents($this->logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Writes informational message.
     *
     * @param string $message
     */
    public function info($message) {
        $this->log('INFO', $message);
    }

    /**
     * Writes an error message.
     *
     * @param string $message
     */
    public function error($message) {
        $this->log('ERROR', $message);
    }

    /**
     * Inserts an empty line for easier scanning between batches.
     */
    public function newLine() {
        file_put_contents($this->logFile, PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Logs a summary of execution results.
     *
     * Produces output with timestamps and log levels similar to normal log entries.
     * Includes counts of successful rows, PBS API errors, other errors, peak memory usage, and optional total rows.
     *
     * Example output:
     * [2025-11-10 12:01:19] [INFO] === SUMMARY ===
     * [2025-11-10 12:01:19] [INFO] Total rows: 384
     * [2025-11-10 12:01:19] [INFO] Successful videos: 206
     * [2025-11-10 12:01:19] [INFO] Successful rows: 375     
     * [2025-11-10 12:01:19] [INFO] Failed PBS calls: 169
     * [2025-11-10 12:01:19] [INFO] Failed other calls: 9
     * [2025-11-10 12:01:19] [INFO] Peak memory use: 8 MB
     *
     * @param array $summaryData
     *   Associative array containing summary counts:
     *     - 'totalRows' => int (optional)
     *     - 'successVideos' => int     
     *     - 'successRows' => int
     *     - 'pbsErrors' => int
     *     - 'otherErrors' => int
     */
    public function summary(array $summaryData) {
        $memoryUsed = round(memory_get_peak_usage(true) / 1024 / 1024, 2); //MB

        $this->newLine();
        $this->info('=== SUMMARY ===');
        $this->info('Total rows: ' . ($summaryData['totalRows'] ?? 'not set'));
        $this->info('Successful videos: ' . ($summaryData['successVideos'] ?? 0));
        $this->info('Successful rows: ' . ($summaryData['successRows'] ?? 0));        
        $this->info('Failed PBS calls: ' . ($summaryData['pbsErrors'] ?? 0));
        $this->info('Failed other calls: ' . ($summaryData['otherErrors'] ?? 0));
        $this->info('Peak memory use: ' . $memoryUsed . ' MB');
    }   

    /**
     * Creates the log directory if it doesn’t exist.
     */
    private function ensureLogDirExists() {
        $dir = dirname($this->logFile);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    /**
     * Rotates or truncates log file if it exceeds max size.
     *
     * @param int $maxBackups
     *   Maximum number of log files to keep before deleting oldest file (default 5).
     */
    private function rotateIfNeeded($maxBackups=5) {
        if (!file_exists($this->logFile)) return;

        $size = filesize($this->logFile);
        if ($size <= $this->maxSize) return;

        //rotate current log
        $timestamp = date('Y-m-d_His');
        $backupFile = $this->logFile . '.' . $timestamp;
        rename($this->logFile, $backupFile);

        //start prep for deleting old backups beyond $maxBackups
        $logDir = dirname($this->logFile);
        $baseName = basename($this->logFile);
        $backups = glob($logDir . '/' . $baseName . '.*');

        //sort descending (newest first)
        rsort($backups);

        //keep only $maxBackups, delete the rest
        $toDelete = array_slice($backups, $maxBackups);
        foreach ($toDelete as $file) {
            @unlink($file);
        }
    }
}
