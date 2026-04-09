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
$string['evaluate_done'] = 'Отчёт сформирован. Его можно открыть из списка выше.';
$string['evaluate_error'] = 'Не удалось выполнить оценку.';

$string['confirm_delete'] = 'Удалить этот отчёт безвозвратно?';

$string['metric_total'] = 'Итоговый балл';
$string['metric_structure'] = 'Разнообразие структуры';
$string['metric_recommended'] = 'Рекомендуемые типы активностей';
$string['metric_content_use'] = 'Использование теоретических материалов';
$string['metric_reach'] = 'Охват студентов';
$string['metric_stability'] = 'Стабильность вовлечённости';

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
