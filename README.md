# PrestaShop Marketplace in your Back Office (MBO)

![PHP tests](https://github.com/PrestaShopCorp/ps_mbo/workflows/PHP%20tests/badge.svg)
![Build & Release draft](https://github.com/PrestaShopCorp/ps_mbo/workflows/Build%20&%20Release%20draft/badge.svg)
[![GitHub license](https://img.shields.io/github/license/PrestaShopCorp/ps_mbo)](https://github.com/PrestaShopCorp/ps_mbo/LICENSE.md)

## About

The module **ps_mbo** is the PrestaShop modules marketplace within your shop Backoffice.

It is responsible for :

- Displaying the modules catalogue
- Actions on modules : install, uninstall, upgrade, ...
- Linking your backoffice to Addons

## Installation

MBO is integrated by default on PrestaShop Edition versions.

You can install it manually by downloading the latest release on https://github.com/PrestaShopCorp/ps_mbo/releases and uploading it through the Module manager page of your Backoffice.

## Requirements

To be fully functional, MBO requires

- [ps_accounts][ps_accounts]
- Your server must be callable by external referrers. This is needed to perform actions on modules in your backoffice (install, upgrade, auto-upgrade, ...)

## Reporting issues

You can report issues in the module's repository. [Click here to report an issue][report-issue].

## Multistore compatibility

This module is compatible with the multistore :heavy_check_mark:

Once installed it's available whatever the shop context

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
- Install [MBO API and Vue server][mbo-api-and-vue] and follow instructions in the readme
- On MBO root folder, copy .env.dist to .env and replace the values to the ones matching your environment
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
[contribution-guidelines]: https://devdocs.prestashop.com/1.7/contribute/contribution-guidelines/project-modules/
[mbo-api-and-vue]: https://github.com/PrestaShopCorp/mbo.prestashop.com
[coding-standards]: https://devdocs.prestashop.com/1.7/development/coding-standards/
[AFL-3.0]: https://opensource.org/licenses/AFL-3.0
[translations-docs]: docs/translations.md
