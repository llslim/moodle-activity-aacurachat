# AACURA Chatbot Activity Module (`mod_aacurachat`)

This repository contains the user-facing **Moodle Activity Module** for **AACURA** (AAC Understanding & Reflective Assistant). It provides the interactive training simulator UI where students practice clinical communication skills (LAFF Strategy) by roleplaying with realistic simulated personas.

---

## 🌟 Activity Features

### 1. 🎛️ Per-Activity Customization & Settings
Instructors can customize each chat activity instance when adding or editing it in a course:
* **Active Roleplay Persona**: Select from built-in personas (*Anna Charles*, *Brianna Mitchell*, *Cathy Fratner*, *Mary*) or any custom persona registered site-wide.
* **Direct Custom JSON Scenario Upload**: Attach an activity-specific `.json` scenario file directly using the Moodle filepicker.
* **Custom Scenario Builder Launcher**: Direct shortcut button in the activity settings form that opens the interactive Scenario Builder.
* **Minimum Turn Count Override (`min_turns`)**: Select the minimum number of student turns (4–20 turns, or "Use site default (8)") required before the conversation is graded. Reaching a resolution early continues dialogue until this minimum is satisfied.
* **Parent Assertiveness Override (`parent_intensity`)**: Set persona assertiveness/aggressiveness for the activity (*Very Low*, *Low*, *Medium*, *High*, *Very High*, or site default).

### 2. 💬 Trainee Chat Interface
* **Realistic Dialogue Simulation**: Dynamic conversational exchanges with context-aware, non-repeating persona responses.
* **Persona Switcher Dropdown**: Allows students to switch personas mid-session or replay with different communication profiles (if enabled).
* **Light / Dark Mode**: Built-in high-contrast theme toggle accessible directly in the chat panel.
* **Voice / Microphone Input**: Speech-to-text audio input option alongside standard typing.
* **Clear & Restart**: Reset button allowing students to start a fresh attempt at any time.
* **Accessible PDF Export**: Generates a clean, formatted PDF transcript containing the complete dialogue and rubric feedback report.
* **AI-Driven Scenario Builder Mode**: Instructors with `local/aacuracore:manage` capability can activate an interviewer mode in the chat interface to conversationally create new scenarios.

### 3. 📊 Moodle Gradebook Integration
* When a student finishes their dialogue (meeting minimum turn count requirements), the evaluation engine grades their application of the LAFF framework against per-state rubric criteria.
* Grades and feedback are automatically published to the Moodle Gradebook.

---

## 📥 Installation & Setup Guide

### Step 1: Install Core Engine (`local_aacuracore`)
Before installing the activity module, install the companion backend engine plugin:
* **Repository**: [llslim/moodle-plugin-aacuracore](https://github.com/llslim/moodle-plugin-aacuracore)
* **Path**: `local/aacuracore`

### Step 2: Install Activity Module (`mod_aacurachat`)

#### Option A: Install via Composer (Recommended)
Add the repository to your Moodle project's root `composer.json` and require it:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/llslim/moodle-activity-aacurachat.git"
    }
],
"require": {
    "llslim/moodle-activity-aacurachat": "dev-main"
}
```

Then run:
```bash
composer update
```

#### Option B: Manual Installation
1. Download the latest release or ZIP from [llslim/moodle-activity-aacurachat](https://github.com/llslim/moodle-activity-aacurachat).
2. Install via Moodle Administration:
   ```
   Site Administration → Plugins → Install Plugins → Install plugin from ZIP file
   ```
   Or clone directly into Moodle's `mod/` directory:
   ```bash
   git clone https://github.com/llslim/moodle-activity-aacurachat.git mod/aacurachat
   ```

---

## 🔄 Upgrading from Legacy `mod_geniai`

For sites migrating existing course activities created with legacy `mod_geniai`, run the core migration tool:

```bash
php local/aacuracore/cli/migrate_geniai_to_aacura.php
```

This tool automatically renames activity tables, updates course module instance IDs, and updates gradebook links.

For existing installations upgrading to version 2.x, run the turn schema migration utility to update `aacurachat` table columns and module versions:

```bash
php local/aacuracore/cli/migrate_turns_schema.php
```

---

## 🏷️ Version History & Git Tagging Log

For the semantic version log of the activity plugin commits mapped according to our tagging policy, see **[version_history.md](version_history.md)**.
