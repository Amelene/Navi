USE navi_shipping;

-- 1) Ensure standalone ABSTRACT exam category exists
INSERT INTO exam_categories (
    department,
    category,
    vessel_type,
    description,
    total_questions,
    time_limit,
    passing_score,
    status
)
VALUES (
    'ABSTRACT',
    'ABSTRACT',
    'GENERAL',
    'Standalone Abstract Reasoning Test',
    1,
    30,
    70,
    'active'
)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    total_questions = VALUES(total_questions),
    time_limit = VALUES(time_limit),
    passing_score = VALUES(passing_score),
    status = VALUES(status);

-- 2) Get category id
SET @abstract_category_id = (
    SELECT id
    FROM exam_categories
    WHERE UPPER(department) = 'ABSTRACT'
      AND UPPER(category) = 'ABSTRACT'
      AND UPPER(vessel_type) = 'GENERAL'
    LIMIT 1
);

-- 3) Insert question (q1 image path stored in question_text for now)
INSERT INTO questions (
    exam_category_id,
    question_id,
    question_text,
    question_order,
    status
)
VALUES (
    @abstract_category_id,
    'ABSTRACT_Q1',
    'abstract_question/q1.png',
    1,
    'active'
)
ON DUPLICATE KEY UPDATE
    question_text = VALUES(question_text),
    question_order = VALUES(question_order),
    status = VALUES(status);

-- 4) Get question id
SET @abstract_q1_id = (
    SELECT id
    FROM questions
    WHERE exam_category_id = @abstract_category_id
      AND question_id = 'ABSTRACT_Q1'
    LIMIT 1
);

-- 5) Clean previous options for safe re-run
DELETE FROM question_options
WHERE question_id = @abstract_q1_id;

-- 6) Insert placeholder options A-E (replace later with image options)
INSERT INTO question_options (question_id, option_letter, option_text, is_correct) VALUES
(@abstract_q1_id, 'A', 'Option A', 0),
(@abstract_q1_id, 'B', 'Option B', 0),
(@abstract_q1_id, 'C', 'Option C', 1),
(@abstract_q1_id, 'D', 'Option D', 0),
(@abstract_q1_id, 'E', 'Option E', 0);

-- Verify
SELECT ec.id AS category_id, ec.department, ec.category, ec.vessel_type, ec.total_questions
FROM exam_categories ec
WHERE ec.id = @abstract_category_id;

SELECT q.id AS question_db_id, q.question_id, q.question_text, q.question_order, q.status
FROM questions q
WHERE q.id = @abstract_q1_id;

SELECT qo.option_letter, qo.option_text, qo.is_correct
FROM question_options qo
WHERE qo.question_id = @abstract_q1_id
ORDER BY qo.option_letter;
