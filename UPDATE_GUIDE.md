# 🚀 Stackrium Update & Migration Master Guide

This document serves as the absolute "Source of Truth" for releasing new versions of Stackrium Control. Because Stackrium uses a highly aggressive `rsync --delete` engine to guarantee perfectly consistent server states, failing to follow these steps exactly **will result in deleted files, broken servers, or failed migrations.**

Read this document carefully before every release.

---

## 🛑 The "Golden Source" Rule
The most critical concept of the Stackrium update engine is the **Golden Source**.
When a server downloads an update, the `rsync` engine blindly trusts the ZIP file. If a file or folder is missing from your ZIP file, **rsync will immediately delete it from the client's server.**

Before you create any release, you MUST ensure that your local computer (or GitHub repository) is a perfect, 100% accurate representation of what the live server should look like.

---

## 🛠 Part 1: Preparing the Codebase (Applies to both methods)

Before you compress any files or trigger a GitHub Action, you must prepare the core files:

### 1. Update the Version Number
You must increment the version number so the client server knows it successfully updated.
* **Open:** `www/version.php`
* **Action:** Change the `PANEL_VERSION` constant.
```php
<?php define('PANEL_VERSION', '1.0.6'); ?>
```

### 2. Create the Database Migration (If required)
If your new update requires changes to the database (e.g., adding a new table or column), you must create a migration file.
* **Navigate to:** `cli/migrations/`
* **Action:** Create a new file named exactly after your new version (e.g., `1.0.6.sql`).
* **Write your SQL:**
```sql
-- Example: cli/migrations/1.0.6.sql
ALTER TABLE `users` ADD COLUMN `plan_type` VARCHAR(50) DEFAULT 'basic';
```

---

## 🤖 Part 2A: The GitHub Actions Release (Recommended)
This is the safest method. The GitHub robot guarantees that the `payload` folder is structured perfectly and eliminates human error during zipping.

1. **Commit your changes:** Ensure `version.php`, your new code, and any `.sql` migrations are committed.
   ```bash
   git add .
   git commit -m "Prepared v1.0.6 release"
   ```
2. **Create the Git Tag:** The GitHub Action is triggered by version tags.
   ```bash
   git tag v1.0.6
   ```
3. **Push to GitHub:**
   ```bash
   git push origin main
   git push origin v1.0.6
   ```
4. **Wait for the Build:** Go to your GitHub repository -> **Actions** tab. Wait for the `Build Stackrium Release` job to turn green.
5. **Get the Download Link:** Go to the **Releases** tab on GitHub, right-click the `stackrium-v1.0.6.zip` asset, and copy the link address.

---

## 💻 Part 2B: The Manual Local Release (Fallback Method)
If you are not using GitHub Actions and must zip the files manually from your computer, you must construct the folder hierarchy perfectly.

1. **Create a `payload` folder:** On your desktop, create an empty folder named `payload`.
2. **Move Core Folders:** Copy your `www`, `scripts`, `daemon`, `cli`, and `templates` folders **inside** the `payload` folder.
   * *Correct Structure:* `payload/www/`, `payload/scripts/`, etc.
3. **Compress the Payload:** Zip the `payload` folder itself. The final zip file MUST contain the `payload` directory at its root, not just the files inside it.
   * *Correct Output:* `update.zip` -> extracts to -> `payload/www/...`
4. **Upload:** Upload this `update.zip` to a secure location on Stackrium Central.

---

## 🌍 Part 3: Updating Stackrium Central (`updates.php`)
Once your ZIP file is hosted on GitHub or your master server, you must tell the global API that the update is ready.

* **Open:** The `updates.php` file on Stackrium Central (your licensing API).
* **Action:** Update the version number and the download URL.
```php
'stable' => [
    'version' => '1.0.6',
    'release_date' => date('Y-m-d'),
    'url' => '[https://github.com/yourname/repo/releases/download/v1.0.6/stackrium-v1.0.6.zip](https://github.com/yourname/repo/releases/download/v1.0.6/stackrium-v1.0.6.zip)',
    'changelog' => 'Added new user plan types and optimized Nginx.'
],
```

***Congratulations! At 4:00 AM, thousands of servers will check this API, see `1.0.6`, download the zip, apply the rsync, run the `1.0.6.sql` migration, and restart automatically!***

---

## 🚨 CRITICAL DO's and DON'Ts

### THE DO'S
* **DO** place `version.php` directly inside the `/www/` folder.
* **DO** test your `.sql` migrations locally before pushing. A broken SQL query will leave the client's database in a partial state.
* **DO** ensure the `cli` folder is included in the permissions block of `updater.sh`.
* **DO** include `systemctl daemon-reload` at the end of `updater.sh` if you ever modify the Python daemon `.service` files.

### THE DON'TS
* **DON'T** put `version.php` inside `/www/config/`. Rsync is instructed to ignore the `config` folder, so the version file will never be updated!
* **DON'T** hardcode the database name in your migration SQL files.
  * **BAD:** `ALTER TABLE panel_core.users...` (Breaks if the user changed the DB name or you are testing).
  * **GOOD:** `ALTER TABLE users...` (The PHP migration script automatically selects the correct database based on their config file).
* **DON'T** leave files named `test.php` or `debug.txt` in your local folder when zipping. Rsync will push them to every single client server on earth.
* **DON'T** manually execute `php auto_update.php` on a live server unless you temporarily bypass the API stagger check (`'is_auto' => 'false'`).