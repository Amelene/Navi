-- Add cached AI recommendation column to exam_attempts
-- Run once in MySQL

ALTER TABLE exam_attempts
ADD ai_recommendation TEXT NULL;
