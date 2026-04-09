<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Оценка качества курса';
$string['privacy:metadata'] = 'Плагин сохраняет отчёты об оценке качества курса, запускаемые пользователями.';

$string['privacy:metadata:local_coursequality_report'] = 'Сохранённые снимки оценки качества курса.';
$string['privacy:metadata:local_coursequality_report:courseid'] = 'Оцениваемый курс.';
$string['privacy:metadata:local_coursequality_report:userid'] = 'Пользователь, сформировавший отчёт.';
$string['privacy:metadata:local_coursequality_report:timecreated'] = 'Время формирования отчёта.';
$string['privacy:metadata:local_coursequality_report:totalscore'] = 'Сводный балл.';
$string['privacy:metadata:local_coursequality_report:metrics'] = 'JSON с подбаллами и счётчиками.';

$string['admin_menu'] = 'Оценка качества курсов';
$string['admin_category'] = 'Оценка качества курсов';
$string['nav_course_quality'] = 'Оценить качество курса';

$string['coursequality:evaluate'] = 'Запускать оценку качества курса';
$string['coursequality:view'] = 'Просматривать отчёты о качестве курса';
$string['coursequality:manage'] = 'Управлять оценкой качества курсов (сайт)';

$string['page_title_course'] = 'Качество курса: {$a}';
$string['page_title_report'] = 'Отчёт о качестве';
$string['page_title_admin'] = 'Оценка качества курсов';

$string['reports_heading'] = 'Сохранённые отчёты';
$string['noreports'] = 'Отчётов пока нет. Запустите оценку, чтобы сформировать первый отчёт.';
$string['report_date'] = 'Сформирован';
$string['report_score'] = 'Балл';
$string['report_view'] = 'Открыть';
$string['report_delete'] = 'Удалить';

$string['evaluate_button'] = 'Оценить курс';
$string['evaluating'] = 'Выполняется оценка…';
$string['evaluate_done'] = 'Отчёт сформирован.';
$string['evaluate_error'] = 'Не удалось выполнить оценку.';

$string['evaluation_heading'] = 'Оценка курса';
$string['evaluation_from'] = 'С';
$string['evaluation_to'] = 'по';

$string['error_choose_dates'] = 'Выберите даты начала и завершения анализа (нельзя оставлять оба значения равными сегодняшнему дню).';
$string['error_invalid_dates'] = 'Некорректный формат даты. Ожидается ГГГГ-ММ-ДД.';
$string['error_end_in_future'] = 'Дата окончания не может быть позже сегодняшнего дня.';
$string['error_start_after_end'] = 'Дата начала не может быть позже даты окончания.';

$string['confirm_delete'] = 'Удалить этот отчёт безвозвратно?';

$string['metric_total'] = 'Итоговый балл';
$string['metric_structure'] = 'Разнообразие структуры';
$string['metric_recommended'] = 'Рекомендуемые типы активностей';
$string['metric_content_use'] = 'Использование теоретических материалов';
$string['metric_reach'] = 'Охват студентов';
$string['metric_stability'] = 'Стабильность вовлечённости';

$string['metric_structure_desc'] = 'Оценивает разнообразие видимых элементов курса по типам (энтропия распределения + число типов).';
$string['metric_recommended_desc'] = 'Доля рекомендуемых типов активностей, которые присутствуют среди видимых элементов курса (lesson/quiz/assign/H5P/game и т.д.).';
$string['metric_content_use_desc'] = 'Средняя доля студентов, которые открывали теоретические элементы (page/book/resource/folder/url) в выбранный период; мёртвые элементы учитываются как <5%.';
$string['metric_reach_desc'] = 'Доля студентов, у которых есть хотя бы одно событие в логах курса за выбранный период.';
$string['metric_stability_desc'] = 'Стабильность недельной активности: чем меньше колебания доли активных студентов по неделям, тем выше балл.';

$string['admin_reports'] = 'Все отчёты';
$string['admin_bulk'] = 'Массовая оценка';
$string['bulk_categories'] = 'Категории (Ctrl/Cmd — несколько)';
$string['bulk_include_subcats'] = 'Включать подкатегории';
$string['bulk_submit'] = 'Оценить курсы в выбранных категориях';
$string['bulk_done'] = 'Запущено оценок: {$a}.';
$string['bulk_nothing_selected'] = 'Выберите хотя бы одну категорию или укажите ID курсов.';
$string['bulk_select_course'] = 'Или выберите курсы точечно';
$string['bulk_courses'] = 'Курсы';

$string['error_permission'] = 'Недостаточно прав.';
$string['error_not_editing_teacher'] = 'Доступ только у преподавателей с правом редактирования курса (роль editingteacher или id=3 в контексте этого курса).';
$string['error_logstore'] = 'Стандартное хранилище логов недоступно; часть метрик будет ограничена.';

$string['window_days_label'] = 'Окно анализа (дней):';
$string['student_cohort_label'] = 'Студенты (роль student, зачислены):';
$string['visible_modules_label'] = 'Видимые модули:';
$string['logstore_ok'] = 'Стандартные логи: доступны.';

$string['report_details_title'] = 'Детали расчёта и проблемные элементы';
$string['report_formula'] = 'Итоговый балл = 0.20×Structure + 0.20×Recommended + 0.25×Content use + 0.20×Reach + 0.15×Stability. Все подбаллы приведены к шкале 0–100.';

$string['element_utilization_title'] = 'Используемость видимых элементов';
$string['element_utilization_hint'] = 'Ниже показаны видимые элементы курса, которые в выбранный период открывали ≤{$a}% студентов (crud=r в стандартных логах). Список сгруппирован по типам и отсортирован по возрастанию активности.';
$string['element_utilization_none'] = 'Элементов с низкой активностью по заданному порогу не обнаружено.';
