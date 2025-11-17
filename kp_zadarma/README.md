# KP Zadarma

Integrates the Zadarma callback API with a Vue.js-powered phone input for Drupal 10 and 11.

## Features
- Provides a configurable block with a Vue.js-based phone input for initiating Zadarma callbacks.
- Provides a REST API endpoint for receiving Zadarma callbacks.
- Validates phone numbers using libphonenumber via a dedicated PhoneNumberValidator service.
- Supports time-based display rules for weekdays and weekends.
- Built with Vue 2 and vue-tel-input for a modern frontend experience.

## Requirements
- Drupal 10.3 or 11
- PHP 8.1+
- Composer dependencies:
  - zadarma/user-api-v1
  - giggsey/libphonenumber-for-php
- Front-end libraries:
  - Vue.js 3.4.0+
  - Axios 0.19.2+

## Installation
1. Install via Composer:
```bash
composer require drupal/kp_zadarma:1.0.0
composer require giggsey/libphonenumber-for-php
```
2. Enable the module:
```bash
drush en kp_zadarma
drush cr
```

## Configuration
Navigate to the module settings page:
```text
/admin/config/content/kp_zadarma_settings
```
On this page you must fill in all required fields:

#### API key
Your Zadarma API key.
Available in your Zadarma personal account.

#### API secret
Your Zadarma API secret used to sign requests.

#### API parameter "from"
The phone number or caller ID used when initiating outgoing callbacks (e.g., a company number).

#### Predicted mode
A boolean option enabling or disabling Zadarma's "predicted" parameter for call routing.


## REST API Configuration
Enable required permissions, go to:
```text
/admin/people/permissions
```
And make sure to enable:
- Access Zadarma callback REST resource


## Displaying the Callback Block
The module provides a reusable block with a Vue.js-powered phone input.
To display it:
1. Go to "/admin/structure/block".
2. Find block: "KP Zadarma Block".
3. Click Place block.
4. Select the region (e.g. Sidebar, Content, Footer) and press Save block.

## License
GPL-2.0-or-later
