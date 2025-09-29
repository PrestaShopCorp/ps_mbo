# PrestaShop Marketplace in your Back Office (MBO)

![PHP tests](https://github.com/PrestaShopCorp/ps_mbo/workflows/PHP%20tests/badge.svg)
![Build & Release draft](https://github.com/PrestaShopCorp/ps_mbo/workflows/Build%20&%20Release%20draft/badge.svg)
[![GitHub license](https://img.shields.io/github/license/PrestaShopCorp/ps_mbo)](https://github.com/PrestaShopCorp/ps_mbo/LICENSE.md)

## Table of Contents

- [About](#about)
- [Version Compatibility](#version-compatibility)
- [Installation](#installation)
- [Troubleshooting](#troubleshooting)
- [Requirements](#requirements)
- [Reporting issues](#reporting-issues)
- [Multistore compatibility](#multistore-compatibility)
- [Tools and helpers](#tools-and-helpers)
- [Translations](#translations)
- [Contributing](#contributing)
- [License](#license)

## About

The module **ps_mbo** is the PrestaShop modules marketplace within your shop Backoffice.

It is responsible for :

- Displaying the module catalogue
- Actions on modules : install, uninstall, upgrade, ...
- Linking your backoffice to purchases made on Addons

## Version Compatibility

**Important**: Make sure to download the module version that matches your PrestaShop version.

| Module Version | Compatible PrestaShop |
|----------------|-----------------------|
| v5.x           | 9.x                   |
| v4.x           | 8.x                   |
| v3.x           | 1.7.7 & 1.7.8         |

> **Technical Note**: This segmentation is necessary due to strong dependencies between the module, PrestaShop core, and Symfony version changes that significantly impact compatibility.

**Download Options**:
- **Addons Marketplace**: The module is also available on the [PrestaShop Addons marketplace](https://addons.prestashop.com/en/marketplace-builder/39574-prestashop-marketplace-in-your-back-office.html). When specifying your shop version during download, the platform will automatically provide the latest compatible version.
- **Manual Verification**: You can also check the `ps_mbo.php` file and verify the `ps_versions_compliancy` array in the version tag to confirm compatibility.

## Installation

You can install it manually by downloading the latest release on https://github.com/PrestaShopCorp/ps_mbo/releases and uploading it through the Module manager page of your Backoffice.

**Note :** An admin user (Prestashop Marketplace) is created when installing the module. This user is mandatory to allow the module to be callable by the external API.

## Troubleshooting

### Update Issues

During module updates, you may encounter various errors or unexpected behaviors. This typically occurs because the ps_mbo module is responsible for updating itself, which creates a technical challenge: the module remains loaded in PHP's cache/memory during the entire update process execution within a single PHP thread. This can lead to conflicts between the old cached version and the new files being deployed.

### Common Symptoms

One of the most frequent issues is:
- The update displays an error message such as "The ps_mbo module has not been updated"
- Or the update appears to succeed with a success message
- **But** the module still shows as requiring an update in the Module Manager

In this specific case, the issue is often resolved by **simply clicking the Update button again**. Due to cache conflicts, the update scripts may need to run a second time with the newly cached files to complete successfully.

If clicking Update again doesn't resolve the issue, or if you encounter other problems, try the following solutions in order:

#### 1. Clear Shop Cache

**Via Back Office:**
- Go to **Advanced Parameters > Performance** in your PrestaShop back office
- Click **Clear cache** to flush all cached files
- Test the module functionality

**Alternative (Developer method):**
- Delete all contents under `/var/cache/` folder in your PrestaShop installation
- Load any page in your back office to trigger cache regeneration
- Test the module functionality

#### 2. Reinstall via Interface
- Go to **Modules > Module Manager**
- Find the **PrestaShop Marketplace in your Back Office** module
- Click **Uninstall**
- Click **Install** to reinstall the module
- Test the module functionality

#### 3. Complete Manual Reinstallation
If the previous solutions don't work:
- Delete the entire `/modules/ps_mbo/` folder from your PrestaShop installation
- Clear the shop cache (see step 1)
- Load any page in your back office to trigger cache regeneration
- Download the latest compatible version from the [releases page](https://github.com/PrestaShopCorp/ps_mbo/releases) (refer to the [Version Compatibility](#version-compatibility) section)
- Install the module via **Modules > Module Manager > Upload a module**

> **Note**: In most cases, solution #1 or #2 will resolve the issue. Complete manual reinstallation should only be used as a last resort.

## Requirements

To be fully functional, MBO requires

- [ps_accounts][ps_accounts]
- **Your server must be callable by external referrers.** This is needed to perform actions on modules in your backoffice (install, upgrade, auto-upgrade, ...)

## Reporting issues

You can report issues in the module's repository. [Click here to report an issue][report-issue].

## Multistore compatibility

This module is compatible with the multistore :heavy_check_mark:

Once installed it's available whatever the shop context

## Tools and helpers

MBO provides some tools for developers to gather informations on modules

- [Update infos][tools-update-infos]

## Translations

See [translations-docs][translations-docs]

## Contributing

MBO is a closed-source software own by the [PrestaShop company][prestashop].

But everyone is welcome and even encouraged to contribute with their own improvements!

PrestaShop company is the only decision maker to integrate or not a contribution.

Just make sure to follow our [contribution guidelines][contribution-guidelines].

To contribute, you'll need to run the project locally :

- Fork this repository
- Create a branch from the version of MBO you want to patch
  - You are a PrestaShop employee :
    - Install [MBO API and Vue server][mbo-api-and-vue] and follow instructions in the readme
    - On MBO root folder, copy .env.dist to .env and replace the values to the ones matching your environment
  - You are an external contributor :
    - Get the .env file from the last released module.
- Package and install your module to your PrestaShop local shop
- Make your changes and push to the branch on your fork
- Create a pull request on the module's project (target the patched branch)
- Wait for one of the core developers either to include your change in the codebase, or to comment on possible improvements you should make to your code.

Please respect the [coding standards][coding-standards]

**TIPS** : To ease your development phase, you can modify the module directly on the shop sources and then copy modifications to your fork.


## License

This module is released under the [Academic Free License 3.0][AFL-3.0]

[report-issue]: https://github.com/PrestaShopCorp/ps_mbo/issues/new
[prestashop]: https://www.prestashop.com/
[ps_accounts]: https://github.com/PrestaShopCorp/ps_accounts
[contribution-guidelines]: https://devdocs.prestashop.com/9/contribute/contribution-guidelines/project-modules/
[mbo-api-and-vue]: https://github.com/PrestaShopCorp/mbo.prestashop.com
[coding-standards]: https://devdocs.prestashop.com/9/development/coding-standards/
[AFL-3.0]: https://opensource.org/licenses/AFL-3.0
[translations-docs]: docs/translations.md
[tools-update-infos]: docs/tools-update-infos.md
