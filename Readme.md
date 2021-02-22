# This is a fork of punktde/codeception-mailhog

## Module functions to test using Mailhog

### How to use

### Prequesits

You have to have Mailhog installed and have your application configured to send mails to mailhog. See https://github.com/mailhog/MailHog

#### Module

Use the module `Surplex\Codeception\Mailhog\Module\Mailhog` in your `codeception.yaml`. You can configure under which uri the mailhog client is reachable (default is http://127.0.0.1:8025)

```
modules:
   enabled:
      - Surplex\Codeception\Mailhog\Module\Mailhog:
        base_uri: http://mailhog.project
```






