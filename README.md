# WebDevStudios Headless WordPress <!-- omit in toc -->

Turn WordPress into a headless CMS. Used as the "backend" for our [Next.js WordPress Starter](https://github.com/webdevstudios/nextjs-wordpress-starter).

<a href="https://webdevstudios.com/contact/"><img src="https://webdevstudios.com/wp-content/uploads/2018/04/wds-github-banner.png" alt="WebDevStudios. Your Success is Our Mission."></a>

---

- [Contributing](#contributing)
- [Install](#install)
- [Development](#development)
  - [Git Workflow](#git-workflow)
  - [Deployments](#deployments)
  - [Code Linting](#code-linting)
  - [Tips to help your PR get approved](#tips-to-help-your-pr-get-approved)

## Contributing

Before submitting an issue or making a feature request, please search for existing [issues](https://github.com/WebDevStudios/wds-headless-wordpress/issues).

If you do file an issue, be sure to fill out the report completely!

---

## Setup

Clone the repo into a fresh WordPress installation. This repo will replace `/wp-content`

```bash
git clone https://github.com/WebDevStudios/wds-headless-wordpress.git wp-content
```

See the [Backend Setup wiki](https://github.com/WebDevStudios/nextjs-wordpress-starter/wiki/Backend-Setup) for full setup instractions.

## Development

### Git Workflow

1. Create a `feature` branch off `main`
2. Work locally adhering to coding standards
3. When ready, open a draft Pull Request on Github
4. Merge your code into `develop` and test on WP Engine
5. When finished, fill out the PR template and publish your PR
6. Your PR must pass assertions and deploy successfully
7. After peer review, the PR will be merged back into `main`
8. Repeat ♻️

### Deployments

There are two primary branches:

1. `develop` auto-deploys to [WPE Develop](https://nextjsdevstart.wpengine.com/wp-admin/).
2. `main` manual deploys to [WPE Production](https://nextjs.wpengine.com/wp-admin/).

Releases to production are only handled through WP Engine's "copy environment" feature at the end of a sprint.

### Code Linting

This project uses PHPCS via Composer to enforce standards both [WebDevStudios](https://github.com/WebDevStudios/php-coding-standards) and [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).

Lint PHP with phpcs:

```bash
composer run lint
```

Format PHP with phpcbf:

```bash
composer run format
```

Check compatability:

```bash
composer run compat
```

### Tips to help your PR get approved

1. Make sure your code editor supports real-time linting and has the PHPCS extension installed
2. [PHP DocBlocks](https://docs.phpdoc.org/latest/guide/guides/docblocks.html) are required
3. Run `composer run lint` before submitting your PR
4. Be courteous in your communications

---
