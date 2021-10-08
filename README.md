# Friendly Captcha anti-spam plugin for Joomla!
Register at https://friendlycaptcha.com to get your site and secret keys.

## Plugin Features
- Standard light and dark themes;
- CDN support;
- Option to load polyfills for older browsers;
- Supports 11 built-in languages (English, French, German, Italian, Dutch, Portuguese, Spanish, Catalan, Danish and Japanese);
- Supports Dedicated EU Endpoint (requires Friendly Captcha Business or Enterprise plan) with global endpoint as fallback.

## System Requirements
- Joomla! 3.8 or higher (4.0 is supported);
- PHP 5.3.10 or higher (8.0 is supported);
- OpenSSL with TLS 1.2 support.
- One of the following HTTP transports in PHP: cURL, `fsockopen()` or `fopen()` with `allow_url_fopen` optin enabled. 

## Troubleshooting
Q: Captcha doesn't protect from bots.

A: Most likely PHP doesn't have OpenSSL extension enabled or OpenSSL version doesn't support TLS 1.2. Enable Joomla! debug mode to get more information on validation failure. If system requirements are met, another issue could be that Captcha API service is down. To prevent form submission in such case, enable `Strict Mode` option in plugin configuration.
