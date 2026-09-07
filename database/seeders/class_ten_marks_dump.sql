-- ==========================================================
-- MACS School - Class Ten 2nd Term Exam 2026 Marks Dump
-- Generated: 2026-09-07 04:42:33
-- Total Marks Records: 300
-- ==========================================================

-- 1. Ensure 2nd Term Exam exists
INSERT INTO `exams` (`id`, `name`, `session_year_id`, `status`, `created_at`, `updated_at`) 
VALUES (2, "2nd Term Exam 2026", 1, 'upcoming', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 2. Ensure Student Roll 25 exists
INSERT INTO `students` (`id`, `student_identity`, `student_name`, `name_in_bangla`, `branch_id`, `class_id`, `section_id`, `shift_id`, `session_year_id`, `roll_number`, `dob`, `gender`, `religion`, `photo`, `father_name`, `father_mobile`, `mother_name`, `mother_mobile`, `guardian_name`, `guardian_mobile`, `present_village`, `present_post_office`, `present_district`, `present_post_code`, `present_division`, `permanent_village`, `permanent_post_office`, `permanent_district`, `permanent_post_code`, `permanent_division`, `sms_status`, `user_id`, `created_at`, `updated_at`) 
VALUES (574, '26051200025', "Abdur Rahman", "\u0986\u09ac\u09cd\u09a6\u09c1\u09b0 \u09b0\u09b9\u09ae\u09be\u09a8", 3, 12, 1, 5, 1, '25', '2010-01-01', 'Male', 'Islam', 'img/boy.png', 'Father', '01708118159', 'Mother', '01708118159', 'Guardian', '01708118159', 'Jalalpur', 'Jalalpur', 'Pabna', '6600', 'Rajshahi', 'Jalalpur', 'Jalalpur', 'Pabna', '6600', 'Rajshahi', 'Active', 1, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `student_name` = VALUES(`student_name`);

-- 3. Exam Schedules for Class Ten
INSERT INTO `exam_schedules` (`branch_id`, `class_id`, `subject_id`, `exam_id`, `full_marks`, `pass_marks`, `ct_marks`, `written_marks`, `mcq_marks`, `created_at`, `updated_at`) 
VALUES (3, 12, 98, 2, 100.00, 33.00, 0.00, 70.00, 30.00, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `full_marks` = VALUES(`full_marks`), `pass_marks` = VALUES(`pass_marks`);
INSERT INTO `exam_schedules` (`branch_id`, `class_id`, `subject_id`, `exam_id`, `full_marks`, `pass_marks`, `ct_marks`, `written_marks`, `mcq_marks`, `created_at`, `updated_at`) 
VALUES (3, 12, 99, 2, 100.00, 33.00, 0.00, 70.00, 30.00, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `full_marks` = VALUES(`full_marks`), `pass_marks` = VALUES(`pass_marks`);
INSERT INTO `exam_schedules` (`branch_id`, `class_id`, `subject_id`, `exam_id`, `full_marks`, `pass_marks`, `ct_marks`, `written_marks`, `mcq_marks`, `created_at`, `updated_at`) 
VALUES (3, 12, 100, 2, 100.00, 33.00, 0.00, 100.00, 0.00, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `full_marks` = VALUES(`full_marks`), `pass_marks` = VALUES(`pass_marks`);
INSERT INTO `exam_schedules` (`branch_id`, `class_id`, `subject_id`, `exam_id`, `full_marks`, `pass_marks`, `ct_marks`, `written_marks`, `mcq_marks`, `created_at`, `updated_at`) 
VALUES (3, 12, 101, 2, 100.00, 33.00, 0.00, 100.00, 0.00, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `full_marks` = VALUES(`full_marks`), `pass_marks` = VALUES(`pass_marks`);
INSERT INTO `exam_schedules` (`branch_id`, `class_id`, `subject_id`, `exam_id`, `full_marks`, `pass_marks`, `ct_marks`, `written_marks`, `mcq_marks`, `created_at`, `updated_at`) 
VALUES (3, 12, 102, 2, 100.00, 33.00, 0.00, 70.00, 30.00, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `full_marks` = VALUES(`full_marks`), `pass_marks` = VALUES(`pass_marks`);
INSERT INTO `exam_schedules` (`branch_id`, `class_id`, `subject_id`, `exam_id`, `full_marks`, `pass_marks`, `ct_marks`, `written_marks`, `mcq_marks`, `created_at`, `updated_at`) 
VALUES (3, 12, 103, 2, 100.00, 33.00, 0.00, 70.00, 30.00, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `full_marks` = VALUES(`full_marks`), `pass_marks` = VALUES(`pass_marks`);
INSERT INTO `exam_schedules` (`branch_id`, `class_id`, `subject_id`, `exam_id`, `full_marks`, `pass_marks`, `ct_marks`, `written_marks`, `mcq_marks`, `created_at`, `updated_at`) 
VALUES (3, 12, 104, 2, 100.00, 33.00, 0.00, 70.00, 30.00, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `full_marks` = VALUES(`full_marks`), `pass_marks` = VALUES(`pass_marks`);
INSERT INTO `exam_schedules` (`branch_id`, `class_id`, `subject_id`, `exam_id`, `full_marks`, `pass_marks`, `ct_marks`, `written_marks`, `mcq_marks`, `created_at`, `updated_at`) 
VALUES (3, 12, 107, 2, 100.00, 33.00, 25.00, 50.00, 25.00, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `full_marks` = VALUES(`full_marks`), `pass_marks` = VALUES(`pass_marks`);
INSERT INTO `exam_schedules` (`branch_id`, `class_id`, `subject_id`, `exam_id`, `full_marks`, `pass_marks`, `ct_marks`, `written_marks`, `mcq_marks`, `created_at`, `updated_at`) 
VALUES (3, 12, 106, 2, 100.00, 33.00, 25.00, 50.00, 25.00, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `full_marks` = VALUES(`full_marks`), `pass_marks` = VALUES(`pass_marks`);
INSERT INTO `exam_schedules` (`branch_id`, `class_id`, `subject_id`, `exam_id`, `full_marks`, `pass_marks`, `ct_marks`, `written_marks`, `mcq_marks`, `created_at`, `updated_at`) 
VALUES (3, 12, 105, 2, 100.00, 33.00, 25.00, 50.00, 25.00, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `full_marks` = VALUES(`full_marks`), `pass_marks` = VALUES(`pass_marks`);
INSERT INTO `exam_schedules` (`branch_id`, `class_id`, `subject_id`, `exam_id`, `full_marks`, `pass_marks`, `ct_marks`, `written_marks`, `mcq_marks`, `created_at`, `updated_at`) 
VALUES (3, 12, 108, 2, 100.00, 33.00, 25.00, 50.00, 25.00, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `full_marks` = VALUES(`full_marks`), `pass_marks` = VALUES(`pass_marks`);
INSERT INTO `exam_schedules` (`branch_id`, `class_id`, `subject_id`, `exam_id`, `full_marks`, `pass_marks`, `ct_marks`, `written_marks`, `mcq_marks`, `created_at`, `updated_at`) 
VALUES (3, 12, 109, 2, 50.00, 17.00, 25.00, 0.00, 25.00, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `full_marks` = VALUES(`full_marks`), `pass_marks` = VALUES(`pass_marks`);

-- 4. Marks for Class Ten (25 Students x 12 Subjects = 300 Records)
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 545, 0.00, 25.00, 44.00, 69.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 545, 0.00, 22.00, 44.00, 66.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 545, 0.00, 0.00, 85.00, 85.00, 'A+', 5.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 545, 0.00, 0.00, 68.00, 68.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 545, 0.00, 13.00, 48.00, 61.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 545, 0.00, 24.00, 47.00, 71.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 545, 0.00, 23.00, 42.00, 65.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 545, 25.00, 17.00, 25.00, 67.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 545, 25.00, 12.00, 31.00, 68.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 545, 25.00, 14.00, 37.00, 76.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 545, 25.00, 17.00, 42.00, 84.00, 'A+', 5.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 545, 20.00, 22.00, 0.00, 42.00, 'A+', 5.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 548, 0.00, 23.00, 38.00, 61.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 548, 0.00, 14.00, 51.00, 65.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 548, 0.00, 0.00, 87.00, 87.00, 'A+', 5.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 548, 0.00, 0.00, 73.00, 73.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 548, 0.00, 11.00, 32.00, 43.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 548, 0.00, 23.00, 49.00, 72.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 548, 0.00, 21.00, 45.00, 66.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 548, 25.00, 16.00, 31.00, 72.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 548, 25.00, 12.00, 33.00, 70.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 548, 25.00, 17.00, 44.00, 86.00, 'A+', 5.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 548, 25.00, 12.00, 29.00, 66.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 548, 20.00, 21.00, 0.00, 41.00, 'A+', 5.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 547, 0.00, 23.00, 17.00, 40.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 547, 0.00, 12.00, 52.00, 64.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 547, 0.00, 0.00, 85.00, 85.00, 'A+', 5.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 547, 0.00, 0.00, 52.00, 52.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 547, 0.00, 13.00, 32.00, 45.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 547, 0.00, 20.00, 54.00, 74.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 547, 0.00, 18.00, 48.00, 66.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 547, 25.00, 9.00, 18.00, 52.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 547, 25.00, 9.00, 20.00, 54.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 547, 25.00, 17.00, 37.00, 79.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 547, 25.00, 9.00, 31.00, 65.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 547, 20.00, 18.00, 0.00, 38.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 554, 0.00, 20.00, 33.00, 53.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 554, 0.00, 10.00, 46.00, 56.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 554, 0.00, 0.00, 59.00, 59.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 554, 0.00, 0.00, 35.00, 35.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 554, 0.00, 18.00, 34.00, 52.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 554, 0.00, 16.00, 39.00, 55.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 554, 0.00, 16.00, 30.00, 46.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 554, 25.00, 15.00, 18.00, 58.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 554, 25.00, 8.00, 24.00, 57.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 554, 20.00, 12.00, 36.00, 68.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 554, 25.00, 18.00, 24.00, 67.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 554, 20.00, 17.00, 0.00, 37.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 550, 0.00, 20.00, 56.00, 76.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 550, 0.00, 11.00, 37.00, 48.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 550, 0.00, 0.00, 82.00, 82.00, 'A+', 5.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 550, 0.00, 0.00, 38.00, 38.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 550, 0.00, 18.00, 29.00, 47.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 550, 0.00, 14.00, 47.00, 61.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 550, 0.00, 18.00, 36.00, 54.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 550, 0.00, 12.00, 10.00, 22.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 550, 25.00, 15.00, 22.00, 62.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 550, 25.00, 13.00, 37.00, 75.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 550, 25.00, 21.00, 35.00, 81.00, 'A+', 5.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 550, 20.00, 16.00, 0.00, 36.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 549, 0.00, 20.00, 39.00, 59.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 549, 0.00, 12.00, 35.00, 47.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 549, 0.00, 0.00, 80.00, 80.00, 'A+', 5.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 549, 0.00, 0.00, 60.00, 60.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 549, 0.00, 13.00, 28.00, 41.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 549, 0.00, 18.00, 27.00, 45.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 549, 0.00, 18.00, 34.00, 52.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 549, 25.00, 11.00, 19.00, 55.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 549, 0.00, 11.00, 15.00, 26.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 549, 25.00, 14.00, 28.00, 67.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 549, 25.00, 13.00, 35.00, 73.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 549, 15.00, 21.00, 0.00, 36.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 559, 0.00, 21.00, 60.00, 81.00, 'A+', 5.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 559, 0.00, 13.00, 49.00, 62.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 559, 0.00, 0.00, 65.00, 65.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 559, 0.00, 0.00, 39.00, 39.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 559, 0.00, 15.00, 24.00, 39.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 559, 0.00, 17.00, 43.00, 60.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 559, 0.00, 19.00, 34.00, 53.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 559, 0.00, 11.00, 10.00, 21.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 559, 0.00, 8.00, 15.00, 23.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 559, 25.00, 11.00, 30.00, 66.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 559, 25.00, 16.00, 36.00, 77.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 559, 20.00, 12.00, 0.00, 32.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 546, 0.00, 22.00, 53.00, 75.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 546, 0.00, 12.00, 51.00, 63.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 546, 0.00, 0.00, 58.00, 58.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 546, 0.00, 0.00, 28.00, 28.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 546, 0.00, 14.00, 40.00, 54.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 546, 0.00, 16.00, 46.00, 62.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 546, 0.00, 21.00, 45.00, 66.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 546, 0.00, 9.00, 8.00, 17.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 546, 25.00, 14.00, 21.00, 60.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 546, 25.00, 13.00, 40.00, 78.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 546, 0.00, 4.00, 13.00, 17.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 546, 20.00, 15.00, 0.00, 35.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 555, 0.00, 25.00, 59.00, 84.00, 'A+', 5.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 555, 0.00, 14.00, 52.00, 66.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 555, 0.00, 0.00, 59.00, 59.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 555, 0.00, 0.00, 36.00, 36.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 555, 0.00, 13.00, 30.00, 43.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 555, 0.00, 16.00, 40.00, 56.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 555, 0.00, 17.00, 28.00, 45.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 555, 0.00, 8.00, 6.00, 14.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 555, 25.00, 11.00, 18.00, 54.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 555, 25.00, 16.00, 33.00, 74.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 555, 0.00, 8.00, 8.00, 16.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 555, 20.00, 17.00, 0.00, 37.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 561, 0.00, 18.00, 52.00, 70.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 561, 0.00, 10.00, 36.00, 46.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 561, 0.00, 0.00, 43.00, 43.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 561, 0.00, 0.00, 30.00, 30.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 561, 0.00, 19.00, 19.00, 38.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 561, 0.00, 14.00, 41.00, 55.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 561, 0.00, 11.00, 30.00, 41.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 561, 0.00, 12.00, 32.00, 44.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 561, 0.00, 12.00, 17.00, 29.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 561, 0.00, 17.00, 34.00, 51.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 561, 25.00, 16.00, 30.00, 71.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 561, 20.00, 14.00, 0.00, 34.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 574, 0.00, 21.00, 43.00, 64.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 574, 0.00, 11.00, 45.00, 56.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 574, 0.00, 0.00, 55.00, 55.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 574, 0.00, 0.00, 33.00, 33.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 574, 0.00, 7.00, 33.00, 40.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 574, 0.00, 20.00, 36.00, 56.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 574, 0.00, 15.00, 32.00, 47.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 574, 25.00, 14.00, 23.00, 62.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 574, 0.00, 5.00, 8.00, 13.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 574, 0.00, 12.00, 19.00, 31.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 574, 0.00, 11.00, 14.00, 25.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 574, 20.00, 17.00, 0.00, 37.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 553, 0.00, 20.00, 41.00, 61.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 553, 0.00, 14.00, 38.00, 52.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 553, 0.00, 0.00, 52.00, 52.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 553, 0.00, 0.00, 20.00, 20.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 553, 0.00, 13.00, 19.00, 32.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 553, 0.00, 9.00, 37.00, 46.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 553, 0.00, 10.00, 20.00, 30.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 553, 0.00, 8.00, 25.00, 33.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 553, 0.00, 12.00, 15.00, 27.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 553, 0.00, 16.00, 26.00, 42.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 553, 25.00, 21.00, 23.00, 69.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 553, 20.00, 15.00, 0.00, 35.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 560, 0.00, 21.00, 37.00, 58.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 560, 0.00, 13.00, 36.00, 49.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 560, 0.00, 0.00, 46.00, 46.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 560, 0.00, 0.00, 24.00, 24.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 560, 0.00, 10.00, 23.00, 33.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 560, 0.00, 18.00, 27.00, 45.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 560, 0.00, 15.00, 24.00, 39.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 560, 0.00, 14.00, 12.00, 26.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 560, 0.00, 7.00, 21.00, 28.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 560, 25.00, 13.00, 25.00, 63.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 560, 0.00, 18.00, 15.00, 33.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 560, 20.00, 20.00, 0.00, 40.00, 'A+', 5.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 556, 0.00, 17.00, 36.00, 53.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 556, 0.00, 11.00, 39.00, 50.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 556, 0.00, 0.00, 31.00, 31.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 556, 0.00, 0.00, 25.00, 25.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 556, 0.00, 11.00, 12.00, 23.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 556, 0.00, 14.00, 23.00, 37.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 556, 0.00, 16.00, 23.00, 39.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 556, 0.00, 11.00, 8.00, 19.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 556, 0.00, 14.00, 5.00, 19.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 556, 25.00, 14.00, 31.00, 70.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 556, 25.00, 14.00, 25.00, 64.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 556, 20.00, 20.00, 0.00, 40.00, 'A+', 5.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 562, 0.00, 23.00, 26.00, 49.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 562, 0.00, 10.00, 32.00, 42.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 562, 0.00, 0.00, 33.00, 33.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 562, 0.00, 0.00, 21.00, 21.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 562, 0.00, 7.00, 14.00, 21.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 562, 0.00, 16.00, 28.00, 44.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 562, 0.00, 11.00, 20.00, 31.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 562, 0.00, 13.00, 26.00, 39.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 562, 0.00, 14.00, 14.00, 28.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 562, 0.00, 19.00, 26.00, 45.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 562, 25.00, 17.00, 27.00, 69.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 562, 20.00, 14.00, 0.00, 34.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 557, 0.00, 17.00, 26.00, 43.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 557, 0.00, 13.00, 38.00, 51.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 557, 0.00, 0.00, 34.00, 34.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 557, 0.00, 0.00, 28.00, 28.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 557, 0.00, 9.00, 20.00, 29.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 557, 0.00, 19.00, 19.00, 38.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 557, 0.00, 17.00, 24.00, 41.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 557, 0.00, 9.00, 9.00, 18.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 557, 0.00, 14.00, 11.00, 25.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 557, 25.00, 13.00, 22.00, 60.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 557, 25.00, 17.00, 21.00, 63.00, 'A-', 3.50, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 557, 15.00, 9.00, 0.00, 24.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 558, 0.00, 21.00, 25.00, 46.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 558, 0.00, 13.00, 38.00, 51.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 558, 0.00, 0.00, 49.00, 49.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 558, 0.00, 0.00, 28.00, 28.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 558, 0.00, 18.00, 20.00, 38.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 558, 0.00, 12.00, 23.00, 35.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 558, 0.00, 17.00, 24.00, 41.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 558, 0.00, 15.00, 11.00, 26.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 558, 0.00, 9.00, 4.00, 13.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 558, 25.00, 10.00, 18.00, 53.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 558, 0.00, 7.00, 9.00, 16.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 558, 20.00, 17.00, 0.00, 37.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 551, 0.00, 21.00, 29.00, 50.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 551, 0.00, 10.00, 34.00, 44.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 551, 0.00, 0.00, 52.00, 52.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 551, 0.00, 0.00, 57.00, 57.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 551, 0.00, 10.00, 9.00, 19.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 551, 0.00, 7.00, 25.00, 32.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 551, 0.00, 13.00, 20.00, 33.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 551, 0.00, 10.00, 11.00, 21.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 551, 0.00, 9.00, 11.00, 20.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 551, 20.00, 12.00, 20.00, 52.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 551, 0.00, 12.00, 4.00, 16.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 551, 20.00, 16.00, 0.00, 36.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 552, 0.00, 21.00, 20.00, 41.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 552, 0.00, 10.00, 38.00, 48.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 552, 0.00, 0.00, 54.00, 54.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 552, 0.00, 0.00, 24.00, 24.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 552, 0.00, 12.00, 11.00, 23.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 552, 0.00, 14.00, 22.00, 36.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 552, 0.00, 0.00, 0.00, 0.00, 'F', 0.00, 1, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 552, 0.00, 12.00, 12.00, 24.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 552, 25.00, 8.00, 17.00, 50.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 552, 20.00, 9.00, 18.00, 47.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 552, 0.00, 6.00, 8.00, 14.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 552, 20.00, 18.00, 0.00, 38.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 564, 0.00, 28.00, 24.00, 52.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 564, 0.00, 10.00, 36.00, 46.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 564, 0.00, 0.00, 29.00, 29.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 564, 0.00, 0.00, 16.00, 16.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 564, 0.00, 8.00, 14.00, 22.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 564, 0.00, 6.00, 18.00, 24.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 564, 0.00, 14.00, 14.00, 28.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 564, 0.00, 11.00, 23.00, 34.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 564, 0.00, 9.00, 14.00, 23.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 564, 0.00, 12.00, 23.00, 35.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 564, 0.00, 13.00, 14.00, 27.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 564, 20.00, 15.00, 0.00, 35.00, 'A', 4.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 563, 0.00, 19.00, 24.00, 43.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 563, 0.00, 11.00, 29.00, 40.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 563, 0.00, 0.00, 29.00, 29.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 563, 0.00, 0.00, 7.00, 7.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 563, 0.00, 12.00, 25.00, 37.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 563, 0.00, 12.00, 5.00, 17.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 563, 0.00, 17.00, 14.00, 31.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 563, 0.00, 14.00, 4.00, 18.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 563, 0.00, 7.00, 15.00, 22.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 563, 15.00, 15.00, 15.00, 45.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 563, 0.00, 7.00, 7.00, 14.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 563, 15.00, 14.00, 0.00, 29.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 566, 0.00, 22.00, 19.00, 41.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 566, 0.00, 14.00, 40.00, 54.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 566, 0.00, 0.00, 23.00, 23.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 566, 0.00, 0.00, 9.00, 9.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 566, 0.00, 14.00, 4.00, 18.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 566, 0.00, 20.00, 6.00, 26.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 566, 0.00, 9.00, 6.00, 15.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 566, 0.00, 14.00, 9.00, 23.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 566, 0.00, 8.00, 6.00, 14.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 566, 0.00, 14.00, 8.00, 22.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 566, 0.00, 16.00, 16.00, 32.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 566, 15.00, 13.00, 0.00, 28.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 565, 0.00, 21.00, 17.00, 38.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 565, 0.00, 11.00, 31.00, 42.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 565, 0.00, 0.00, 24.00, 24.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 565, 0.00, 0.00, 16.00, 16.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 565, 0.00, 10.00, 4.00, 14.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 565, 0.00, 10.00, 12.00, 22.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 565, 0.00, 9.00, 5.00, 14.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 565, 0.00, 10.00, 14.00, 24.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 565, 0.00, 9.00, 4.00, 13.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 565, 0.00, 13.00, 12.00, 25.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 565, 0.00, 10.00, 11.00, 21.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 565, 15.00, 11.00, 0.00, 26.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 568, 0.00, 17.00, 23.00, 40.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 568, 0.00, 8.00, 0.00, 8.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 568, 0.00, 0.00, 24.00, 24.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 568, 0.00, 0.00, 30.00, 30.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 568, 0.00, 8.00, 8.00, 16.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 568, 0.00, 9.00, 15.00, 24.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 568, 0.00, 11.00, 17.00, 28.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 568, 0.00, 14.00, 16.00, 30.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 568, 0.00, 12.00, 3.00, 15.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 568, 0.00, 13.00, 14.00, 27.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 568, 0.00, 0.00, 0.00, 0.00, 'F', 0.00, 1, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 568, 15.00, 9.00, 0.00, 24.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 98, 567, 0.00, 0.00, 0.00, 0.00, 'F', 0.00, 1, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 99, 567, 0.00, 14.00, 29.00, 43.00, 'C', 2.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 100, 567, 0.00, 0.00, 34.00, 34.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 101, 567, 0.00, 0.00, 9.00, 9.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 102, 567, 0.00, 0.00, 0.00, 0.00, 'F', 0.00, 1, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 103, 567, 0.00, 12.00, 21.00, 33.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 104, 567, 0.00, 5.00, 17.00, 22.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 107, 567, 0.00, 19.00, 19.00, 38.00, 'D', 1.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 106, 567, 0.00, 0.00, 0.00, 0.00, 'F', 0.00, 1, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 105, 567, 0.00, 12.00, 18.00, 30.00, 'F', 0.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 108, 567, 25.00, 12.00, 18.00, 55.00, 'B', 3.00, 0, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
INSERT INTO `marks` (`session_year_id`, `branch_id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `student_id`, `ct_mark`, `mcq_mark`, `written_mark`, `total_mark`, `letter_grade`, `grade_point`, `is_absent`, `created_at`, `updated_at`) 
VALUES (1, 3, 2, 12, 1, 109, 567, 0.00, 0.00, 0.00, 0.00, 'F', 0.00, 1, NOW(), NOW()) 
ON DUPLICATE KEY UPDATE `ct_mark` = VALUES(`ct_mark`), `mcq_mark` = VALUES(`mcq_mark`), `written_mark` = VALUES(`written_mark`), `total_mark` = VALUES(`total_mark`), `letter_grade` = VALUES(`letter_grade`), `grade_point` = VALUES(`grade_point`), `is_absent` = VALUES(`is_absent`);
