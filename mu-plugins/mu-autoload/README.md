# Composer WordPress Must-Use Autoloader

Installs an mu-plugin to load libraries for all themes & plugins from a common vendor directory (usually in wp-content).

# Installation

Add to your project's composer file by running:

## Optional
(if you haven't yet added the WDS Satis package server to your composer.json)

`composer config repositories.wds-satis composer https://packages.wdslab.com`

## Required

`composer config scripts.post-autoload-dump "WebDevStudios\MUAutoload\Installer::install"`

`composer require webdevstudios/mu-autoload:^1.0`
