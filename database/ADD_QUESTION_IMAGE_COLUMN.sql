USE navi_shipping;

ALTER TABLE questions
ADD COLUMN image_filename VARCHAR(255) NULL AFTER question_text;
