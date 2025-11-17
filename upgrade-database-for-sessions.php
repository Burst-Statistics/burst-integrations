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
 * This is a testing script to check what rows will be updated.
 */

    -- Test query
SELECT
    bs1.ID,
    bs1.uid,
    bs1.session_id as old_session_id,
    bs2.group_session_id as new_session_id,
    bs1.time,
    FROM_UNIXTIME(bs1.time) as time_readable,
    bs1.page_url
FROM wp_burst_statistics bs1
INNER JOIN (
    WITH RankedHits AS (
SELECT
            ID,
            uid,
            session_id,
            time,
            LAG(time) OVER (PARTITION BY uid ORDER BY time) as prev_time,
            CASE
                WHEN LAG(time) OVER (PARTITION BY uid ORDER BY time) IS NULL
OR time - LAG(time) OVER (PARTITION BY uid ORDER BY time) > 1800
                THEN 1
                ELSE 0
            END as is_new_session
        FROM wp_burst_statistics
        WHERE time BETWEEN 1234567890 AND 1234567999
AND uid IS NOT NULL
AND uid != ''
    ),
    SessionGroups AS (
SELECT
            ID,
            uid,
            session_id,
            time,
            SUM(is_new_session) OVER (PARTITION BY uid ORDER BY time) as session_group
        FROM RankedHits
    ),
    FirstSessionPerGroup AS (
SELECT
            uid,
            session_group,
            MIN(session_id) as group_session_id
        FROM SessionGroups
        GROUP BY uid, session_group
    )
    SELECT
        sg.ID,
        sg.uid,
        fsg.group_session_id
    FROM SessionGroups sg
    INNER JOIN FirstSessionPerGroup fsg
        ON sg.uid = fsg.uid
AND sg.session_group = fsg.session_group
) bs2 ON bs1.ID = bs2.ID
WHERE bs1.session_id != bs2.group_session_id
ORDER BY bs1.uid, bs1.time;

/**
 * This MySQL query consolidates session IDs for the same user (uid) while respecting the 30-minute session timeout rule:
 *
 * Groups pageviews by user (uid) and orders them chronologically
 * Creates new session groups whenever there's a gap of more than 30 minutes (1800 seconds) between consecutive hits from the same user
 * Assigns all pageviews within each session group the lowest session_id from that group
 * Only processes records within the specified time range
 *
 */

    UPDATE wp_burst_statistics bs1
INNER JOIN (
    WITH RankedHits AS (
SELECT
            ID,
            uid,
            session_id,
            time,
            LAG(time) OVER (PARTITION BY uid ORDER BY time) as prev_time,
            -- Nieuwe sessie als > 30 minuten sinds vorige hit
            CASE
                WHEN LAG(time) OVER (PARTITION BY uid ORDER BY time) IS NULL
OR time - LAG(time) OVER (PARTITION BY uid ORDER BY time) > 1800
                THEN 1
                ELSE 0
            END as is_new_session
        FROM wp_burst_statistics
        WHERE time BETWEEN 1234567890 AND 1234567999
AND uid IS NOT NULL
AND uid != ''
    ),
    SessionGroups AS (
SELECT
            ID,
            uid,
            session_id,
            time,
            -- Cumulatieve som van nieuwe sessies = sessie groep nummer
            SUM(is_new_session) OVER (PARTITION BY uid ORDER BY time) as session_group
        FROM RankedHits
    ),
    FirstSessionPerGroup AS (
SELECT
            uid,
            session_group,
            MIN(session_id) as group_session_id
        FROM SessionGroups
        GROUP BY uid, session_group
    )
    SELECT
        sg.ID,
        fsg.group_session_id
    FROM SessionGroups sg
    INNER JOIN FirstSessionPerGroup fsg
        ON sg.uid = fsg.uid
AND sg.session_group = fsg.session_group
) bs2 ON bs1.ID = bs2.ID
SET bs1.session_id = bs2.group_session_id
WHERE bs1.time BETWEEN 1234567890 AND 1234567999
AND bs1.uid IS NOT NULL
AND bs1.uid != '';;

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