---
name: release-versioning
description: "Version bump + GitHub release workflow for ps_mbo 4.x branch. Analyzes commits since last tag, proposes patch/minor bump, checks hooks for upgrade file need, commits the bump, then creates the GitHub release with auto-generated changelog."
---

# Release Versioning Workflow (4.x branch)

Use this skill to bump the module version and publish a GitHub release from the 4.x branch.

## Step 1: Ensure clean state on 4.x

```bash
git checkout 4.x
git fetch origin
git pull origin 4.x
```

If there are uncommitted changes, stop and ask the user to commit or stash them first.

## Step 2: Identify current state

```bash
git describe --tags --abbrev=0        # e.g. v4.14.1
git log <last_tag>..HEAD --oneline --no-merges
```

Also read:
- `ps_mbo.php`: `const VERSION = '...'` and `$this->version = '...'`
- `config.xml`: `<version><![CDATA[...]]></version>`

Note the last-tag version (e.g., 4.14.1) and the current file version (may already be bumped).

## Step 3: Analyze commits and propose bump

Scan commit messages since the last tag:

| Commit type | Bump type |
|-------------|-----------|
| `feat:` or `feat(...):` | **minor** (4.Y.0) |
| `BREAKING CHANGE` in body | Refuse major, apply minor and flag it |
| `fix:`, `chore:`, `refactor:`, `docs:`, `ci:`, `perf:`, `test:` | **patch** (4.x.Z) |
| No conventional prefix | **patch** (assume patch, flag as ambiguous) |

Highest-priority rule wins: any `feat:` -> propose minor, else -> propose patch.

**Present both options to the user**, pre-selecting the recommended one. Never propose major (no 5.x from this branch).

## Step 4: Check hooks for upgrade file

Detect hook-related changes since the last tag:

```bash
git diff --name-status <last_tag>..HEAD -- src/Traits/Hooks/
git diff <last_tag>..HEAD -- ps_mbo.php | grep -E "^\+.*hook|^\-.*hook"
```

If any hook trait was added or removed, or the hooks list in `ps_mbo.php` changed:
1. Check if `upgrade/upgrade-<new_version>.php` already exists.
2. If it does not exist, propose creating it with this template:

```php
<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

$rootDir = defined('_PS_ROOT_DIR_') ? _PS_ROOT_DIR_ : getenv('_PS_ROOT_DIR_');
if (!$rootDir) {
    $rootDir = __DIR__ . '/../../../';
}

require_once $rootDir . '/vendor/autoload.php';

/**
 * @param ps_mbo $module
 *
 * @return bool
 */
function upgrade_module_<version_underscored>(Module $module): bool
{
    $module->updateHooks();

    return true;
}
```

Replace `<version_underscored>` with the new version using underscores (e.g., `4_14_2` for version `4.14.2`).

If no hook changes are detected, skip this step silently.

## Step 5: Confirm and apply the bump

Once the user confirms the target version:

**If files already have this version** (detected in Step 2):
- Say: "Files already at vX.Y.Z (bumped in commit abc1234). No new bump commit needed."
- Jump to Step 6.

**If files are still on the old version:**
1. Update `ps_mbo.php`:
   - `const VERSION = '<new_version>';`
   - `$this->version = '<new_version>';`
2. Update `config.xml`:
   - `<version><![CDATA[<new_version>]]></version>`
3. Stage and commit (include upgrade file if created in Step 4):
   ```bash
   git add ps_mbo.php config.xml
   git commit -m "chore: bump version <old_version> -> <new_version>"
   git push origin 4.x
   ```

If an upgrade file was created but the version bump was already committed separately:
```bash
git add upgrade/upgrade-<new_version>.php
git commit -m "chore: add upgrade script for v<new_version>"
git push origin 4.x
```

## Step 6: Create the GitHub release

```bash
gh release create v<new_version> \
  --title "v<new_version>" \
  --generate-notes \
  --target 4.x
```

Do NOT add `--latest` — this is a maintenance branch; the latest release remains on master/v5.

Output the release URL.

## Rules

- Never bump major (no 5.x from this branch — that is a separate codebase).
- Tag format: `v<major>.<minor>.<patch>` (e.g., `v4.14.2`). Release name = tag name.
- `--generate-notes` uses GitHub's auto-changelog from commits between the previous and new tag.
- No `--latest` flag: 4.x is a maintenance branch; v5 (master) is the canonical latest.
- If branch protection prevents direct push to 4.x: use `chore/bump-<new_version>` branch, open PR targeting `4.x`, merge, then run `gh release create` after merge.
