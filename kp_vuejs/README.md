# KP Vue.js

Provides Vue.js, vue-tel-input, and Axios libraries for KP modules in Drupal 10 and 11.

## Features

Provides Vue.js 2 for reactive frontend components.
Includes vue-tel-input for international phone number input with validation.
Provides Axios for HTTP requests in JavaScript.
Designed as a dependency for other KP modules (e.g., KP Zadarma).


## Requirements
- Drupal 10.3 or 11
- PHP 8.1+

## Installation

1. Install the module and enable it.
2. Install Vue.js (version 2.7.16 or later) frontend library manually:

* Download from unpkg: https://app.unpkg.com/@vue/compiler-sfc@2.7.16
* Unzip and place [dist/vue.min.js in /libraries/vue/dist/vue.min.js].

3. Install Axios (version 0.19.2) frontend library manually:

* Download from GitHub: https://github.com/axios/axios/archive/refs/tags/v0.19.2.zip
* Unzip and place [dist/axios.min.js in /libraries/axios/dist/axios.min.js].


## Usage
This module is intended as a dependency for other KP modules (e.g., KP Zadarma). Do not place blocks or configure it directly. Use the libraries in your module's *.libraries.yml:
```text
my-module-vue:
  js:
    js/my-module-vue.js: {}
  dependencies:
    - kp_vuejs/vuejs
    - kp_vuejs/axios
    - kp_vuejs/vue-tel-input
```

## License
GPL-2.0-or-later
