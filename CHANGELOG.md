# Changelog

All notable changes to `aaix/laravel-islands-search` will be documented in this file.

## [1.0.12] - 2026-10-28

### Fixed

- Hits whose title or subtitle wraps to a second line overlapped the next row. The built-in row
  had a fixed height of 44px; it now starts at 44px and grows with its text. One-line hits keep
  their height.
- Arrow keys moved the active row out of the list's visible area. The active row now scrolls fully
  into view, whatever the height of the rows around it.
