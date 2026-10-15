---
name: pr
description: Draft and open a pull request for the current branch, or update its description after new commits, with a human-readable body (TL;DR + collapsible sections) and a session log for the AI reviewer. Use when the user says "/pr", "ouvre la PR", "crée la PR", "mets à jour la PR", "open a PR", "update the PR", "prépare la description de PR".
---

# /pr

Writes the PR body in `.github/pull_request_template.md` format, shows it to the user, and publishes it only after they approve it. If the branch already has a PR, updates its description with what changed since the last run.

## The rule: if there's no source, leave it out

Every statement in the body needs one of these sources:

| Source | Example |
|---|---|
| **diff** | `git diff origin/<base>...HEAD` |
| **session** | a command run in this conversation whose output you saw, or a decision discussed in it |
| **ticket** | the Jira ticket, read in this session with `getJiraIssue` |
| **user** | something the user said in this conversation |

Without a source, you **do not write it**. You don't extrapolate, you don't add something plausible to fill a gap, and you don't write "N/A". A section with no sourced content is **removed**.

What this means in practice:
- **Business impact**: only from the ticket or the user. Never inferred from the code.
- **Tests ✅**: only a command run **in this session** whose output you saw, with its actual result and the short SHA it ran on: `(at abc1234)`. A test that exists but was not run is ❌.
- **Ticket**: only if it appears in the branch name, the commits or the user's messages. Never guess a number.
- **Session log**: only what happened in **this** conversation. Was the code written in another session? Then there is no session log: remove the section. No reconstruction from the diff.
- **Checklist**: remove the items that don't apply to this PR (e.g. PHP items on a PR without PHP). An item is checked only if it was verified in this session. Otherwise, leave it unchecked.

## Run marker

The body always ends with this visible line, rewritten on every run:

```
<sub>/pr last run: <YYYY-MM-DD> on [<short SHA>](https://github.com/<owner>/<repo>/commit/<full HEAD SHA>)</sub>
```

It tells reviewers which commit the description matches, and tells a later run which commits it has not described yet (the full SHA is in the link).

## Steps

1. **Target branch**: the one the user names, otherwise the repo default (`gh repo view --json defaultBranchRef --jq .defaultBranchRef.name`). Call it `<base>`. If the current branch is `<base>`, stop and tell the user.
2. **Up to date**: `git fetch origin <base>`, then `git rev-list --count HEAD..origin/<base>`. If it is not 0, the branch is behind its target: tell the user and ask them how to update it (rebase or merge). Never update it without their approval.
3. **Existing PR?** `gh pr view --json number,url,body,title`. If there is one, go to **Update mode** below. Otherwise continue.
4. **Collect** (in parallel), always against `origin/<base>`, never a local branch: `git log origin/<base>..HEAD --oneline`, `git diff origin/<base>...HEAD --stat`, `git diff origin/<base>...HEAD`, `git status --short`. If uncommitted changes remain, flag them and ask whether they belong in the PR.
5. **Ticket**: look for `SM-\d+` (or another Jira key) in the branch name, the commits, then the conversation. If you find one, read it with `getJiraIssue` (Atlassian MCP) to source the business impact.
6. **Draft** the body by filling in `.github/pull_request_template.md`:
   - TL;DR: 3 lines max. What changes, for whom. No "how".
   - Tech: the behavior changes and design choices, not the list of files (GitHub shows it).
   - Tests: ✅ verified (command + result + SHA) / ❌ not verified (what and why, if known).
   - Session log: decisions with the rejected alternative, human corrections, assumptions (✅ verified / ❓ not verified), deliberately out of scope, dead ends (only those that save someone from retrying them).
   - Title: Conventional Commits with the ticket key, as in recent PRs (`gh pr list --state merged --limit 10 --json title`): `type(SM-XXX): description`. No ticket found: `type: description`.
   - Public repo: no internal URLs other than the Jira link, no customer data, no secrets in the body or the session log.
   - The run marker as the last line.
7. **Source check**: reread each line of the draft and ask "where does this come from?". No answer, delete the line. Then delete the empty sections.
8. **Style**: everything in English (title, body, session log), even if the conversation is in another language. No adjectives ("robust", "comprehensive", "seamless", "clean"), no em-dashes, no sentence that restates the diff.
9. **Show** the title and body to the user. Wait for explicit approval.
10. **Publish** once approved: `git push -u origin <branch>` if needed, then `gh pr create --base <base> --title "..." --body-file <file in the scratchpad>`. Add the attribution line given by the conversation's system-reminder, if there is one, just above the run marker. Return the PR URL.

## Update mode

1. Read `last-run` (the full SHA in the marker link) from the current body.
   - `git merge-base --is-ancestor <last-run> HEAD` succeeds: the new work is `git log <last-run>..HEAD` and `git diff <last-run>..HEAD`.
   - No marker: the body was not written by `/pr` and does not follow the template. Rewrite it in full from the template (steps 4 to 9), using the full diff against `origin/<base>`. The old body is only a source: keep a claim from it only after checking it against the diff, and drop what you cannot check.
   - The SHA is no longer an ancestor (rebase, force push): say so to the user, and use the full diff against `origin/<base>`.
   - `last-run` equals HEAD: nothing new in the code. Only add what this session brings (tests run, decisions), if anything.
2. With a marker, edit only what the new commits or this session change. Keep the rest of the body as it is: it was approved before, and you don't have the context it was written in.
   - TL;DR, Tech, Docs, Checklist: amend to match the code at HEAD. Remove what the new commits made false.
   - Tests: keep existing lines with their SHA. Add the tests run in this session, at HEAD.
   - Session log: never rewrite previous entries. Add a `**Update <YYYY-MM-DD> (<short SHA>)**` sub-block with this session's decisions, corrections and assumptions. No session context for the new commits? Add nothing.
   - Rewrite the run marker.
3. Apply the source check and the style rules (steps 7 and 8) to every changed line.
4. Show the user the changed lines only, then wait for approval.
5. Push if needed, then `gh pr edit <number> --body-file <file in the scratchpad>`. Return the PR URL.

## Formatting `<details>`

GitHub renders the Markdown inside `<details>` only if there is **a blank line** after `</summary>` and before `</details>`. Keep those blank lines.
