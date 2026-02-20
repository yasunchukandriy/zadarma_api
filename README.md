# 📞 Zadarma API — Drupal Module

> Drupal module that integrates the **Zadarma callback API** with a **Vue.js-powered** phone input component.

[![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Drupal](https://img.shields.io/badge/Drupal-0678BE?style=flat-square&logo=drupal&logoColor=white)](https://drupal.org)
[![Vue.js](https://img.shields.io/badge/Vue.js-4FC08D?style=flat-square&logo=vue.js&logoColor=white)](https://vuejs.org)
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)

---

## 📋 Overview

This Drupal module integrates **Zadarma** cloud telephony into any Drupal-based website. Users enter their phone number via an interactive Vue.js component, and Zadarma automatically initiates a callback connecting the operator with the customer.

**How it works:**
1. User enters a phone number via the Vue.js input form
2. Drupal sends a request to the Zadarma API
3. Zadarma connects the operator with the client via callback

---

## 🗂️ Project Structure

```
zadarma_api/
├── kp_zadarma/        # Drupal module (PHP) — Zadarma API integration
└── kp_vuejs/          # Vue.js component — interactive phone input
```

---

## ⚙️ Tech Stack

| Layer | Technology |
|---|---|
| CMS | Drupal |
| Backend | PHP |
| Frontend | Vue.js, JavaScript |
| Styling | CSS |
| API | Zadarma Callback API |

---

## 🚀 Installation

### 1. Drupal Module

```bash
# Copy kp_zadarma into your Drupal custom modules folder
cp -r kp_zadarma /var/www/html/modules/custom/

# Enable via Drush
drush en kp_zadarma -y

# Or via admin panel:
# Admin → Extend → find "KP Zadarma" → enable
```

### 2. API Configuration

Navigate to the module settings:
```
Admin → Configuration → KP Zadarma Settings
```

Enter your credentials from the Zadarma dashboard:
- **API Key**
- **API Secret**
- **Callback phone number**

### 3. Vue.js Component

```bash
cd kp_vuejs
npm install
npm run build
```

---

## 🔧 Requirements

- Drupal **8.x / 9.x / 10.x / 11.x**
- PHP **7.4+**
- Node.js **14+** (for Vue.js build)
- Active account at [zadarma.com](https://zadarma.com)

---

## 📖 Zadarma API Docs

[zadarma.com/en/support/api](https://zadarma.com/en/support/api/)

---

## 👤 Author

**Andrii Yasynchuk** — Senior Full Stack Developer
[![LinkedIn](https://img.shields.io/badge/LinkedIn-0077B5?style=flat-square&logo=linkedin&logoColor=white)](https://www.linkedin.com/in/andriy-yasunchuk-750028197/)
[![GitHub](https://img.shields.io/badge/GitHub-100000?style=flat-square&logo=github&logoColor=white)](https://github.com/yasunchukandriy)
