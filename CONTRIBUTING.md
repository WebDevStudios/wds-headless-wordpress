# Contributing <!-- omit in toc -->

## Introduction

Thanks for contributing — you rock! 🤘

---

## Table of Contents <!-- omit in toc -->

- [Introduction](#introduction)
- [Submitting Issues and Feature Requests](#submitting-issues-and-feature-requests)
- [Development](#development)
  - [Environments and Primary Branches](#environments-and-primary-branches)
    - [WordPress (Backend)](#wordpress-backend)
  - [Git Workflow](#git-workflow)
  - [Code Linting](#code-linting)
  - [Tips to help your PR get approved](#tips-to-help-your-pr-get-approved)

---

## Submitting Issues and Feature Requests

Before submitting an issue or making a feature request, please search for existing [issues](https://github.com/WebDevStudios/wds-headless-wordpress/issues). If you do file an issue, be sure to fill out the report completely!

---

## Development

### Environments and Primary Branches

#### WordPress (Backend)

- [WP Engine Prod](https://nextjs.wpengine.com/wp-admin/) - `main` branch - Manual releases only
- [WP Engine Dev](https://nextjsdevstart.wpengine.com/wp-admin/) - `develop` branch - Auto deploy [via Buddy](https://app.buddy.works/webdevstudios/wds-headless-wordpress/pipelines)

### Git Workflow

1. Create a `feature` branch off `main`
2. Work locally adhering to coding standards
3. Merge your `feature` into `develop` to test on [WPE Dev environment](https://nextjsdevstart.wpengine.com/wp-admin/)
4. When your `feature` has been tested on WPE Dev, open a Pull Request (PR) and fill out the PR template
5. Your PR must pass assertions
6. After peer review, the PR will be merged back into `main`
7. Repeat ♻️

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
