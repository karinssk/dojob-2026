# LINE LIFF Integration — Full Feature Requirements
> Project: dojob-2026 | Framework: CodeIgniter 4 | Updated: 2026-02-19 (rev 2)

---

## Table of Contents
1. [Overview](#overview)
2. [LINE LIFF Login Flow](#1-line-liff-login-flow)
3. [User Approval System](#2-user-approval-system)
4. [Task Management (LIFF)](#3-task-management-liff)
5. [Event Management (LIFF)](#4-event-management-liff)
6. [Project Management (LIFF)](#5-project-management-liff)
7. [Personal To-Do](#6-personal-to-do)
8. [LINE Notification Engine](#7-line-notification-engine)
9. [Admin Settings Page — Credentials + Approvals](#8-admin-settings-page--credentials--approvals)
10. [Soft UI Design System](#9-soft-ui-design-system)
11. [Database Schema Changes](#10-database-schema-changes)
12. [File Map (New & Modified)](#11-file-map-new--modified)
13. [Route Map](#12-route-map)
14. [Implementation Phases](#13-implementation-phases)

---

## Overview

Build a LINE LIFF (LINE Front-end Framework) mini-app embedded inside LINE that gives all team members a mobile-first interface to:
- **Login** via LINE identity (no password required)
- **Manage tasks, events, projects** assigned to them
- **Upload images** and update statuses directly from LINE
- **Receive smart LINE notifications** before deadlines and when items have no updates
- **Approve/reject** new LIFF user registrations via the admin settings panel

The LIFF app is an **additional interface** on top of the existing web app. All data is shared — same DB, same CI4 backend, same user accounts.

---

## 1. LINE LIFF Login Flow

### 1.1 Entry Point
- LIFF app opens at: `https://{domain}/index.php/liff`
- User opens via LINE app (shared link or chat card)
- LIFF SDK v2 is loaded; `liff.init({ liffId: '...' })` runs automatically

### 1.2 Flow Diagram
```
[User opens LIFF in LINE]
        │
        ▼
[liff.getProfile()] ──→ get LINE UID + display name
        │
        ▼
[POST /liff/verify] ──→ server checks user_mappings_arr
        │
   ┌────┴────┐
   │ FOUND   │ NOT FOUND
   │         │
   ▼         ▼
[Check     [Show: fill display name + select
approval    existing user from dropdown]
status]            │
   │               ▼
   │    [POST /liff/request_link]
   │               │
   │    [Save to liff_pending_registrations]
   │               │
   │               ▼
   │    [Show PENDING APPROVAL page]
   │    (user cannot access until approved)
   │
   ├── status = pending → show Pending page
   ├── status = rejected → show Rejected page
   └── status = approved → set CI4 session → redirect LIFF dashboard
```

### 1.3 LIFF Verify (Server-Side)
- Verify LINE `id_token` via LINE API: `POST https://api.line.me/oauth2/v2.1/verify`
- Extract `sub` (LINE UID) from verified token
- Check `user_mappings_arr.line_user_id = UID`
- If mapping exists → check `liff_pending_registrations.approval_status`

### 1.4 Link New User
- Show dropdown of all active staff users from `users` table
- Check if selected `rise_user_id` already has a **different** LINE UID linked → show warning: "This account is already linked to another LINE ID. Contact admin."
- If no conflict → insert into `liff_pending_registrations` with `status = pending`
- Send LINE notification to admin(s) about new registration request

### 1.5 Session Handling (After Approval)
- On approval → `$session->set('user_id', $rise_user_id)`
- Session key same as normal web login → LIFF app uses same auth guards
- Add `liff_session` flag to distinguish from normal web sessions

---

## 2. User Approval System

### 2.1 Pending State (User Side)
After submitting link request, user sees a **full-screen pending page**:

```
┌─────────────────────────────────┐
│  ⏳  Waiting for Approval        │
│                                  │
│  You've linked your LINE to:     │
│    [Selected User Name]        │
│  📅  Submitted: 19 Feb 2026      │
│                                  │
│  An admin will review your       │
│  request. You'll receive a LINE  │
│  message when approved.          │
│                                  │
│  [ Check Status]               │
└─────────────────────────────────┘
```

- User cannot navigate to any other LIFF page until `status = approved`
- "Check Status" button calls `GET /liff/check_status` (polling, no auto-refresh loop)
- On approved → auto-redirect to LIFF dashboard

### 2.2 Rejection State (User Side)
```
┌─────────────────────────────────┐
│  ❌  Request Rejected            │
│                                  │
│  Reason: [admin note]            │
│                                  │
│  [Try Again with Different User] │
└─────────────────────────────────┘
```
- "Try Again" resets the flow and shows the user selection form again

---

## 3. Task Management (LIFF)

### 3.1 Task List View
- Bottom tab: "Tasks" icon
- Tabs within page: **My Tasks** | **Assigned by Me** | **All** (admin only)
- Each task shown as a **card**:
  ```
  ┌─────────────────────────────────────┐
  │ [Priority chip] [Status chip]       │
  │ Task Title                          │
  │ 📅 19 Feb —  09:00 → 17:00       │
  │  Assigned to: พี่ mike            │
  │ 🖼 [thumbnail] [thumbnail]          │
  │ [📝 Update] [Done]               │
  └─────────────────────────────────────┘
  ```

### 3.2 Create / Edit Task Form
Fields:
- **Title** (required)
- **Description** (rich text or plain)
- **Project** (dropdown — from user's projects)
- **Assigned To** (user picker — avatar list)
- **Collaborators** (multi-select)
- **Start Date + Start Time** (date + time picker)
- **End Date + End Time** (deadline date + time)
- **Priority** (chip selector: Low / Normal / High / Urgent)
- **Status** (chip selector from task_status table)
- **Images** (tap-to-upload, multiple, preview thumbnails)
- **LINE Notification Settings** (collapsible section — entirely optional, hidden by default):
  - Toggle: Enable LINE notification for this task *(default: OFF)*
  - When ON, three sub-fields appear — all individually optional:
    -  Notify X minutes before start time *(leave blank = skip)*
    -  Notify X minutes before end time *(leave blank = skip)*
    -  Alert if no status update after X hours *(leave blank = no alert)*
  - Sub-fields pre-fill with system defaults from settings if user turns ON the toggle
  - User can clear any sub-field to disable just that specific notification

### 3.3 Task Detail View
- Full card view with all info
- Activity feed (who updated, when)
- Comment section
- Image gallery
- Quick status update buttons (swipe or tap chips)
- Timeline of status changes

### 3.4 Quick Update (from LINE message)
- When LINE notification is sent, include **Flex Message** with:
  - `[Mark Done]` button → callback to `/liff/task_quick_update`
  - `[📝 Add Update]` button → opens LIFF task detail
  - `[ Snooze]` button → postpone next notification by 2h

---

## 4. Event Management (LIFF)

### 4.1 Event List View
- Bottom tab: "Calendar" icon
- View: **Today** | **This Week** | **All**
- Event card shows: title, start date, start time → end time, color label, share_with

### 4.2 Create / Edit Event Form
Fields:
- **Title** (required)
- **Description**
- **Start Date + Start Time**
- **End Date + End Time**
- **Label / Color** (chip selector)
- **Share With** (only me / all team / specific members)
- **Assigned Members** (user multi-pick)
- **Images** (upload)
- **LINE Notification Settings** (collapsible — entirely optional, hidden by default):
  - Toggle: Enable LINE notification for this event *(default: OFF)*
  - When ON, sub-fields appear — all individually optional:
    -  Notify X minutes before start *(leave blank = skip)*
    -  Notify X minutes before end *(leave blank = skip)*
    -  Alert if no update after X hours *(leave blank = no alert)*
  - Sub-fields pre-fill with system defaults if toggle is turned ON

### 4.3 Event Detail View
- Full event info
- List of attendees with avatars
- Image gallery
- Quick RSVP: [Attending] [❌ Can't go]

---

## 5. Project Management (LIFF)

### 5.1 Project List View
- Bottom tab: "Projects" icon
- Show only projects where the logged-in user is a member
- Project card:
  ```
  ┌─────────────────────────────────────┐
  │ [Status chip]  PROJECT NAME         │
  │ 📅 Due: 28 Feb 2026                 │
  │ ████████░░ 78% complete             │
  │ 👥 5 members  |   12 tasks        │
  │  LINE notify: ON                  │
  └─────────────────────────────────────┘
  ```

### 5.2 Project Detail View
Tabs inside project detail:
- **Overview** — description, members, dates, progress bar
- **Tasks** — filterable task list for this project (cards, same as Task Management)
- **Files** — uploaded files/images list
- **Activity** — recent updates feed

### 5.3 Project Task Quick Add
- FAB (+) inside project → quick create task pre-filled with project context
- Assign to project member (auto-filtered)

### 5.4 Project Progress
- Visual progress bar based on completed tasks / total tasks
- Member avatars with their task counts
- Overdue task count badge

### 5.5 Project LINE Notification Settings
Per-project toggles (stored in `project_settings` or new column):
-  Notify all project members on new task created
-  Notify assigned user on task assignment
-  Notify all members on project status change
-  Daily summary notification (configurable time)

---

## 6. Personal To-Do

### 6.1 To-Do List View
- Bottom tab: "To-Do" icon (home/checklist)
- Personal to-dos belong to logged-in user only
- Group by: **Today** | **Upcoming** | **Done**
- Swipe right to complete, swipe left to delete

### 6.2 To-Do Card
```
┌─────────────────────────────┐
│ ○ Buy materials             │
│   📅 20 Feb  [#work] [#buy] │
└─────────────────────────────┘
```

### 6.3 Quick Add To-Do
- Text input bar fixed at bottom of to-do screen
- Tap `+` → expand inline form with date picker and label tags
- No approval needed — personal and instant

---

## 7. LINE Notification Engine

### 7.1 Notification Types

> **All per-task/event notifications are opt-in and optional.** A notification is only sent when:
> 1. `line_notify_enabled = 1` (master switch ON for that task/event), AND
> 2. The specific column (`line_notify_before_start`, `line_notify_before_end`, or `line_notify_no_update_hours`) is **NOT NULL**.
> If either condition is false, that notification is silently skipped.

| Type | Required Condition | Trigger | Target |
|------|--------------------|---------|--------|
| Before Start | `line_notify_enabled=1` AND `line_notify_before_start IS NOT NULL` AND `start_time IS NOT NULL` | X minutes before start_date + start_time | Assigned user |
| Before End | `line_notify_enabled=1` AND `line_notify_before_end IS NOT NULL` AND deadline set | X minutes before deadline | Assigned user |
| No Update Alert | `line_notify_enabled=1` AND `line_notify_no_update_hours IS NOT NULL` | updated_at older than X hours AND not closed | Assigned user + collaborators |
| New Assignment | Assigned user has LINE UID linked | Task/Event assigned to user | Assigned user |
| Approval Result | Always | Admin approves/rejects LIFF user | Requesting user (LINE push) |
| New LIFF Request | Always | User submits registration | Admin(s) via `line_admin_notify_uids` |
| Project: New Task | `line_notify_enabled=1` AND `line_notify_on_new_task=1` | Task added to project | All project members |
| Project: Status Change | `line_notify_enabled=1` AND `line_notify_on_status_change=1` | Project status updated | All project members |

### 7.2 Cron Job Schedule
- Runs every **15 minutes** via `Cron_job` library
- Checks `tasks` and `events` tables for notification triggers
- Logs each sent notification in `line_notification_logs` table
- Prevents duplicate sends: check `line_notification_logs` before sending

### 7.3 Notification Message Format (LINE Flex Message)
```
┌─────────────────────────────────┐
│  Task Reminder                │
│─────────────────────────────────│
│ รายงานยอดขายประจำเดือน          │
│ 📅 Start: 19 Feb 09:00          │
│  End:   19 Feb 17:00          │
│  Assigned: พี่ mike           │
│  Status: In Progress          │
│─────────────────────────────────│
│ [📝 Open Task] [Mark Done]   │
└─────────────────────────────────┘
```

### 7.4 No-Update Alert Logic

> All conditions guard against NULL — a NULL column means that notification type is not configured and is silently skipped.

```sql
-- No-update alert: only fires when explicitly configured (NOT NULL)
SELECT tasks.* FROM tasks
WHERE tasks.line_notify_enabled = 1                              -- master switch ON
  AND tasks.line_notify_no_update_hours IS NOT NULL              -- explicitly configured (not NULL = not skipped)
  AND tasks.status_id NOT IN (
        SELECT id FROM task_status WHERE key_name='closed'
      )
  AND tasks.updated_at < NOW() - INTERVAL tasks.line_notify_no_update_hours HOUR
  AND NOT EXISTS (
    SELECT 1 FROM line_notification_logs
    WHERE ref_type='task'
      AND ref_id = tasks.id
      AND type = 'no_update'
      AND sent_at > NOW() - INTERVAL tasks.line_notify_no_update_hours HOUR
  )
```

**Before-start alert** (same NULL guard pattern):
```sql
-- Only fires when line_notify_before_start IS NOT NULL AND start_time IS NOT NULL
WHERE tasks.line_notify_enabled = 1
  AND tasks.line_notify_before_start IS NOT NULL   -- optional: skip if NULL
  AND tasks.start_date IS NOT NULL
  AND tasks.start_time IS NOT NULL                 -- optional: skip if no time set
  AND TIMESTAMPDIFF(MINUTE, NOW(),
        CONCAT(tasks.start_date, ' ', tasks.start_time)
      ) BETWEEN tasks.line_notify_before_start - 7
            AND tasks.line_notify_before_start + 7  -- ±7 min window around cron run
```

**Before-end alert** (same NULL guard pattern):
```sql
WHERE tasks.line_notify_enabled = 1
  AND tasks.line_notify_before_end IS NOT NULL     -- optional: skip if NULL
  AND tasks.deadline IS NOT NULL
  AND TIMESTAMPDIFF(MINUTE, NOW(),
        CONCAT(tasks.deadline, ' ', IFNULL(tasks.end_time, '23:59'))
      ) BETWEEN tasks.line_notify_before_end - 7
            AND tasks.line_notify_before_end + 7
```

---

## 8. Admin Settings Page — Credentials + Approvals

### 8.1 URL
```
https://{current_domain}/index.php/settings/approve_line_liff_users
```

This single page is the **unified LINE LIFF administration hub** with four tabs:

| Tab | Purpose |
|-----|---------|
| **Credentials** | Configure all LINE API keys, secrets, LIFF IDs |
| **Pending** | New registration requests awaiting approval |
| **Approved** | Currently approved user links |
| **Rejected** | Rejected requests with notes |

### 8.2 Access Control
- Admin only — same `access_only_admin_or_settings_admin()` guard as other settings pages
- Linked from **Settings sidebar → LINE** group

---

### 8.3 Tab 1 — Credentials Configuration

Full page wireframe:
```
┌──────────────────────────────────────────────────────────────────┐
│  ⚙️ Settings > LINE LIFF                                         │
│                                                                  │
│  [🔑 Credentials] [⏳ Pending (3)] [Approved] [❌ Rejected]   │
│━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                                  │
│  ┌── 📡 Messaging API (LINE Bot) ─────────────────────────────┐ │
│  │  Channel ID          [ 1234567890              ]           │ │
│  │  Channel Secret      [ ••••••••••••••••••••••  ] [👁 Show] │ │
│  │  Channel Access Token[ ••••••••••••••••••••••  ] [👁 Show] │ │
│  │  Webhook URL         [ https://yourdomain/line_webhook ]   │ │
│  │                      (read-only, copy to LINE console)     │ │
│  └─────────────────────────────────────────────────────────── ┘ │
│                                                                  │
│  ┌── 🔐 LINE Login / LIFF ────────────────────────────────────┐ │
│  │  Login Channel ID    [ 9876543210              ]           │ │
│  │  Login Channel Secret[ ••••••••••••••••••••••  ] [👁 Show] │ │
│  │  LIFF ID             [ 1234567890-AbCdEfGh     ]           │ │
│  │  LIFF Entry URL      [ https://yourdomain/liff ]           │ │
│  │                      (read-only, register in LINE console) │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  ┌──  Notification Defaults ────────────────────────────────┐ │
│  │  Enable LINE Notifications   [ ON  / OFF ]              │ │
│  │  Default notify before start [ 30  ] minutes               │ │
│  │  Default notify before end   [ 60  ] minutes               │ │
│  │  Default no-update alert     [ 24  ] hours                 │ │
│  │  Admin LINE UIDs (notify on new LIFF requests):            │ │
│  │  [ U0e70424426101ba374a3e27a45c6cfce          ]            │ │
│  │  [ + Add another admin UID ]                               │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  ┌── 🧪 Connection Test ──────────────────────────────────────┐ │
│  │  [🔌 Test Messaging API]  [🔌 Test LIFF Login Channel]     │ │
│  │  Last test: 19 Feb 2026 10:30 — OK                      │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│                              [💾 Save Settings]                  │
└──────────────────────────────────────────────────────────────────┘
```

#### 8.3.1 Messaging API Section
All fields stored in `settings` table via existing `Settings_model`:

| Setting Key | Type | Description |
|-------------|------|-------------|
| `line_messaging_channel_id` | string | Numeric channel ID from LINE Developer Console |
| `line_channel_secret` | string (masked) | Channel secret for webhook signature verification |
| `line_channel_access_token` | string (masked) | Long-lived or stateless access token for push/reply |
| `line_webhook_url` | read-only display | Auto-built: `{base_url}line_webhook` |

- **Masked fields**: displayed as `••••••••` with a [👁 Show/Hide] toggle button (JS only, no server round-trip)
- **Webhook URL**: read-only text + [ Copy] button; reminds admin to paste into LINE Developer Console

#### 8.3.2 LINE Login / LIFF Section

| Setting Key | Type | Description |
|-------------|------|-------------|
| `line_login_channel_id` | string | Channel ID of the LINE Login channel (separate from Messaging API) |
| `line_login_channel_secret` | string (masked) | Secret for verifying id_token from LIFF |
| `line_liff_id` | string | LIFF App ID (format: `{channelId}-{suffix}`) registered in console |
| `line_liff_entry_url` | read-only display | Auto-built: `{base_url}liff` |

> **Note**: LINE Login channel and Messaging API channel are typically **different channels** in LINE Developer Console. The Login channel handles LIFF user authentication; the Messaging API channel handles bot messages. Both are needed.

- **LIFF Entry URL**: read-only + [ Copy]; admin pastes this as the LIFF Endpoint URL in LINE console
- **LIFF ID**: auto-populated hint — after adding the LIFF app in the console, paste the generated ID here

#### 8.3.3 Notification Defaults Section

| Setting Key | Type | Default | Description |
|-------------|------|---------|-------------|
| `enable_line_notifications` | bool | 0 | Master switch for all LINE notifications |
| `line_default_notify_before_start` | int | 30 | Default minutes before start (used if task has no override) |
| `line_default_notify_before_end` | int | 60 | Default minutes before end/deadline |
| `line_default_no_update_hours` | int | 24 | Default hours threshold for no-update alert |
| `line_admin_notify_uids` | JSON array | `[]` | LINE UIDs of admins who receive new LIFF registration alerts |
| `line_reminder_days_before` | int | 3 | Days before deadline to start sending reminders |
| `line_notify_recurring_tasks` | bool | 0 | Send notifications for recurring tasks |
| `line_notify_overdue_tasks` | bool | 1 | Send alerts for overdue tasks |

**Admin UID list UI**:
- Each UID is a text input row
- [+ Add] appends a new row; [× Remove] deletes a row
- Saved as JSON array: `["Uabc123", "Udef456"]`
- On load: resolve each UID to a display name via `user_mappings_arr` if available

#### 8.3.4 Connection Test Section
- **Test Messaging API**: calls LINE `GET https://api.line.me/v2/bot/info` with stored token → shows bot name, picture, follower count
- **Test LIFF Login Channel**: calls LINE `GET https://api.line.me/oauth2/v2.1/verify` with a dummy check → confirms channel ID is valid
- Result stored in `settings` as `line_last_test_result` (timestamp + status)
- Tests are triggered via AJAX; result shown inline without page reload

---

### 8.4 Tab 2 — Pending Approvals

```
┌──────────────────────────────────────────────────────────────────┐
│  [🔑 Credentials] [⏳ Pending (3)] [Approved] [❌ Rejected]   │
│━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│  🔍 Search...                              [📤 Export CSV]        │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  LINE display: พี่เล็ก (Rubyshop lek)                 │   │
│  │    LINE UID: Ud5fc1f611437c15ef91b8d70d8f411c8           │   │
│  │    → Link to: อโรชา / User #4 [staff]                   │   │
│  │    📅 Submitted: 19 Feb 2026 10:30                       │   │
│  │    [Approve]  [❌ Reject with Note...]                 │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  LINE display: อุ้ม                                    │   │
│  │    LINE UID: U441fe09ded061e38874f36a2217b320a           │   │
│  │    → Link to: อภิญญา ญาณโภชน์ / User #39 [staff]       │   │
│  │    📅 Submitted: 18 Feb 2026 15:22                       │   │
│  │    [Approve]  [❌ Reject with Note...]                 │   │
│  └──────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────┘
```

**Approve Action:**
1. `UPDATE liff_pending_registrations SET status='approved', approved_by={admin_id}, approved_at=NOW()`
2. `INSERT INTO user_mappings_arr (line_user_id, rise_user_id, nick_name, line_display_name, is_active)` — or update if UID already exists
3. Push LINE message to user via `line_channel_access_token`:
   ```
   คำขอของคุณได้รับการอนุมัติแล้ว!
   กดที่นี่เพื่อเปิดแอป: {liff_entry_url}
   ```

**Reject Action:**
1. Modal appears asking for optional rejection note
2. `UPDATE liff_pending_registrations SET status='rejected', rejection_note='{note}'`
3. Push LINE message to user:
   ```
   ❌ คำขอของคุณถูกปฏิเสธ
   เหตุผล: {rejection_note}
   หากต้องการลองใหม่: {liff_entry_url}
   ```

---

### 8.5 Tab 3 — Approved Users

```
┌──────────────────────────────────────────────────────────────────┐
│  All approved LINE ↔ app user links                              │
│  🔍 Search by name or LINE UID...                                │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  พี่ตี้  ↔  User: [staff #1]                          │   │
│  │    LINE UID: U0e70424426101ba374a3e27a45c6cfce           │   │
│  │    Approved: 04 Oct 2025 by Admin                        │   │
│  │    [🔗 View User]  [🚫 Revoke Access]                    │   │
│  └──────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────┘
```

**Revoke Access Action:**
- Sets `user_mappings_arr.is_active = 0`
- Does NOT delete record (audit trail preserved)
- User will see the "pending" screen on next LIFF open (re-submit required)

---

### 8.6 Tab 4 — Rejected Requests

- Same table as Pending tab but shows `status = 'rejected'` records
- Shows rejection note and who rejected
- Action: **[↩ Re-open]** → sets status back to `pending` so admin can reconsider

---

### 8.7 Settings Tab Sidebar Integration

In `app/Views/settings/tabs.php` — add entry to the LINE tab group:
```php
// In the LINE section of tabs:
array(
    'label'    => 'LINE LIFF',          // tab label
    'url'      => 'settings/approve_line_liff_users',
    'icon'     => 'smartphone',         // feather icon
    'badge'    => $liff_pending_count,  // red badge if > 0
)
```

Badge count (`$liff_pending_count`) is passed to every settings page view via `Settings::index()` so it shows on all tabs, not just when on the LIFF page.

---

### 8.8 Settings Controller Methods (new)

```php
// app/Controllers/Settings.php — new methods:

approve_line_liff_users()       // GET  — render the full page with all 4 tabs
save_liff_credentials()         // POST — save Credentials tab form
test_line_messaging_api()       // POST — AJAX test Messaging API connection
test_line_login_channel()       // POST — AJAX test Login Channel / LIFF
approve_line_liff_user()        // POST — approve one pending request
reject_line_liff_user()         // POST — reject with optional note
revoke_line_liff_user()         // POST — revoke approved access
reopen_line_liff_request()      // POST — re-open rejected → pending
liff_pending_count()            // GET  — JSON: { "count": 3 } for badge
```

---

### 8.9 New Settings Keys (stored in `settings` table)

| Key | Used By |
|-----|---------|
| `line_messaging_channel_id` | Webhook signature verification |
| `line_channel_secret` | Webhook signature + token verify (existing) |
| `line_channel_access_token` | Push/reply messages (existing) |
| `line_login_channel_id` | LIFF id_token verification |
| `line_login_channel_secret` | LIFF id_token verification |
| `line_liff_id` | Injected into LIFF views as `liff.init({ liffId })` |
| `line_default_notify_before_start` | Task/Event notification defaults |
| `line_default_notify_before_end` | Task/Event notification defaults |
| `line_default_no_update_hours` | Task/Event notification defaults |
| `line_admin_notify_uids` | JSON array of admin LINE UIDs |
| `line_last_test_result` | JSON: `{ "tested_at": "...", "messaging": true, "liff": true }` |

---

## 9. Soft UI Design System

### 9.1 Design Principles
- **Card-based layout** — every content block is a floating card
- **Lots of whitespace** — padding 16–24px inside cards
- **Soft shadows** — `box-shadow: 0 4px 20px rgba(0,0,0,0.06)`
- **Rounded corners** — `border-radius: 16px` for cards, `999px` for chips/buttons
- **Pastel accent chips** — status/priority shown as small colored pill badges
- **iOS-like minimalism** — no heavy borders, clean typography
- **Material-style components** — tabs, bottom navigation, FAB, ripple on tap
- **Mobile-first** — max-width 480px, optimized for LINE in-app browser

### 9.2 Color Palette
```css
/* Backgrounds */
--color-bg:           #F4F6FB;
--color-card:         #FFFFFF;
--color-surface:      #FAFBFD;

/* Accents */
--color-blue:         #6C8EF5;   /* primary actions */
--color-green:        #6FCBA3;   /* success / done */
--color-orange:       #FFA96A;   /* warning / in progress */
--color-pink:         #F97FA3;   /* urgent / overdue */
--color-purple:       #A78BFA;   /* events */
--color-yellow:       #FBBF24;   /* pending */

/* Text */
--color-text:         #1E293B;
--color-muted:        #94A3B8;
--color-label:        #64748B;

/* Shadows */
--shadow-card:        0 4px 20px rgba(0,0,0,0.06);
--shadow-fab:         0 8px 24px rgba(108,142,245,0.35);
```

### 9.3 Typography
```css
font-family: 'Sarabun', 'Noto Sans Thai', sans-serif;  /* Thai support */
--font-heading:  600  20px/1.3;
--font-body:     400  14px/1.5;
--font-caption:  400  12px/1.4;
--font-chip:     500  11px/1;
```

### 9.4 Component Specs

#### Task / Event Card
```
border-radius: 16px
padding: 16px
background: #FFFFFF
box-shadow: var(--shadow-card)
margin-bottom: 12px
```

#### Status Chip
```
border-radius: 999px
padding: 4px 10px
font-size: 11px
font-weight: 600
background: pastel variant of status color (opacity 0.15)
color: status color (full opacity)
```

#### Floating Action Button (FAB)
```
position: fixed
bottom: 80px    /* above bottom tab bar */
right: 20px
width: 56px; height: 56px
border-radius: 50%
background: var(--color-blue)
box-shadow: var(--shadow-fab)
color: white
font-size: 28px
```

#### Bottom Tab Bar
```
position: fixed
bottom: 0; left: 0; right: 0
height: 64px
background: white
border-top: 1px solid #F1F5F9
display: flex; justify-content: space-around; align-items: center
padding-bottom: env(safe-area-inset-bottom)   /* iPhone notch */

Tabs: Todo | Tasks | Projects | Events | Profile
```

#### LINE Notification Toggle (Task/Event Form)
```
[ LINE Notification]  ○──●  OFF   ← hidden by default, entire section optional

When toggled ON → section expands with animation:
  ├─ "Notify before start"  [ 30 ] min  (pre-filled from system default; clear to skip)
  ├─ "Notify before end"    [ 60 ] min  (pre-filled from system default; clear to skip)
  └─ "Alert if no update"   [ 24 ] hrs  (pre-filled from system default; clear to skip)

All three sub-fields are independently optional:
  - Empty / blank = that specific notification is disabled (saved as NULL)
  - Value = saved as integer in DB
  - If toggle is OFF → all three saved as NULL regardless of field values
```

### 9.5 Animation
```css
/* Card entry */
@keyframes slide-up {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}
.card { animation: slide-up 0.25s ease; }

/* Chip tap ripple */
.chip:active { transform: scale(0.95); transition: transform 0.1s; }

/* FAB pulse */
.fab:hover { transform: scale(1.08); transition: transform 0.2s ease; }
```

---

## 10. Database Schema Changes

### 10.1 New Table: `liff_pending_registrations`
```sql
CREATE TABLE `liff_pending_registrations` (
  `id`                  INT(11)      NOT NULL AUTO_INCREMENT,
  `line_uid`            VARCHAR(255) NOT NULL,
  `line_display_name`   VARCHAR(255) DEFAULT NULL,
  `rise_user_id`        INT(11)      NOT NULL,
  `rise_user_name`      VARCHAR(255) DEFAULT NULL,   -- snapshot at time of request
  `status`              ENUM('pending','approved','rejected') DEFAULT 'pending',
  `rejection_note`      TEXT         DEFAULT NULL,
  `approved_by`         INT(11)      DEFAULT NULL,   -- rise user id of admin
  `approved_at`         DATETIME     DEFAULT NULL,
  `created_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_line_uid`     (`line_uid`),
  KEY `idx_rise_user_id` (`rise_user_id`),
  KEY `idx_status`       (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 10.2 Alter `tasks` Table
> All LINE notification columns are **fully optional — NULL means not configured / disabled**.
> The cron job skips any row where `line_notify_enabled = 0` OR the specific column is NULL.

```sql
ALTER TABLE `tasks`
  ADD COLUMN `start_time`                  TIME        DEFAULT NULL   AFTER `start_date`,
  ADD COLUMN `end_time`                    TIME        DEFAULT NULL   AFTER `deadline`,
  ADD COLUMN `line_notify_enabled`         TINYINT(1)  DEFAULT 0      COMMENT '0=off, 1=on; master switch per task',
  ADD COLUMN `line_notify_before_start`    INT         DEFAULT NULL   COMMENT 'NULL = use system default or skip',
  ADD COLUMN `line_notify_before_end`      INT         DEFAULT NULL   COMMENT 'NULL = use system default or skip',
  ADD COLUMN `line_notify_no_update_hours` INT         DEFAULT NULL   COMMENT 'NULL = no alert configured';
```

| Column | Nullable | Default | Meaning when NULL |
|--------|----------|---------|-------------------|
| `start_time` | YES | NULL | No specific start time set |
| `end_time` | YES | NULL | No specific end time set |
| `line_notify_enabled` | NO | 0 | Notifications off |
| `line_notify_before_start` | YES | NULL | Use system default OR skip if no start_time |
| `line_notify_before_end` | YES | NULL | Use system default OR skip if no deadline |
| `line_notify_no_update_hours` | YES | NULL | No "no-update" alert configured |

### 10.3 Alter `events` Table
> Same rule: all LINE notification columns are **optional — NULL = not configured**.

```sql
ALTER TABLE `events`
  ADD COLUMN `line_notify_enabled`         TINYINT(1)  DEFAULT 0      COMMENT '0=off, 1=on; master switch per event',
  ADD COLUMN `line_notify_before_start`    INT         DEFAULT NULL   COMMENT 'NULL = use system default or skip',
  ADD COLUMN `line_notify_before_end`      INT         DEFAULT NULL   COMMENT 'NULL = use system default or skip',
  ADD COLUMN `line_notify_no_update_hours` INT         DEFAULT NULL   COMMENT 'NULL = no alert configured';
```

| Column | Nullable | Default | Meaning when NULL |
|--------|----------|---------|-------------------|
| `line_notify_enabled` | NO | 0 | Notifications off |
| `line_notify_before_start` | YES | NULL | Use system default OR skip |
| `line_notify_before_end` | YES | NULL | Use system default OR skip |
| `line_notify_no_update_hours` | YES | NULL | No "no-update" alert configured |

### 10.4 Alter `projects` Table (LINE notification settings)
> Project-level toggles are also **optional — NULL or 0 = disabled**.

```sql
ALTER TABLE `projects`
  ADD COLUMN `line_notify_enabled`           TINYINT(1)  DEFAULT 0      COMMENT 'Master switch for this project',
  ADD COLUMN `line_notify_on_new_task`       TINYINT(1)  DEFAULT NULL   COMMENT 'NULL = inherit system default',
  ADD COLUMN `line_notify_on_assign`         TINYINT(1)  DEFAULT NULL   COMMENT 'NULL = inherit system default',
  ADD COLUMN `line_notify_on_status_change`  TINYINT(1)  DEFAULT NULL   COMMENT 'NULL = inherit system default',
  ADD COLUMN `line_notify_daily_summary`     TINYINT(1)  DEFAULT 0,
  ADD COLUMN `line_notify_daily_time`        TIME        DEFAULT NULL   COMMENT 'NULL = disabled';
```

### 10.5 Existing Tables Used (No Change)
| Table | Purpose |
|-------|---------|
| `user_mappings_arr` | LINE UID ↔ rise_user_id mapping (write after approval) |
| `users` | App user accounts (read for user selection dropdown) |
| `tasks` | Task data (alter above) |
| `events` | Event data (alter above) |
| `projects` | Project data (alter above) |
| `project_members` | Which users belong to which project |
| `to_do` | Personal to-do items |
| `line_notification_logs` | Notification history (existing) |

---

## 11. File Map (New & Modified)

### New Files

#### Controllers
```
app/Controllers/Liff_auth.php          — LIFF login, verify, link_user, check_status
app/Controllers/Liff_app.php           — LIFF dashboard, task/event/project/todo pages
app/Controllers/Liff_api.php           — JSON endpoints for LIFF AJAX calls
```

#### Views — LIFF Auth
```
app/Views/liff_auth/index.php          — Entry: LIFF init + loading screen
app/Views/liff_auth/select_user.php    — Select existing user form
app/Views/liff_auth/pending.php        — Waiting for approval screen
app/Views/liff_auth/rejected.php       — Rejected screen + try again
```

#### Views — LIFF App (Soft UI)
```
app/Views/liff_app/layout.php          — Base HTML with CSS, bottom tab bar, FAB
app/Views/liff_app/dashboard.php       — Home: summary cards (tasks due, events today, todos)
app/Views/liff_app/tasks/index.php     — Task list with tabs
app/Views/liff_app/tasks/form.php      — Create/edit task form
app/Views/liff_app/tasks/detail.php    — Task detail + activity feed + comments
app/Views/liff_app/events/index.php    — Event list (Today/Week/All)
app/Views/liff_app/events/form.php     — Create/edit event form
app/Views/liff_app/events/detail.php   — Event detail + attendees
app/Views/liff_app/projects/index.php  — Project list (user's projects only)
app/Views/liff_app/projects/detail.php — Project detail: overview + tasks + files + activity
app/Views/liff_app/projects/task_form.php  — Quick task create inside project
app/Views/liff_app/todo/index.php      — Personal to-do list
app/Views/liff_app/profile/index.php   — User profile + LINE link status
```

#### Views — Admin Settings
```
app/Views/settings/approve_line_liff_users.php   — Unified LIFF hub (4 tabs: Credentials, Pending, Approved, Rejected)
app/Views/settings/liff_credentials_tab.php      — Credentials tab partial (Messaging API + Login/LIFF + Defaults)
app/Views/settings/liff_pending_tab.php          — Pending approvals tab partial
app/Views/settings/liff_approved_tab.php         — Approved users tab partial
app/Views/settings/liff_rejected_tab.php         — Rejected requests tab partial
```

#### Assets
```
assets/css/liff-ui.css                 — Soft UI design system stylesheet
assets/js/liff-app.js                  — LIFF SDK init, common helpers, API calls
```

#### Models
```
app/Models/Liff_pending_model.php      — CRUD for liff_pending_registrations
```

### Modified Files

#### Controllers
```
app/Controllers/Settings.php           — Add 9 new methods: approve_line_liff_users, save_liff_credentials,
                                         test_line_messaging_api, test_line_login_channel,
                                         approve_line_liff_user, reject_line_liff_user,
                                         revoke_line_liff_user, reopen_line_liff_request, liff_pending_count
app/Controllers/Tasks.php              — Add start_time, end_time, LINE notify fields to save()
app/Controllers/Events.php             — Add LINE notify fields to save()
app/Controllers/Projects.php           — Add LINE notify settings to save()
```

#### Models
```
app/Models/Tasks_model.php             — Include new time/notify columns
app/Models/Events_model.php            — Include new notify columns
app/Models/Projects_model.php          — Include new notify columns
```

#### Config & Views
```
app/Config/Routes.php                  — Add all /liff/* and /settings/approve_line_liff_users routes
app/Views/settings/tabs.php            — Add "LIFF Users" tab with pending badge count
```

#### Libraries
```
app/Libraries/Cron_job.php             — Add 3 new notification jobs (before-start, before-end, no-update)
app/Libraries/Line_webhook.php         — Add send_push_to_user($rise_user_id, $message) helper
```

---

## 12. Route Map

```php
// ── LIFF Auth (public — no session required) ──────────────────────
$routes->get('liff',                    'Liff_auth::index');
$routes->post('liff/verify',            'Liff_auth::verify');
$routes->post('liff/request_link',      'Liff_auth::request_link');
$routes->get('liff/check_status',       'Liff_auth::check_status');
$routes->get('liff/pending',            'Liff_auth::pending');

// ── LIFF App (requires LIFF session) ─────────────────────────────
$routes->get('liff/app',                'Liff_app::dashboard');
$routes->get('liff/app/tasks',          'Liff_app::tasks');
$routes->get('liff/app/tasks/create',   'Liff_app::task_create');
$routes->get('liff/app/tasks/(:num)',   'Liff_app::task_detail/$1');
$routes->get('liff/app/events',         'Liff_app::events');
$routes->get('liff/app/events/create',  'Liff_app::event_create');
$routes->get('liff/app/events/(:num)',  'Liff_app::event_detail/$1');
$routes->get('liff/app/projects',       'Liff_app::projects');
$routes->get('liff/app/projects/(:num)','Liff_app::project_detail/$1');
$routes->get('liff/app/todo',           'Liff_app::todo');
$routes->get('liff/app/profile',        'Liff_app::profile');

// ── LIFF JSON API ─────────────────────────────────────────────────
$routes->post('liff/api/tasks/save',             'Liff_api::task_save');
$routes->post('liff/api/tasks/update_status',    'Liff_api::task_update_status');
$routes->post('liff/api/tasks/upload_image',     'Liff_api::task_upload_image');
$routes->post('liff/api/events/save',            'Liff_api::event_save');
$routes->post('liff/api/todo/save',              'Liff_api::todo_save');
$routes->post('liff/api/todo/toggle',            'Liff_api::todo_toggle');
$routes->post('liff/api/task_quick_update',      'Liff_api::task_quick_update');

// ── Admin Settings — LINE LIFF Hub ───────────────────────────────
// Main page (all 4 tabs)
$routes->get('settings/approve_line_liff_users',            'Settings::approve_line_liff_users');

// Credentials tab
$routes->post('settings/save_liff_credentials',             'Settings::save_liff_credentials');
$routes->post('settings/test_line_messaging_api',           'Settings::test_line_messaging_api');
$routes->post('settings/test_line_login_channel',           'Settings::test_line_login_channel');

// Approval actions
$routes->post('settings/approve_line_liff_user',            'Settings::approve_line_liff_user');
$routes->post('settings/reject_line_liff_user',             'Settings::reject_line_liff_user');
$routes->post('settings/revoke_line_liff_user',             'Settings::revoke_line_liff_user');
$routes->post('settings/reopen_line_liff_request',          'Settings::reopen_line_liff_request');

// Badge count (polled by sidebar)
$routes->get('settings/liff_pending_count',                 'Settings::liff_pending_count');
```

---

## 13. Implementation Phases

### Phase 1 — Foundation (DB + Auth)
- [ ] Write and run DB migrations (liff_pending_registrations, alter tasks/events/projects)
- [ ] Create `Liff_pending_model.php`
- [ ] Create `Liff_auth` controller with verify, request_link, check_status
- [ ] Build LIFF auth views (index, select_user, pending, rejected) with LIFF SDK v2
- [ ] Add `/liff/*` routes
- [ ] Test LIFF login end-to-end in LINE app

### Phase 2 — Admin Settings Hub (Credentials + Approvals)
- [ ] Add all 9 new methods to `Settings` controller
- [ ] Build `approve_line_liff_users.php` main view with 4-tab layout
- [ ] Build **Credentials tab** partial:
  - [ ] Messaging API section (Channel ID, Secret, Token, Webhook URL display)
  - [ ] LINE Login / LIFF section (Login Channel ID, Login Secret, LIFF ID, LIFF URL display)
  - [ ] Notification Defaults section (master switch, default times, admin UID list)
  - [ ] Connection Test section (AJAX test buttons)
  - [ ] [💾 Save Settings] saves all credential + default keys
- [ ] Build **Pending tab** partial (list with Approve / Reject modal)
- [ ] Build **Approved tab** partial (list with Revoke action)
- [ ] Build **Rejected tab** partial (list with Re-open action)
- [ ] Implement approve/reject/revoke/reopen actions with LINE push notification to user
- [ ] Add "LIFF" entry to `settings/tabs.php` sidebar with pending badge count
- [ ] Wire LIFF ID into `Liff_auth` controller — read from `get_setting('line_liff_id')`
- [ ] Wire Login Channel Secret into `Liff_auth::verify()` for id_token validation
- [ ] Test full flow: configure credentials → LIFF login → request → admin approve → user access

### Phase 3 — Soft UI Foundation
- [ ] Create `assets/css/liff-ui.css` with design system
- [ ] Create `liff_app/layout.php` with bottom tab bar + FAB
- [ ] Create LIFF dashboard view (summary cards)
- [ ] Create `assets/js/liff-app.js` (LIFF SDK helpers, fetch wrappers)

### Phase 4 — Task Management (LIFF)
- [ ] Create `Liff_app` controller + `Liff_api` controller
- [ ] Build task list view with My Tasks / Assigned by Me tabs
- [ ] Build task create/edit form (with time pickers + LINE notify toggle)
- [ ] Build task detail view (activity + comments + images)
- [ ] Add start_time, end_time, LINE notify fields to Tasks controller save()
- [ ] Image upload endpoint in `Liff_api`

### Phase 5 — Event Management (LIFF)
- [ ] Build event list view (Today / Week / All)
- [ ] Build event create/edit form
- [ ] Build event detail view
- [ ] Add LINE notify fields to Events controller save()

### Phase 6 — Project Management (LIFF)
- [ ] Build project list view (user's projects only)
- [ ] Build project detail view (Overview / Tasks / Files / Activity tabs)
- [ ] Quick task create inside project
- [ ] Add LINE notify settings to Projects controller save()

### Phase 7 — Personal To-Do (LIFF)
- [ ] Build to-do list view (Today / Upcoming / Done groups)
- [ ] Inline quick-add bar
- [ ] Swipe-to-complete / swipe-to-delete

### Phase 8 — LINE Notification Engine
- [ ] Extend `Cron_job` library: before-start notifier
- [ ] Extend `Cron_job` library: before-end notifier
- [ ] Extend `Cron_job` library: no-update alerter
- [ ] Add project-level notification triggers (new task, assign, status change)
- [ ] Build Flex Message templates for each notification type
- [ ] Add `send_push_to_user()` helper in `Line_webhook` library
- [ ] Add deduplication check against `line_notification_logs`
- [ ] Test all notification types end-to-end

### Phase 9 — Polish & Testing
- [ ] LINE Flex Message quick-action callbacks (Mark Done, Snooze)
- [ ] LIFF app full regression test on iOS LINE app + Android LINE app
- [ ] Check pending count badge updates live on settings tab
- [ ] Performance check (project list with many members/tasks)
- [ ] Thai language strings for all new UI text

---

## Notes & Constraints

### Credentials & Config
- **All credentials** (tokens, secrets, IDs) are stored in the `settings` table via existing `Settings_model` — no `.env` file changes needed for runtime config
- **Masked display**: secrets shown as `••••••••` in UI; plain text only briefly revealed client-side via [👁 Show] toggle — never stored in localStorage
- **Two LINE channels required**: (1) Messaging API channel for bot push/webhook; (2) LINE Login channel for LIFF user identity. Both configured on the same settings page
- **LIFF ID format**: `{LoginChannelId}-{suffix}` (e.g., `1234567890-AbCdEfGh`) — generated in LINE Developer Console after creating a LIFF app under the Login channel
- **LIFF endpoint** must be HTTPS — use ngrok or actual domain for local testing; update setting value per environment

### Auth & Security
- **LIFF id_token verification**: always verify on server side via `POST https://api.line.me/oauth2/v2.1/verify` using the **Login Channel ID** (not Messaging API Channel ID)
- **user_mappings_arr** is the source of truth for LINE UID → app user; write to it only **after admin approval**
- **Session isolation**: LIFF sessions use `liff_session = true` flag in CI4 session data; does not conflict with desktop web sessions

### Data & Compatibility
- **Image storage**: use existing `files/` directory and `Upload_pasted_image` mechanism
- **Cron job**: ensure server cron calls `/cron` endpoint every 15 minutes (or use CI4 scheduler)
- **Backward compatibility**: all changes to tasks/events/projects add nullable columns — existing data unaffected
- **Admin UID list** (`line_admin_notify_uids`): stored as JSON string in `settings` table; fallback to existing `line_user_ids` setting if not set
