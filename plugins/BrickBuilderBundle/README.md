# Brick Builder plugin for Webmecanik Automation / Mautic Community

## Setup

1. Unzip files to plugins/BrickBuilderBundle
2. Clear cache (var/cache/prod/)
3. From UI, go to plugins
4. Click the button "Install/Upgrade Plugins"
5. Setup configuration parameters in `local.php` - specification bellow
5. Go to emails
6. You should able to use Brick editor for your emails

## Configuration parameters

- `brick_builder_enable` - default is `false` - set `true` to enable brick builder
- `both_builder_support` - default is `false` - set `true` if you want both type of template support (old builder/new builder)
- `theme_email_default` - default is `blank` -  theme predefined while you create new email - for example blank-mjml in case builder is enabled (require https://github.com/mautic/mautic/pull/9189 to work)

## Fixes we recommend to use

- Fix filemanager absolute url https://github.com/mautic/mautic/pull/9271
