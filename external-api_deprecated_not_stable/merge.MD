Claude’s Plan
Plan: Merge LINE Bot Expenses into CodeIgniter 4 App
Summary
Migrate the external Node.js expense bot (external-api/server-expenses.js + routes) into the main CI4 app as a new line_bot_expenses module. After migration, the external API is no longer needed. All hardcoded data becomes configurable via new admin pages. Keywords use exact match (===).

New Files to Create
1. Database Migration Script
File: app/Database/Migrations/create_line_expenses_tables.sql

Two new tables:

rise_line_expenses_title_keywords (keyword, title, sort, deleted) - replaces titile_expenses from data.js
rise_line_expenses_project_keywords (keyword, client_name, project_name, is_monthly_project, sort, deleted) - replaces client_and_project from data.js
user_mappings table (if not exists) - maps LINE user IDs to Rise user IDs
Seed all existing data from data.js (20 title keywords, 3 project keywords).

Settings stored in existing rise_settings table with prefix line_expenses_*:

line_expenses_enabled, line_expenses_channel_access_token, line_expenses_channel_secret
line_expenses_report_target_id, line_expenses_report_target_type
line_expenses_daily_report_enabled, line_expenses_daily_report_time
line_expenses_monthly_report_enabled, line_expenses_monthly_report_days, line_expenses_monthly_report_time
line_expenses_rooms, line_expenses_default_category_id
2. Model
File: app/Models/Line_expenses_model.php

CRUD for title keywords (get_all, get_one, save, delete, find_by_exact_keyword)
CRUD for project keywords (get_all, get_one, save, delete, find_by_exact_keyword)
User mapping queries (get_rise_user_from_line_id, save_mapping)
Report data queries (daily expenses by project, monthly expenses by project, created-today totals)
Category lookup by ID from rise_expense_categories
3. Library
File: app/Libraries/Line_expenses_webhook.php

Core business logic ported from server-expenses.js (1898 lines), dailyReportsRoute.js (1196 lines), monthlyReportsRoute.js (1536 lines):

Expense parsing: parse_expense_input() - format: date-title-category-description-amount-project[-vat]
Keyword matching: ALL keywords use exact match (===) instead of includes()
Expense processing: process_expense() - find user, match keywords, calc VAT, insert into rise_expenses, log activity
LINE API: send_reply(), send_flex_reply(), send_push_message(), download_line_image(), get_user_profile()
Flex messages: build_expense_confirmation_flex() - confirmation card after expense recorded
Daily reports: generate_daily_report_data(), build_daily_header_flex(), build_daily_project_flex(), send_daily_report()
Monthly reports: generate_monthly_report_data(), build_monthly_header_flex(), build_monthly_project_flex(), send_monthly_report()
Helpers: format_number_with_commas(), parse_buddhist_date(), calculate_vat()
4. Controller
File: app/Controllers/Line_bot_expenses.php

Constructor uses parent::__construct(false) to allow webhook without auth.

Settings pages (require admin auth):

index() - main page with inner tabs
save_settings() - save LINE credentials & general config
title_keywords(), title_keywords_list_data(), title_keyword_modal_form(), save_title_keyword(), delete_title_keyword() - CRUD
project_keywords(), project_keywords_list_data(), project_keyword_modal_form(), save_project_keyword(), delete_project_keyword() - CRUD
save_daily_report_settings(), save_monthly_report_settings() - report config
test_daily_report(), test_monthly_report() - manual trigger
Webhook (no auth, signature verification):

webhook() - POST handler at line/v2/expenses/webhook
Cron:

cron_daily_report(), cron_monthly_report() - called by system cron
5. Views
Directory: app/Views/settings/line_bot_expenses/

File	Content
index.php	Main page with settings sidebar + inner tabs (Settings, Keywords, Projects, Daily Report, Monthly Report)
settings_tab.php	LINE credentials, enable/disable, webhook URL, report target selector
title_keywords_tab.php	DataTable of title keywords + Add button
title_keyword_modal.php	Modal form: keyword + vendor title
project_keywords_tab.php	DataTable of project keywords + Add button
project_keyword_modal.php	Modal form: keyword, client name, project name, is_monthly_project toggle
daily_report_tab.php	Enable toggle, time picker, target selector, "Send Test" button
monthly_report_tab.php	Enable toggle, day checkboxes (Mon/Sat/1st), time picker, target selector, "Send Test" button
Files to Modify
1. app/Views/settings/tabs.php (line 12)
Add after line_settings entry:


array("name" => "line_bot_expenses", "url" => "line_bot_expenses"),
2. app/Config/Routes.php (before line 197)
Add:


$routes->post('line/v2/expenses/webhook', 'Line_bot_expenses::webhook');
3. app/Language/english/custom_lang.php
Add ~30 language strings for the new module (line_bot_expenses, title_keywords, project_keywords, report settings, etc.)

Key Design Decisions
Exact keyword match: WHERE keyword = ? (SQL exact match) replaces Node.js includes(). User confirmed keywords will 100% match.
Separate LINE credentials: Own channel access token/secret, independent from existing LINE notify bot.
Own webhook: line/v2/expenses/webhook - separate from existing line/v1/webhook.
DB-configurable data: All hardcoded arrays from data.js move to database tables with full CRUD UI.
Categories: Use rise_expense_categories table directly (category ID from input maps to rise_expense_categories.id). No separate category keyword table needed.
Cron for reports: PHP endpoints called by system cron, checking configured time/day before sending.
Image sessions: Store in rise_settings as line_expenses_session_{userId} (JSON), cleaned up by cron.
Implementation Order
Run SQL migration (create tables + seed data)
Create Line_expenses_model.php
Create Line_expenses_webhook.php (core library)
Create Line_bot_expenses.php (controller)
Create all view files
Modify tabs.php, Routes.php, custom_lang.php
Test webhook + settings pages
Verification
Access settings page at /index.php/line_bot_expenses - verify all tabs render
Add/edit/delete title keywords and project keywords via UI
Save LINE credentials and report settings
Send a test message to the webhook endpoint and verify expense is created in rise_expenses
Trigger test daily/monthly reports and verify LINE flex messages are received
Verify exact keyword matching works (only exact match triggers, partial does not)