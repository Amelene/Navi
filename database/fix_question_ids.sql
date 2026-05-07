-- Fix corrupted IDs on questions/question_options and restore AUTO_INCREMENT integrity.
-- Run in phpMyAdmin SQL tab (same database).

START TRANSACTION;

-- 1) Ensure unique temporary IDs for rows with id=0 in questions
SET @max_q_id := (SELECT COALESCE(MAX(id), 0) FROM questions);
SET @next_q_id := @max_q_id;

UPDATE questions
SET id = (@next_q_id := @next_q_id + 1)
WHERE id = 0
ORDER BY question_order, question_text;

-- 2) Ensure unique temporary IDs for rows with id=0 in question_options
SET @max_qo_id := (SELECT COALESCE(MAX(id), 0) FROM question_options);
SET @next_qo_id := @max_qo_id;

UPDATE question_options
SET id = (@next_qo_id := @next_qo_id + 1)
WHERE id = 0
ORDER BY question_id, option_letter;

-- 3) Enforce AUTO_INCREMENT on PK columns
ALTER TABLE questions
  MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;

ALTER TABLE question_options
  MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;

-- 4) Make sure next auto increment starts above max id
SET @next_questions_ai := (SELECT COALESCE(MAX(id), 0) + 1 FROM questions);
SET @sql_q := CONCAT('ALTER TABLE questions AUTO_INCREMENT = ', @next_questions_ai);
PREPARE stmt_q FROM @sql_q;
EXECUTE stmt_q;
DEALLOCATE PREPARE stmt_q;

SET @next_qo_ai := (SELECT COALESCE(MAX(id), 0) + 1 FROM question_options);
SET @sql_qo := CONCAT('ALTER TABLE question_options AUTO_INCREMENT = ', @next_qo_ai);
PREPARE stmt_qo FROM @sql_qo;
EXECUTE stmt_qo;
DEALLOCATE PREPARE stmt_qo;

COMMIT;

-- Verification queries:
-- 1) No zero IDs
-- SELECT id, question_text FROM questions WHERE id = 0;
-- SELECT id, question_id, option_letter, option_text FROM question_options WHERE id = 0 OR question_id = 0;
--
-- 2) Orphan options (must be 0 rows)
-- SELECT qo.id, qo.question_id, qo.option_letter
-- FROM question_options qo
-- LEFT JOIN questions q ON q.id = qo.question_id
-- WHERE q.id IS NULL;
