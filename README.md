<p align="center"><a href="https://paystack.com/"><img src="https://github.com/PaystackHQ/plugin-sprout-invoices/blob/master/.paystack/assets/banner.png?raw=true" alt="Payment Forms for Paystack"></a></p>

# Paystack Gateway for Sprout Invoices

Welcome to the Paystack Gateway for Sprout Invoices repository on GitHub. 

The **Paystack Gateway for Sprout Invoices** plugin provides a payment option for the popular Client Invoicing by Sprout Invoices – Easy Estimates and Invoices for WordPress plugin.

Here you can browse the source, look at open issues and keep track of development. 

## Installation

1. Install the [Paystack Gateway for Sprout Invoices](https://wordpress.org/plugins/paystack-sprout-invoices/) via the Plugins section of your WordPress Dashboard.


## Running the sprout-invoices plugin on docker
Contained within this repo, is a dockerfile and a docker-compose file to quickly spin up a wordpress and mysql container with the paystack sprout-invoices plugin installed.

### Prerequisites
- Install [Docker](https://www.docker.com/)

### Quick Steps
- Create a `local.env` file off the `local.env.sample` in the root directory. Replace the `*******` with the right values
- Run `docker-compose up` from the root directory to build and start the mysql and wordpress containers.
- Visit `localhost:8000` on your browser to access and setup wordpress.
- Run `docker-compose down` from the root directory to stop the containers.


## Documentation
* [Paystack Documentation](https://developers.paystack.co/v1.0/docs/)
* [Paystack Helpdesk](https://paystack.com/help)

## Support
 For bug reports and feature requests directly related to this plugin, please use the [issue tracker](https://github.com/PaystackHQ/plugin-sprout-invoices/issues). 

For questions related to using the plugin, please post an inquiry to the plugin [support forum](https://wordpress.org/support/plugin/paystack-sprout-invoices).

For general support or questions about your Paystack account, you can reach out by sending a message from [our website](https://paystack.com/contact).

## Community
If you are a developer, please join our Developer Community on [Slack](https://slack.paystack.com).

## Contributing to Paystack Gateway for Sprout Invoices

If you have a patch or have stumbled upon an issue with the Paystack Gateway for Sprout Invoices plugin, you can contribute this back to the code. Please read our [contributor guidelines](https://github.com/PaystackHQ/paystack-gateway-for-sprout-invoices/CONTRIBUTING.md) for more information how you can do this.
