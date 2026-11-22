# Verification badge: implementation reference

Nov 22, 2026 · @smordziol

## Goal

Add a README badge that shows which X4: Foundations version the project was last verified against, and when. The badge must update without editing the README and must not depend on code changes.

The design has three parts:

1. A `composer verify` script that runs the build and tests against the extracted game data, then writes two JSON files.
2. A shields.io endpoint badge in the README that reads one of those files.
3. An optional scheduled GitHub workflow that turns the badge orange when the game has been patched since verification.

Plain `composer build` must not touch the status files. Otherwise every development build produces a diff and the date stops meaning "deliberately verified".

This reference targets `x4-mod-cargo-sizes`. Keep the writer class free of project-specific logic so it can move into a shared library later.

## Status files

Two files are needed because shields.io rejects endpoint JSON that contains keys outside its schema. Both live in a `verification/` folder at the repository root and are committed.

`verification/status.json` is the source of truth:

```json
{
  "gameVersion": "8.00",
  "steamBuildId": "12345678",
  "verifiedAt": "2026-11-22",
  "modVersion": "3.1.0"
}
```

| Field | Source | Rules |
| --- | --- | --- |
| `gameVersion` | The extracted game data, via the X4 Data Extractor library if it exposes the version | Required. Abort the stamp if it cannot be determined. |
| `steamBuildId` | `buildid` in the Steam app manifest of the game installation | Optional, `null` when unavailable (for example on non-Steam installs). |
| `verifiedAt` | Current date, UTC, `YYYY-MM-DD` | Required. |
| `modVersion` | `mod-version.txt` | Required. Informational only. |

The values above are placeholders, not real version numbers.

`verification/badge.json` is derived from `status.json` and follows the shields.io endpoint schema exactly:

```json
{
  "schemaVersion": 1,
  "label": "verified against X4",
  "message": "v8.00 · 2026-11-22",
  "color": "brightgreen"
}
```

Do not add other keys to `badge.json`. Write both files with pretty-printed JSON and a trailing newline so diffs stay small.

## The verify script

Add a `verify` entry to the `scripts` section of `composer.json`. It runs these steps in order and stops at the first failure:

1. Run the existing build (`composer build`).
2. Run the test suite (PHPUnit).
3. Collect the four status values.
4. Write `status.json`, then `badge.json`.

Implementation notes:

- Put the logic in one small class, for example `VerificationStamp`, with a method that takes the four values and the target folder. Game-specific lookups stay outside it.
- Write nothing if any earlier step fails. A failed verification must leave the previous stamp intact.
- A successful run always updates `verifiedAt`, even when the game version is unchanged. Re-verifying the same version is a valid event.
- Print the resulting badge message to the console so the developer sees what was stamped.
- Follow the project's existing error code convention for any new exceptions.
- Add unit tests for the writer: both files are produced, `badge.json` contains only the four schema keys, and a `null` build ID is handled.

## README badge

Place the badge directly under the H1 title in `README.md`. It links to `status.json` so readers can see the raw values.

```text
[![Verified against X4](https://img.shields.io/endpoint?url=https%3A%2F%2Fraw.githubusercontent.com%2FMistralys%2Fx4-mod-cargo-sizes%2Fmain%2Fverification%2Fbadge.json)](verification/status.json)
```

The `url` parameter must be URL-encoded. The badge can lag a commit by several minutes because raw.githubusercontent.com, shields.io and GitHub's image proxy each cache the result.

Add a short "Verification status" paragraph to the Development section of the README that explains what the badge means and that `composer verify` updates it.

## Staleness check (optional)

A scheduled workflow compares the verified Steam build ID with the current public build. When they differ, it rewrites `badge.json` in orange and commits it. It never touches `status.json`; the next `composer verify` run resets the badge to green.

Create `.github/workflows/verification-staleness.yml`. The workflow below is an untested draft:

```yaml
name: Verification staleness check

on:
  schedule:
    - cron: "0 6 * * 1"
  workflow_dispatch:

permissions:
  contents: write

jobs:
  check:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Compare build IDs
        run: |
          verified=$(jq -r '.steamBuildId // empty' verification/status.json)
          if [ -z "$verified" ]; then
            echo "No build ID recorded, skipping."
            exit 0
          fi

          current=$(curl -fsS https://api.steamcmd.net/v1/info/392160 \
            | jq -r '.data["392160"].depots.branches.public.buildid // empty')
          if [ -z "$current" ]; then
            echo "Could not read the current build ID, skipping."
            exit 0
          fi

          if [ "$current" = "$verified" ]; then
            echo "Still current."
            exit 0
          fi

          version=$(jq -r '.gameVersion' verification/status.json)
          jq -n --arg message "v$version · game updated since" \
            '{schemaVersion: 1, label: "verified against X4", message: $message, color: "orange"}' \
            > verification/badge.json

      - name: Commit badge change
        run: |
          if git diff --quiet; then
            exit 0
          fi
          git config user.name "github-actions[bot]"
          git config user.email "41898282+github-actions[bot]@users.noreply.github.com"
          git commit -am "Mark verification badge as stale"
          git push
```

A lookup failure must leave the badge unchanged, never mark it stale. GitHub disables scheduled workflows in public repositories after 60 days without repository activity, so a dormant project needs the workflow re-enabled by hand.

## Assumptions to check first

These points were not verified when this reference was written. Confirm each one before building on it, and report back if one is wrong.

- [ ] How the game version is obtained. Check whether the X4 Data Extractor library exposes it. If not, the game folder is believed to contain a `version.dat` file with a number such as `800` for v8.00.
- [ ] The Steam app ID for X4: Foundations is believed to be `392160`, which makes the manifest file `steamapps/appmanifest_392160.acf`.
- [ ] `api.steamcmd.net` is a third-party service. Confirm the endpoint and the JSON path to the public branch's `buildid` with a live request.
- [ ] Confirm against the shields.io endpoint badge documentation that unknown keys are rejected and which keys are allowed.
- [ ] Check whether `actions/checkout@v4` is still the current major version.

## Acceptance criteria

- [ ] `composer verify` writes both files after a successful build and test run, and writes nothing after a failed one.
- [ ] `composer build` leaves `verification/` untouched.
- [ ] `badge.json` validates against the shields.io endpoint schema, and the badge renders on the GitHub README page.
- [ ] The badge shows the game version and the verification date.
- [ ] The writer has unit tests, and PHPStan and PHPUnit pass.
- [ ] The README explains the badge and the `composer verify` command.
- [ ] If the staleness workflow is included: a manual run with a deliberately wrong build ID in `status.json` turns the badge orange, and a lookup failure changes nothing.
