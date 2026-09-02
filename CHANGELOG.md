# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.4.0] - 2026-09-02

### Added

- Add the optional hash-gated Winter exception view with per-user editor links and path mapping.
- Add `Settings::getNotificationRecipient()` as the canonical email/name contract for future Xitara system notifications.
- Add Nexus-owned locale time-zone storage that becomes available regardless of when optional Winter.Translate is installed.
- Add a guarded migration for Nexus-marked backend-user deletion requests.
- Permanently purge only Nexus self-deactivated backend users after the documented 14-day retention period.

### Changed

- Require PHP 8.2 or newer and Winter CMS 1.2 or newer.
- Align the Composer installer constraint with the version required by Winter CMS 1.2.
- Render Winter's original report-widget dashboard lifecycle and preference context inside the Nexus side menu while retaining the permission-based rich-text fallback.
- Build custom menu groups directly in the aggregator and use `xitara.nexus.custommenu.<slug>` as their canonical permission.
- Build the compact backend stylesheet as a supported Webpack entry point.
- Move active browser sources from JavaScript to strict TypeScript and modernize the related build and lint configuration.
- Align npm version, license, and repository metadata with the Winter plugin release and source repository.
- Update the canonical repository metadata after moving the project to the Xitara-SoftWerX GitHub organization.
- Replace obsolete implementation-history comments and inactive backend-user hooks with current intent-focused documentation.
- Update the project documentation to reflect the supported runtime baseline.
- Exclude published migration files from automatic Prettier rewrites.

### Deprecated

- Retain external `injectSideMenu()`, `Plugin::getSideMenu()`, `Plugin::getMenuOrder()`, `::hidden`, and reflection discovery for the 2.4 transition release; removal is planned for 3.0.
- Deprecate the unconsumed global PHP helpers and `/xitara/nexus/jsvars.js`; retain them through the 2.x line for unknown external consumers.

### Removed

- Remove the unregistered Nexus Twig filter copy and its Composer libraries; `Xitara.TwigExtender` is now the sole implementation.
- Remove the unused Nexus Font Awesome and PWA components and PWA/service-worker build entries.
- Remove the FakeBlog, FakeUser, and progress-test console commands plus the every-minute Docker diagnostic scheduler callback.
- Drop the unused `xitara_nexus_configs` table through a new migration when it exists.

### Fixed

- Restore Winter's switch styling on the menu-sorting page by using its expected anchor element for `.slide-button`.
- Point users from the global exception-view switch to the per-user editor settings in Winter's backend preferences.
- Preserve legacy namespace-based custom-menu permission keys as temporary aliases while applying the registered canonical permission.
- Use the actual account-deletion partial, restrict permanent cleanup to explicitly marked self-deletions, and cancel that cleanup when an account is restored.
- Deny dashboard report-container AJAX requests when the user lacks the Nexus dashboard permission.

[Unreleased]: https://github.com/Xitara-SoftWerX/wn-nexus-plugin/compare/v2.4.0...HEAD
[2.4.0]: https://github.com/Xitara-SoftWerX/wn-nexus-plugin/releases/tag/v2.4.0
