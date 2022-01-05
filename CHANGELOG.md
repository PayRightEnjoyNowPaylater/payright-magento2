# Change Log
All notable changes to the Payright plugin will be documented in this file.

The format is based on **Keep a Changelog** and this project adheres to Semantic Versioning (`semver`).

# How to use
In each changelog record, we use these keywords as part of the template description:
1. `recommended` - to notify plugin user that it is a `stable` plugin version, we highly recommend downloading and use.
2. `optional` - to notify plugin user that it is not necessary to download this plugin version, `optional` to download and use.

Why are we following these keywords? Our `semver` practices interpreted differently, our `minor` and `patch` versions are "mixed".

# Testing
Update this section with the latest testing information and details, of the platform / plugins tested on.

Please see README.md, for minimum WordPress & WooCommerce platform versions.

<p>PHP: ^7.2</p>
<p>Magento: ~2.0.0 | ~2.1.0 | ~2.2.0 | ~2.3.5</p>

## [2.0.3] - 2021-NN-NN
This is a `optional` release to download and update your plugin version with.

## Added
1. N/A
## Changed
1. N/A
## Fixed
1. N/A

## [2.0.2] - 2021-08-12
This is a `recommended` release to download and update your plugin version with.

## Added
1. Feature: Added new `composer.json` file. Please note, **NOT READY** for use at Magento Marketplace, see `README.md` for more information.
2. Feature: Added new plugin configuration "Display Term", to toggle between showing suggested "Weekly" or "Fortnightly" rates on Magento 2 store.
3. Feature: Defined new 'required' field validation, for `accesstoken` in `system.xml`.
## Changed
1. Refactor: Major codebase refactoring - CSS classes, phpdoc comments and HTML updates for new classes / ids.
2. Markdown: Updated `README.md` 'How to install' instructions.
## Fixed
1. Feature: Added additional field validation for `accesstoken` setting, as `required-entry`.
2. Fix: Observer OrderData invalid property naming.

## [2.0.0 - 2.0.1] - 2021-02-22
This is a `recommended` release to download and update your plugin version with.

## Changed
1. Feature: Code migration from Payright's E-Commerce v1 flow, to v2 flow - to align with Payright's upgraded API architecture for E-Commerce services.
2. Feature: Changed and added new plugin configuration fields, related to `feature` #1.
## Fixed
1. Hotfix: Fixed plugin configuration fields.

## [1.0.0] - 2021-02-08
**DO NOT USE** this release to download and update your plugin version with. It is an "unstable" version, 
and **AVOID** this release.

Proceed to immediately to install `^2.0.0` releases.