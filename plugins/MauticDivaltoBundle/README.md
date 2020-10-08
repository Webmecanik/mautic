# Divalto integration

## Instalation

Require:

1. This PR https://github.com/Webmecanik/Automation_dev/pull/961
Warning: As I notice in that PR, this PR should not be required after this PR merged https://github.com/mautic/mautic/pull/7754 (not testet yet)

2. New plugin integration https://github.com/mautic-inc/plugin-integrations

3. This bundle install to plugins/MauticDivaltoIntegration

Don't forget remove cache

## Setup

Setup is very easy. 

1. Just set URL and Project Code/UserName/Password access to Divalto.
2. If authentification failed, config form return error. 
3. If success, you should see matching fields and settings
4. Setup is very intuitive

### Sync

Once integration is setup, Automation store every contact/companies changes to _sync_object_field_change_report_ table. From this point integration sync this changes to integration.

Sync from integration is based from interval set by command _--start-datetime_ parameter (default is -24 hours).

Example:

`php app/console mautic:integrations:sync Divalto --start-datetime="-15 minutes"`

This command will sync 
- all changes from Mautic from time when was integration initialized  
- changes from integration from last 15 minutes

Example 2:

`php app/console mautic:integrations:sync Divalto --start-datetime="-7 days" --first-time-sync`

This command sync:
- all contacts modified or added from last 5 days from Mautic/Integration (not respect already integrated changes...)


Other options for sync command: 

- --disable-pull
- --disable-push
- --mautic-object-id
- --integration-object-id
- {mapped-integration-object} token

For more info check https://github.com/mautic-inc/plugin-integrations#sync-command