# local_coursequality

Local plugin for **Moodle 4.5+** that stores heuristic **course quality** snapshots based on:

- visible module mix (structure diversity),
- presence of recommended activity types (lesson, quiz, assign, H5P/HVP, game),
- student touch-rate on “theory” modules (page, book, resource, folder, url) via `logstore_standard_log`,
- student reach and weekly stability (from the same logs).

## Install

1. Copy the folder `local/coursequality` into your Moodle tree: `MOODLEROOT/local/coursequality`.
2. Visit **Site administration → Notifications** to run the installer.
3. Purge caches (**Site administration → Development → Purge all caches**).

## Capabilities

- `local/coursequality:view` — open the course report list and read reports (archetype: editingteacher only).
- `local/coursequality:evaluate` — run evaluation and delete own reports (archetype: editingteacher only).
- `local/coursequality:manage` — admin reports + bulk evaluation (archetype: manager only).

**Course UI** (settings / “More” menu, `index.php`, `evaluate.php`): the link and scripts additionally require an **editing teacher role assignment in the course context** — role shortname `editingteacher` **or** role id `3` (default Moodle id for that role). This blocks site admins who are not enrolled as editing teachers from using the in-course tool (they can use the admin pages if they have `manage`).

## Uninstall

Uninstalling the plugin runs `db/uninstall.php`, which **drops only** the table `mdl_local_coursequality_report`. No other tables are touched.

## UI entry points

- **Course**: link *Course quality* in course navigation + course settings navigation → `local/coursequality/index.php`.
- **Administration**: *Courses → Course quality evaluations* and *Courses → … bulk*.

## Requirements

- Standard log store **enabled** and table `{logstore_standard_log}` present (otherwise log-based metrics are weak).

## Notes (v0.1)

- Evaluation window defaults to **120 days** (see `classes/evaluator.php`).
- Bulk run is capped at **40** courses per submit (see `admin/bulk.php`).
- AJAX web service `local_coursequality_evaluate_course` is registered for future UI; the teacher button uses synchronous **POST** to `evaluate.php`.

## Uninstall

Remove the plugin folder and uninstall via **Site administration → Plugins → Local plugins**.
