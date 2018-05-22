# PrestaShop 1.6.x Checkout setup instructions

1. Download our latest PrestaShop 1.6.x Checkout module [here](https://github.com/hipspay/prestashop-checkout-module/releases).
2. Login to admin and go to Modules and Services.
3. Press the Add a new module in the top right corner and upload [HIPS.PS.x.x.x.Checkout.zip](https://github.com/hipspay/prestashop-checkout-module/releases). 
4. Go to Modules and Services → Modules and Services → Payments and Gateways.  
5. Install Hips Checkout.
6. Press the Configure button.
7. Enter your **Public API Key** (will be found <a href="https://dashboard.hips.com/sales_channels" target="_blank">here</a>).
8. Enter your **Private API Key** (will be found <a href="https://dashboard.hips.com/sales_channels" target="_blank">here</a>).
9. Update the settings.
10. Configure your shipping methods (will be found <a href="https://dashboard.hips.com/shippings" target="_blank">here</a>).
11. ==All done!==
12. (**optional**) If you want to accept Paypal, Invoice etc you may do that by <a href="https://dashboard.hips.com/account/relay" target="_blank">connecting those to your HIPS account</a>

## Contributing

If you want to contribute to a Hips project and make it better, your help is very welcome. Contributing is also a great way to learn more about social coding on Github, new technologies and and their ecosystems and how to make constructive, helpful bug reports, feature requests and the noblest of all contributions: a good, clean pull request.

### How to make a clean pull request

- Create a personal fork of the project on Github.
- Clone the fork on your local machine. Your remote repo on Github is called `origin`.
- Add the original repository as a remote called `upstream`.
- If you created your fork a while ago be sure to pull upstream changes into your local repository.
- Create a new branch to work on! Branch from `develop` if it exists, else from `master`.
- Implement/fix your feature, comment your code.
- Follow the code style of the project, including indentation.
- If the project has tests run them!
- Write or adapt tests as needed.
- Add or change the documentation as needed.
- Squash your commits into a single commit with git's [interactive rebase](https://help.github.com/articles/interactive-rebase). Create a new branch if necessary.
- Push your branch to your fork on Github, the remote `origin`.
- From your fork open a pull request in the correct branch. Target the project's `develop` branch if there is one, else go for `master`!
- ...
- Once the pull request is approved and merged you can pull the changes from `upstream` to your local repo and delete
your extra branch(es).

And last but not least: Always write your commit messages in the present tense. Your commit message should describe what the commit, when applied, does to the code – not what you did to the code.

