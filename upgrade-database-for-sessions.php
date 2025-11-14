<?php
/**
 * Replace 1234567890 and 1234567999 with the actual start and end timestamps for the period you want to update.
 * This script gets the first session_id for each uid and updates all records for that uid in the specified time range with that session_id.
 *
 * The second part of the script identifies and deletes orphaned sessions from the wp_burst_sessions table.
 *
 * Each script has a test version to check how many rows will be affected before running the actual update or delete operation.
 */

/**
 * This is a testing script to check how many rows will be updated.
 */

    -- Test query
    SELECT COUNT(*) as rows_to_update
    FROM wp_burst_statistics
    WHERE time BETWEEN 1234567890 AND 1234567999
    AND uid IS NOT NULL
    AND uid != '';

/**
 * Script to update the session_id in wp_burst_statistics table based on the first session_id for each unique uid within the specified time range.
 */

    UPDATE wp_burst_statistics bs1
INNER JOIN (
    SELECT
        uid,
        MIN(session_id) as first_session_id
    FROM wp_burst_statistics
    WHERE time BETWEEN 1234567890 AND 1234567999
AND uid IS NOT NULL
AND uid != ''
    GROUP BY uid
) bs2 ON bs1.uid = bs2.uid
SET bs1.session_id = bs2.first_session_id
WHERE bs1.time BETWEEN 1234567890 AND 1234567999
AND bs1.uid IS NOT NULL
AND bs1.uid != '';

    /**
     * Test script to test how many orphaned sessions are in the wp_burst_sessions table after running the update script.
     */

    -- Test query
    SELECT COUNT(*) as orphaned_sessions
    FROM wp_burst_sessions ws
    LEFT JOIN wp_burst_statistics bs ON ws.ID = bs.session_id
    WHERE bs.session_id IS NULL;

    /**
     *  Script to delete orphaned sessions from the wp_burst_sessions table.
     */
    DELETE ws
    FROM wp_burst_sessions ws
    LEFT JOIN wp_burst_statistics bs ON ws.ID = bs.session_id
    WHERE bs.session_id IS NULL;