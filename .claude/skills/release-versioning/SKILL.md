---
name: release-versioning
description: "Version bump + GitHub release workflow for ps_mbo. Analyzes commits since last tag, proposes patch/minor bump, checks hooks for upgrade file need, commits the bump, creates the GitHub release with auto-generated changelog, then produces a short non-technical changelog for the Addons Marketplace."
---

# Release Versioning Workflow

Use this skill to bump the module version and publish a GitHub release.

## Step 1: Ensure clean state on master

```bash
git checkout master
git fetch origin
git pull origin master
```

If there are uncommitted changes, stop and ask the user to commit or stash them first.

## Step 2: Identify current state

```bash
git describe --tags --abbrev=0        # e.g. v5.2.2
git log <last_tag>..HEAD --oneline --no-merges
```

Also read:
- `ps_mbo.php`: `const VERSION = '...'` and `$this->version = '...'`
- `config.xml`: `<version><![CDATA[...]]></version>`

Note the last-tag version (e.g., 5.2.2) and the current file version (may already be bumped).

## Step 3: Analyze commits and propose bump

Scan commit messages since the last tag:

| Commit type | Bump type |
|-------------|-----------|
| `feat:` or `feat(...):` | **minor** (x.Y.0) |
| `BREAKING CHANGE` in body | Refuse major, apply minor and flag it |
| `fix:`, `chore:`, `refactor:`, `docs:`, `ci:`, `perf:`, `test:` | **patch** (x.y.Z) |
| No conventional prefix | **patch** (assume patch, flag as ambiguous) |

Highest-priority rule wins: any `feat:` -> propose minor, else -> propose patch.

**Present both options to the user**, pre-selecting the recommended one. Never propose major.

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

Replace `<version_underscored>` with the new version using underscores (e.g., `5_2_3` for version `5.2.3`).

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
   git push origin master
   ```

If an upgrade file was created but the version bump was already committed separately:
```bash
git add upgrade/upgrade-<new_version>.php
git commit -m "chore: add upgrade script for v<new_version>"
git push origin master
```

## Step 6: Create the GitHub release

```bash
gh release create v<new_version> \
  --title "v<new_version>" \
  --generate-notes \
  --target master
```

Output the release URL.

## Step 7: Produce the Marketplace changelog

The GitHub auto-notes are too technical for the Addons Marketplace. Generate a short,
merchant-facing changelog from the same commits analyzed in Step 2.

Rules for the Marketplace changelog:
- English, plain language, no internal/technical terms (no class names, no PHP version
  internals unless merchant-relevant, no refactor/CI/chore noise).
- Group commits by merchant-visible benefit, not by commit type.
- 2 to 4 bullet points max. Collapse internal-only work (refactor, ci, chore, build,
  test, deps) into a single line like "Stability, performance, and security improvements."
- Keep `feat:`/`fix:` user-facing changes as their own bullets, reworded as benefits.

Output it in a fenced block titled `What's new in <new_version>` so it can be pasted
directly into the Marketplace. Example shape:

```
What's new in <new_version>

- <feature reworded as a merchant benefit>
- <user-facing fix reworded>
- Stability, performance, and security improvements.
```

## Rules

- Never bump major. If breaking changes detected, apply minor and note it.
- Tag format: `v<major>.<minor>.<patch>` (e.g., `v5.2.3`). Release name = tag name.
- `--generate-notes` uses GitHub's auto-changelog from commits between the previous and new tag.
- If branch protection prevents direct push to master: use `chore/bump-<new_version>` branch, open PR, merge, then run `gh release create` after merge.
